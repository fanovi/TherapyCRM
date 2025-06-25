import apiClient from '../services/axiosConfig';

/**
 * SIMULAZIONE DATI - Tipologie di richieste disponibili
 * Questi dati verranno sostituiti dalla chiamata reale all'endpoint backend
 */
const MOCK_REQUEST_TYPES = [
  {
    id: 1,
    name: 'Certificato Medico',
    description: 'Richiesta certificato medico per assenza lavorativa',
    icon: 'file-document-outline',
    category: 'medical',
    estimated_days: 3,
    requires_reason: true,
    requires_date_range: true,
  },
  {
    id: 2,
    name: 'Relazione Terapeutica',
    description: 'Richiesta relazione dettagliata sui progressi terapeutici',
    icon: 'chart-line',
    category: 'therapy',
    estimated_days: 5,
    requires_reason: true,
    requires_date_range: false,
  },
  {
    id: 3,
    name: 'Copia Cartella Clinica',
    description: 'Richiesta copia della cartella clinica completa',
    icon: 'folder-account',
    category: 'medical',
    estimated_days: 7,
    requires_reason: true,
    requires_date_range: false,
  },
  {
    id: 4,
    name: 'Certificato di Idoneità',
    description: 'Certificato di idoneità per attività sportiva/lavorativa',
    icon: 'medal',
    category: 'fitness',
    estimated_days: 2,
    requires_reason: true,
    requires_date_range: false,
  },
  {
    id: 5,
    name: 'Referto Esami',
    description: 'Richiesta copia referto di esami specifici',
    icon: 'test-tube',
    category: 'medical',
    estimated_days: 1,
    requires_reason: false,
    requires_date_range: true,
  },
  {
    id: 6,
    name: 'Cambio Appuntamento',
    description: 'Richiesta modifica o spostamento appuntamento esistente',
    icon: 'calendar-edit',
    category: 'appointment',
    estimated_days: 1,
    requires_reason: true,
    requires_date_range: false,
  },
];

/**
 * Simula stati delle richieste esistenti per test
 */
const MOCK_USER_REQUESTS = [
  {
    id: 101,
    request_type: 'Certificato Medico',
    status: 'in_progress',
    created_at: '2025-01-20T10:30:00Z',
    estimated_completion: '2025-01-23T18:00:00Z',
    reason: 'Certificato per assenza lavorativa dal 15/01 al 20/01',
    notes: 'Richiesta in elaborazione da parte del medico responsabile',
  },
  {
    id: 102,
    request_type: 'Relazione Terapeutica',
    status: 'completed',
    created_at: '2025-01-15T14:20:00Z',
    completed_at: '2025-01-18T16:45:00Z',
    reason: 'Relazione per visita specialistica',
    download_url: '#',
  },
];

/**
 * Ottiene le tipologie di richieste disponibili
 * @returns {Promise<Array>}
 */
export const getRequestTypes = async () => {
  try {
    console.log('📋 Recuperando tipologie di richieste disponibili...');

    // TODO: Sostituire con chiamata reale quando l'endpoint sarà disponibile
    // const response = await apiClient.get('/requests/types');

    // Simulazione con delay per realismo
    await new Promise(resolve => setTimeout(resolve, 800));

    const mockResponse = {
      success: true,
      data: MOCK_REQUEST_TYPES,
    };

    console.log('✅ Tipologie richieste recuperate:', mockResponse.data);
    return mockResponse;
  } catch (error) {
    console.error('❌ Errore recuperando tipologie richieste:', error);
    throw error;
  }
};

/**
 * Crea una nuova richiesta
 * @param {Object} requestData
 * @param {number} requestData.type_id - ID tipologia richiesta
 * @param {string} requestData.reason - Motivo della richiesta
 * @param {string} requestData.date_from - Data inizio (opzionale)
 * @param {string} requestData.date_to - Data fine (opzionale)
 * @param {string} requestData.notes - Note aggiuntive (opzionale)
 * @returns {Promise}
 */
export const createRequest = async requestData => {
  try {
    console.log('📝 Creando nuova richiesta...', requestData);

    // TODO: Sostituire con chiamata reale
    // const response = await apiClient.post('/requests', requestData);

    // Simulazione creazione
    await new Promise(resolve => setTimeout(resolve, 1200));

    const requestType = MOCK_REQUEST_TYPES.find(
      type => type.id === requestData.type_id,
    );
    const newRequest = {
      id: Date.now(), // ID temporaneo
      request_type: requestType?.name || 'Richiesta Generica',
      status: 'pending',
      created_at: new Date().toISOString(),
      estimated_completion: new Date(
        Date.now() + (requestType?.estimated_days || 3) * 24 * 60 * 60 * 1000,
      ).toISOString(),
      reason: requestData.reason,
      date_from: requestData.date_from,
      date_to: requestData.date_to,
      notes: requestData.notes,
    };

    const mockResponse = {
      success: true,
      data: newRequest,
      message:
        'Richiesta creata con successo! Riceverai una notifica quando sarà pronta.',
    };

    console.log('✅ Richiesta creata:', mockResponse.data);
    return mockResponse;
  } catch (error) {
    console.error('❌ Errore creando richiesta:', error);
    throw error;
  }
};

