import apiClient from '../services/axiosConfig';

export const login = async (email, password) => {
  try {
    console.log('🌐 === API CALL DEBUG ===');
    console.log('📍 Endpoint: POST /auth/login');
    console.log('🔗 URL completo:', `${apiClient.defaults.baseURL}/auth/login`);
    console.log('📦 Payload da inviare:', {
      email: email || '[MISSING]',
      password: password ? '[PRESENT]' : '[MISSING]',
    });

    // Crea FormData per compatibilità con PHP
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    console.log(
      '📋 Formato dati: FormData (application/x-www-form-urlencoded)',
    );

    const response = await apiClient.post('/auth/login', formData, {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    });

    console.log('✅ Response ricevuta:', {
      status: response.status,
      url: response.config?.url || 'UNKNOWN',
      method: response.config?.method?.toUpperCase() || 'UNKNOWN',
      success: response.data?.success,
      hasData: !!response.data?.data,
      error: response.data?.error || 'NONE',
    });

    return response.data;
  } catch (error) {
    console.error('❌ Login API error:', {
      message: error.message,
      type: error.type,
      status: error.status,
      response: error.response?.data,
    });
    throw error;
  }
};

export const refreshToken = async refreshToken => {
  try {
    console.log(
      'Sending refresh token:',
      refreshToken.substring(0, 10) + '...',
    );

    const response = await apiClient.post('/auth/refresh', {
      refreshToken,
    });

    console.log('Refresh response:', response.data);
    return response.data;
  } catch (error) {
    console.error('Refresh token error details:', error);
    throw error;
  }
};

// Verifica se il token è ancora valido lato backend
export const checkTokenValidity = async token => {
  try {
    const response = await apiClient.get('/auth/check-token');
    return response.data; // { success: true, valid: true/false }
  } catch (error) {
    console.error('Check token validity error:', error);
    throw error;
  }
};
