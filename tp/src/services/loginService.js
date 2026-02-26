import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  loginStart,
  loginSuccess,
  loginFailure,
  changePasswordSuccess,
  logoutUser,
  resetPasswordRequestSuccess,
  twoFactorRequired,
  twoFactorSuccess,
  twoFactorFailure,
} from '../slices/authSlice';
import {setPatientsFromLogin, resetPatients} from '../slices/patientSlice';
import {authService} from './authService';

export const loginService = {
  async login(dispatch, credentials) {
    dispatch(loginStart());

    try {
      console.log('🚀 Starting login process...');

      // Leggi device_token da AsyncStorage per "ricorda dispositivo"
      let deviceToken = null;
      try {
        deviceToken = await AsyncStorage.getItem('deviceToken');
      } catch (e) {
        // Ignora errore di lettura device token
      }

      // Aggiungi device_token alle credenziali se presente
      const loginCredentials = deviceToken
        ? {...credentials, device_token: deviceToken}
        : credentials;

      const response = await authService.login(loginCredentials);

      console.log('✅ Login response received:', {
        hasUser: !!response.user,
        requiresPasswordChange: response.requiresPasswordChange,
        requires2fa: response.requires2fa,
        hasTempToken: !!response.tempToken,
        hasToken: !!response.token,
      });

      // Check 2FA richiesto
      if (response.requires2fa) {
        console.log(
          '🔐 2FA required - method:',
          response.twoFactorMethod,
        );
        dispatch(
          twoFactorRequired({
            user: response.user,
            twoFactorMethod: response.twoFactorMethod,
            showRememberDevice: response.showRememberDevice,
            tempToken: response.tempToken,
          }),
        );
        return response;
      }

      if (response.requiresPasswordChange) {
        // Primo login - richiede cambio password
        console.log('🔐 First login detected - password change required');

        // Ensure tempToken is a string
        const tempToken = response.tempToken ? String(response.tempToken) : '';
        const userString = JSON.stringify(response.user);

        // Salva solo il temp token e i dati utente
        await AsyncStorage.setItem('tempToken', tempToken);
        await AsyncStorage.setItem('user', userString);

        dispatch(
          loginSuccess({
            user: response.user,
            token: response.tempToken, // Usa temp token temporaneamente
            requiresPasswordChange: true,
            tempToken: response.tempToken,
          }),
        );

        // Gestisci i pazienti anche per il primo login
        if (response.user.patients && response.user.patients.length > 0) {
          console.log(
            '👥 Setting patients for first login:',
            response.user.patients.length,
          );
          dispatch(setPatientsFromLogin(response.user.patients));
        }
      } else {
        // Login normale - utente completamente autenticato
        console.log('✅ Normal login - user fully authenticated');

        // Ensure all values are strings
        const authToken = response.token ? String(response.token) : '';
        const userString = JSON.stringify(response.user);

        console.log('💾 Saving auth data:', {
          hasAuthToken: !!authToken,
          authTokenLength: authToken.length,
          hasUserString: !!userString,
        });

        await AsyncStorage.multiSet([
          ['authToken', authToken],
          ['user', userString],
        ]);

        dispatch(
          loginSuccess({
            user: response.user,
            token: response.token,
            requiresPasswordChange: false,
          }),
        );

        // Gestisci i pazienti per login normale
        if (response.user.patients && response.user.patients.length > 0) {
          console.log(
            '👥 Setting patients for normal login:',
            response.user.patients.length,
          );
          dispatch(setPatientsFromLogin(response.user.patients));
        }
      }

      return response;
    } catch (error) {
      console.error('❌ Login failed:', error.message);
      dispatch(loginFailure(error.message));
      throw error;
    }
  },

  async changePassword(dispatch, {tempToken, newPassword, confirmPassword}) {
    dispatch(loginStart());

    try {
      console.log('🔐 Starting password change...');
      const response = await authService.changePassword(
        tempToken,
        newPassword,
        confirmPassword,
      );

      console.log('✅ Password changed successfully');

      // Ensure all values are strings
      const authToken = response.token ? String(response.token) : '';
      const userString = JSON.stringify(response.user);

      console.log('💾 Saving password change auth data:', {
        hasAuthToken: !!authToken,
        authTokenLength: authToken.length,
        hasUserString: !!userString,
      });

      // Salva il nuovo token e aggiorna i dati utente
      await AsyncStorage.multiSet([
        ['authToken', authToken],
        ['user', userString],
      ]);

      // Rimuovi il temp token
      await AsyncStorage.removeItem('tempToken');

      dispatch(
        changePasswordSuccess({
          user: response.user,
          token: response.token,
        }),
      );

      // Gestisci i pazienti dopo il cambio password
      if (response.user.patients && response.user.patients.length > 0) {
        console.log(
          '👥 Setting patients after password change:',
          response.user.patients.length,
        );
        dispatch(setPatientsFromLogin(response.user.patients));
      }

      return response;
    } catch (error) {
      console.error('❌ Password change failed:', error.message);
      dispatch(loginFailure(error.message));
      throw error;
    }
  },

  async requestPasswordReset(dispatch, email) {
    dispatch(loginStart());

    try {
      console.log('🔐 Starting password reset request...');
      const response = await authService.requestPasswordReset(email);

      console.log('✅ Password reset request completed');
      dispatch(resetPasswordRequestSuccess());

      return response;
    } catch (error) {
      console.error('❌ Password reset request failed:', error.message);
      dispatch(loginFailure(error.message));
      throw error;
    }
  },

  async resetPassword(dispatch, {resetToken, newPassword, confirmPassword}) {
    dispatch(loginStart());

    try {
      console.log('🔐 Starting password reset...');
      const response = await authService.resetPassword(
        resetToken,
        newPassword,
        confirmPassword,
      );

      console.log('✅ Password reset completed successfully');

      // Ensure all values are strings
      const authToken = response.token ? String(response.token) : '';
      const userString = JSON.stringify(response.user);

      console.log('💾 Saving password reset auth data:', {
        hasAuthToken: !!authToken,
        authTokenLength: authToken.length,
        hasUserString: !!userString,
      });

      // Salva il nuovo token e aggiorna i dati utente
      await AsyncStorage.multiSet([
        ['authToken', authToken],
        ['user', userString],
      ]);

      dispatch(
        loginSuccess({
          user: response.user,
          token: response.token,
          requiresPasswordChange: false,
        }),
      );

      // Gestisci i pazienti dopo il reset password
      if (response.user.patients && response.user.patients.length > 0) {
        console.log(
          '👥 Setting patients after password reset:',
          response.user.patients.length,
        );
        dispatch(setPatientsFromLogin(response.user.patients));
      }

      return response;
    } catch (error) {
      console.error('❌ Password reset failed:', error.message);
      dispatch(loginFailure(error.message));
      throw error;
    }
  },

  async verify2fa(
    dispatch,
    {tempToken, code, rememberDevice = false, deviceName = null},
  ) {
    dispatch(loginStart());

    try {
      console.log('🔐 Starting 2FA verification...');
      const response = await authService.verify2fa(
        tempToken,
        code,
        rememberDevice,
        deviceName,
      );

      console.log('✅ 2FA verification successful');

      // Salva il token di autenticazione
      const authToken = response.token ? String(response.token) : '';
      const userString = JSON.stringify(response.user);

      await AsyncStorage.multiSet([
        ['authToken', authToken],
        ['user', userString],
      ]);

      // Se c'è un device_token (ricorda dispositivo), salvalo
      if (response.deviceToken) {
        await AsyncStorage.setItem('deviceToken', response.deviceToken);
      }

      dispatch(
        twoFactorSuccess({
          user: response.user,
          token: response.token,
        }),
      );

      // Gestisci i pazienti
      if (response.user.patients && response.user.patients.length > 0) {
        console.log(
          '👥 Setting patients after 2FA:',
          response.user.patients.length,
        );
        dispatch(setPatientsFromLogin(response.user.patients));
      }

      return response;
    } catch (error) {
      console.error('❌ 2FA verification failed:', error.message);
      dispatch(twoFactorFailure(error.message));
      throw error;
    }
  },

  async resendEmailOtp(dispatch, tempToken) {
    try {
      console.log('📧 Resending email OTP...');
      const response = await authService.resendEmailOtp(tempToken);
      console.log('✅ Email OTP resent successfully');
      return response;
    } catch (error) {
      console.error('❌ Resend OTP failed:', error.message);
      throw error;
    }
  },

  async logout(dispatch) {
    try {
      console.log('🚪 Starting logout process...');

      // 1. Prova a invalidare il token sul server (importante per sicurezza)
      try {
        const token = await AsyncStorage.getItem('authToken');
        if (token) {
          console.log('🔐 Invalidating token on server...');
          await fetch(
            `${require('../config/api').API_CONFIG.BASE_URL}/auth/logout`,
            {
              method: 'POST',
              headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
                Accept: 'application/json',
              },
            },
          );
          console.log('✅ Token invalidated on server');
        } else {
          console.log('⚠️ No token found to invalidate');
        }
      } catch (apiError) {
        console.warn(
          '⚠️ Server logout failed (token might be expired):',
          apiError.message,
        );
        // Non bloccare il logout se l'API fallisce
      }

      // 2. Pulisci AsyncStorage (preserva deviceToken per "ricorda dispositivo")
      const savedDeviceToken = await AsyncStorage.getItem('deviceToken');
      await AsyncStorage.clear();
      if (savedDeviceToken) {
        await AsyncStorage.setItem('deviceToken', savedDeviceToken);
      }
      console.log('🧹 AsyncStorage cleared (deviceToken preserved)');

      // 3. Reset pazienti
      dispatch(resetPatients());
      console.log('👥 Patients data reset');

      // 4. Reset stato auth
      dispatch(logoutUser());
      console.log('🔄 Auth state reset');

      console.log('✅ Logout completed successfully');
    } catch (error) {
      console.error('❌ Logout error:', error);

      // Fallback: forza la pulizia anche in caso di errore
      try {
        const fallbackDeviceToken = await AsyncStorage.getItem('deviceToken');
        await AsyncStorage.clear();
        if (fallbackDeviceToken) {
          await AsyncStorage.setItem('deviceToken', fallbackDeviceToken);
        }
        dispatch(resetPatients());
        dispatch(logoutUser());
        console.log('🧹 Force cleanup completed (deviceToken preserved)');
      } catch (finalError) {
        // Ultima risorsa: almeno reset Redux
        console.error('❌ Final cleanup error:', finalError);
        dispatch(logoutUser());
      }
    }
  },
};
