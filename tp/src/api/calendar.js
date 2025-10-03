import apiClient from '../services/axiosConfig';

/**
 * Recupera gli appuntamenti di un paziente per una data specifica
 * @param {number} patientId - ID del paziente
 * @param {string} date - Data in formato YYYY-MM-DD
 * @returns {Promise<Object>}
 */
export const getPatientAppointments = async (patientId, date) => {
  try {
    console.log('\n=== RECUPERO APPUNTAMENTI PAZIENTE ===');
    console.log('📅 Patient ID:', patientId);
    console.log('📅 Date:', date);

    const response = await apiClient.post('/calendar/patient-appointments', {
      patient_id: patientId,
      date: date,
    });

    console.log('\n=== RISPOSTA APPUNTAMENTI ===');
    console.log('📥 Success:', response.data.success);
    console.log('📥 Count:', response.data.meta?.count || 0);
    console.log('📥 Data:', response.data.data);

    return response.data;
  } catch (error) {
    console.error('\n❌ Errore recuperando appuntamenti paziente:', error);

    // Gestione errori più specifica per evitare logout non necessario
    if (error.type === 'AUTH_ERROR') {
      // Errore di autenticazione - lascia che l'interceptor gestisca il logout
      throw error;
    } else if (error.type === 'NETWORK_ERROR') {
      // Errore di rete - non forzare logout
      throw {
        type: 'CALENDAR_ERROR',
        message: 'Errore di connessione. Verifica la tua connessione internet.',
        originalError: error,
      };
    } else if (error.type === 'SERVER_ERROR') {
      // Errore del server - non forzare logout per errori non di autenticazione
      throw {
        type: 'CALENDAR_ERROR',
        message:
          error.message ||
          'Errore del server durante il caricamento degli appuntamenti',
        originalError: error,
      };
    } else {
      // Errore generico
      throw {
        type: 'CALENDAR_ERROR',
        message: 'Errore imprevisto durante il caricamento degli appuntamenti',
        originalError: error,
      };
    }
  }
};

/**
 * Recupera i giorni del mese che hanno appuntamenti per evidenziarli nel calendario
 * @param {number} patientId - ID del paziente
 * @param {string} month - Mese in formato YYYY-MM
 * @returns {Promise<Object>}
 */
export const getPatientMarkedDates = async (patientId, month) => {
  try {
    console.log('\n=== RECUPERO DATE MARCATE PAZIENTE ===');
    console.log('📅 Patient ID:', patientId);
    console.log('📅 Month:', month);

    const response = await apiClient.post('/calendar/patient-marked-dates', {
      patient_id: patientId,
      month: month,
    });

    console.log('\n=== RISPOSTA DATE MARCATE ===');
    console.log('📥 Success:', response.data.success);
    console.log(
      '📥 Days with appointments:',
      response.data.meta?.total_days_with_appointments || 0,
    );
    console.log('📥 Marked dates:', response.data.data);

    return response.data;
  } catch (error) {
    console.error('\n❌ Errore recuperando date marcate paziente:', error);

    // Gestione errori più specifica per evitare logout non necessario
    if (error.type === 'AUTH_ERROR') {
      // Errore di autenticazione - lascia che l'interceptor gestisca il logout
      throw error;
    } else if (error.type === 'NETWORK_ERROR') {
      // Errore di rete - non forzare logout
      throw {
        type: 'CALENDAR_ERROR',
        message: 'Errore di connessione. Verifica la tua connessione internet.',
        originalError: error,
      };
    } else if (error.type === 'SERVER_ERROR') {
      // Errore del server - non forzare logout per errori non di autenticazione
      throw {
        type: 'CALENDAR_ERROR',
        message:
          error.message ||
          'Errore del server durante il caricamento del calendario',
        originalError: error,
      };
    } else {
      // Errore generico
      throw {
        type: 'CALENDAR_ERROR',
        message: 'Errore imprevisto durante il caricamento del calendario',
        originalError: error,
      };
    }
  }
};

/**
 * Utility per mappare gli stati degli appuntamenti ai colori
 * @param {string} status - Stato dell'appuntamento
 * @returns {string}
 */
