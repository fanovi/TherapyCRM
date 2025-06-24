import {
  getBlockingNotifications,
  confirmNotificationRead,
  markNotificationAsViewed,
} from '../api/notifications';
import {
  setBlockingNotifications,
  removeBlockingNotification,
  clearBlockingNotifications,
} from '../slices/uiSlice';

class BlockingNotificationService {
  constructor() {
    this.store = null;
  }

  /**
   * Imposta il riferimento allo store Redux
   * @param {Object} store
   */
  setStore(store) {
    this.store = store;
  }

  /**
   * Controlla e carica le notifiche bloccanti
   * @returns {Promise<boolean>} true se ci sono notifiche bloccanti
   */
  async checkAndLoadBlockingNotifications() {
    try {
      console.log('🚨 Controllo notifiche bloccanti...');

      if (!this.store) {
        console.error('❌ Store non inizializzato');
        return false;
      }

      const response = await getBlockingNotifications();
      const notifications = response.data || response.notifications || response;

      console.log(
        `📥 Trovate ${notifications.length} notifiche bloccanti:`,
        notifications.map(n => ({
          id: n.id,
          title: n.title,
          requires_read_confirmation: n.requires_read_confirmation,
        })),
      );

      // Aggiorna lo store con le notifiche bloccanti
      this.store.dispatch(setBlockingNotifications(notifications));

      return notifications.length > 0;
    } catch (error) {
      console.error('❌ Errore controllo notifiche bloccanti:', error);

      // In caso di errore, mantieni lo stato attuale ma logga l'errore
      return false;
    }
  }

  /**
   * Segna una notifica come visualizzata
   * @param {number} notificationId
   * @returns {Promise<boolean>}
   */
  async markAsViewed(notificationId) {
    try {
      console.log(
        `👁️ Segnando notifica ${notificationId} come visualizzata...`,
      );

      await markNotificationAsViewed(notificationId);

      console.log('✅ Notifica segnata come visualizzata');
      return true;
    } catch (error) {
      console.error('❌ Errore segnando notifica come visualizzata:', error);
      return false;
    }
  }

  /**
   * Conferma la lettura di una notifica bloccante
   * @param {number} notificationId
   * @returns {Promise<boolean>}
   */
  async confirmRead(notificationId) {
    try {
      console.log(`✅ Confermando lettura notifica ${notificationId}...`);

      if (!this.store) {
        console.error('❌ Store non inizializzato');
        return false;
      }

      // Chiama l'API per confermare la lettura
      await confirmNotificationRead(notificationId);

      // Rimuovi la notifica dalle notifiche bloccanti
      this.store.dispatch(removeBlockingNotification(notificationId));

      console.log('✅ Lettura notifica confermata e rimossa dalle bloccanti');

      // Controlla se ci sono ancora notifiche bloccanti
      const {blockingNotifications} = this.store.getState().ui;
      const remainingCount = blockingNotifications.length;

      console.log(
        `📊 Notifiche bloccanti rimanenti: ${remainingCount}`,
        remainingCount === 0 ? '🎉 App sbloccata!' : '🔒 App ancora bloccata',
      );

      return true;
    } catch (error) {
      console.error('❌ Errore confermando lettura notifica:', error);
      return false;
    }
  }

  /**
   * Pulisce tutte le notifiche bloccanti (per logout)
   */
  clearAll() {
    if (this.store) {
      console.log('🧹 Pulizia notifiche bloccanti per logout');
      this.store.dispatch(clearBlockingNotifications());
    }
  }

  /**
   * Ottiene lo stato corrente delle notifiche bloccanti
   * @returns {Object}
   */
  getCurrentState() {
    if (!this.store) {
      return {
        isAppBlocked: false,
        blockingNotifications: [],
        count: 0,
      };
    }

    const {isAppBlocked, blockingNotifications} = this.store.getState().ui;

    return {
      isAppBlocked,
      blockingNotifications,
      count: blockingNotifications.length,
    };
  }

  /**
   * Controlla periodicamente le notifiche bloccanti
   * @param {number} intervalMs Intervallo in millisecondi (default: 30 secondi)
   * @returns {Function} Funzione per fermare il polling
   */
  startPolling(intervalMs = 30000) {
    console.log(`🔄 Avvio polling notifiche bloccanti ogni ${intervalMs}ms`);

    const interval = setInterval(() => {
      this.checkAndLoadBlockingNotifications();
    }, intervalMs);

    // Ritorna una funzione per fermare il polling
    return () => {
      console.log('⏹️ Fermato polling notifiche bloccanti');
      clearInterval(interval);
    };
  }
}

// Crea un'istanza singleton del servizio
const blockingNotificationService = new BlockingNotificationService();

export default blockingNotificationService;
