import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  loginStart,
  loginSuccess,
  loginFailure,
  changePasswordSuccess,
  logoutUser,
} from '../slices/authSlice';
import {setPatientsFromLogin, resetPatients} from '../slices/patientSlice';
import {authService} from './authService';

export const loginService = {
  async login(dispatch, credentials) {
    dispatch(loginStart());

    try {
      console.log('🚀 Starting login process...');
      const response = await authService.login(credentials);

      console.log('✅ Login response received:', {
        hasUser: !!response.user,
        requiresPasswordChange: response.requiresPasswordChange,
        hasTempToken: !!response.tempToken,
        hasToken: !!response.token,
      });

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

  async logout(dispatch) {
    try {
      console.log('🚪 Starting logout process...');

      // Reset dei pazienti prima del logout completo
      dispatch(resetPatients());
      console.log('👥 Patients data reset');

      // Chiama l'API di logout (se il token è ancora valido)
      try {
        await authService.logout();
        console.log('✅ Logout API call successful');
      } catch (error) {
        console.warn(
          '⚠️ Logout API call failed (token might be expired):',
          error.message,
        );
        // Non bloccare il logout se l'API fallisce
      }

      // Pulisci lo storage locale
      await AsyncStorage.multiRemove([
        'authToken',
        'refreshToken',
        'user',
        'tempToken',
      ]);

      // Aggiorna lo stato Redux
      dispatch(logoutUser());

      console.log('✅ Logout completed successfully');
    } catch (error) {
      console.error('❌ Logout error:', error);
      // Anche in caso di errore, pulisci lo stato locale
      dispatch(resetPatients());
      await AsyncStorage.multiRemove([
        'authToken',
        'refreshToken',
        'user',
        'tempToken',
      ]);
      dispatch(logoutUser());
    }
  },
};
