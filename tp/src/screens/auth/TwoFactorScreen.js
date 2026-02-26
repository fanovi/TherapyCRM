import React, {useState, useEffect, useCallback} from 'react';
import {View, StyleSheet, ScrollView, TouchableOpacity, Platform} from 'react-native';
import ClipboardPkg from '@react-native-clipboard/clipboard';
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
import Icon from 'react-native-vector-icons/MaterialIcons';
import {clear2faState, clearError} from '../../slices/authSlice';
import {loginService} from '../../services/loginService';
import {authService} from '../../services/authService';

const TwoFactorScreen = () => {
  const navigation = useNavigation();
  const dispatch = useDispatch();
  const {
    isLoading,
    error,
    twoFactorMethod,
    showRememberDevice,
    twoFactorTempToken,
    totpConfigured,
    user,
  } = useSelector(state => state.auth);
  const theme = useTheme();

  // State machine: 'choose' -> 'emailCode' | 'totpSetup' -> 'totpCode'
  const [step, setStep] = useState('choose');
  const [code, setCode] = useState('');
  const [rememberDevice, setRememberDevice] = useState(false);
  const [resendCountdown, setResendCountdown] = useState(0);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [totpSecret, setTotpSecret] = useState('');
  const [methodLoading, setMethodLoading] = useState(false);

  // Countdown timer for email resend
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

  const handleChooseEmail = useCallback(async () => {
    setMethodLoading(true);
    try {
      await loginService.resendEmailOtp(dispatch, twoFactorTempToken);
      setResendCountdown(60);
      setCode('');
      setStep('emailCode');
    } catch (err) {
      setSnackbarMessage("Errore nell'invio del codice. Riprova.");
      setSnackbarVisible(true);
    } finally {
      setMethodLoading(false);
    }
  }, [dispatch, twoFactorTempToken]);

  const handleChooseTotp = useCallback(async () => {
    if (totpConfigured) {
      setCode('');
      setStep('totpCode');
    } else {
      setMethodLoading(true);
      try {
        const result = await authService.setup2faTemp(twoFactorTempToken);
        setTotpSecret(result.secret);
        setStep('totpSetup');
      } catch (err) {
        setSnackbarMessage('Errore nella configurazione. Riprova.');
        setSnackbarVisible(true);
      } finally {
        setMethodLoading(false);
      }
    }
  }, [totpConfigured, twoFactorTempToken]);

  const handleVerifyEmail = useCallback(async () => {
    if (!code.trim() || code.trim().length !== 6) {
      setSnackbarMessage('Inserisci un codice di 6 cifre');
      setSnackbarVisible(true);
      return;
    }

    try {
      await loginService.verify2fa(dispatch, {
        tempToken: twoFactorTempToken,
        code: code.trim(),
        method: 'email',
        rememberDevice,
        deviceName: Platform.OS === 'ios' ? 'iPhone' : 'Android',
      });
    } catch (err) {
      // Error handled by twoFactorFailure reducer
    }
  }, [code, dispatch, twoFactorTempToken, rememberDevice]);

  const handleVerifyTotp = useCallback(async () => {
    if (!code.trim() || code.trim().length !== 6) {
      setSnackbarMessage('Inserisci un codice di 6 cifre');
      setSnackbarVisible(true);
      return;
    }

    try {
      await loginService.verify2fa(dispatch, {
        tempToken: twoFactorTempToken,
        code: code.trim(),
        method: 'totp',
        rememberDevice,
        deviceName: Platform.OS === 'ios' ? 'iPhone' : 'Android',
      });
    } catch (err) {
      // Error handled by twoFactorFailure reducer
    }
  }, [code, dispatch, twoFactorTempToken, rememberDevice]);

  const handleResendCode = useCallback(async () => {
    if (resendCountdown > 0) {
      return;
    }

    try {
      await loginService.resendEmailOtp(dispatch, twoFactorTempToken);
      setResendCountdown(60);
      setSnackbarMessage('Nuovo codice inviato alla tua email');
      setSnackbarVisible(true);
    } catch (err) {
      setSnackbarMessage("Errore nell'invio del codice. Riprova.");
      setSnackbarVisible(true);
    }
  }, [resendCountdown, dispatch, twoFactorTempToken]);

  const handleChangeMethod = useCallback(() => {
    setCode('');
    setStep('choose');
  }, []);

  const handleBackToLogin = useCallback(() => {
    dispatch(clear2faState());
    navigation.navigate('Login');
  }, [dispatch, navigation]);

  const handleClearError = useCallback(() => {
    dispatch(clearError());
  }, [dispatch]);

  // ========== STEP: choose ==========
  const renderChooseStep = () => (
    <View style={styles.content}>
      <View style={styles.header}>
        <View
          style={[
            styles.iconCircle,
            {backgroundColor: theme.colors.primaryContainer},
          ]}>
          <Icon name="verified-user" size={40} color={theme.colors.primary} />
        </View>
        <Text style={[styles.title, {color: theme.colors.onSurface}]}>
          Verifica la tua identit&agrave;
        </Text>
        <Text
          style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
          Scegli come ricevere il codice di verifica
        </Text>
      </View>

      {methodLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={theme.colors.primary} />
          <Text
            style={[
              styles.loadingText,
              {color: theme.colors.onSurfaceVariant},
            ]}>
            Preparazione in corso...
          </Text>
        </View>
      ) : (
        <View>
          <TouchableOpacity
            onPress={handleChooseEmail}
            activeOpacity={0.7}
            style={styles.methodCardWrapper}>
            <Card style={styles.methodCard}>
              <Card.Content style={styles.methodCardContent}>
                <View
                  style={[
                    styles.methodIconContainer,
                    {backgroundColor: theme.colors.primaryContainer},
                  ]}>
                  <Icon name="email" size={32} color={theme.colors.primary} />
                </View>
                <View style={styles.methodTextContainer}>
                  <Text
                    style={[
                      styles.methodTitle,
                      {color: theme.colors.onSurface},
                    ]}>
                    Codice via Email
                  </Text>
                  <Text
                    style={[
                      styles.methodSubtitle,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Ricevi un codice a 6 cifre via email
                  </Text>
                </View>
                <Icon
                  name="chevron-right"
                  size={24}
                  color={theme.colors.onSurfaceVariant}
                />
              </Card.Content>
            </Card>
          </TouchableOpacity>

          <TouchableOpacity
            onPress={handleChooseTotp}
            activeOpacity={0.7}
            style={styles.methodCardWrapper}>
            <Card style={styles.methodCard}>
              <Card.Content style={styles.methodCardContent}>
                <View
                  style={[
                    styles.methodIconContainer,
                    {backgroundColor: theme.colors.primaryContainer},
                  ]}>
                  <Icon
                    name="security"
                    size={32}
                    color={theme.colors.primary}
                  />
                </View>
                <View style={styles.methodTextContainer}>
                  <Text
                    style={[
                      styles.methodTitle,
                      {color: theme.colors.onSurface},
                    ]}>
                    Google Authenticator
                  </Text>
                  <Text
                    style={[
                      styles.methodSubtitle,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Usa l'app Google Authenticator
                  </Text>
                </View>
                <Icon
                  name="chevron-right"
                  size={24}
                  color={theme.colors.onSurfaceVariant}
                />
              </Card.Content>
            </Card>
          </TouchableOpacity>
        </View>
      )}

      <Button
        mode="text"
        onPress={handleBackToLogin}
        style={styles.backButton}
        icon="arrow-left">
        Torna al login
      </Button>
    </View>
  );

  // ========== STEP: emailCode ==========
  const renderEmailCodeStep = () => (
    <View style={styles.content}>
      <View style={styles.header}>
        <View
          style={[
            styles.iconCircle,
            {backgroundColor: theme.colors.primaryContainer},
          ]}>
          <Icon name="email" size={40} color={theme.colors.primary} />
        </View>
        <Text style={[styles.title, {color: theme.colors.onSurface}]}>
          Codice di verifica
        </Text>
        <Text
          style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
          Inserisci il codice a 6 cifre inviato a{' '}
          {user?.email || 'la tua email'}
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
            onPress={handleVerifyEmail}
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

          <Button
            mode="text"
            onPress={handleChangeMethod}
            style={styles.changeMethodButton}
            icon="swap-horiz">
            Cambia metodo
          </Button>
        </Card.Content>
      </Card>
    </View>
  );

  // ========== STEP: totpSetup ==========
  const renderTotpSetupStep = () => (
    <View style={styles.content}>
      <View style={styles.header}>
        <View
          style={[
            styles.iconCircle,
            {backgroundColor: theme.colors.primaryContainer},
          ]}>
          <Icon name="security" size={40} color={theme.colors.primary} />
        </View>
        <Text style={[styles.title, {color: theme.colors.onSurface}]}>
          Configura Google Authenticator
        </Text>
      </View>

      <Card style={styles.card}>
        <Card.Content style={styles.cardContent}>
          <View style={styles.instructionsList}>
            <View style={styles.instructionItem}>
              <View
                style={[
                  styles.instructionNumber,
                  {backgroundColor: theme.colors.primary},
                ]}>
                <Text style={styles.instructionNumberText}>1</Text>
              </View>
              <Text
                style={[
                  styles.instructionText,
                  {color: theme.colors.onSurface},
                ]}>
                Apri l'app Google Authenticator
              </Text>
            </View>
            <View style={styles.instructionItem}>
              <View
                style={[
                  styles.instructionNumber,
                  {backgroundColor: theme.colors.primary},
                ]}>
                <Text style={styles.instructionNumberText}>2</Text>
              </View>
              <Text
                style={[
                  styles.instructionText,
                  {color: theme.colors.onSurface},
                ]}>
                Tocca + per aggiungere un account
              </Text>
            </View>
            <View style={styles.instructionItem}>
              <View
                style={[
                  styles.instructionNumber,
                  {backgroundColor: theme.colors.primary},
                ]}>
                <Text style={styles.instructionNumberText}>3</Text>
              </View>
              <Text
                style={[
                  styles.instructionText,
                  {color: theme.colors.onSurface},
                ]}>
                Seleziona "Inserisci codice di configurazione"
              </Text>
            </View>
            <View style={styles.instructionItem}>
              <View
                style={[
                  styles.instructionNumber,
                  {backgroundColor: theme.colors.primary},
                ]}>
                <Text style={styles.instructionNumberText}>4</Text>
              </View>
              <Text
                style={[
                  styles.instructionText,
                  {color: theme.colors.onSurface},
                ]}>
                Inserisci questo codice:
              </Text>
            </View>
          </View>

          <View
            style={[
              styles.secretContainer,
              {backgroundColor: theme.colors.surfaceVariant},
            ]}>
            <Text
              style={[styles.secretText, {color: theme.colors.onSurface}]}
              selectable>
              {totpSecret}
            </Text>
          </View>

          <Button
            mode="outlined"
            onPress={() => {
              ClipboardPkg.setString(totpSecret);
              setSnackbarMessage('Codice copiato negli appunti');
              setSnackbarVisible(true);
            }}
            style={styles.copyButton}
            icon="content-copy">
            Copia codice
          </Button>

          <Button
            mode="contained"
            onPress={() => {
              setCode('');
              setStep('totpCode');
            }}
            style={styles.verifyButton}
            contentStyle={styles.buttonContent}
            icon="check">
            Ho aggiunto il codice
          </Button>

          <Button
            mode="text"
            onPress={handleChangeMethod}
            style={styles.changeMethodButton}
            icon="swap-horiz">
            Cambia metodo
          </Button>
        </Card.Content>
      </Card>
    </View>
  );

  // ========== STEP: totpCode ==========
  const renderTotpCodeStep = () => (
    <View style={styles.content}>
      <View style={styles.header}>
        <View
          style={[
            styles.iconCircle,
            {backgroundColor: theme.colors.primaryContainer},
          ]}>
          <Icon name="security" size={40} color={theme.colors.primary} />
        </View>
        <Text style={[styles.title, {color: theme.colors.onSurface}]}>
          Codice Google Authenticator
        </Text>
        <Text
          style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
          Inserisci il codice a 6 cifre dall'app Google Authenticator
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
            onPress={handleVerifyTotp}
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
            onPress={handleChangeMethod}
            style={styles.changeMethodButton}
            icon="swap-horiz">
            Cambia metodo
          </Button>
        </Card.Content>
      </Card>
    </View>
  );

  // ========== Render current step ==========
  const renderStep = () => {
    switch (step) {
      case 'choose':
        return renderChooseStep();
      case 'emailCode':
        return renderEmailCodeStep();
      case 'totpSetup':
        return renderTotpSetupStep();
      case 'totpCode':
        return renderTotpCodeStep();
      default:
        return renderChooseStep();
    }
  };

  return (
    <View
      style={[styles.container, {backgroundColor: theme.colors.background}]}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled">
        {renderStep()}
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
  loadingContainer: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 14,
  },
  methodCardWrapper: {
    marginBottom: 12,
  },
  methodCard: {
    borderRadius: 16,
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 2},
    shadowOpacity: 0.15,
    shadowRadius: 6,
  },
  methodCardContent: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 20,
    paddingHorizontal: 16,
  },
  methodIconContainer: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  methodTextContainer: {
    flex: 1,
  },
  methodTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  methodSubtitle: {
    fontSize: 13,
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
  changeMethodButton: {
    marginBottom: 0,
  },
  backButton: {
    marginTop: 24,
    alignSelf: 'center',
  },
  instructionsList: {
    marginBottom: 20,
  },
  instructionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 14,
  },
  instructionNumber: {
    width: 28,
    height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  instructionNumberText: {
    color: 'white',
    fontSize: 14,
    fontWeight: '700',
  },
  instructionText: {
    fontSize: 14,
    flex: 1,
    lineHeight: 20,
  },
  secretContainer: {
    borderRadius: 12,
    padding: 16,
    marginBottom: 24,
    alignItems: 'center',
  },
  secretText: {
    fontSize: 18,
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
    fontWeight: '700',
    letterSpacing: 2,
    textAlign: 'center',
  },
  copyButton: {
    marginBottom: 24,
    borderRadius: 8,
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

export default TwoFactorScreen;
