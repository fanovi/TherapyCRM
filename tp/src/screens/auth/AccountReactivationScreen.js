import React, {useState, useEffect, useCallback} from 'react';
import {View, StyleSheet, ScrollView} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  Snackbar,
  ActivityIndicator,
  useTheme,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {useNavigation} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import {clearReactivationState, clearError} from '../../slices/authSlice';
import {loginService} from '../../services/loginService';

const AccountReactivationScreen = () => {
  const navigation = useNavigation();
  const dispatch = useDispatch();
  const {isLoading, error, reactivationTempToken, user} = useSelector(
    state => state.auth,
  );
  const theme = useTheme();

  const [code, setCode] = useState('');
  const [resendCountdown, setResendCountdown] = useState(60);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [snackbarVisible, setSnackbarVisible] = useState(false);

  // Countdown timer for resend
  useEffect(() => {
    let timer;
    if (resendCountdown > 0) {
      timer = setTimeout(() => {
        setResendCountdown(prev => prev - 1);
      }, 1000);
    }
    return () => {
      if (timer) {
        clearTimeout(timer);
      }
    };
  }, [resendCountdown]);

  const handleVerify = useCallback(async () => {
    if (!code.trim() || code.trim().length !== 6) {
      setSnackbarMessage('Inserisci un codice di 6 cifre');
      setSnackbarVisible(true);
      return;
    }

    try {
      await loginService.verifyReactivation(dispatch, {
        tempToken: reactivationTempToken,
        code: code.trim(),
      });
    } catch (err) {
      // Error handled by loginFailure reducer
    }
  }, [code, dispatch, reactivationTempToken]);

  const handleResendCode = useCallback(async () => {
    if (resendCountdown > 0) {
      return;
    }

    try {
      await loginService.resendReactivationOtp(dispatch, reactivationTempToken);
      setResendCountdown(60);
      setSnackbarMessage('Nuovo codice inviato alla tua email');
      setSnackbarVisible(true);
    } catch (err) {
      setSnackbarMessage("Errore nell'invio del codice. Riprova.");
      setSnackbarVisible(true);
    }
  }, [resendCountdown, dispatch, reactivationTempToken]);

  const handleBackToLogin = useCallback(() => {
    dispatch(clearReactivationState());
    navigation.navigate('Login');
  }, [dispatch, navigation]);

  const handleClearError = useCallback(() => {
    dispatch(clearError());
  }, [dispatch]);

  return (
    <View
      style={[styles.container, {backgroundColor: theme.colors.background}]}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled">
        <View style={styles.content}>
          <View style={styles.header}>
            <View
              style={[
                styles.iconCircle,
                {backgroundColor: theme.colors.primaryContainer},
              ]}>
              <Icon name="lock-clock" size={40} color={theme.colors.primary} />
            </View>
            <Text style={[styles.title, {color: theme.colors.onSurface}]}>
              Account disattivato
            </Text>
            <Text
              style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
              Il tuo account è stato disattivato per inattività. Inserisci il
              codice OTP inviato a {user?.email || 'la tua email'} per
              riattivarlo.
            </Text>
          </View>

          <Card style={styles.card}>
            <Card.Content style={styles.cardContent}>
              <TextInput
                label="Codice di verifica"
                value={code}
                onChangeText={text => {
                  const filtered = text.replace(/[^0-9]/g, '').slice(0, 6);
                  setCode(filtered);
                }}
                mode="outlined"
                style={styles.codeInput}
                keyboardType="number-pad"
                maxLength={6}
                autoFocus
                left={<TextInput.Icon icon="shield-key" />}
              />

              <Button
                mode="contained"
                onPress={handleVerify}
                style={styles.verifyButton}
                contentStyle={styles.buttonContent}
                disabled={isLoading || code.length !== 6}
                icon={isLoading ? undefined : 'check-circle'}>
                {isLoading ? (
                  <ActivityIndicator color="white" size="small" />
                ) : (
                  'Verifica'
                )}
              </Button>

              <Button
                mode="text"
                onPress={handleResendCode}
                style={styles.resendButton}
                disabled={resendCountdown > 0}
                icon="email-fast">
                {resendCountdown > 0
                  ? `Reinvia codice (${resendCountdown}s)`
                  : 'Reinvia codice'}
              </Button>
            </Card.Content>
          </Card>

          <Button
            mode="text"
            onPress={handleBackToLogin}
            style={styles.backButton}
            icon="arrow-left">
            Torna al login
          </Button>
        </View>
      </ScrollView>

      {/* Snackbar for reducer errors */}
      <Snackbar
        visible={!!error}
        onDismiss={handleClearError}
        duration={4000}
        style={styles.errorSnackbar}
        wrapperStyle={styles.snackbarWrapper}>
        {error}
      </Snackbar>

      {/* Snackbar for local messages */}
      <Snackbar
        visible={snackbarVisible}
        onDismiss={() => setSnackbarVisible(false)}
        duration={3000}
        style={
          snackbarMessage.includes('inviato')
            ? styles.successSnackbar
            : styles.errorSnackbar
        }
        wrapperStyle={styles.snackbarWrapper}>
        {snackbarMessage}
      </Snackbar>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
  },
  content: {
    paddingHorizontal: 24,
    paddingVertical: 32,
  },
  header: {
    alignItems: 'center',
    marginBottom: 32,
  },
  iconCircle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 24,
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 2},
    shadowOpacity: 0.1,
    shadowRadius: 8,
  },
  title: {
    fontSize: 28,
    fontWeight: '300',
    marginBottom: 12,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    textAlign: 'center',
    fontWeight: '400',
    lineHeight: 20,
    paddingHorizontal: 16,
  },
  card: {
    borderRadius: 16,
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 4},
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  cardContent: {
    padding: 24,
  },
  codeInput: {
    marginBottom: 16,
    textAlign: 'center',
    fontSize: 24,
    letterSpacing: 8,
  },
  verifyButton: {
    marginTop: 8,
    marginBottom: 16,
    borderRadius: 8,
  },
  buttonContent: {
    height: 48,
  },
  resendButton: {
    marginBottom: 0,
  },
  backButton: {
    marginTop: 24,
    alignSelf: 'center',
  },
  snackbarWrapper: {
    top: 0,
    bottom: undefined,
    position: 'absolute',
  },
  errorSnackbar: {
    backgroundColor: '#f44336',
  },
  successSnackbar: {
    backgroundColor: '#4caf50',
  },
});

export default AccountReactivationScreen;
