import {checkTokenValidity, login, refreshToken} from '../api/auth';
import {
  loginSuccess,
  loginStart,
  loginFailure,
  logoutUser,
  clearError,
} from '../slices/authSlice';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {decodeJwt} from '../utils/jwt';

export const loginUser = credentials => async dispatch => {
  try {
    console.log('🎯 === AUTH ACTION DEBUG ===');
    console.log(
      '📥 Login attempt for:',
      credentials?.email || '[MISSING EMAIL]',
    );

    dispatch(loginStart());

    // Estrai email e password dalle credenziali
    const {email, password} = credentials;

    const response = await login(email, password);

    console.log('🔍 === RESPONSE ANALYSIS ===');
    console.log('✅ Login success:', response.success);

    if (!response.success) {
      throw new Error(response.error || 'Login failed');
    }

    // Mappa correttamente i dati dalla risposta del backend
    const {data} = response;

    // Estrai dati dalla risposta
    const token = data?.access_token || data?.temp_token;
    const user = data?.user;
    const requiresPasswordChange = data?.requires_password_change || false;

    // Debug per token se è un oggetto (situazione inaspettata)
    if (typeof token === 'object' && token !== null) {
      console.log('🔧 Token è un oggetto, keys:', Object.keys(token));
    }

    console.log('🎯 Token presente:', !!token, 'User presente:', !!user);
    console.log('🔄 Richiede cambio password:', requiresPasswordChange);

    if (!token) {
      throw new Error('Token mancante nella risposta del server');
    }

    if (!user) {
      throw new Error('Dati utente mancanti nella risposta del server');
    }

    // Gestisci il token - se è un oggetto, prova a estrarre il valore corretto
    let finalToken = token;
    if (typeof token === 'object' && token !== null) {
      console.log("🔧 Estraggo token dall'oggetto...");

      // Prova diverse proprietà comuni per i token
      finalToken =
        token.token || token.access_token || token.value || token.data;

      if (!finalToken) {
        console.error("❌ Impossibile estrarre token dall'oggetto");
        throw new Error('Formato token non valido');
      }

      console.log('✅ Token estratto con successo');
    }

    // Valida e converte i dati per AsyncStorage
    const tokenString = String(finalToken);
    const userString = JSON.stringify(user);

    console.log('💾 Salvataggio dati autenticazione...');

    await AsyncStorage.multiSet([
      ['authToken', tokenString],
      ['refreshToken', ''], // Il backend non restituisce refresh token per ora
      ['user', userString],
    ]);

    console.log('✅ Dati salvati con successo');

    dispatch(
      loginSuccess({
        token: finalToken,
        refreshToken: '', // Placeholder per ora
        user: user,
        requiresPasswordChange: requiresPasswordChange,
      }),
    );

    return response;
  } catch (error) {
    console.log('💥 === LOGIN ERROR DEBUG ===');
    console.log('❌ Errore catturato:', {
      type: error.type,
      message: error.message,
      response: error.response?.data,
      status: error.status,
    });

    // Gestisci i diversi tipi di errore dal sistema Axios
    let errorMessage = 'Authentication failed';

    if (error.type === 'NETWORK_ERROR') {
      errorMessage = error.message;
    } else if (error.type === 'SERVER_ERROR') {
      errorMessage = error.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    console.log('🔴 Messaggio errore finale:', errorMessage);

    dispatch(loginFailure(errorMessage));
    throw error;
  }
};

export const refreshUserToken = refreshTokenString => async dispatch => {
  try {
    const response = await refreshToken(refreshTokenString);

    if (!response.success) {
      throw new Error(response.error || 'Token refresh failed');
    }

    await AsyncStorage.multiSet([
      ['authToken', response.token],
      ['refreshToken', response.refreshToken],
      ['user', JSON.stringify(response.user)],
    ]);

    // Usa solo i dati utente dal response, non dal token
    dispatch(
      loginSuccess({
        token: response.token,
        refreshToken: response.refreshToken,
        user: response.user,
      }),
    );

    return response;
  } catch (error) {
    // If refresh fails, clear auth state
    await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
    dispatch(logoutUser());
    throw error;
  }
};

export const verifyTokenWithBackend = token => async dispatch => {
  try {
    const response = await checkTokenValidity(token);
    return response.valid;
  } catch (error) {
    return false; // In caso di errore, consideriamo il token non valido
  }
};

// Esporta anche le azioni semplici
export {clearError};
