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
