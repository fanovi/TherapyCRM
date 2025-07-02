import apiClient from '../services/axiosConfig';

/**
 * Ottiene le tipologie di richieste disponibili dal backend
 * @returns {Promise<Array>}
 */
export const getRequestTypes = async () => {
  try {
    console.log('\n=== RICHIESTA TIPI ===');
    console.log('📤 Recupero tipologie di richieste...');

    const response = await apiClient.get('/requests/types');

    console.log('\n=== RISPOSTA TIPI ===');
    console.log('📥 Struttura completa:');
    console.log(JSON.stringify(response.data, null, 2));

    return response.data;
  } catch (error) {
    console.error('\n❌ Errore recuperando tipologie richieste:', error);
    throw error;
  }
};

/**
 * Crea una nuova richiesta
 * @param {Object} requestData
 * @param {number} requestData.request_type_id - ID tipologia richiesta (CAMBIATO da type_id)
 * @param {number} requestData.patient_id - ID paziente per cui fare la richiesta
 * @param {string} requestData.notes - Note aggiuntive (opzionale)
 * @param {number} requestData.therapeutic_plan_id - ID piano terapeutico (opzionale)
 * @param {number} requestData.therapy_id - ID terapia (opzionale)
 * @returns {Promise}
 */
export const createRequest = async requestData => {
  try {
    console.log('\n=== CREAZIONE NUOVA RICHIESTA ===');
    console.log('📋 Dati ricevuti:', JSON.stringify(requestData, null, 2));

    // Pulisci i dati rimuovendo campi undefined o vuoti
    const cleanedData = Object.entries(requestData).reduce(
      (acc, [key, value]) => {
        // Includi il campo solo se ha un valore valido
        if (value !== undefined && value !== null && value !== '') {
          acc[key] = value;
        }
        return acc;
      },
      {},
    );

    console.log('\n📤 Dati puliti da inviare al backend:');
    console.log(JSON.stringify(cleanedData, null, 2));

    const response = await apiClient.post('/requests', cleanedData);

    console.log('\n=== RISPOSTA BACKEND ===');
    console.log('📥 Struttura completa:');
    console.log(JSON.stringify(response.data, null, 2));
    return response.data;
  } catch (error) {
    console.error('\n❌ Errore creando richiesta:', error);
    throw error;
  }
};

/**
 * Ottiene le richieste per un paziente specifico
 * @param {number} patientId - ID del paziente
 * @param {string} status - Filtro per stato (opzionale)
 * @param {number} page - Numero di pagina (default: 1)
 * @param {number} limit - Elementi per pagina (default: 20)
 * @returns {Promise}
 */
export const getPatientRequests = async (
  patientId,
  status = null,
  page = 1,
  limit = 20,
) => {
  try {
    console.log(
      `📋 Recuperando richieste per paziente ${patientId}${
        status ? ` con stato: ${status}` : ''
      }...`,
    );

    const params = {
      patient_id: patientId,
      page,
      limit,
    };

    if (status) {
      params.status = status;
    }

    const response = await apiClient.get('/requests', {params});

    console.log('✅ Richieste paziente recuperate:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore recuperando richieste paziente:', error);
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

    const response = await apiClient.get(`/requests/${requestId}`);

    console.log('✅ Dettagli richiesta recuperati:', response.data);
    return response.data;
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

    // TODO: Implementare endpoint di cancellazione quando sarà disponibile
    // const response = await apiClient.post(`/requests/${requestId}/cancel`, { reason });

    // Per ora simulazione
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

    // TODO: Implementare download reale quando sarà disponibile
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
 * Aggiornato per gli stati del backend
 * @param {string} status
 * @returns {string}
 */
export const getStatusColor = status => {
  const colors = {
    inviata: '#FFA726', // Arancione - corrisponde a "pending"
    presa_in_carico: '#42A5F5', // Blu - corrisponde a "in_progress"
    stampato: '#7986CB', // Indaco - documento stampato
    consegnato: '#66BB6A', // Verde - corrisponde a "completed"
    rifiutata: '#EF5350', // Rosso - corrisponde a "rejected"
    annullata: '#BDBDBD', // Grigio - corrisponde a "cancelled"
  };
  return colors[status] || '#9E9E9E';
};

/**
 * Utility per ottenere l'icona associata allo stato
 * Aggiornata per gli stati del backend
 * @param {string} status
 * @returns {string}
 */
export const getStatusIcon = status => {
  const icons = {
    inviata: 'clock-outline',
    presa_in_carico: 'progress-clock',
    stampato: 'printer',
    consegnato: 'check-circle',
    rifiutata: 'close-circle',
    annullata: 'cancel',
  };
  return icons[status] || 'help-circle';
};

/**
 * Utility per ottenere il label italiano dello stato
 * Aggiornata per gli stati del backend
 * @param {string} status
 * @returns {string}
 */
export const getStatusLabel = status => {
  const labels = {
    inviata: 'Inviata',
    presa_in_carico: 'In Lavorazione',
    stampato: 'Stampato',
    consegnato: 'Consegnato',
    rifiutata: 'Rifiutata',
    annullata: 'Annullata',
  };
  return labels[status] || status;
};

/**
 * Mappa gli stati del backend agli stati del frontend per compatibilità
 * @param {string} backendStatus
 * @returns {string}
 */
export const mapBackendStatusToFrontend = backendStatus => {
  const statusMap = {
    inviata: 'pending',
    presa_in_carico: 'in_progress',
    stampato: 'in_progress',
    consegnato: 'completed',
    rifiutata: 'rejected',
    annullata: 'cancelled',
  };
  return statusMap[backendStatus] || backendStatus;
};

/**
 * Mappa i filtri del frontend agli stati del backend
 * @param {string} frontendFilter
 * @returns {string|null}
 */
export const mapFrontendFilterToBackend = frontendFilter => {
  const filterMap = {
    all: null,
    pending: 'inviata',
    in_progress: 'presa_in_carico',
    completed: 'consegnato',
  };
  return filterMap[frontendFilter];
};

/**
 * Utility per determinare se una richiesta può essere annullata
 * @param {string} status
 * @returns {boolean}
 */
export const canCancelRequest = status => {
  return status === 'inviata'; // Solo le richieste appena inviate possono essere annullate
};

/**
 * Utility per determinare se una richiesta ha un documento scaricabile
 * @param {string} status
 * @returns {boolean}
 */
export const hasDownloadableDocument = status => {
  return status === 'consegnato'; // Solo le richieste consegnate hanno documenti scaricabili
};
