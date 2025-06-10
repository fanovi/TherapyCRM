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
import moment from 'moment';
import 'moment/locale/it';
import 'react-native-gesture-handler';

// Configura moment per il locale italiano
moment.locale('it');

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
        </PaperProvider>
      </PersistGate>
    </Provider>
  );
};

export default App;
