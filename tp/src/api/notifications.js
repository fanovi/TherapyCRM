import apiClient from '../services/axiosConfig';

/**
 * Segna una notifica come letta
 * @param {number} notificationId
 * @returns {Promise}
 */
export const markNotificationAsRead = async notificationId => {
  try {
    console.log(`🔔 Segnando notifica ${notificationId} come letta...`);

    const response = await apiClient.post(
      `/notifications/${notificationId}/mark-read`,
    );

    console.log('✅ Notifica segnata come letta:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore segnando notifica come letta:', error);
    throw error;
  }
};

/**
 * Conferma la lettura di una notifica con requires_read_confirmation
 * @param {number} notificationId
 * @returns {Promise}
 */
export const confirmNotificationRead = async notificationId => {
  try {
    console.log(`🔔 Confermando lettura notifica ${notificationId}...`);

    const response = await apiClient.post(
      `/notifications/${notificationId}/confirm-read`,
    );

    console.log('✅ Lettura notifica confermata:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore confermando lettura notifica:', error);
    throw error;
  }
};

/**
 * Segna una notifica come visualizzata (ma non confermata)
 * @param {number} notificationId
 * @returns {Promise}
 */
export const markNotificationAsViewed = async notificationId => {
  try {
    console.log(`👁️ Segnando notifica ${notificationId} come visualizzata...`);

    const response = await apiClient.post(
      `/notifications/${notificationId}/mark-viewed`,
    );

    console.log('✅ Notifica segnata come visualizzata:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore segnando notifica come visualizzata:', error);
    throw error;
  }
};

/**
 * Verifica se ci sono notifiche bloccanti per l'utente
 * @returns {Promise}
 */
export const hasBlockingNotifications = async () => {
  try {
    console.log('🔍 Verificando se ci sono notifiche bloccanti...');

    const response = await apiClient.get('/notifications/has-blocking');

    console.log('✅ Verifica notifiche bloccanti completata:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore verificando notifiche bloccanti:', error);
    throw error;
  }
};

/**
 * Ottiene le notifiche bloccanti (requires_read_confirmation = true e non lette)
 * @returns {Promise}
 */
export const getBlockingNotifications = async () => {
  try {
    console.log('🚨 Recuperando notifiche bloccanti...');

    const response = await apiClient.get('/notifications/blocking');

    console.log('✅ Notifiche bloccanti recuperate:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore recuperando notifiche bloccanti:', error);
    throw error;
  }
};

/**
 * Crea una notifica di test (solo in sviluppo)
 * @param {string} type - 'blocking' o 'normal'
 * @param {string} title - Titolo personalizzato (opzionale)
 * @param {string} message - Messaggio personalizzato (opzionale)
 * @returns {Promise}
 */
export const createTestNotification = async (
  type = 'blocking',
  title = null,
  message = null,
) => {
  try {
    console.log(`🧪 Creando notifica di test tipo: ${type}...`);

    const payload = {type};
    if (title) payload.title = title;
    if (message) payload.message = message;

    const response = await apiClient.post(
      '/notifications/create-test',
      payload,
    );

    console.log('✅ Notifica di test creata:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore creando notifica di test:', error);
    throw error;
  }
};

/**
 * Segna multiple notifiche come lette
 * @param {Array} notificationIds
 * @returns {Promise}
 */
export const markMultipleNotificationsAsRead = async notificationIds => {
  try {
    console.log(
      `🔔 Segnando ${notificationIds.length} notifiche come lette...`,
    );

    // Chiamate parallele per efficienza
    const promises = notificationIds.map(id => markNotificationAsRead(id));
    const results = await Promise.allSettled(promises);

    const successful = results.filter(
      result => result.status === 'fulfilled',
    ).length;
    const failed = results.filter(
      result => result.status === 'rejected',
    ).length;

    console.log(`✅ Risultato: ${successful} successi, ${failed} fallimenti`);

    return {
      successful,
      failed,
      results,
    };
  } catch (error) {
    console.error('❌ Errore segnando multiple notifiche come lette:', error);
    throw error;
  }
};

/**
 * Ottiene le notifiche non lette dell'utente
 * @param {number} limit
 * @returns {Promise}
 */
export const getUnreadNotifications = async (limit = 50) => {
  try {
    console.log(`🔔 Recuperando notifiche non lette (limit: ${limit})...`);

    const response = await apiClient.get('/notifications/unread', {
      params: {limit},
    });

    console.log('✅ Notifiche non lette recuperate:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore recuperando notifiche non lette:', error);
    throw error;
  }
};

/**
 * Ottiene tutte le notifiche dell'utente con paginazione
 * @param {number} page
 * @param {number} limit
 * @returns {Promise}
 */
export const getNotifications = async (page = 1, limit = 20) => {
  try {
    console.log(`🔔 Recuperando notifiche (page: ${page}, limit: ${limit})...`);

    const response = await apiClient.get('/notifications', {
      params: {page, limit},
    });

    console.log('✅ Notifiche recuperate:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore recuperando notifiche:', error);
    throw error;
  }
};

/**
 * Invia una notifica (solo per admin/coordinatori)
 * @param {Object} notificationData
 * @returns {Promise}
 */
export const sendNotification = async notificationData => {
  try {
    console.log('🔔 Inviando notifica...', notificationData);

    const response = await apiClient.post(
      '/notifications/send',
      notificationData,
    );

    console.log('✅ Notifica inviata:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore inviando notifica:', error);
    throw error;
  }
};

/**
 * Invia una notifica usando un template
 * @param {Object} templateData
 * @returns {Promise}
 */
export const sendNotificationFromTemplate = async templateData => {
  try {
    console.log('🔔 Inviando notifica da template...', templateData);

    const response = await apiClient.post(
      '/notifications/send-template',
      templateData,
    );

    console.log('✅ Notifica da template inviata:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Errore inviando notifica da template:', error);
    throw error;
  }
};
