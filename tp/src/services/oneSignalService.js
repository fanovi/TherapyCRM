import {OneSignal} from 'react-native-onesignal';

class OneSignalService {
  constructor() {
    this.appId = '8ab64a7b-8b43-41b4-8444-18922a41a7fc';
    this.isInitialized = false;
    this.currentUserId = null;
  }

  /**
   * Inizializza OneSignal
   */
  async initialize() {
    if (this.isInitialized) {
      return;
    }

    try {
      // Rimuovi la richiesta di consenso su iOS se non necessaria
      OneSignal.Notifications.requestPermission(true);

      // Inizializza OneSignal con l'App ID
      OneSignal.initialize(this.appId);

      // Setup notification handlers
      this.setupNotificationHandlers();

      // Debug logging (solo in sviluppo)
      if (__DEV__) {
        OneSignal.Debug.setLogLevel(6); // VERBOSE
        console.log('📱 OneSignal inizializzato con App ID:', this.appId);
      }

      this.isInitialized = true;
    } catch (error) {
      console.error('Errore inizializzazione OneSignal:', error);
    }
  }

  /**
   * Configura i gestori delle notifiche
   */
  setupNotificationHandlers() {
    // Gestisce quando l'app riceve una notifica in background
    OneSignal.Notifications.addEventListener('foregroundWillDisplay', event => {
      if (__DEV__) {
        console.log(
          '📢 Notifica ricevuta in foreground:',
          event.getNotification(),
        );
      }

      // Mostra la notifica anche quando l'app è in foreground
      event.getNotification().display();
    });

    // Gestisce quando l'utente tocca una notifica
    OneSignal.Notifications.addEventListener('click', event => {
      if (__DEV__) {
        console.log('👆 Notifica cliccata:', event.notification);
      }

      // Qui puoi implementare la navigazione basata sui dati della notifica
      this.handleNotificationClick(event.notification);
    });
  }

  /**
   * Gestisce il click su una notifica
   * @param {Object} notification
   */
  handleNotificationClick(notification) {
    const additionalData = notification.additionalData;

    if (additionalData) {
      // Esempi di navigazione basata sui dati della notifica
      if (additionalData.screen) {
        // Naviga alla schermata specificata
        console.log('Navigazione verso:', additionalData.screen);
        // Implementa la navigazione qui usando React Navigation
      }

      if (additionalData.notification_ids) {
        // Segna le notifiche come lette nell'API
        this.markNotificationsAsRead(additionalData.notification_ids);
      }
    }
  }

  /**
   * Imposta il tag user_id per identificare l'utente
   * @param {number|string} userId
   */
  async setUserTag(userId) {
    if (!this.isInitialized) {
      await this.initialize();
    }

    try {
      this.currentUserId = userId;

      // Imposta il tag user_id su OneSignal
      OneSignal.User.addTag('user_id', userId.toString());

      if (__DEV__) {
        console.log('🏷️ Tag user_id impostato:', userId);
      }
    } catch (error) {
      console.error('Errore impostazione tag user_id:', error);
    }
  }

  /**
   * Imposta il tag user_id a -1 (logout)
   */
  async clearUserTag() {
    try {
      // Invece di rimuovere il tag, lo impostiamo a -1 per indicare logout
      OneSignal.User.addTag('user_id', '-1');
      this.currentUserId = null;

      if (__DEV__) {
        console.log('🏷️ Tag user_id impostato a -1 (logout)');
      }
    } catch (error) {
      console.error('Errore impostazione tag user_id a -1:', error);
    }
  }

  /**
   * Ottiene l'ID del dispositivo OneSignal
   * @returns {Promise<string>}
   */
  async getDeviceId() {
    try {
      const deviceState = await OneSignal.User.getOnesignalId();
      return deviceState;
    } catch (error) {
      console.error('Errore recupero device ID:', error);
      return null;
    }
  }

  /**
   * Segna le notifiche come lette tramite API
   * @param {Array} notificationIds
   */
  async markNotificationsAsRead(notificationIds) {
    try {
      const {markMultipleNotificationsAsRead} = await import(
        '../api/notifications'
      );
      await markMultipleNotificationsAsRead(notificationIds);
      console.log('✅ Notifiche segnate come lette:', notificationIds);
    } catch (error) {
      console.error('❌ Errore segnando notifiche come lette:', error);
    }
  }

  /**
   * Richiede il permesso per le notifiche (solo se necessario)
   */
  async requestPermissions() {
    try {
      const permission = await OneSignal.Notifications.requestPermission(true);
      if (__DEV__) {
        console.log('🔔 Permessi notifiche:', permission);
      }
      return permission;
    } catch (error) {
      console.error('Errore richiesta permessi:', error);
      return false;
    }
  }

  /**
   * Invia un tag personalizzato
   * @param {string} key
   * @param {string} value
   */
  async sendTag(key, value) {
    try {
      OneSignal.User.addTag(key, value);
      if (__DEV__) {
        console.log(`🏷️ Tag inviato: ${key} = ${value}`);
      }
    } catch (error) {
      console.error('Errore invio tag:', error);
    }
  }

  /**
   * Invia più tag contemporaneamente
   * @param {Object} tags
   */
  async sendTags(tags) {
    try {
      OneSignal.User.addTags(tags);
      if (__DEV__) {
        console.log('🏷️ Tags inviati:', tags);
      }
    } catch (error) {
      console.error('Errore invio tags:', error);
    }
  }
}

// Esporta un'istanza singleton
export default new OneSignalService();
