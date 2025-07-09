import {authService} from '../services/authService';

// Test data based on your API mock data
const TEST_CREDENTIALS = {
  PATIENT: {
    email: 'paziente@test.it',
    password: 'password123',
  },
  THERAPIST: {
    email: 'terapista1@example.com',
    password: 'password789',
  },
};

export const testApiConnection = async () => {
  console.log('🧪 Testing API Connection...');

  try {
    // Test patient login
    console.log('Testing patient login...');
    const patientResult = await authService.login(TEST_CREDENTIALS.PATIENT);
    console.log('✅ Patient login successful:', {
      user: patientResult.user.email,
      role: patientResult.user.role,
      token: patientResult.token.substring(0, 20) + '...',
    });

    // Test token validation
    console.log('Testing token validation...');
    const validationResult = await authService.validateToken(
      patientResult.token,
    );
    console.log('✅ Token validation successful:', validationResult);

    // Test logout
    console.log('Testing logout...');
    await authService.logout();
    console.log('✅ Logout successful');

    return {
      success: true,
      message: 'All API tests passed successfully!',
    };
  } catch (error) {
    console.error('❌ API Test failed:', error.message);
    return {
      success: false,
      message: error.message,
      error,
    };
  }
};

export const testLogin = async credentials => {
  try {
    const result = await authService.login(credentials);
    console.log('Login test result:', result);
    return result;
  } catch (error) {
    console.error('Login test error:', error);
    throw error;
  }
};

export const testChangePassword = async (
  tempToken,
  newPassword = 'NewPassword123',
) => {
  try {
    console.log('🧪 Testing change password...');
    const result = await authService.changePassword(
      tempToken,
      newPassword,
      newPassword,
    );
    console.log('✅ Change password successful:', {
      userEmail: result.user.email,
      tokenPreview: result.token.substring(0, 20) + '...',
    });
    return result;
  } catch (error) {
    console.error('❌ Change password test failed:', error.message);
    throw error;
  }
};

// Helper function to format API responses for debugging
export const debugApiResponse = response => {
  return {
    success: response.success,
    message: response.message,
    userEmail: response.data?.user?.email,
    userRole: response.data?.user?.user_type,
    tokenPreview: response.data?.access_token?.substring(0, 30) + '...',
  };
};
