import {configureStore} from '@reduxjs/toolkit';
import {persistStore, persistReducer} from 'redux-persist';
import AsyncStorage from '@react-native-async-storage/async-storage';
import authReducer from '../slices/authSlice';
import uiReducer from '../slices/uiSlice';
import patientReducer from '../slices/patientSlice';

const persistConfig = {
  key: 'root',
  storage: AsyncStorage,
  whitelist: ['user', 'token', 'isAuthenticated'], // Persisti anche il token e lo stato di autenticazione
};

const patientPersistConfig = {
  key: 'patient',
  storage: AsyncStorage,
  whitelist: ['patients', 'currentPatient'], // Persisti i pazienti e il paziente selezionato
};

const persistedAuthReducer = persistReducer(persistConfig, authReducer);
const persistedPatientReducer = persistReducer(
  patientPersistConfig,
  patientReducer,
);

export const store = configureStore({
  reducer: {
    auth: persistedAuthReducer,
    ui: uiReducer,
    patient: persistedPatientReducer,
  },
  middleware: getDefaultMiddleware =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['persist/PERSIST', 'persist/REHYDRATE'],
      },
    }),
});

export const persistor = persistStore(store);
