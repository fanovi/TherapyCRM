import React, {useEffect, useState} from 'react';
import {NavigationContainer} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {useSelector, useDispatch} from 'react-redux';
import {ActivityIndicator, View} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {checkTokenValidity} from '../api/auth';
import {loginSuccess, logoutUser, setInitialized} from '../slices/authSlice';
import SafeAreaWrapper from '../components/SafeAreaWrapper';
import AuthNavigator from './AuthNavigator';
import PatientNavigator from './PatientNavigator';
import TherapistNavigator from './TherapistNavigator';

const Stack = createNativeStackNavigator();

const AppNavigator = () => {
  const dispatch = useDispatch();
  const {isAuthenticated, user, requiresPasswordChange} = useSelector(
    state => state.auth,
  );
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    checkInitialAuth();
  }, []);

  const checkInitialAuth = async () => {
    try {
      console.log('🚀 === INITIAL AUTH CHECK ===');

      // 1. C'è un token?
      const token = await AsyncStorage.getItem('authToken');
      console.log('📦 Token in storage:', token ? 'YES' : 'NO');

      if (!token) {
        console.log('❌ No token found - going to login');
        dispatch(logoutUser());
        setIsLoading(false);
        return;
      }

      // 2. Il token è valido?
      console.log('🔄 Checking token validity with backend...');
      const response = await checkTokenValidity(token);

      if (!response.valid) {
        console.log('❌ Token invalid - going to login');
        await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
        dispatch(logoutUser());
        setIsLoading(false);
        return;
      }

      // 3. Token valido - recupera dati utente
      console.log('✅ Token valid - restoring session');
      const userString = await AsyncStorage.getItem('user');
      const rawUser = userString ? JSON.parse(userString) : null;

      if (rawUser) {
        // Mappa i dati dal formato backend al formato app
        const user = {
          ...rawUser,
          firstName: rawUser.nome,
          lastName: rawUser.cognome,
          fullName: `${rawUser.nome} ${rawUser.cognome}`,
          // Mantieni anche i campi originali per compatibilità
          nome: rawUser.nome,
          cognome: rawUser.cognome,
        };

        console.log('📝 User mapped:', {
          firstName: user.firstName,
          lastName: user.lastName,
          fullName: user.fullName,
        });

        dispatch(
          loginSuccess({
            token,
            user,
            requiresPasswordChange: false,
          }),
        );
      } else {
        console.log('❌ No user data - going to login');
        dispatch(logoutUser());
      }
    } catch (error) {
      console.error('❌ Auth check error:', error);
      await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
      dispatch(logoutUser());
    } finally {
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
          {!isAuthenticated || requiresPasswordChange ? (
            <Stack.Screen name="Auth" component={AuthNavigator} />
          ) : user?.user_type === 'paziente' || user?.role === 'patient' ? (
            <Stack.Screen name="Patient" component={PatientNavigator} />
          ) : (
            <Stack.Screen name="Therapist" component={TherapistNavigator} />
          )}
        </Stack.Navigator>
      </NavigationContainer>
    </SafeAreaWrapper>
  );
};

export default AppNavigator;
