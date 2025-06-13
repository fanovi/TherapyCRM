import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  loginStart,
  loginSuccess,
  loginFailure,
  changePasswordSuccess,
  logoutUser,
} from '../slices/authSlice';
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

        // Salva solo il temp token e i dati utente
        await AsyncStorage.setItem('tempToken', response.tempToken);
        await AsyncStorage.setItem('user', JSON.stringify(response.user));

        dispatch(
          loginSuccess({
            user: response.user,
            token: response.tempToken, // Usa temp token temporaneamente
            requiresPasswordChange: true,
            tempToken: response.tempToken,
          }),
        );
      } else {
        // Login normale - utente completamente autenticato
        console.log('✅ Normal login - user fully authenticated');

        await AsyncStorage.multiSet([
          ['authToken', response.token],
          ['user', JSON.stringify(response.user)],
        ]);

        dispatch(
          loginSuccess({
            user: response.user,
            token: response.token,
            requiresPasswordChange: false,
          }),
        );
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

      // Salva il nuovo token e aggiorna i dati utente
      await AsyncStorage.multiSet([
        ['authToken', response.token],
        ['user', JSON.stringify(response.user)],
      ]);

      // Rimuovi il temp token
      await AsyncStorage.removeItem('tempToken');

      dispatch(
        changePasswordSuccess({
          user: response.user,
          token: response.token,
        }),
      );

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
