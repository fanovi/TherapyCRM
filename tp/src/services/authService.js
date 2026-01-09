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
  console.log('🌐 API URL chiamato:', url);

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

        console.log('🔍 === TOKEN EXTRACTION DEBUG ===');
        console.log('🎫 access_token tipo:', typeof access_token);
        console.log('🎫 access_token valore:', access_token);

        // Estrai il token JWT vero dall'oggetto access_token
        let actualToken = null;
        if (typeof access_token === 'object' && access_token !== null) {
          // Il backend restituisce access_token come oggetto con token dentro
          actualToken = access_token.token;
          console.log(
            '✅ Token estratto da access_token.token:',
            actualToken ? actualToken.substring(0, 30) + '...' : 'NULL',
          );
        } else if (typeof access_token === 'string') {
          // Se fosse già una stringa (caso futuro)
          actualToken = access_token;
          console.log(
            '✅ Token è già una stringa:',
            actualToken.substring(0, 30) + '...',
          );
        } else {
          console.log(
            '❌ access_token non è né oggetto né stringa:',
            access_token,
          );
        }

        // DEBUG: Log dei dati raw dal backend nel login
        console.log('🔍 === LOGIN RAW BACKEND DATA ===');
        console.log('📦 user.codice_fiscale:', user.codice_fiscale);
        console.log('📦 user.telefono:', user.telefono);
        console.log('📦 user.indirizzo:', user.indirizzo);
        console.log('📦 user._debug_profile:', JSON.stringify(user._debug_profile, null, 2));

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
          // Add patients info for users with patients
          patients: user.patients || [],
          // DEBUG: passa anche i dati raw dal backend
          _debug_profile: user._debug_profile || null,
          // Add additional fields for therapists
          ...(user.user_type === 'terapista' && {
            specializzazione: user.specializzazione || '',
            numeroAlbo: user.numero_albo || '',
          }),
        };

        console.log(
          '✅ Final token to return:',
          actualToken ? actualToken.substring(0, 30) + '...' : 'NULL',
        );

        return {
          user: transformedUser,
          requiresPasswordChange: !!requires_password_change,
          tempToken: temp_token,
          token: actualToken || temp_token, // Usa il token estratto
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

  async requestPasswordReset(email) {
    try {
      const formData = new FormData();
      formData.append('email', email);

      const response = await apiCall(
        API_CONFIG.ENDPOINTS.REQUEST_PASSWORD_RESET,
        {
          method: 'POST',
          body: formData,
          headers: {
            Accept: 'application/json',
          },
        },
      );

      return response;
    } catch (error) {
      console.error('Request password reset error:', error);
      throw error;
    }
  },

  async resetPassword(resetToken, newPassword, confirmPassword) {
    try {
      const formData = new FormData();
      formData.append('reset_token', resetToken);
      formData.append('new_password', newPassword);
      formData.append('confirm_password', confirmPassword);

      const response = await apiCall(API_CONFIG.ENDPOINTS.RESET_PASSWORD, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
        },
      });

      if (response.success && response.data) {
        const {user, access_token, token_type, expires_in} = response.data;

        console.log('🔍 === RESET PASSWORD TOKEN EXTRACTION ===');
        console.log('🎫 access_token tipo:', typeof access_token);

        // Estrai il token JWT vero dall'oggetto access_token
        let actualToken = null;
        if (typeof access_token === 'object' && access_token !== null) {
          actualToken = access_token.token;
          console.log(
            '✅ Token estratto da access_token.token:',
            actualToken ? actualToken.substring(0, 30) + '...' : 'NULL',
          );
        } else if (typeof access_token === 'string') {
          actualToken = access_token;
          console.log(
            '✅ Token è già una stringa:',
            actualToken.substring(0, 30) + '...',
          );
        }

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
          token: actualToken,
          tokenType: token_type,
          expiresIn: expires_in,
          requiresPasswordChange: false,
        };
      }

      throw new Error('Risposta del server non valida');
    } catch (error) {
      console.error('Reset password error:', error);
      throw error;
    }
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

        console.log('🔍 === CHANGE PASSWORD TOKEN EXTRACTION ===');
        console.log('🎫 access_token tipo:', typeof access_token);

        // Estrai il token JWT vero dall'oggetto access_token
        let actualToken = null;
        if (typeof access_token === 'object' && access_token !== null) {
          actualToken = access_token.token;
          console.log(
            '✅ Token estratto da access_token.token:',
            actualToken ? actualToken.substring(0, 30) + '...' : 'NULL',
          );
        } else if (typeof access_token === 'string') {
          actualToken = access_token;
          console.log(
            '✅ Token è già una stringa:',
            actualToken.substring(0, 30) + '...',
          );
        }

        // Debug del backend response per changePassword
        console.log('🔍 === CHANGE PASSWORD USER DATA DEBUG ===');
        console.log('🔍 Backend user object:', user);
        console.log('🔍 user.nome:', user.nome);
        console.log('🔍 user.cognome:', user.cognome);
        console.log('🔍 user.user_type:', user.user_type);

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
          token: actualToken, // Usa il token estratto
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

        // DEBUG: Log raw backend response
        console.log('🔍 === VALIDATE TOKEN RAW BACKEND DATA ===');
        console.log('📦 user.codice_fiscale:', user.codice_fiscale);
        console.log('📦 user.telefono:', user.telefono);
        console.log('📦 user.indirizzo:', user.indirizzo);
        console.log('📦 user._debug_profile:', JSON.stringify(user._debug_profile, null, 2));

        // Transform API response to match app's expected format
        // Include ALL fields like in login()
        return {
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
          patients: user.patients || [],
          // DEBUG: passa anche i dati raw dal backend
          _debug_profile: user._debug_profile || null,
          // Add additional fields for therapists
          ...(user.user_type === 'terapista' && {
            specializzazione: user.specializzazione || '',
            numeroAlbo: user.numero_albo || '',
          }),
        };
      }

      return null;
    } catch (error) {
      console.error('Token validation error:', error);
      return null;
    }
  },

  async logout() {
    console.log(
      '🚪 AuthService logout - deprecated, use loginService.logout instead',
    );
    // Questa funzione è deprecata - il logout principale è ora in loginService
    // Non fare nulla per evitare conflitti
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
