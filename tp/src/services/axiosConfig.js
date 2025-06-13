import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {API_CONFIG} from '../config/api';

// Store per il dispatch (sarà inizializzato dall'app)
let store = null;

export const setStore = appStore => {
  store = appStore;
};

// Configurazione base di Axios
const API_BASE_URL = API_CONFIG.BASE_URL;

// Debug configurazione
console.log('⚙️ === AXIOS CONFIG DEBUG ===');
console.log('🌐 Base URL configurata:', API_BASE_URL);

// Crea un'istanza di Axios
export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Lista degli endpoint che NON devono avere il token nell'header
const ENDPOINTS_WITHOUT_TOKEN = [
  '/auth/login',
  '/auth/forgot-password',
  '/auth/reset-password',
  '/auth/change-first-password', // Usa temp_token nel body
  '/auth/register',
];

// Funzione per verificare se l'endpoint richiede il token
const shouldIncludeToken = url => {
  return !ENDPOINTS_WITHOUT_TOKEN.some(endpoint => url.includes(endpoint));
};

// REQUEST INTERCEPTOR - Aggiunge automaticamente il token
apiClient.interceptors.request.use(
  async config => {
    // Debug della richiesta
    console.log('📤 === REQUEST INTERCEPTOR DEBUG ===');
    console.log('🔗 URL finale richiesta:', `${config.baseURL}${config.url}`);
    console.log('📋 Metodo:', config.method?.toUpperCase());

    // Verifica se l'endpoint richiede il token
    if (shouldIncludeToken(config.url)) {
      try {
        const token = await AsyncStorage.getItem('authToken');
        console.log(
          '🎫 Token raw dal storage:',
          typeof token,
          token ? `${token.substring(0, 20)}...` : 'NULL',
        );

        if (token) {
          // Assicurati che il token sia una stringa
          const tokenString = typeof token === 'string' ? token : String(token);

          console.log(
            '🎫 Token convertito:',
            typeof tokenString,
            tokenString.substring(0, 20) + '...',
          );

          const {isValidToken} = await import('../utils/authUtils');

          const tokenIsValid = await isValidToken(tokenString);

          if (!tokenIsValid) {
            console.log("❌ Token non valido nell'interceptor");
            const {performAutoLogout} = await import('../utils/authUtils');
            await performAutoLogout('Token scaduto durante richiesta');

            // Rejecta la richiesta
            return Promise.reject({
              type: 'AUTH_ERROR',
              message: 'Sessione scaduta. Effettua nuovamente il login.',
            });
          }

          config.headers.Authorization = `Bearer ${tokenString}`;
          console.log(
            '✅ Authorization header impostato:',
            `Bearer ${tokenString.substring(0, 20)}...`,
          );
        } else {
          console.log('⚠️ Nessun token trovato per endpoint protetto');
        }
      } catch (error) {
        console.error('❌ Errore nel recupero/verifica del token:', error);

        // In caso di errore nella decodifica del token, fai logout
        const {performAutoLogout} = await import('../utils/authUtils');
        await performAutoLogout('Errore nella verifica del token');
      }
    } else {
      console.log('ℹ️ Endpoint non richiede token:', config.url);
    }

    console.log('🎯 Headers finali:', config.headers);
    return config;
  },
  error => {
    return Promise.reject(error);
  },
);

// RESPONSE INTERCEPTOR - Gestisce 401 e altri errori
apiClient.interceptors.response.use(
  response => {
    // Risposta OK, ritorna i dati
    return response;
  },
  async error => {
    const {response} = error;

    // Gestione errore 401 - Token scaduto o non valido
    if (response?.status === 401) {
      console.log('❌ Errore 401 ricevuto dal server');
      const {performAutoLogout} = await import('../utils/authUtils');
      await performAutoLogout('Errore 401 dal server');

      // Ritorna un errore specifico per la gestione nel UI
      return Promise.reject({
        type: 'AUTH_ERROR',
        message: 'Sessione scaduta. Effettua nuovamente il login.',
        originalError: error,
      });
    }

    // Gestione errori di rete
    if (!response) {
      console.log('❌ Errore di rete:', error.message);
      const networkError = {
        type: 'NETWORK_ERROR',
        message: 'Errore di connessione. Verifica la tua connessione internet.',
        originalError: error,
      };

      // Mostra errore di rete nell'UI
      if (store) {
        const {setNetworkError} = await import('../slices/uiSlice');
        store.dispatch(setNetworkError(networkError));
      }

      return Promise.reject(networkError);
    }

    // Gestione altri errori del server
    const errorMessage =
      response.data?.message ||
      response.data?.error ||
      `Errore del server (${response.status})`;

    console.log('❌ Errore del server:', response.status, errorMessage);

    const serverError = {
      type: 'SERVER_ERROR',
      message: errorMessage,
      status: response.status,
      originalError: error,
    };

    // Mostra errore del server nell'UI (solo per errori 5xx)
    if (store && response.status >= 500) {
      const {setNetworkError} = await import('../slices/uiSlice');
      store.dispatch(setNetworkError(serverError));
    }

    return Promise.reject(serverError);
  },
);

export default apiClient;
