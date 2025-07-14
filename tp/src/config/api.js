// API Configuration
export const API_CONFIG = {
  BASE_URL: 'https://17203f7f58bf.ngrok-free.app/TherapyCRM/api',
  ENDPOINTS: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    VERIFY: '/auth/verify',
    CHANGE_FIRST_PASSWORD: '/auth/change-first-password',
    RESET_PASSWORD: '/auth/reset-password', // Future implementation
    REFRESH_TOKEN: '/auth/refresh', // Future implementation

    // Notifications
    NOTIFICATIONS: '/notifications',
    NOTIFICATION_DETAIL: id => `/notifications/${id}`,
    NOTIFICATIONS_UNREAD: '/notifications/unread',
    NOTIFICATIONS_BLOCKING: '/notifications/blocking',
    NOTIFICATIONS_HAS_BLOCKING: '/notifications/has-blocking',
    NOTIFICATION_MARK_READ: id => `/notifications/${id}/mark-read`,
    NOTIFICATION_CONFIRM_READ: id => `/notifications/${id}/confirm-read`,
    NOTIFICATION_MARK_VIEWED: id => `/notifications/${id}/mark-viewed`,
    NOTIFICATIONS_SEND: '/notifications/send',
    NOTIFICATIONS_SEND_TEMPLATE: '/notifications/send-template',
    NOTIFICATIONS_CREATE_TEST: '/notifications/create-test',
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
  NETWORK_ERROR:
    'Impossibile connettersi al server. Verificare la connessione.',
  INVALID_CREDENTIALS: 'Credenziali non valide',
  SERVER_ERROR: 'Errore del server',
  TOKEN_EXPIRED: 'Sessione scaduta. Effettua nuovamente il login.',
  GENERIC_ERROR: 'Si è verificato un errore imprevisto',
};

// Aggiungi gli endpoint del calendario se non presenti
export const API_ENDPOINTS = {
  // Calendar endpoints
  CALENDAR_PATIENT_APPOINTMENTS: '/calendar/patient-appointments',
  CALENDAR_PATIENT_MARKED_DATES: '/calendar/patient-marked-dates',
  CALENDAR_CANCEL_APPOINTMENT: '/calendar/cancel-appointment',
};