export const getAppointmentStatusColor = status => {
  const colors = {
    confermato: '#007AFF', // Blu
    completato: '#34C759', // Verde
    annullato: '#FF3B30', // Rosso
    assente_giustificato: '#FF9500', // Arancione
    assente_non_giustificato: '#FF3B30', // Rosso
    therapist_absent: '#FF9500', // Arancione
  };
  return colors[status] || '#8E8E93'; // Grigio default
};

/**
 * Utility per mappare gli stati agli label italiani
 * @param {string} status - Stato dell'appuntamento
 * @returns {string}
 */
export const getAppointmentStatusLabel = status => {
  const labels = {
    confermato: 'Confermato',
    completato: 'Completato',
    annullato: 'Annullato',
    assente_giustificato: 'Assente Giustificato',
    assente_non_giustificato: 'Assente Non Giustificato',
    therapist_absent: 'Terapista Assente',
  };
  return labels[status] || status;
};

/**
 * Utility per determinare se un appuntamento può essere cancellato
 * @param {Object} appointment - Oggetto appuntamento
 * @returns {boolean}
 */
export const canCancelAppointment = appointment => {
  // Può essere cancellato solo se è confermato e la data è futura
  if (appointment.status !== 'confermato') {
    return false;
  }

  const appointmentDate = new Date(appointment.datetime);
  const now = new Date();
  const hoursUntilAppointment = (appointmentDate - now) / (1000 * 60 * 60);

  // Deve essere almeno 24 ore prima
  return hoursUntilAppointment > 24;
};

/**
 * Utility per formattare la data e ora dell'appuntamento
 * @param {string} datetime - Data e ora in formato ISO
 * @returns {Object}
 */
export const formatAppointmentDateTime = datetime => {
  const date = new Date(datetime);

  return {
    date: date.toLocaleDateString('it-IT', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    }),
    time: date.toLocaleTimeString('it-IT', {
      hour: '2-digit',
      minute: '2-digit',
    }),
    shortDate: date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }),
  };
};

/**
 * Utility per generare le date marcate per react-native-calendars
 * @param {Object} markedDatesData - Dati dal backend
 * @param {string} selectedDate - Data attualmente selezionata
 * @param {string} primaryColor - Colore primario del tema
 * @returns {Object}
 */
export const generateMarkedDates = (
  markedDatesData,
  selectedDate,
  primaryColor = '#007AFF',
) => {
  const markedDates = {...markedDatesData};

  // Aggiungi la selezione del giorno corrente
  if (selectedDate) {
    markedDates[selectedDate] = {
      ...markedDates[selectedDate],
      selected: true,
      selectedColor: primaryColor,
    };
  }

  return markedDates;
};

/**
 * Cancella un appuntamento del paziente con motivo e note
 * @param {number} appointmentId - ID dell'appuntamento da cancellare
 * @param {string} reason - Motivo della cancellazione
 * @param {string} notes - Note aggiuntive (opzionale)
 * @returns {Promise<Object>} - Risposta dell'API
 */
export const cancelPatientAppointment = async (
  appointmentId,
  reason,
  notes = '',
) => {
  try {
    const response = await apiClient.post('/calendar/cancel-appointment', {
      appointment_id: appointmentId,
      reason: reason,
      notes: notes,
    });

    return response.data;
  } catch (error) {
    console.error('Errore cancellazione appuntamento:', error);

    // Gestione errori specifica per cancellazione appuntamenti
    if (error.type === 'AUTH_ERROR') {
      // Errore di autenticazione - l'interceptor gestirà il logout
      throw error;
    } else if (error.type === 'PERMISSION_ERROR') {
      // Errore di permessi - mostra messaggio ma non fare logout
      throw {
        type: 'CANCELLATION_ERROR',
        message: 'Non hai i permessi per cancellare questo appuntamento',
        originalError: error,
      };
    } else if (error.type === 'NETWORK_ERROR') {
      // Errore di rete
      throw {
        type: 'CANCELLATION_ERROR',
        message: 'Errore di connessione durante la cancellazione',
        originalError: error,
      };
    } else if (error.type === 'SERVER_ERROR') {
      // Errore del server
      throw {
        type: 'CANCELLATION_ERROR',
        message: error.message || 'Errore del server durante la cancellazione',
        originalError: error,
      };
    } else {
      // Errore generico
      throw {
        type: 'CANCELLATION_ERROR',
        message: 'Errore imprevisto durante la cancellazione',
        originalError: error,
      };
    }
  }
};

