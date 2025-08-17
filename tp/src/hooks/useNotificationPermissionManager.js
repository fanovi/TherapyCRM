import {useState, useEffect, useCallback} from 'react';
import {AppState} from 'react-native';
import notificationPermissionService from '../services/notificationPermissionService';

const useNotificationPermissionManager = () => {
  const [shouldShowRequest, setShouldShowRequest] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [permissionStatus, setPermissionStatus] = useState(null);

  const checkPermissionStatus = useCallback(async () => {
    try {
      console.log('🔍 Controllo stato permessi notifiche...');
      setIsLoading(true);

      // Ottieni lo stato dettagliato dei permessi
      const detailedStatus =
        await notificationPermissionService.getDetailedPermissionStatus();

      if (detailedStatus) {
        console.log('📊 Stato dettagliato permessi:', detailedStatus);
        setPermissionStatus(detailedStatus);

        // Mostra il banner se:
        // 1. I permessi NON sono concessi
        // 2. I permessi NON sono unavailable (feature non supportata)
        // 3. Possono ancora essere richiesti (non sono blocked)
        const shouldShow =
          !detailedStatus.isGranted &&
          !detailedStatus.isUnavailable &&
          detailedStatus.canRequest;

        console.log('🎯 Banner dovrebbe essere visibile:', shouldShow);
        setShouldShowRequest(shouldShow);
      } else {
        // Fallback al check semplice
        const hasPermission =
          await notificationPermissionService.checkNotificationPermissions();
        console.log('🔔 Ha i permessi:', hasPermission);
        setShouldShowRequest(!hasPermission);
      }
    } catch (error) {
      console.error('❌ Errore controllo permessi:', error);
      setShouldShowRequest(true);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    // Controllo iniziale
    checkPermissionStatus();

    // Listener per quando l'app torna in foreground
    // Utile per ricontrollare se l'utente ha cambiato i permessi dalle impostazioni
    const subscription = AppState.addEventListener('change', nextAppState => {
      if (nextAppState === 'active') {
        console.log('📱 App tornata in foreground, ricontrollo permessi...');
        checkPermissionStatus();
      }
    });

    // Controllo periodico (opzionale, potrebbe non essere necessario con AppState)
    const interval = setInterval(() => {
      checkPermissionStatus();
    }, 10000); // Ogni 10 secondi invece di 5

    return () => {
      subscription.remove();
      clearInterval(interval);
    };
  }, [checkPermissionStatus]);

  const requestPermission = async () => {
    try {
      console.log('📲 Richiesta permessi in corso...');

      // Se i permessi sono bloccati, apri le impostazioni
      if (permissionStatus?.isBlocked) {
        console.log('🚫 Permessi bloccati, apertura impostazioni...');
        await notificationPermissionService.openNotificationSettings();
        return false;
      }

      const permission =
        await notificationPermissionService.requestNotificationPermissions();

      if (permission) {
        console.log('✅ Permessi concessi');
        setShouldShowRequest(false);
        // Ricontrolla lo stato per aggiornare permissionStatus
        await checkPermissionStatus();
      } else {
        console.log('❌ Permessi negati');
        // Ricontrolla lo stato - potrebbe essere blocked ora
        await checkPermissionStatus();
      }

      return permission;
    } catch (error) {
      console.error('❌ Errore richiesta permessi:', error);
      return false;
    }
  };

  const dismissRequest = () => {
    console.log('👋 Banner dismesso temporaneamente');
    // Non facciamo nulla - il banner rimane visibile al prossimo controllo
    // Se vuoi nasconderlo temporaneamente, potresti usare un flag locale
  };

  const resetPermissionRequest = () => {
    console.log('🔄 Reset richiesta permessi');
    setShouldShowRequest(true);
    checkPermissionStatus();
  };

  const forceShowBanner = () => {
    console.log('🔧 Forzando visualizzazione banner per testing');
    setShouldShowRequest(true);
  };

  return {
    shouldShowRequest,
    isLoading,
    permissionStatus,
    requestPermission,
    dismissRequest,
    resetPermissionRequest,
    checkPermissionStatus,
    forceShowBanner,
  };
};

export default useNotificationPermissionManager;
