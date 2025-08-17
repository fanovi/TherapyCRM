// notificationPermissionService.js usando react-native-permissions
import {Platform} from 'react-native';
import {
  check,
  request,
  PERMISSIONS,
  RESULTS,
  checkNotifications,
  requestNotifications,
} from 'react-native-permissions';

class NotificationPermissionService {
  // Controlla se i permessi per le notifiche sono stati concessi
  async checkNotificationPermissions() {
    try {
      console.log('🔍 Controllo permessi notifiche...');

      // Usa checkNotifications per controllare lo stato delle notifiche
      const {status, settings} = await checkNotifications();

      console.log('📱 Stato notifiche:', status);
      console.log('⚙️ Impostazioni notifiche:', settings);

      // Gli stati possibili sono:
      // 'unavailable' - questa feature non è disponibile (su questo dispositivo / in questo contesto)
      // 'denied' - il permesso è stato negato e non può essere richiesto
      // 'limited' - il permesso è limitato: alcune azioni sono possibili
      // 'granted' - il permesso è concesso
      // 'blocked' - il permesso è negato e non può essere richiesto (l'utente deve andare nelle impostazioni)

      return status === RESULTS.GRANTED || status === RESULTS.LIMITED;
    } catch (error) {
      console.error('❌ Errore controllo permessi notifiche:', error);
      return false;
    }
  }

  // Richiede i permessi per le notifiche
  async requestNotificationPermissions() {
    try {
      console.log('📲 Richiesta permessi notifiche...');

      // Prima controlla lo stato attuale
      const checkResult = await checkNotifications();
      console.log(
        '📊 Stato attuale prima della richiesta:',
        checkResult.status,
      );

      // Se è già granted, ritorna true
      if (checkResult.status === RESULTS.GRANTED) {
        console.log('✅ Permessi già concessi');
        return true;
      }

      // Se è blocked, l'utente deve andare nelle impostazioni
      if (checkResult.status === RESULTS.BLOCKED) {
        console.log(
          "🚫 Permessi bloccati - l'utente deve andare nelle impostazioni",
        );
        // Potresti voler aprire le impostazioni qui
        // import { openSettings } from 'react-native-permissions';
        // openSettings();
        return false;
      }

      // Richiedi i permessi
      // Puoi specificare quali tipi di notifiche vuoi richiedere
      const {status, settings} = await requestNotifications([
        'alert',
        'badge',
        'sound',
      ]);

      console.log('📱 Nuovo stato dopo richiesta:', status);
      console.log('⚙️ Nuove impostazioni:', settings);

      const granted = status === RESULTS.GRANTED || status === RESULTS.LIMITED;

      if (granted) {
        console.log('✅ Permessi notifiche concessi');
      } else {
        console.log('❌ Permessi notifiche negati');
      }

      return granted;
    } catch (error) {
      console.error('❌ Errore richiesta permessi:', error);
      return false;
    }
  }

  // Metodo helper per aprire le impostazioni del dispositivo
  async openNotificationSettings() {
    try {
      const {openSettings} = require('react-native-permissions');
      await openSettings();
      return true;
    } catch (error) {
      console.error('❌ Errore apertura impostazioni:', error);
      return false;
    }
  }

  // Metodo per controllare se i permessi sono bloccati
  async areNotificationsBlocked() {
    try {
      const {status} = await checkNotifications();
      return status === RESULTS.BLOCKED;
    } catch (error) {
      console.error('❌ Errore controllo blocco permessi:', error);
      return false;
    }
  }

  // Metodo per ottenere informazioni dettagliate sui permessi
  async getDetailedPermissionStatus() {
    try {
      const result = await checkNotifications();

      return {
        status: result.status,
        settings: result.settings,
        canRequest:
          result.status !== RESULTS.BLOCKED &&
          result.status !== RESULTS.GRANTED,
        isGranted: result.status === RESULTS.GRANTED,
        isBlocked: result.status === RESULTS.BLOCKED,
        isDenied: result.status === RESULTS.DENIED,
        isLimited: result.status === RESULTS.LIMITED,
        isUnavailable: result.status === RESULTS.UNAVAILABLE,
      };
    } catch (error) {
      console.error('❌ Errore ottenimento stato dettagliato:', error);
      return null;
    }
  }
}

export default new NotificationPermissionService();