/**
 * Ottiene le richieste dell'utente corrente
 * @param {string} status - Filtro per stato (opzionale): 'pending', 'in_progress', 'completed', 'rejected'
 * @returns {Promise}
 */
export const getUserRequests = async (status = null) => {
  try {
    console.log(
      `📋 Recuperando richieste utente${
        status ? ` con stato: ${status}` : ''
      }...`,
    );

    // TODO: Sostituire con chiamata reale
    // const response = await apiClient.get('/requests', { params: { status } });

    await new Promise(resolve => setTimeout(resolve, 600));

    let filteredRequests = [...MOCK_USER_REQUESTS];
    if (status) {
      filteredRequests = filteredRequests.filter(req => req.status === status);
    }

    const mockResponse = {
      success: true,
      data: filteredRequests,
      total: filteredRequests.length,
    };

    console.log('✅ Richieste utente recuperate:', mockResponse.data);
    return mockResponse;
  } catch (error) {
    console.error('❌ Errore recuperando richieste utente:', error);
    throw error;
  }
};

/**
 * Ottiene dettagli di una richiesta specifica
 * @param {number} requestId
 * @returns {Promise}
 */
export const getRequestDetails = async requestId => {
  try {
    console.log(`📋 Recuperando dettagli richiesta ${requestId}...`);

    // TODO: Sostituire con chiamata reale
    // const response = await apiClient.get(`/requests/${requestId}`);

    await new Promise(resolve => setTimeout(resolve, 400));

    const request = MOCK_USER_REQUESTS.find(req => req.id === requestId);
    if (!request) {
      throw new Error('Richiesta non trovata');
    }

    const mockResponse = {
      success: true,
      data: request,
    };

    console.log('✅ Dettagli richiesta recuperati:', mockResponse.data);
    return mockResponse;
  } catch (error) {
    console.error('❌ Errore recuperando dettagli richiesta:', error);
    throw error;
  }
};

/**
 * Annulla una richiesta (solo se in stato pending)
 * @param {number} requestId
 * @param {string} reason - Motivo dell'annullamento
 * @returns {Promise}
 */
export const cancelRequest = async (requestId, reason = '') => {
  try {
    console.log(`🚫 Annullando richiesta ${requestId}...`);

    // TODO: Sostituire con chiamata reale
    // const response = await apiClient.post(`/requests/${requestId}/cancel`, { reason });

    await new Promise(resolve => setTimeout(resolve, 800));

    const mockResponse = {
      success: true,
      message: 'Richiesta annullata con successo',
    };

    console.log('✅ Richiesta annullata');
    return mockResponse;
  } catch (error) {
    console.error('❌ Errore annullando richiesta:', error);
    throw error;
  }
};

/**
 * Scarica un documento completato
 * @param {number} requestId
 * @returns {Promise}
 */
export const downloadRequestDocument = async requestId => {
  try {
    console.log(`📥 Scaricando documento richiesta ${requestId}...`);

    // TODO: Implementare download reale
    // const response = await apiClient.get(`/requests/${requestId}/download`, {
    //   responseType: 'blob'
    // });

    await new Promise(resolve => setTimeout(resolve, 1000));

    // Per ora ritorna solo conferma
    const mockResponse = {
      success: true,
      message: 'Download avviato',
      // In una implementazione reale qui ci sarebbe il blob del file
    };

    console.log('✅ Download completato');
    return mockResponse;
  } catch (error) {
    console.error('❌ Errore durante download:', error);
    throw error;
  }
};

/**
 * Utility per ottenere il colore associato allo stato
 * @param {string} status
 * @returns {string}
 */
export const getStatusColor = status => {
  const colors = {
    pending: '#FFA726', // Arancione
    in_progress: '#42A5F5', // Blu
    completed: '#66BB6A', // Verde
    rejected: '#EF5350', // Rosso
    cancelled: '#BDBDBD', // Grigio
  };
  return colors[status] || '#9E9E9E';
};

/**
 * Utility per ottenere l'icona associata allo stato
 * @param {string} status
 * @returns {string}
 */
export const getStatusIcon = status => {
  const icons = {
    pending: 'clock-outline',
    in_progress: 'progress-clock',
    completed: 'check-circle',
    rejected: 'close-circle',
    cancelled: 'cancel',
  };
  return icons[status] || 'help-circle';
};

/**
 * Utility per ottenere il label italiano dello stato
 * @param {string} status
 * @returns {string}
 */
export const getStatusLabel = status => {
  const labels = {
    pending: 'In Attesa',
    in_progress: 'In Elaborazione',
    completed: 'Completata',
    rejected: 'Rifiutata',
    cancelled: 'Annullata',
  };
  return labels[status] || status;
};
