import React, {useState, useEffect, useRef} from 'react';
import {
  View,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  StatusBar,
  Image,
} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  Snackbar,
  ActivityIndicator,
  Checkbox,
  useTheme,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {useNavigation} from '@react-navigation/native';
import {clear2faState, clearError} from '../../slices/authSlice';
import {loginService} from '../../services/loginService';
import {logo} from '../../assets/images';

const TwoFactorScreen = () => {
  const navigation = useNavigation();
  const dispatch = useDispatch();
  const {
    isLoading,
    error,
    twoFactorMethod,
    showRememberDevice,
    twoFactorTempToken,
  } = useSelector(state => state.auth);
  const theme = useTheme();

  const [code, setCode] = useState('');
  const [rememberDevice, setRememberDevice] = useState(false);
  const [resendCountdown, setResendCountdown] = useState(0);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const countdownRef = useRef(null);

  // Countdown per reinvio codice email
  useEffect(() => {
    if (twoFactorMethod === 'email') {
      setResendCountdown(60);
    }
  }, [twoFactorMethod]);

  useEffect(() => {
    if (resendCountdown > 0) {
      countdownRef.current = setTimeout(() => {
        setResendCountdown(prev => prev - 1);
      }, 1000);
    }
    return () => {
      if (countdownRef.current) {
        clearTimeout(countdownRef.current);
      }
    };
  }, [resendCountdown]);

  const handleVerify = async () => {
    if (!code.trim() || code.trim().length !== 6) {
      setSnackbarMessage('Inserisci un codice di 6 cifre');
      setSnackbarVisible(true);
      return;
    }

    try {
      await loginService.verify2fa(dispatch, {
        tempToken: twoFactorTempToken,
        code: code.trim(),
        rememberDevice,
        deviceName: Platform.OS === 'ios' ? 'iPhone' : 'Android',
      });
    } catch (err) {
      // L'errore viene gestito dal reducer twoFactorFailure
    }
  };

  const handleResendCode = async () => {
    if (resendCountdown > 0) return;

    try {
      await loginService.resendEmailOtp(dispatch, twoFactorTempToken);
      setResendCountdown(60);
      setSnackbarMessage('Nuovo codice inviato alla tua email');
      setSnackbarVisible(true);
    } catch (err) {
      setSnackbarMessage('Errore nell\'invio del codice. Riprova.');
      setSnackbarVisible(true);
    }
  };

  const handleBackToLogin = () => {
    dispatch(clear2faState());
    navigation.navigate('Login');
  };

  const handleClearError = () => {
    dispatch(clearError());
  };

  const getDescriptionText = () => {
    if (twoFactorMethod === 'totp') {
      return 'Inserisci il codice a 6 cifre generato dalla tua app di autenticazione (es. Google Authenticator).';
    }
    return 'Abbiamo inviato un codice di verifica a 6 cifre alla tua email. Inseriscilo qui sotto per accedere.';
  };

  return (
    <View
      style={[styles.container, {backgroundColor: theme.colors.background}]}>
      <StatusBar
        barStyle="dark-content"
        backgroundColor={theme.colors.background}
      />

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}>
        <View style={styles.content}>
          {/* Logo/Header */}
          <View style={styles.header}>
            <View style={styles.logoContainer}>
              <Image source={logo} style={styles.logo} />
            </View>
            <Text style={[styles.title, {color: theme.colors.onSurface}]}>
              Verifica identità
            </Text>
            <Text
              style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
              {getDescriptionText()}
            </Text>
          </View>

          {/* Form Card */}
          <Card style={styles.card}>
            <Card.Content style={styles.cardContent}>
              <TextInput
                label="Codice di verifica"
                value={code}
                onChangeText={text => {
                  // Solo numeri, max 6 cifre
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

              {/* Checkbox Ricorda dispositivo */}
              {showRememberDevice && (
                <View style={styles.rememberContainer}>
                  <Checkbox
                    status={rememberDevice ? 'checked' : 'unchecked'}
                    onPress={() => setRememberDevice(!rememberDevice)}
                  />
                  <Text
                    style={[
                      styles.rememberText,
                      {color: theme.colors.onSurface},
                    ]}
                    onPress={() => setRememberDevice(!rememberDevice)}>
                    Ricorda questo dispositivo
                  </Text>
                </View>
              )}

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

              {/* Reinvia codice (solo email) */}
              {twoFactorMethod === 'email' && (
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
              )}

              <Button
                mode="text"
                onPress={handleBackToLogin}
                style={styles.backButton}
                icon="arrow-left">
                Torna al login
              </Button>
            </Card.Content>
          </Card>
        </View>
      </KeyboardAvoidingView>

      {/* Snackbar per errori dal reducer */}
      <Snackbar
        visible={!!error}
        onDismiss={handleClearError}
        duration={4000}
        style={styles.errorSnackbar}>
        {error}
      </Snackbar>

      {/* Snackbar per messaggi locali */}
      <Snackbar
        visible={snackbarVisible}
        onDismiss={() => setSnackbarVisible(false)}
        duration={3000}
        style={
          snackbarMessage.includes('inviato')
            ? styles.successSnackbar
            : styles.errorSnackbar
        }>
        {snackbarMessage}
      </Snackbar>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  keyboardView: {
    flex: 1,
  },
  content: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  header: {
    alignItems: 'center',
    marginBottom: 40,
  },
  logoContainer: {
    width: 80,
    height: 80,
    borderRadius: 20,
    backgroundColor: 'white',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 24,
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 2},
    shadowOpacity: 0.1,
    shadowRadius: 8,
  },
  logo: {
    width: 60,
    height: 60,
    resizeMode: 'contain',
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
  rememberContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  rememberText: {
    fontSize: 14,
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
    marginBottom: 8,
  },
  backButton: {
    marginBottom: 0,
  },
  errorSnackbar: {
    backgroundColor: '#f44336',
  },
  successSnackbar: {
    backgroundColor: '#4caf50',
  },
});

export default TwoFactorScreen;
