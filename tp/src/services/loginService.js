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
  accountReactivationRequired,
  reactivationSuccess,
  biometricLoginStart,
  biometricLoginSuccess,
  biometricLoginFailure,
} from '../slices/authSlice';
import {setPatientsFromLogin, resetPatients} from '../slices/patientSlice';
import {authService} from './authService';
import {persistor} from '../store';

export const loginService = {
  async login(dispatch, credentials) {
    dispatch(loginStart());

    try {
      console.log('🚀 Starting login process...');

      const response = await authService.login(credentials);

      console.log('✅ Login response received:', {
        hasUser: !!response.user,
        requiresPasswordChange: response.requiresPasswordChange,
        requires2fa: response.requires2fa,
        hasTempToken: !!response.tempToken,
        hasToken: !!response.token,
      });

      // Check riattivazione account
      if (response.requiresReactivation) {
        console.log('🔒 Account reactivation required');
        dispatch(
          accountReactivationRequired({
            user: response.user,
            tempToken: response.tempToken,
          }),
        );
        return response;
      }

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
            tempToken: response.tempToken,
            totpConfigured: response.totpConfigured,
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

        const authToken = response.token ? String(response.token) : '';
        const refreshTokenStr = response.refreshToken ? String(response.refreshToken) : '';
        const userString = JSON.stringify(response.user);

        const storageItems = [
          ['authToken', authToken],
          ['user', userString],
        ];
        if (refreshTokenStr) {
          storageItems.push(['refreshToken', refreshTokenStr]);
        }
        await AsyncStorage.multiSet(storageItems);

        dispatch(
          loginSuccess({
            user: response.user,
            token: response.token,
            refreshToken: response.refreshToken,
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

      // Se dopo il cambio password serve la 2FA, non salvare token: indirizza
      // verso la schermata 2FA con il temp_token aggiornato.
      if (response.requires2fa) {
        console.log(
          '🔐 2FA required after password change - method:',
          response.twoFactorMethod,
        );
        const newTempToken = response.tempToken ? String(response.tempToken) : '';
        await AsyncStorage.setItem('tempToken', newTempToken);
        dispatch(
          twoFactorRequired({
            user: response.user,
            twoFactorMethod: response.twoFactorMethod,
            tempToken: response.tempToken,
            totpConfigured: response.totpConfigured,
          }),
        );
        return response;
      }

      const authToken = response.token ? String(response.token) : '';
      const refreshTokenStr = response.refreshToken ? String(response.refreshToken) : '';
      const userString = JSON.stringify(response.user);

      const storageItems = [
        ['authToken', authToken],
        ['user', userString],
      ];
      if (refreshTokenStr) {
        storageItems.push(['refreshToken', refreshTokenStr]);
      }
      await AsyncStorage.multiSet(storageItems);

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

      const authToken = response.token ? String(response.token) : '';
      const refreshTokenStr = response.refreshToken ? String(response.refreshToken) : '';
      const userString = JSON.stringify(response.user);

      const storageItems = [
        ['authToken', authToken],
        ['user', userString],
      ];
      if (refreshTokenStr) {
        storageItems.push(['refreshToken', refreshTokenStr]);
      }
      await AsyncStorage.multiSet(storageItems);

      dispatch(
        loginSuccess({
          user: response.user,
          token: response.token,
          refreshToken: response.refreshToken,
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
    {tempToken, code, method},
  ) {
    dispatch(loginStart());

    try {
      console.log('🔐 Starting 2FA verification...');
      const response = await authService.verify2fa(
        tempToken,
        code,
        method,
      );

      console.log('✅ 2FA verification successful');

      const authToken = response.token ? String(response.token) : '';
      const refreshTokenStr = response.refreshToken ? String(response.refreshToken) : '';
      const userString = JSON.stringify(response.user);

      const storageItems = [
        ['authToken', authToken],
        ['user', userString],
      ];
      if (refreshTokenStr) {
        storageItems.push(['refreshToken', refreshTokenStr]);
      }
      await AsyncStorage.multiSet(storageItems);

      dispatch(
        twoFactorSuccess({
          user: response.user,
          token: response.token,
          refreshToken: response.refreshToken,
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

  async verifyReactivation(dispatch, {tempToken, code}) {
    dispatch(loginStart());

    try {
      console.log('🔓 Starting account reactivation verification...');
      const response = await authService.verifyReactivation(tempToken, code);

      console.log('✅ Reactivation verification response:', {
        hasUser: !!response.user,
        requiresPasswordChange: response.requiresPasswordChange,
        requires2fa: response.requires2fa,
        hasToken: !!response.token,
      });

      // Se dopo riattivazione serve cambio password
      if (response.requiresPasswordChange) {
        console.log('🔐 Password change required after reactivation');
        const tempTokenStr = response.tempToken ? String(response.tempToken) : '';
        await AsyncStorage.setItem('tempToken', tempTokenStr);
        await AsyncStorage.setItem('user', JSON.stringify(response.user));

        dispatch(
          loginSuccess({
            user: response.user,
            token: response.tempToken,
            requiresPasswordChange: true,
            tempToken: response.tempToken,
          }),
        );
        return response;
      }

      // Se dopo riattivazione serve 2FA
      if (response.requires2fa) {
        console.log('🔐 2FA required after reactivation');
        dispatch(
          twoFactorRequired({
            user: response.user,
            twoFactorMethod: response.twoFactorMethod,
            tempToken: response.tempToken,
            totpConfigured: response.totpConfigured,
          }),
        );
        return response;
      }

      // Login completo dopo riattivazione
      console.log('✅ Reactivation complete - full login');

      const authToken = response.token ? String(response.token) : '';
      const refreshTokenStr = response.refreshToken ? String(response.refreshToken) : '';
      const userString = JSON.stringify(response.user);

      const storageItems = [
        ['authToken', authToken],
        ['user', userString],
      ];
      if (refreshTokenStr) {
        storageItems.push(['refreshToken', refreshTokenStr]);
      }
      await AsyncStorage.multiSet(storageItems);

      dispatch(
        reactivationSuccess({
          user: response.user,
          token: response.token,
          refreshToken: response.refreshToken,
        }),
      );

      // Gestisci i pazienti
      if (response.user.patients && response.user.patients.length > 0) {
        console.log(
          '👥 Setting patients after reactivation:',
          response.user.patients.length,
        );
        dispatch(setPatientsFromLogin(response.user.patients));
      }

      return response;
    } catch (error) {
      console.error('❌ Reactivation verification failed:', error.message);
      dispatch(loginFailure(error.message));
      throw error;
    }
  },

  async resendReactivationOtp(dispatch, tempToken) {
    try {
      console.log('📧 Resending reactivation OTP...');
      const response = await authService.resendReactivationOtp(tempToken);
      console.log('✅ Reactivation OTP resent successfully');
      return response;
    } catch (error) {
      console.error('❌ Resend reactivation OTP failed:', error.message);
      throw error;
    }
  },

  async biometricLogin(dispatch) {
    dispatch(biometricLoginStart());

    try {
      console.log('🔐 Starting biometric login...');
      const {biometricService} = require('./biometricService');
      const result = await biometricService.biometricLogin();

      if (!result.success) {
        dispatch(biometricLoginFailure(result.error));
        return result;
      }

      // Check se serve riattivazione account
      if (result.requiresReactivation) {
        console.log('🔒 Account reactivation required (biometric)');
        dispatch(
          accountReactivationRequired({
            user: result.user,
            tempToken: result.tempToken,
          }),
        );
        return result;
      }

      console.log('✅ Biometric login successful');

      const authToken = result.token ? String(result.token) : '';
      const refreshTokenStr = result.refreshToken ? String(result.refreshToken) : '';
      const userString = JSON.stringify(result.user);

      const storageItems = [
        ['authToken', authToken],
        ['user', userString],
      ];
      if (refreshTokenStr) {
        storageItems.push(['refreshToken', refreshTokenStr]);
      }
      await AsyncStorage.multiSet(storageItems);

      dispatch(
        biometricLoginSuccess({
          user: result.user,
          token: result.token,
          refreshToken: result.refreshToken,
        }),
      );

      // Gestisci i pazienti
      if (result.user.patients && result.user.patients.length > 0) {
        console.log(
          '👥 Setting patients after biometric login:',
          result.user.patients.length,
        );
        dispatch(setPatientsFromLogin(result.user.patients));
      }

      return result;
    } catch (error) {
      console.error('❌ Biometric login failed:', error.message);
      dispatch(biometricLoginFailure(error.message));
      return {success: false, error: error.message};
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

      // 2. Reset Redux PRIMA di pulire storage (evita race condition con persist)
      dispatch(resetPatients());
      console.log('👥 Patients data reset');

      dispatch(logoutUser());
      console.log('🔄 Auth state reset');

      // 3. Purge Redux Persist per eliminare dati persistiti
      await persistor.purge();
      console.log('🗑️ Redux Persist purged');

      // 4. Pulisci AsyncStorage (preserva biometria per ri-login rapido)
      const savedBiometricRegistered = await AsyncStorage.getItem('biometric_registered');
      await AsyncStorage.clear();
      if (savedBiometricRegistered) {
        await AsyncStorage.setItem('biometric_registered', savedBiometricRegistered);
      }
      console.log('🧹 AsyncStorage cleared (biometric preserved)');

      console.log('✅ Logout completed successfully');
    } catch (error) {
      console.error('❌ Logout error:', error);

      // Fallback: forza la pulizia anche in caso di errore
      try {
        dispatch(resetPatients());
        dispatch(logoutUser());
        await persistor.purge();
        const fallbackBiometricRegistered = await AsyncStorage.getItem('biometric_registered');
        await AsyncStorage.clear();
        if (fallbackBiometricRegistered) {
          await AsyncStorage.setItem('biometric_registered', fallbackBiometricRegistered);
        }
        console.log('🧹 Force cleanup completed (biometric preserved)');
      } catch (finalError) {
        // Ultima risorsa: almeno reset Redux
        console.error('❌ Final cleanup error:', finalError);
        dispatch(logoutUser());
      }
    }
  },
};
