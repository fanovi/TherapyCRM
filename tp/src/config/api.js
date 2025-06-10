// API Configuration
export const API_CONFIG = {
  BASE_URL: 'http://127.0.0.1:3307/cms-terapisti/TherapyCRM/api',
  ENDPOINTS: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    VERIFY: '/auth/verify',
    RESET_PASSWORD: '/auth/reset-password', // Future implementation
    REFRESH_TOKEN: '/auth/refresh', // Future implementation
  },
  TIMEOUT: 10000, // 10 seconds
};

// HTTP Status Codes
export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  INTERNAL_SERVER_ERROR: 500,
};

// Error Messages
export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Impossibile connettersi al server. Verificare la connessione.',
  INVALID_CREDENTIALS: 'Credenziali non valide',
  SERVER_ERROR: 'Errore del server',
  TOKEN_EXPIRED: 'Sessione scaduta. Effettua nuovamente il login.',
  GENERIC_ERROR: 'Si è verificato un errore imprevisto',
}; 