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
      if (credentials.device_token) {
        formData.append('device_token', credentials.device_token);
      }

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
          requires_2fa,
          two_factor_method,
          show_remember_device,
          temp_token,
          access_token,
          token_type,
          expires_in,
        } = response.data;

        // Transform user data to app format
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
          patients: user.patients || [],
          ...(user.user_type === 'terapista' && {
            specializzazione: user.specializzazione || '',
            numeroAlbo: user.numero_albo || '',
          }),
        };

        // Check if 2FA is required
        if (requires_2fa) {
          console.log('🔐 2FA required, method:', two_factor_method);
          return {
            user: transformedUser,
            requires2fa: true,
            twoFactorMethod: two_factor_method,
            showRememberDevice: !!show_remember_device,
            tempToken: temp_token,
            requiresPasswordChange: false,
          };
        }

        console.log('🔍 === TOKEN EXTRACTION DEBUG ===');
        console.log('🎫 access_token tipo:', typeof access_token);
        console.log('🎫 access_token valore:', access_token);

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
        } else {
          console.log(
            '❌ access_token non è né oggetto né stringa:',
            access_token,
          );
        }

        console.log(
          '✅ Final token to return:',
          actualToken ? actualToken.substring(0, 30) + '...' : 'NULL',
        );

        return {
          user: transformedUser,
          requiresPasswordChange: !!requires_password_change,
          tempToken: temp_token,
          token: actualToken || temp_token,
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

  async verify2fa(tempToken, code, rememberDevice = false, deviceName = null) {
    try {
      const formData = new FormData();
      formData.append('temp_token', tempToken);
      formData.append('code', code);
      if (rememberDevice) {
        formData.append('remember_device', '1');
      }
      if (deviceName) {
        formData.append('device_name', deviceName);
      }

      const response = await apiCall(API_CONFIG.ENDPOINTS.TWO_FACTOR_VERIFY, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
        },
      });

      if (response.success && response.data) {
        const {user, access_token, token_type, expires_in, device_token} =
          response.data;

        let actualToken = null;
        if (typeof access_token === 'object' && access_token !== null) {
          actualToken = access_token.token;
        } else if (typeof access_token === 'string') {
          actualToken = access_token;
        }

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
          patients: user.patients || [],
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
          deviceToken: device_token || null,
        };
      }

      throw new Error('Risposta del server non valida');
    } catch (error) {
      console.error('2FA verify error:', error);
      throw error;
    }
  },

  async resendEmailOtp(tempToken) {
    try {
      const formData = new FormData();
      formData.append('temp_token', tempToken);

      const response = await apiCall(
        API_CONFIG.ENDPOINTS.TWO_FACTOR_SEND_EMAIL_OTP,
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
      console.error('Resend OTP error:', error);
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

        // Transform API response to match app's expected format
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
