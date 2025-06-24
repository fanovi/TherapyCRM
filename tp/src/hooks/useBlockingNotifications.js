import {useEffect, useRef} from 'react';
import {useSelector, useDispatch} from 'react-redux';
import {AppState} from 'react-native';
import blockingNotificationService from '../services/blockingNotificationService';
import {clearBlockingNotifications} from '../slices/uiSlice';

/**
 * Hook personalizzato per gestire le notifiche bloccanti
 * @param {Object} options Opzioni per il comportamento dell'hook
 * @param {boolean} options.autoCheck - Se controllare automaticamente al mount (default: true)
 * @param {number} options.pollingInterval - Intervallo di polling in ms (default: 30000)
 * @param {boolean} options.enablePolling - Se abilitare il polling (default: true)
 * @param {boolean} options.checkOnForeground - Se controllare quando l'app torna in foreground (default: true)
 * @returns {Object} Stato e funzioni per le notifiche bloccanti
 */
const useBlockingNotifications = (options = {}) => {
  const {
    autoCheck = true,
    pollingInterval = 30000,
    enablePolling = true,
    checkOnForeground = true,
  } = options;

  const dispatch = useDispatch();
  const {isAuthenticated} = useSelector(state => state.auth);
  const {blockingNotifications, isAppBlocked} = useSelector(state => state.ui);

  const stopPollingRef = useRef(null);
  const appStateRef = useRef(AppState.currentState);

  /**
   * Controlla le notifiche bloccanti manualmente
   */
  const checkNotifications = async () => {
    if (!isAuthenticated) {
      console.log('❌ Utente non autenticato, skip controllo notifiche');
      return false;
    }

    try {
      return await blockingNotificationService.checkAndLoadBlockingNotifications();
    } catch (error) {
      console.error('❌ Errore controllo notifiche bloccanti:', error);
      return false;
    }
  };

  /**
   * Pulisce tutte le notifiche bloccanti
   */
  const clearNotifications = () => {
    blockingNotificationService.clearAll();
  };

  /**
   * Avvia il polling delle notifiche
   */
  const startPolling = () => {
    if (stopPollingRef.current) {
      stopPollingRef.current(); // Ferma il polling precedente se attivo
    }

    if (enablePolling && isAuthenticated) {
      console.log('🔄 Avvio polling notifiche bloccanti...');
      stopPollingRef.current =
        blockingNotificationService.startPolling(pollingInterval);
    }
  };

  /**
   * Ferma il polling delle notifiche
   */
  const stopPolling = () => {
    if (stopPollingRef.current) {
      console.log('⏹️ Fermando polling notifiche bloccanti...');
      stopPollingRef.current();
      stopPollingRef.current = null;
    }
  };

  /**
   * Gestisce i cambiamenti dello stato dell'app
   */
  const handleAppStateChange = nextAppState => {
    console.log(
      `📱 App state changed: ${appStateRef.current} -> ${nextAppState}`,
    );

    if (
      checkOnForeground &&
      appStateRef.current.match(/inactive|background/) &&
      nextAppState === 'active' &&
      isAuthenticated
    ) {
      console.log('🔄 App tornata in foreground, controllo notifiche...');
      checkNotifications();
    }

    appStateRef.current = nextAppState;
  };

  // Effect per il controllo iniziale
  useEffect(() => {
    if (autoCheck && isAuthenticated) {
      console.log('🚀 Controllo iniziale notifiche bloccanti...');
      checkNotifications();
    }
  }, [isAuthenticated, autoCheck]);

  // Effect per il polling
  useEffect(() => {
    if (isAuthenticated) {
      startPolling();
    } else {
      stopPolling();
      clearNotifications();
    }

    return () => {
      stopPolling();
    };
  }, [isAuthenticated, enablePolling, pollingInterval]);

  // Effect per monitorare i cambiamenti di stato dell'app
  useEffect(() => {
    if (checkOnForeground) {
      const subscription = AppState.addEventListener(
        'change',
        handleAppStateChange,
      );

      return () => {
        subscription?.remove();
      };
    }
  }, [checkOnForeground, isAuthenticated]);

  // Effect di cleanup quando l'utente si disconnette
  useEffect(() => {
    if (!isAuthenticated) {
      clearNotifications();
    }
  }, [isAuthenticated]);

  // Effect di cleanup al unmount
  useEffect(() => {
    return () => {
      stopPolling();
    };
  }, []);

  return {
    // Stato
    isAppBlocked,
    blockingNotifications,
    notificationCount: blockingNotifications.length,
    isAuthenticated,

    // Funzioni
    checkNotifications,
    clearNotifications,
    startPolling,
    stopPolling,

    // Informazioni
    hasBlockingNotifications: blockingNotifications.length > 0,
    pollingActive: !!stopPollingRef.current,
  };
};

export default useBlockingNotifications;
