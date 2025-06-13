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
    console.log('🔍 === TOKEN VALIDITY CHECK ===');
    console.log('📍 Endpoint: GET /auth/verify');
    console.log('🎫 Token presente:', !!token);
    console.log('🎫 Token length:', token ? token.length : 0);
    console.log(
      '🎫 Token starts with:',
      token ? token.substring(0, 10) : 'N/A',
    );

    if (!token) {
      console.log('❌ Token mancante, returnando invalid');
      return {valid: false, error: 'Token mancante'};
    }

    console.log(
      '🌐 Making request to:',
      `${apiClient.defaults.baseURL}/auth/verify`,
    );

    const response = await apiClient.get('/auth/verify', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    console.log('✅ Token verification response received:');
    console.log('   - Status:', response.status);
    console.log('   - Success:', response.data?.success);
    console.log('   - Message:', response.data?.message);
    console.log('   - Has user data:', !!response.data?.data?.user);
    console.log('   - Full response data:', response.data);

    // Il backend dovrebbe restituire { success: true, data: { user: {...} } }
    return {
      valid: response.data?.success === true,
      user: response.data?.data?.user || null,
    };
  } catch (error) {
    console.error('❌ Token validity check error:');
    console.error('   - Message:', error.message);
    console.error('   - Status:', error.response?.status);
    console.error('   - Response data:', error.response?.data);

    // Se il token è scaduto/invalido, il server dovrebbe restituire 401
    if (error.response?.status === 401) {
      console.log('🔒 Server returned 401 - token is invalid/expired');
      return {valid: false, error: 'Token scaduto o non valido'};
    }

    // Per altri errori (rete, server), consideriamo il token non valido
    console.log('🚫 Other error occurred - considering token invalid');
    return {valid: false, error: error.message};
  }
};
