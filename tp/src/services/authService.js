import { API_CONFIG, ERROR_MESSAGES } from '../config/api';

// API Configuration
const API_BASE_URL = API_CONFIG.BASE_URL;

// Utility function for API calls
const apiCall = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const config = {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    ...options,
  };

  try {
    const response = await fetch(url, config);
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

export const authService = {
  async login(credentials) {
    try {
      const formData = new FormData();
      formData.append('email', credentials.email);
      formData.append('password', credentials.password);

      const response = await apiCall(API_CONFIG.ENDPOINTS.LOGIN, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          // Don't set Content-Type for FormData, let the browser set it
        },
      });

      if (response.success && response.data) {
        const { user, access_token, token_type, expires_in } = response.data;
        
        // Transform API response to match app's expected format
        const transformedUser = {
          id: user.id.toString(),
          email: user.email,
          role: user.user_type === 'paziente' ? 'patient' : 'therapist',
          firstName: user.nome,
          lastName: user.cognome,
          fullName: `${user.nome} ${user.cognome}`,
          codiceFiscale: user.codice_fiscale,
          telefono: user.telefono,
          dataNascita: user.data_nascita,
          indirizzo: user.indirizzo,
          status: user.status,
          isFirstLogin: false,
          isPasswordResetRequired: false,
          // Add additional fields for therapists
          ...(user.user_type === 'terapista' && {
            specializzazione: user.specializzazione,
            numeroAlbo: user.numero_albo,
          }),
        };

        return {
          token: access_token,
          tokenType: token_type,
          expiresIn: expires_in,
          user: transformedUser,
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
    throw new Error('Funzionalità di reset password non ancora implementata nel server');
  },

  async validateToken(token) {
    try {
      const response = await apiCall(API_CONFIG.ENDPOINTS.VERIFY, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      if (response.success && response.data) {
        const { user } = response.data;
        
        // Transform API response to match app's expected format
        return {
          id: user.id.toString(),
          email: user.email,
          role: user.user_type === 'paziente' ? 'patient' : 'therapist',
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
      await apiCall(API_CONFIG.ENDPOINTS.LOGOUT, {
        method: 'POST',
      });
    } catch (error) {
      console.error('Logout error:', error);
      // Don't throw error for logout, just log it
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
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
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
};
