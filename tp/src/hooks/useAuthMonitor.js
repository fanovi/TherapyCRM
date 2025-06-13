import {useEffect} from 'react';
import {useSelector} from 'react-redux';
import {checkAndHandleToken} from '../utils/authUtils';

/**
 * Hook per monitorare automaticamente lo stato dell'autenticazione
 * Controlla la validità del token solo quando l'app diventa attiva (da background)
 */
export const useAuthMonitor = () => {
  const {isAuthenticated, token} = useSelector(state => state.auth);

  useEffect(() => {
    // Controlla il token anche quando l'app diventa attiva (da background)
    const handleAppStateChange = nextAppState => {
      if (nextAppState === 'active' && isAuthenticated && token) {
        console.log('📱 App became active, checking token...');
        checkAndHandleToken();
      }
    };

    // Se disponibile, aggiungi listener per i cambi di stato dell'app
    if (typeof AppState !== 'undefined') {
      const {AppState} = require('react-native');
      const subscription = AppState.addEventListener(
        'change',
        handleAppStateChange,
      );

      return () => {
        if (subscription?.remove) {
          subscription.remove();
        }
      };
    }
  }, [isAuthenticated, token]);
};

export default useAuthMonitor;
