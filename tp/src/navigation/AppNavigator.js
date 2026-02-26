import React, {useEffect, useState} from 'react';
import {NavigationContainer} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {useSelector, useDispatch} from 'react-redux';
import {ActivityIndicator, View} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {checkTokenValidity} from '../api/auth';
import {loginSuccess, logoutUser} from '../slices/authSlice';
import {setPatientsFromLogin} from '../slices/patientSlice';
import SafeAreaWrapper from '../components/SafeAreaWrapper';
import AuthNavigator from './AuthNavigator';
import PatientNavigator from './PatientNavigator';
import TherapistNavigator from './TherapistNavigator';
import useOneSignal from '../hooks/useOneSignal';
import useBlockingNotifications from '../hooks/useBlockingNotifications';
import BlockingNotificationOverlay from '../components/BlockingNotificationOverlay';
import NotificationModal from '../components/NotificationModal';

const Stack = createNativeStackNavigator();

const AppNavigator = () => {
  const dispatch = useDispatch();
  const {isAuthenticated, user, requiresPasswordChange, requires2fa} =
    useSelector(state => state.auth);
  const [isLoading, setIsLoading] = useState(true);

  // Inizializza OneSignal e gestisce i tag dell'utente
  useOneSignal();

  // Gestisce le notifiche bloccanti
  useBlockingNotifications({
    autoCheck: true,
    enablePolling: true,
    pollingInterval: 30000, // 30 secondi
    checkOnForeground: true,
  });

  useEffect(() => {
    checkInitialAuth();
  }, []);

  const checkInitialAuth = async () => {
    try {
      console.log('🚀 === CONTROLLO AUTENTICAZIONE INIZIALE ===');

      // 1. Controlla se esiste il token in AsyncStorage
      const token = await AsyncStorage.getItem('authToken');
      console.log('token', token);
      console.log('📦 Token check result:');
      console.log('   - Token exists:', !!token);
      console.log('   - Token type:', typeof token);
      console.log('   - Token length:', token ? token.length : 0);

      if (!token) {
        console.log('❌ Nessun token trovato -> Login');
        setIsLoading(false);
        return;
      }

      console.log('✅ Token trovato in storage');

      // 2. Verifica il token con il backend
      console.log('🔄 Verifico token con il backend...');
      const result = await checkTokenValidity(token);

      console.log('📋 Token validation result:');
      console.log('   - Valid:', result.valid);
      console.log('   - Error:', result.error);
      console.log('   - User data:', !!result.user);

      if (!result.valid) {
        console.log('❌ Token non valido -> Login');
        console.log('   - Reason:', result.error);
        // Pulisci storage e vai al login (usa action sincrono)
        await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
        const {logoutUser: logoutUserAction} = await import('../slices/authSlice');
        dispatch(logoutUserAction());
        setIsLoading(false);
        return;
      }

      console.log('✅ Token valido -> Dashboard');
      console.log('🔍 === FULL BACKEND RESPONSE ===');
      console.log('📦 result.user:', JSON.stringify(result.user, null, 2));
      console.log('📦 result.user.patients:', JSON.stringify(result.user?.patients, null, 2));

      // 3. Token valido - recupera dati utente e vai alla dashboard
      const userString = await AsyncStorage.getItem('user');
      let user = null;

      console.log('👤 User data recovery:');
      console.log('   - User string exists:', !!userString);
      console.log('   - User from API exists:', !!result.user);

      if (userString) {
        user = JSON.parse(userString);
        console.log('✅ User data from storage:', {
          email: user.email,
          role: user.user_type || user.role,
          id: user.id,
        });
      } else if (result.user) {
        // Se l'endpoint verify ha restituito i dati utente, usali
        user = result.user;
        await AsyncStorage.setItem('user', JSON.stringify(user));
        console.log('✅ User data from API response:', {
          email: user.email,
          role: user.user_type || user.role,
          id: user.id,
        });
      }

      if (user) {
        // Usa sempre i dati dei pazienti dal backend se disponibili (più aggiornati)
        const patientsData = result.user?.patients || user.patients || [];

        // Aggiorna user con i pazienti dal backend
        const updatedUser = {
          ...user,
          patients: patientsData,
        };

        console.log('👤 Final user data for session restore:', {
          email: updatedUser.email,
          role: updatedUser.user_type || updatedUser.role,
          patients: patientsData.length,
        });

        // Ripristina la sessione utente
        dispatch(
          loginSuccess({
            token,
            user: updatedUser,
            requiresPasswordChange: false,
          }),
        );

        // Ripristina anche i pazienti se presenti
        if (patientsData.length > 0) {
          console.log('👥 Restoring patients:', patientsData.length);
          console.log('👥 First patient data:', JSON.stringify(patientsData[0], null, 2));
          dispatch(setPatientsFromLogin(patientsData));
        }

        // Aggiorna anche l'AsyncStorage con i dati utente aggiornati
        await AsyncStorage.setItem('user', JSON.stringify(updatedUser));

        console.log('✅ Session restored successfully');
      } else {
        console.log('❌ Dati utente mancanti -> Login');
        await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
        dispatch(logoutUser());
      }
    } catch (error) {
      console.error('❌ Errore controllo autenticazione:', error);
      console.error('   - Error message:', error.message);
      console.error('   - Error stack:', error.stack);
      // In caso di errore, pulisci tutto e vai al login
      await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
      dispatch(logoutUser());
    } finally {
      console.log('🏁 Auth check completed, setting loading to false');
      setIsLoading(false);
    }
  };

  // Mostra loading durante il controllo iniziale
  if (isLoading) {
    return (
      <View style={{flex: 1, justifyContent: 'center', alignItems: 'center'}}>
        <ActivityIndicator size="large" color="#6200EE" />
      </View>
    );
  }

  return (
    <SafeAreaWrapper>
      <NavigationContainer>
        <Stack.Navigator screenOptions={{headerShown: false}}>
          {!isAuthenticated || requiresPasswordChange || requires2fa ? (
            <Stack.Screen name="Auth" component={AuthNavigator} />
          ) : user?.user_type === 'paziente' || user?.role === 'patient' ? (
            <Stack.Screen name="Patient" component={PatientNavigator} />
          ) : (
            <Stack.Screen name="Therapist" component={TherapistNavigator} />
          )}
        </Stack.Navigator>

        {/* Overlay per notifiche bloccanti - mostrato solo se autenticato */}
        {isAuthenticated && <BlockingNotificationOverlay />}

        {/* Modal per lettura notifiche */}
        {isAuthenticated && <NotificationModal />}
      </NavigationContainer>
    </SafeAreaWrapper>
  );
};

export default AppNavigator;