/**
 * Recupera i prossimi appuntamenti di un paziente
 * @param {number} patientId - ID del paziente
 * @param {number} limit - Numero massimo di appuntamenti da recuperare (default: 3)
 * @returns {Promise<Object>}
 */
export const getPatientUpcomingAppointments = async (patientId, limit = 3) => {
  try {
    console.log('\n=== RECUPERO PROSSIMI APPUNTAMENTI PAZIENTE ===');
    console.log('📅 Patient ID:', patientId);
    console.log('📅 Limit:', limit);
    console.log('🎯 URL ENDPOINT:', '/calendar/patient-upcoming-appointments');
    console.log(
      '🌐 URL COMPLETO:',
      apiClient.defaults.baseURL + '/calendar/patient-upcoming-appointments',
    );

    const response = await apiClient.post(
      '/calendar/patient-upcoming-appointments',
      {
        patient_id: patientId,
        limit: limit,
      },
    );

    console.log('\n=== RISPOSTA PROSSIMI APPUNTAMENTI ===');
    console.log('📥 Success:', response.data.success);
    console.log('📥 Count:', response.data.meta?.count || 0);
    console.log('📥 Data:', response.data.data);

    return response.data;
  } catch (error) {
    console.error(
      '\n❌ Errore recuperando prossimi appuntamenti paziente:',
      error,
    );

    // Gestione errori più specifica per evitare logout non necessario
    if (error.type === 'AUTH_ERROR') {
      // Errore di autenticazione - lascia che l'interceptor gestisca il logout
      throw error;
    } else if (error.type === 'NETWORK_ERROR') {
      // Errore di rete - non forzare logout
      throw {
        type: 'CALENDAR_ERROR',
        message: 'Errore di connessione. Verifica la tua connessione internet.',
        originalError: error,
      };
    } else if (error.type === 'SERVER_ERROR') {
      // Errore del server - non forzare logout per errori non di autenticazione
      throw {
        type: 'CALENDAR_ERROR',
        message:
          error.message ||
          'Errore del server durante il caricamento degli appuntamenti',
        originalError: error,
      };
    } else {
      // Errore generico
      throw {
        type: 'CALENDAR_ERROR',
        message: 'Errore imprevisto durante il caricamento degli appuntamenti',
        originalError: error,
      };
    }
  }
};

/**
 * Opzioni predefinite per i motivi di cancellazione
 */
export const getCancellationReasons = () => [
  'Malattia',
  'Impegno lavorativo',
  'Impegno familiare',
  'Problemi di trasporto',
  'Condizioni meteorologiche',
  'Emergenza personale',
  'Altro',
];

/**
 * Recupera gli appuntamenti del terapista autenticato per una data specifica
 * @param {string} date - Data in formato YYYY-MM-DD
 * @returns {Promise<Object>} Response con gli appuntamenti
 */
export const getTherapistAppointments = async date => {
  try {
    console.log('\n=== RECUPERO APPUNTAMENTI TERAPISTA ===');
    console.log('📅 Date:', date);
    console.log('🎯 URL ENDPOINT:', '/calendar/therapist-appointments');

    const response = await apiClient.post('/calendar/therapist-appointments', {
      date: date,
    });

    console.log('\n=== RISPOSTA APPUNTAMENTI TERAPISTA ===');
    console.log('📥 Success:', response.data.success);
    console.log('📥 Count:', response.data.meta?.count || 0);
    console.log('📥 Groups Count:', response.data.meta?.groups_count || 0);
    console.log('📥 Full Data:', JSON.stringify(response.data.data, null, 2));

    // Log ogni appuntamento con i suoi campi
    if (response.data.data && response.data.data.length > 0) {
      console.log('\n📋 DETTAGLIO APPUNTAMENTI:');
      response.data.data.forEach((apt, index) => {
        console.log(`\n  Appuntamento ${index + 1}:`);
        console.log('    - ID:', apt.id);
        console.log('    - Time:', apt.time);
        console.log('    - Type:', apt.type);
        console.log('    - Status:', apt.status);
        console.log('    - Is Group:', apt.is_group);
        console.log('    - Setting ID:', apt.setting_id || '❌ MANCANTE');
        console.log('    - Patient:', apt.patient?.name);
        if (apt.is_group) {
          console.log('    - Patients Count:', apt.patients_count);
          console.log('    - Group Patients:', apt.group_patients?.length || 0);
        }
      });
    }

    return response.data;
  } catch (error) {
    console.error('Errore recupero appuntamenti terapista:', error);

    // Gestione errori specifica per appuntamenti terapista
    if (error.response?.status === 401) {
      // Errore di autenticazione - l'interceptor gestirà il logout
      throw {
        type: 'AUTH_ERROR',
        message: 'Sessione scaduta. Effettua nuovamente il login.',
      };
    } else if (error.response?.status === 403) {
      // Errore di permessi
      throw {
        type: 'PERMISSION_ERROR',
        message: 'Non hai i permessi per visualizzare questi appuntamenti.',
      };
    } else if (error.response?.status >= 400 && error.response?.status < 500) {
      // Altri errori client
      throw {
        type: 'CALENDAR_ERROR',
        message:
          error.response?.data?.error ||
          'Errore nel caricamento degli appuntamenti.',
      };
    } else if (!error.response) {
      // Errore di rete
      throw {
        type: 'NETWORK_ERROR',
        message: 'Errore di connessione. Verifica la tua connessione internet.',
      };
    } else {
      // Errore server generico
      throw {
        type: 'SERVER_ERROR',
        message: 'Errore del server. Riprova più tardi.',
      };
    }
  }
};

