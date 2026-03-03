import React from 'react';
import {View, StyleSheet} from 'react-native';
import {Modal, Portal, Text, Button, useTheme} from 'react-native-paper';
import {useSelector} from 'react-redux';
import {biometricService} from '../services/biometricService';

const BiometricEnrollmentModal = ({visible, onDismiss, onSuccess}) => {
  const theme = useTheme();
  const {token} = useSelector(state => state.auth);
  const {biometricBiometryType} = useSelector(state => state.auth);
  const [loading, setLoading] = React.useState(false);

  const biometricLabel = biometricService.getBiometricLabel(biometricBiometryType);

  const handleEnable = async () => {
    setLoading(true);
    try {
      const deviceName = `${biometricLabel}`;
      const result = await biometricService.registerBiometric(token, deviceName);

      if (result.success) {
        onSuccess?.();
      } else {
        console.warn('Biometric registration failed:', result.error);
      }
    } catch (error) {
      console.error('Biometric enrollment error:', error);
    } finally {
      setLoading(false);
      onDismiss();
    }
  };

  const handleDecline = async () => {
    await biometricService.setEnrollmentDeclined();
    onDismiss();
  };

  return (
    <Portal>
      <Modal
        visible={visible}
        onDismiss={onDismiss}
        contentContainerStyle={styles.container}>
        <View style={styles.content}>
          <Text style={[styles.title, {color: theme.colors.onSurface}]}>
            Accesso rapido
          </Text>

          <Text
            style={[
              styles.description,
              {color: theme.colors.onSurfaceVariant},
            ]}>
            Vuoi abilitare l'accesso con {biometricLabel}? Ai prossimi accessi
            potrai entrare senza inserire email e password.
          </Text>

          <View style={styles.buttons}>
            <Button
              mode="contained"
              onPress={handleEnable}
              loading={loading}
              disabled={loading}
              style={styles.enableButton}
              icon="fingerprint">
              Abilita {biometricLabel}
            </Button>

            <Button
              mode="text"
              onPress={handleDecline}
              disabled={loading}
              style={styles.declineButton}>
              Non ora
            </Button>
          </View>
        </View>
      </Modal>
    </Portal>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: 'white',
    margin: 20,
    borderRadius: 16,
    padding: 24,
  },
  content: {
    alignItems: 'center',
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 12,
    textAlign: 'center',
  },
  description: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    marginBottom: 24,
  },
  buttons: {
    width: '100%',
    gap: 8,
  },
  enableButton: {
    borderRadius: 8,
  },
  declineButton: {
    borderRadius: 8,
  },
});

export default BiometricEnrollmentModal;
