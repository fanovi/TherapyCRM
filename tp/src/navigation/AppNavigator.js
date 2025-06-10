import React, {useEffect} from 'react';
import {NavigationContainer} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {useSelector, useDispatch} from 'react-redux';
import {ActivityIndicator, View} from 'react-native';
import {validateToken} from '../store/authSlice';
import SafeAreaWrapper from '../components/SafeAreaWrapper';
import AuthNavigator from './AuthNavigator';
import PatientNavigator from './PatientNavigator';
import TherapistNavigator from './TherapistNavigator';

const Stack = createNativeStackNavigator();

const AppNavigator = () => {
  const dispatch = useDispatch();
  const {isAuthenticated, isLoading, isInitialized, user} = useSelector(
    state => state.auth,
  );

  useEffect(() => {
    // Controlla se c'è un token salvato all'avvio
    if (!isInitialized) {
      dispatch(validateToken());
    }
  }, [dispatch, isInitialized]);

  // Mostra loading durante l'inizializzazione
  if (!isInitialized || isLoading) {
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
          {!isAuthenticated || (user && user.isPasswordResetRequired) ? (
            <Stack.Screen name="Auth" component={AuthNavigator} />
          ) : user?.role === 'patient' ? (
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
