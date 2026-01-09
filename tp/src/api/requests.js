import apiClient from '../services/axiosConfig';

/**
 * Ottiene le tipologie di richieste disponibili dal backend per un paziente specifico
 * @param {number} patientId - ID del paziente per cui recuperare le tipologie
 * @returns {Promise<Object>}
 */
export const getRequestTypes = async patientId => {
  try {
    console.log('\n=== RICHIESTA TIPI ===');
    console.log('📤 Recupero tipologie di richieste per paziente:', patientId);

    if (!patientId) {
      throw new Error(
        'ID paziente mancante - impossibile recuperare le tipologie di richieste',
      );
    }

    const response = await apiClient.get('/requests/types', {
      params: {
        patient_id: patientId,
      },
    });

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
 * Supporta sia valori numerici (dal backend) che stringhe
 * @param {string|number} status
 * @returns {string}
 */
export const getStatusColor = status => {
  const colors = {
    // Valori numerici (come arrivano dal backend)
    1: '#FFA726', // Inviata - Arancione
    2: '#42A5F5', // Presa in carico - Blu
    3: '#7986CB', // Stampato - Indaco
    4: '#66BB6A', // Consegnato - Verde
    // Valori stringa (per compatibilità)
    inviata: '#FFA726',
    presa_in_carico: '#42A5F5',
    stampato: '#7986CB',
    consegnato: '#66BB6A',
    rifiutata: '#EF5350',
    annullata: '#BDBDBD',
  };
  return colors[status] || '#9E9E9E';
};

/**
 * Utility per ottenere l'icona associata allo stato
 * Supporta sia valori numerici (dal backend) che stringhe
 * @param {string|number} status
 * @returns {string}
 */
export const getStatusIcon = status => {
  const icons = {
    // Valori numerici
    1: 'clock-outline', // Inviata
    2: 'progress-clock', // Presa in carico
    3: 'printer', // Stampato
    4: 'check-circle', // Consegnato
    // Valori stringa
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
 * Supporta sia valori numerici (dal backend) che stringhe
 * @param {string|number} status
 * @returns {string}
 */
export const getStatusLabel = status => {
  const labels = {
    // Valori numerici
    1: 'Inviata',
    2: 'In Lavorazione',
    3: 'Stampato',
    4: 'Consegnato',
    // Valori stringa
    inviata: 'Inviata',
    presa_in_carico: 'In Lavorazione',
    stampato: 'Stampato',
    consegnato: 'Consegnato',
    rifiutata: 'Rifiutata',
    annullata: 'Annullata',
  };
  return labels[status] || `Stato: ${status}`;
};

/**
 * Mappa gli stati del backend agli stati del frontend per compatibilità
 * Supporta sia valori numerici che stringhe
 * @param {string|number} backendStatus
 * @returns {string}
 */
export const mapBackendStatusToFrontend = backendStatus => {
  const statusMap = {
    // Valori numerici
    1: 'pending', // Inviata
    2: 'in_progress', // Presa in carico
    3: 'in_progress', // Stampato
    4: 'completed', // Consegnato
    // Valori stringa
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
 * Mappa i filtri del frontend agli stati del backend (valori numerici)
 * @param {string} frontendFilter
 * @returns {number|null}
 */
export const mapFrontendFilterToBackend = frontendFilter => {
  const filterMap = {
    all: null,
    pending: 1, // STATUS_INVIATA
    in_progress: 2, // STATUS_PRESA_IN_CARICO
    completed: 4, // STATUS_CONSEGNATO
  };
  return filterMap[frontendFilter];
};

/**
 * Utility per determinare se una richiesta può essere annullata
 * Supporta sia valori numerici che stringhe
 * @param {string|number} status
 * @returns {boolean}
 */
export const canCancelRequest = status => {
  // STATUS_INVIATA = 1 o 'inviata'
  return status === 1 || status === 'inviata';
};

/**
 * Utility per determinare se una richiesta ha un documento scaricabile
 * Supporta sia valori numerici che stringhe
 * @param {string|number} status
 * @returns {boolean}
 */
export const hasDownloadableDocument = status => {
  // STATUS_CONSEGNATO = 4 o 'consegnato'
  return status === 4 || status === 'consegnato';
};
