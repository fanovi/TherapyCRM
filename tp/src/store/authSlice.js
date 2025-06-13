import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {authService} from '../services/authService';
import * as Keychain from 'react-native-keychain';

// Async thunks
export const loginUser = createAsyncThunk(
  'auth/login',
  async (credentials, {rejectWithValue}) => {
    try {
      const response = await authService.login(credentials);

      // Salva il token in Keychain
      await Keychain.setInternetCredentials(
        'cms-terapisti-token',
        'token',
        response.token,
      );

      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const resetPassword = createAsyncThunk(
  'auth/resetPassword',
  async ({userId, data}, {rejectWithValue}) => {
    try {
      const user = await authService.resetPassword(userId, data);
      return user;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const validateToken = createAsyncThunk(
  'auth/validateToken',
  async (_, {rejectWithValue}) => {
    try {
      const authStatus = await authService.checkAuthStatus();

      if (!authStatus.isValid) {
        return rejectWithValue(authStatus.error || 'Token non valido');
      }

      return {token: authStatus.token, user: authStatus.user};
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const changePassword = createAsyncThunk(
  'auth/changePassword',
  async ({tempToken, newPassword, confirmPassword}, {rejectWithValue}) => {
    try {
      const response = await authService.changePassword(
        tempToken,
        newPassword,
        confirmPassword,
      );

      // Salva il nuovo token in Keychain
      await Keychain.setInternetCredentials(
        'cms-terapisti-token',
        'token',
        response.token,
      );

      return response;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const logoutUser = createAsyncThunk(
  'auth/logout',
  async (_, {rejectWithValue}) => {
    try {
      await authService.logout();
      await Keychain.resetInternetCredentials('cms-terapisti-token');
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

const initialState = {
  isAuthenticated: false,
  user: null,
  token: null,
  isLoading: false,
  error: null,
  isInitialized: false,
  requiresPasswordChange: false,
  tempToken: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    clearError: state => {
      state.error = null;
    },
    setInitialized: state => {
      state.isInitialized = true;
    },
  },
  extraReducers: builder => {
    builder
      // Login
      .addCase(loginUser.pending, state => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.requiresPasswordChange =
          action.payload.requiresPasswordChange || false;
        state.tempToken = action.payload.tempToken;
        state.isAuthenticated = !state.requiresPasswordChange;
        state.error = null;
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload;
      })

      // Reset Password
      .addCase(resetPassword.pending, state => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(resetPassword.fulfilled, (state, action) => {
        state.isLoading = false;
        state.user = action.payload;
        state.error = null;
      })
      .addCase(resetPassword.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload;
      })

      // Validate Token
      .addCase(validateToken.pending, state => {
        state.isLoading = true;
      })
      .addCase(validateToken.fulfilled, (state, action) => {
        state.isLoading = false;
        state.isAuthenticated = true;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isInitialized = true;
      })
      .addCase(validateToken.rejected, state => {
        state.isLoading = false;
        state.isAuthenticated = false;
        state.user = null;
        state.token = null;
        state.isInitialized = true;
      })

      // Change Password
      .addCase(changePassword.pending, state => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(changePassword.fulfilled, (state, action) => {
        state.isLoading = false;
        state.isAuthenticated = true;
        // Preserva i dati utente esistenti e aggiorna solo i campi necessari
        state.user = {
          ...state.user,
          ...action.payload.user,
          // Assicurati che firstName e lastName siano preservati se esistono già
          firstName: action.payload.user.firstName || state.user?.firstName,
          lastName: action.payload.user.lastName || state.user?.lastName,
          fullName: action.payload.user.fullName || state.user?.fullName,
          isFirstLogin: false,
          isPasswordResetRequired: false,
        };
        state.token = action.payload.token;
        state.requiresPasswordChange = false;
        state.tempToken = null;
        state.error = null;
      })
      .addCase(changePassword.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.payload;
      })

      // Logout
      .addCase(logoutUser.fulfilled, state => {
        state.isAuthenticated = false;
        state.user = null;
        state.token = null;
        state.requiresPasswordChange = false;
        state.tempToken = null;
        state.error = null;
      });
  },
});

export const {clearError, setInitialized} = authSlice.actions;
export default authSlice.reducer;
