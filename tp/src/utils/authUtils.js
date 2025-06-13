import AsyncStorage from '@react-native-async-storage/async-storage';

let store = null;

export const setStore = appStore => {
  store = appStore;
};

/**
 * Esegue un logout automatico pulendo tutti i dati
 * @param {string} reason - Motivo del logout
 */
export const performAutoLogout = async reason => {
  try {
    console.log('🚨 Logout automatico:', reason);

    // Pulisci AsyncStorage
    await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);

    // Dispatch logout se store è disponibile
    if (store) {
      const {logoutUser} = await import('../slices/authSlice');
      store.dispatch(logoutUser());
    }

    console.log('✅ Logout completato');
    return true;
  } catch (error) {
    console.error('❌ Errore durante logout automatico:', error);
    return false;
  }
};

/**
 * Verifica se un token è valido e non scaduto
 * @param {string} token - Il token da verificare
 * @returns {Promise<boolean>} - True se valido, false altrimenti
 */
export const isValidToken = async token => {
  if (!token) return false;

  try {
    const {isTokenExpired} = await import('./jwt');
    return !isTokenExpired(token);
  } catch (error) {
    console.error('Errore nella verifica del token:', error);
    return false;
  }
};

/**
 * Controlla la validità del token corrente e fa logout se necessario
 * @returns {Promise<boolean>} - True se il token è valido, false se è stato fatto logout
 */
export const checkAndHandleToken = async () => {
  try {
    console.log('🔍 === CHECKING TOKEN ON APP START ===');
    const token = await AsyncStorage.getItem('authToken');
    console.log(
      '📦 Token in storage:',
      token ? `${token.substring(0, 20)}...` : 'NULL',
    );

    if (!token) {
      console.log('❌ No token found in storage');
      await performAutoLogout('Token mancante');
      return false;
    }

    // Verifica il token con il backend
    console.log('🔄 Validating token with backend...');
    const response = await checkTokenValidity(token);

    if (!response.valid) {
      console.log('❌ Token invalidated by backend');
      await performAutoLogout('Token non valido');
      return false;
    }

    console.log('✅ Token validated by backend');

    // Se il token è valido, inizializza l'app e imposta lo stato di autenticazione
    if (store) {
      console.log('✅ Token valid, initializing app...');
      const {setInitialized, loginSuccess} = await import(
        '../slices/authSlice'
      );

      // Recupera i dati utente da AsyncStorage
      const userString = await AsyncStorage.getItem('user');
      const user = userString ? JSON.parse(userString) : null;

      if (user) {
        console.log('✅ User data found, restoring session...');
        // Ripristina la sessione con i dati salvati
        store.dispatch(
          loginSuccess({
            token,
            user,
            requiresPasswordChange: false,
          }),
        );
      }

      store.dispatch(setInitialized());
    }

    return true;
  } catch (error) {
    console.error('❌ Error checking token:', error);
    await performAutoLogout('Errore nel controllo del token');
    return false;
  }
};
