/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 *
 * @format
 */

import React from 'react';
import {Provider} from 'react-redux';
import {PersistGate} from 'redux-persist/integration/react';
import {PaperProvider} from 'react-native-paper';
import {ActivityIndicator, View} from 'react-native';
import {store, persistor} from './src/store';
import AppNavigator from './src/navigation/AppNavigator';
import lightTheme from './src/theme';
import {setStore} from './src/services/authService';
import {setStore as setAxiosStore} from './src/services/axiosConfig';
import {setStore as setAuthUtilsStore} from './src/utils/authUtils';
import ErrorModal from './src/components/ErrorModal';
import moment from 'moment';
import 'moment/locale/it';
import 'react-native-gesture-handler';

// Configura moment per il locale italiano
moment.locale('it');

// Inizializza il store per authService, axiosConfig e authUtils
setStore(store);
setAxiosStore(store);
setAuthUtilsStore(store);

// Debug token al caricamento (solo in sviluppo) - DISABILITATO PER DEBUG
/*
if (__DEV__) {
  import('./src/utils/tokenDebug').then(
    ({debugTokenStatus, clearAllTokens}) => {
      setTimeout(async () => {
        const status = await debugTokenStatus();

        // Se i token sono invalidi, puliscili
        if (status && (!status.tokenValid || !status.hasAuthToken)) {
          console.log('🧹 Rilevati token invalidi, pulizia automatica...');
          await clearAllTokens();

          // Forza un reload dell'app per ripartire puliti
          try {
            const {DevSettings} = require('react-native');
            if (DevSettings && DevSettings.reload) {
              DevSettings.reload();
            }
          } catch (e) {
            console.log('DevSettings non disponibile');
          }
        }
      }, 1000);
    },
  );
}
*/

const App = () => {
  return (
    <Provider store={store}>
      <PersistGate
        loading={
          <View
            style={{flex: 1, justifyContent: 'center', alignItems: 'center'}}>
            <ActivityIndicator size="large" color="#6200EE" />
          </View>
        }
        persistor={persistor}>
        <PaperProvider theme={lightTheme}>
          <AppNavigator />
          <ErrorModal />
        </PaperProvider>
      </PersistGate>
    </Provider>
  );
};

export default App;
