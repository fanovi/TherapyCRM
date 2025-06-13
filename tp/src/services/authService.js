import {API_CONFIG, ERROR_MESSAGES} from '../config/api';
import * as Keychain from 'react-native-keychain';

// Store per il dispatch (sarà inizializzato dall'app)
let store = null;

export const setStore = appStore => {
  store = appStore;
};

// API Configuration
const API_BASE_URL = API_CONFIG.BASE_URL;

// Utility function for API calls with automatic token management
const apiCall = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}${endpoint}`;

  const config = {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    ...options,
  };

  try {
    const response = await fetch(url, config);

    // Gestione automatica errori di autenticazione
    if (response.status === 401 || response.status === 403) {
      console.warn('🚨 Token scaduto o non valido - logout automatico');

      // Rimuovi token dal Keychain
      await Keychain.resetInternetCredentials('cms-terapisti-token');

      // Dispatch logout se store è disponibile
      if (store) {
        const {logoutUser} = await import('../slices/authSlice');
        store.dispatch(logoutUser());
      }

      throw new Error('Sessione scaduta. Effettua nuovamente il login.');
    }

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message || 'Errore del server');
    }

    return data;
  } catch (error) {
    if (error.name === 'TypeError' && error.message.includes('fetch')) {
      throw new Error(ERROR_MESSAGES.NETWORK_ERROR);
    }
    throw error;
  }
};

// Funzione per verificare se il token è scaduto
const isTokenExpired = token => {
  try {
    const {isTokenExpired: checkExpired} = require('../utils/jwt');
    return checkExpired(token);
  } catch (error) {
    console.warn('Errore nella verifica scadenza token:', error);
    return true;
  }
};

// Funzione per ottenere un token valido (con controllo automatico)
const getValidToken = async () => {
  try {
    const credentials = await Keychain.getInternetCredentials(
      'cms-terapisti-token',
    );

    if (!credentials) {
      throw new Error('Nessun token trovato');
    }

    const token = credentials.password;

    // Verifica se il token è scaduto
    if (isTokenExpired(token)) {
      console.warn('🚨 Token scaduto - rimozione automatica');
      await Keychain.resetInternetCredentials('cms-terapisti-token');

      // Usa le nuove funzioni di utilità per logout
      const {performAutoLogout} = require('../utils/authUtils');
      await performAutoLogout('Token scaduto in authService');

      throw new Error('Token scaduto');
    }

    return token;
  } catch (error) {
    throw new Error('Token non valido o scaduto');
  }
};

export const authService = {
  // Nuovo metodo per verificare lo stato dell'autenticazione
  async checkAuthStatus() {
    try {
      const token = await getValidToken();
      const user = await this.validateToken(token);
      return {isValid: true, token, user};
    } catch (error) {
      console.log('Auth status check failed:', error.message);
      return {isValid: false, error: error.message};
    }
  },

  async login(credentials) {
    try {
      const formData = new FormData();
      formData.append('email', credentials.email);
      formData.append('password', credentials.password);

      const response = await apiCall(API_CONFIG.ENDPOINTS.LOGIN, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
          // Don't set Content-Type for FormData, let the browser set it
        },
      });

      if (response.success && response.data) {
        const {
          user,
          requires_password_change,
          temp_token,
          access_token,
          token_type,
          expires_in,
        } = response.data;

        // Transform API response to match app's expected format
        const transformedUser = {
          id: user.id.toString(),
          email: user.email,
          role: user.user_type === 'paziente' ? 'patient' : 'therapist',
          firstName: user.nome,
          lastName: user.cognome,
          fullName: `${user.nome} ${user.cognome}`,
          codiceFiscale: user.codice_fiscale || '',
          telefono: user.telefono || '',
          dataNascita: user.data_nascita || '',
          indirizzo: user.indirizzo || '',
          status: user.status || 'attivo',
          isFirstLogin: !!requires_password_change,
          isPasswordResetRequired: !!requires_password_change,
          // Add additional fields for therapists
          ...(user.user_type === 'terapista' && {
            specializzazione: user.specializzazione || '',
            numeroAlbo: user.numero_albo || '',
          }),
        };

        return {
          user: transformedUser,
          requiresPasswordChange: !!requires_password_change,
          tempToken: temp_token,
          token: access_token || temp_token,
          tokenType: token_type,
          expiresIn: expires_in,
        };
      }

      throw new Error('Risposta del server non valida');
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  },

  async resetPassword(userId, data) {
    // This endpoint might not exist yet in your API
    // For now, we'll throw an error indicating it needs to be implemented
    throw new Error(
      'Funzionalità di reset password non ancora implementata nel server',
    );
  },

  async changePassword(tempToken, newPassword, confirmPassword) {
    try {
      const formData = new FormData();
      formData.append('temp_token', tempToken);
      formData.append('new_password', newPassword);
      formData.append('confirm_password', confirmPassword);

      const response = await apiCall(
        API_CONFIG.ENDPOINTS.CHANGE_FIRST_PASSWORD,
        {
          method: 'POST',
          body: formData,
          headers: {
            Accept: 'application/json',
          },
        },
      );

      if (response.success && response.data) {
        const {user, access_token, token_type, expires_in} = response.data;

        // Transform API response to match app's expected format
        const transformedUser = {
          id: user.id.toString(),
          email: user.email,
          role: user.user_type === 'paziente' ? 'patient' : 'therapist',
          firstName: user.nome,
          lastName: user.cognome,
          fullName: `${user.nome} ${user.cognome}`,
          codiceFiscale: user.codice_fiscale || '',
          telefono: user.telefono || '',
          dataNascita: user.data_nascita || '',
          indirizzo: user.indirizzo || '',
          status: user.status || 'attivo',
          isFirstLogin: false,
          isPasswordResetRequired: false,
          // Add additional fields for therapists
          ...(user.user_type === 'terapista' && {
            specializzazione: user.specializzazione || '',
            numeroAlbo: user.numero_albo || '',
          }),
        };

        return {
          user: transformedUser,
          token: access_token,
          tokenType: token_type,
          expiresIn: expires_in,
          requiresPasswordChange: false,
        };
      }

      throw new Error('Risposta del server non valida');
    } catch (error) {
      console.error('Change password error:', error);
      throw error;
    }
  },

  async validateToken(token) {
    try {
      const response = await apiCall(API_CONFIG.ENDPOINTS.VERIFY, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });

      if (response.success && response.data) {
        const {user} = response.data;

        // Transform API response to match app's expected format
        return {
          id: user.id.toString(),
          email: user.email,
          role: user.user_type === 'paziente' ? 'patient' : 'therapist',
          firstName: user.nome,
          lastName: user.cognome,
          fullName: `${user.nome} ${user.cognome}`,
        };
      }

      return null;
    } catch (error) {
      console.error('Token validation error:', error);
      return null;
    }
  },

  async logout() {
    try {
      // Prova a chiamare l'endpoint di logout se il token è ancora valido
      try {
        const token = await getValidToken();
        await apiCall(API_CONFIG.ENDPOINTS.LOGOUT, {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
      } catch (error) {
        // Ignora errori di logout se il token è già scaduto
        console.log(
          'Logout API call failed (token potrebbe essere scaduto):',
          error.message,
        );
      }

      // Rimuovi sempre il token dal Keychain
      await Keychain.resetInternetCredentials('cms-terapisti-token');
    } catch (error) {
      console.error('Logout error:', error);
      // Non lanciare errore per logout, assicurati solo che il token sia rimosso
      await Keychain.resetInternetCredentials('cms-terapisti-token');
    }
  },

  // Additional utility methods
  async refreshToken(token) {
    // This would be implemented if your API supports token refresh
    throw new Error('Token refresh non ancora implementato nel server');
  },

  // Method to get user profile details
  async getUserProfile(token) {
    try {
      const response = await apiCall(API_CONFIG.ENDPOINTS.VERIFY, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });

      if (response.success && response.data) {
        return response.data.user;
      }

      return null;
    } catch (error) {
      console.error('Get user profile error:', error);
      return null;
    }
  },

  // Metodo per fare chiamate API autenticate
  async authenticatedApiCall(endpoint, options = {}) {
    try {
      const token = await getValidToken();

      const authOptions = {
        ...options,
        headers: {
          ...options.headers,
          Authorization: `Bearer ${token}`,
        },
      };

      return await apiCall(endpoint, authOptions);
    } catch (error) {
      console.error('Authenticated API call failed:', error);
      throw error;
    }
  },
};
