import {useEffect} from 'react';
import {useSelector} from 'react-redux';
import oneSignalService from '../services/oneSignalService';

/**
 * Hook per gestire l'integrazione OneSignal con Redux
 */
const useOneSignal = () => {
  const {isAuthenticated, user} = useSelector(state => state.auth);

  useEffect(() => {
    // Quando l'utente fa login, imposta il tag user_id
    if (isAuthenticated && user && user.id) {
      console.log(
        '🔐 Utente autenticato, impostazione tag OneSignal:',
        user.id,
      );
      oneSignalService.setUserTag(user.id);

      // Opzionalmente, invia altri tag utili
      const additionalTags = {};

      if (user.email) {
        additionalTags.email = user.email;
      }

      if (user.role || user.user_type) {
        additionalTags.user_role = user.role || user.user_type;
      }

      if (user.username) {
        additionalTags.username = user.username;
      }

      // Invia i tag aggiuntivi se presenti
      if (Object.keys(additionalTags).length > 0) {
        oneSignalService.sendTags(additionalTags);
      }
    }

    // Quando l'utente fa logout, imposta tag user_id a -1
    else if (!isAuthenticated && !user) {
      console.log('🚪 Utente disconnesso, impostazione tag OneSignal a -1');
      oneSignalService.clearUserTag();
    }
  }, [isAuthenticated, user]);

  return {
    isInitialized: oneSignalService.isInitialized,
    currentUserId: oneSignalService.currentUserId,
    sendTag: oneSignalService.sendTag.bind(oneSignalService),
    sendTags: oneSignalService.sendTags.bind(oneSignalService),
    requestPermissions:
      oneSignalService.requestPermissions.bind(oneSignalService),
    getDeviceId: oneSignalService.getDeviceId.bind(oneSignalService),
  };
};

export default useOneSignal;
