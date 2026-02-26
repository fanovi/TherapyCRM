import {createSlice} from '@reduxjs/toolkit';

const initialState = {
  isAuthenticated: false,
  token: null,
  refreshToken: null,
  isLoading: false,
  user: null,
  error: null,
  currentRole: null, // Nuovo campo per il ruolo corrente
  isInitialized: false, // Per gestire lo stato di inizializzazione
  requiresPasswordChange: false, // Per gestire il cambio password obbligatorio
  tempToken: null, // Token temporaneo per il cambio password
  // 2FA state
  requires2fa: false,
  twoFactorMethod: null, // 'totp' | 'email'
  showRememberDevice: false,
  twoFactorTempToken: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    loginStart: state => {
      state.isLoading = true;
      state.error = null;
    },
    loginSuccess: (state, action) => {
      state.isAuthenticated = !action.payload.requiresPasswordChange; // Solo autenticato se non richiede cambio password
      state.token = action.payload.token;
      state.refreshToken = action.payload.refreshToken;
      state.user = action.payload.user;
      state.isLoading = false;
      state.error = null;
      state.requiresPasswordChange =
        action.payload.requiresPasswordChange || false;
      state.tempToken = action.payload.tempToken || null;
      state.currentRole =
        action.payload.user.roles?.[action.payload.user.roles.length - 1] ||
        action.payload.user.role ||
        action.payload.user.user_type;
    },
    loginFailure: (state, action) => {
      state.isAuthenticated = false;
      state.isLoading = false;
      state.error = action.payload;
    },
    changePasswordSuccess: (state, action) => {
      state.isAuthenticated = true;
      state.token = action.payload.token;
      state.user = action.payload.user;
      state.requiresPasswordChange = false;
      state.tempToken = null;
      state.isLoading = false;
      state.error = null;
      state.currentRole =
        action.payload.user.roles?.[action.payload.user.roles.length - 1] ||
        action.payload.user.role ||
        action.payload.user.user_type;
    },
    logoutUser: state => {
      // Resettiamo tutti i valori allo stato iniziale
      Object.assign(state, initialState);
    },
    clearError: state => {
      state.error = null;
    },
    setCurrentRole: (state, action) => {
      state.currentRole = action.payload;
    },
    setInitialized: state => {
      state.isInitialized = true;
    },
    resetPasswordRequestSuccess: state => {
      state.isLoading = false;
      state.error = null;
    },
    // 2FA reducers
    twoFactorRequired: (state, action) => {
      state.isLoading = false;
      state.error = null;
      state.requires2fa = true;
      state.twoFactorMethod = action.payload.twoFactorMethod;
      state.showRememberDevice = action.payload.showRememberDevice || false;
      state.twoFactorTempToken = action.payload.tempToken;
      state.user = action.payload.user;
    },
    twoFactorSuccess: (state, action) => {
      state.isAuthenticated = true;
      state.token = action.payload.token;
      state.user = action.payload.user;
      state.isLoading = false;
      state.error = null;
      state.requires2fa = false;
      state.twoFactorMethod = null;
      state.showRememberDevice = false;
      state.twoFactorTempToken = null;
      state.currentRole =
        action.payload.user.roles?.[action.payload.user.roles.length - 1] ||
        action.payload.user.role ||
        action.payload.user.user_type;
    },
    twoFactorFailure: (state, action) => {
      state.isLoading = false;
      state.error = action.payload;
    },
    clear2faState: state => {
      state.requires2fa = false;
      state.twoFactorMethod = null;
      state.showRememberDevice = false;
      state.twoFactorTempToken = null;
      state.isLoading = false;
      state.error = null;
    },
  },
});

export const {
  loginStart,
  loginSuccess,
  loginFailure,
  changePasswordSuccess,
  logoutUser,
  clearError,
  setCurrentRole,
  setInitialized,
  resetPasswordRequestSuccess,
  twoFactorRequired,
  twoFactorSuccess,
  twoFactorFailure,
  clear2faState,
} = authSlice.actions;

export default authSlice.reducer;