/**
 * Recupera le date marcate per il terapista autenticato in un mese specifico
 * @param {string} month - Mese in formato YYYY-MM
 * @returns {Promise<Object>} Response con le date marcate
 */
export const getTherapistMarkedDates = async month => {
  try {
    const response = await apiClient.post('/calendar/therapist-marked-dates', {
      month: month,
    });

    return response.data;
  } catch (error) {
    console.error('Errore recupero date marcate terapista:', error);

    // Gestione errori specifica per date marcate terapista
    if (error.response?.status === 401) {
      // Errore di autenticazione - l'interceptor gestirà il logout
      throw {
        type: 'AUTH_ERROR',
        message: 'Sessione scaduta. Effettua nuovamente il login.',
      };
    } else if (error.response?.status === 403) {
      // Errore di permessi
      throw {
        type: 'PERMISSION_ERROR',
        message: 'Non hai i permessi per visualizzare questi dati.',
      };
    } else if (error.response?.status >= 400 && error.response?.status < 500) {
      // Altri errori client
      throw {
        type: 'CALENDAR_ERROR',
        message:
          error.response?.data?.error || 'Errore nel caricamento delle date.',
      };
    } else if (!error.response) {
      // Errore di rete
      throw {
        type: 'NETWORK_ERROR',
        message: 'Errore di connessione. Verifica la tua connessione internet.',
      };
    } else {
      // Errore server generico
      throw {
        type: 'SERVER_ERROR',
        message: 'Errore del server. Riprova più tardi.',
      };
    }
  }
};

/**
 * Segna un paziente come assente da parte del terapista
 * @param {number} appointmentId - ID dell'appuntamento
 * @param {string} absenceType - Tipo di assenza: 'justified' o 'not_justified'
 * @param {string} reason - Motivo dell'assenza
 * @param {string} notes - Note aggiuntive (opzionale)
 * @returns {Promise<Object>} Response con esito operazione
 */
export const markPatientAbsent = async (
  appointmentId,
  absenceType,
  reason,
  notes = '',
  applyToGroup = false, // NUOVO parametro
) => {
  try {
    const response = await apiClient.post('/calendar/mark-patient-absent', {
      appointment_id: appointmentId,
      absence_type: absenceType,
      reason: reason,
      notes: notes,
      apply_to_group: applyToGroup, // AGGIUNGI questo
    });

    console.log('response', response);
    return response.data;
  } catch (error) {
    console.error('Errore nella segnalazione assenza:', error);
    throw error;
  }
};

/**
 * Restituisce i motivi di assenza disponibili per i terapisti
 * @returns {Array<string>} Array di motivi di assenza
 */
export const getAbsenceReasons = () => {
  return [
    'Paziente non si è presentato',
    'Paziente arrivato in ritardo',
    'Paziente non collaborativo',
    'Problemi di salute del paziente',
    'Problemi familiari del paziente',
    'Paziente ha rifiutato la terapia',
    'Altro',
  ];
};

/**
 * Verifica se un appuntamento può essere cancellato
 * @param {Object} appointment - Oggetto appuntamento
 * @returns {boolean} - True se può essere cancellato
 */
