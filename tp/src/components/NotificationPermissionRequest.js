import React, {useState} from 'react';
import {View, Text, TouchableOpacity, StyleSheet, Alert} from 'react-native';
import useNotificationPermissionManager from '../hooks/useNotificationPermissionManager';

const NotificationPermissionRequest = () => {
  const [isRequesting, setIsRequesting] = useState(false);
  const {
    shouldShowRequest,
    isLoading,
    requestPermission,
  } = useNotificationPermissionManager();

  const handleRequestPermission = async () => {
    if (isRequesting) return;

    setIsRequesting(true);
    try {
      const permission = await requestPermission();

      if (permission) {
        Alert.alert(
          '✅ Permesso Concesso',
          'Ora riceverai le notifiche per rimanere aggiornato sulle tue attività.',
          [{text: 'Perfetto!'}],
        );
      } else {
        Alert.alert(
          '❌ Permesso Negato',
          'Non riceverai notifiche. Puoi sempre abilitarle dalle impostazioni del dispositivo.',
          [{text: 'Capisco'}],
        );
      }
    } catch (error) {
      console.error('Errore richiesta permessi:', error);
      Alert.alert(
        '⚠️ Errore',
        'Si è verificato un errore durante la richiesta dei permessi.',
        [{text: 'OK'}],
      );
    } finally {
      setIsRequesting(false);
    }
  };

  console.log('🔍 NotificationPermissionRequest - Stato:', {
    isLoading,
    shouldShowRequest,
  });

  // Se sta caricando, non mostrare nulla
  if (isLoading) {
    console.log('⏳ Caricamento in corso, non mostro nulla');
    return null;
  }

  // Se non deve mostrare la richiesta, non mostrare nulla
  if (!shouldShowRequest) {
    console.log('🚫 Banner non deve essere visibile');
    return null;
  }

  console.log('✅ Rendering banner permessi notifiche');

  return (
    <View style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>🔔 Abilita le Notifiche</Text>
        <Text style={styles.description}>
          Ricevi aggiornamenti in tempo reale sui tuoi appuntamenti, messaggi e
          attività importanti.
        </Text>

        <TouchableOpacity
          style={[styles.button, isRequesting && styles.buttonDisabled]}
          onPress={handleRequestPermission}
          disabled={isRequesting}>
          <Text style={styles.buttonText}>
            {isRequesting ? 'Richiesta in corso...' : 'Abilita Notifiche'}
          </Text>
        </TouchableOpacity>

        <Text style={styles.note}>
          Puoi sempre modificare questa impostazione dalle impostazioni del
          dispositivo
        </Text>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f8f9fa',
    borderRadius: 12,
    margin: 16,
    padding: 20,
    borderWidth: 1,
    borderColor: '#e9ecef',
  },
  content: {
    alignItems: 'center',
  },
  title: {
    fontSize: 18,
    fontWeight: '600',
    color: '#212529',
    marginBottom: 8,
    textAlign: 'center',
  },
  description: {
    fontSize: 14,
    color: '#6c757d',
    textAlign: 'center',
    marginBottom: 20,
    lineHeight: 20,
  },
  button: {
    backgroundColor: '#007bff',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
    marginBottom: 16,
  },
  buttonDisabled: {
    backgroundColor: '#6c757d',
  },
  buttonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  note: {
    fontSize: 12,
    color: '#adb5bd',
    textAlign: 'center',
    fontStyle: 'italic',
  },
  dismissButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    marginBottom: 16,
  },
  dismissButtonText: {
    color: '#6c757d',
    fontSize: 14,
    textDecorationLine: 'underline',
  },
});

export default NotificationPermissionRequest;
