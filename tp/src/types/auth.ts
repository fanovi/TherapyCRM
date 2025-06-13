// User Types
export interface User {
  id: string;
  email: string;
  role: 'patient' | 'therapist';
  firstName: string;
  lastName: string;
  fullName: string;
  codiceFiscale: string;
  telefono: string;
  dataNascita: string;
  indirizzo?: string;
  status: 'attivo' | 'sospeso' | 'disattivo';
  isFirstLogin: boolean;
  isPasswordResetRequired: boolean;
  // Additional fields for therapists
  specializzazione?: string;
  numeroAlbo?: string;
}

// API Response Types
export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data?: T;
  error_code?: string;
}

export interface LoginResponse {
  user: ApiUser;
  requires_password_change?: boolean;
  temp_token?: string;
  access_token?: string;
  token_type?: string;
  expires_in?: number;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface AuthState {
  isAuthenticated: boolean;
  user: User | null;
  token: string | null;
  tokenType?: string;
  expiresIn?: number;
  isLoading: boolean;
  error: string | null;
  isInitialized: boolean;
  requiresPasswordChange: boolean;
  tempToken?: string;
}

// Password Reset Types
export interface ResetPasswordData {
  password: string;
  confirmPassword: string;
  currentPassword?: string;
}

// API User from Backend (before transformation)
export interface ApiUser {
  id: number;
  email: string;
  nome: string;
  cognome: string;
  codice_fiscale: string;
  telefono: string;
  data_nascita: string;
  indirizzo?: string;
  user_type: 'paziente' | 'terapista';
  status: 'attivo' | 'sospeso' | 'disattivo';
  // Additional fields for therapists
  specializzazione?: string;
  numero_albo?: string;
}
