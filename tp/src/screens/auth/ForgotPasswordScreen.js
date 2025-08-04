import React, {useState} from 'react';
import {
  View,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  StatusBar,
} from 'react-native';
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
import LinearGradient from 'react-native-linear-gradient';
import {clearError} from '../../slices/authSlice';
import {loginService} from '../../services/loginService';

const ForgotPasswordScreen = () => {
  const navigation = useNavigation();
  const dispatch = useDispatch();
  const {isLoading, error} = useSelector(state => state.auth);
  const theme = useTheme();

  const [email, setEmail] = useState('');
  const [resetToken, setResetToken] = useState('');
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [showTokenInput, setShowTokenInput] = useState(false);

  const handleRequestReset = async () => {
    if (!email.trim()) {
      return;
    }

    try {
      await loginService.requestPasswordReset(
        dispatch,
        email.toLowerCase().trim(),
      );
      setIsSubmitted(true);
    } catch (error) {
      console.error('❌ Password reset request failed:', error);
    }
  };

  const handleResetWithToken = async () => {
    if (!resetToken.trim()) {
      return;
    }

    try {
      // Navigate to reset password screen with token
      navigation.navigate('ResetPasswordWithToken', {token: resetToken});
    } catch (error) {
      console.error('❌ Token navigation failed:', error);
    }
  };

  const handleClearError = () => {
    dispatch(clearError());
  };

  const handleBackToLogin = () => {
    navigation.goBack();
  };

  if (isSubmitted) {
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
            {/* Success Header */}
            <View style={styles.header}>
              <View style={styles.iconContainer}>
                <Text style={styles.iconText}>✅</Text>
              </View>
              <Text style={[styles.title, {color: theme.colors.onSurface}]}>
                Email Inviata
              </Text>
              <Text
                style={[
                  styles.subtitle,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Se l'email è registrata, riceverai le istruzioni per resettare
                la password
              </Text>
            </View>

            {/* Info Card */}
            <Card style={styles.card}>
              <Card.Content style={styles.cardContent}>
                <Text
                  style={[
                    styles.infoText,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Controlla la tua casella email e segui le istruzioni per
                  creare una nuova password.
                </Text>

                <Button
                  mode="contained"
                  onPress={handleBackToLogin}
                  style={styles.backButton}
                  contentStyle={styles.buttonContent}>
                  Torna al Login
                </Button>
              </Card.Content>
            </Card>
          </View>
        </KeyboardAvoidingView>
      </View>
    );
  }

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
          {/* Header */}
          <View style={styles.header}>
            <View style={styles.logoContainer}>
              <Text style={styles.logoText}>🔐</Text>
            </View>
            <Text style={[styles.title, {color: theme.colors.onSurface}]}>
              Password Dimenticata
            </Text>
            <Text
              style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
              Inserisci la tua email per ricevere le istruzioni di reset
            </Text>
          </View>

          {/* Form Card */}
          <Card style={styles.card}>
            <Card.Content style={styles.cardContent}>
              {!showTokenInput ? (
                <>
                  <TextInput
                    label="Email"
                    value={email}
                    onChangeText={setEmail}
                    mode="outlined"
                    style={styles.input}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    autoCorrect={false}
                    left={<TextInput.Icon icon="email" />}
                  />

                  <Button
                    mode="contained"
                    onPress={handleRequestReset}
                    style={styles.resetButton}
                    contentStyle={styles.buttonContent}
                    disabled={isLoading || !email.trim()}
                    icon={isLoading ? undefined : 'send'}>
                    {isLoading ? (
                      <ActivityIndicator color="white" size="small" />
                    ) : (
                      'Invia Email di Reset'
                    )}
                  </Button>

                  <View style={styles.divider}>
                    <View
                      style={[
                        styles.dividerLine,
                        {backgroundColor: theme.colors.outline},
                      ]}
                    />
                    <Text
                      style={[
                        styles.dividerText,
                        {color: theme.colors.onSurfaceVariant},
                      ]}>
                      oppure
                    </Text>
                    <View
                      style={[
                        styles.dividerLine,
                        {backgroundColor: theme.colors.outline},
                      ]}
                    />
                  </View>

                  <Button
                    mode="outlined"
                    onPress={() => setShowTokenInput(true)}
                    style={styles.tokenButton}
                    icon="key">
                    Ho già il token
                  </Button>
                </>
              ) : (
                <>
                  <TextInput
                    label="Token di Reset"
                    value={resetToken}
                    onChangeText={setResetToken}
                    mode="outlined"
                    style={styles.input}
                    autoCapitalize="none"
                    autoCorrect={false}
                    left={<TextInput.Icon icon="key" />}
                  />

                  <Button
                    mode="contained"
                    onPress={handleResetWithToken}
                    style={styles.resetButton}
                    contentStyle={styles.buttonContent}
                    disabled={!resetToken.trim()}
                    icon="check">
                    Conferma Token
                  </Button>

                  <Button
                    mode="text"
                    onPress={() => setShowTokenInput(false)}
                    style={styles.backButton}
                    icon="arrow-left">
                    Torna alla richiesta email
                  </Button>
                </>
              )}

              <Button
                mode="text"
                onPress={handleBackToLogin}
                style={styles.backButton}
                icon="arrow-left">
                Torna al Login
              </Button>
            </Card.Content>
          </Card>
        </View>
      </KeyboardAvoidingView>

      <Snackbar
        visible={!!error}
        onDismiss={handleClearError}
        duration={4000}
        style={styles.snackbar}>
        {error}
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
  logoText: {
    fontSize: 40,
  },
  iconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(76, 175, 80, 0.1)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 24,
  },
  iconText: {
    fontSize: 40,
  },
  title: {
    fontSize: 32,
    fontWeight: '300',
    marginBottom: 8,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    textAlign: 'center',
    fontWeight: '400',
    lineHeight: 22,
  },
  card: {
    borderRadius: 16,
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  cardContent: {
    padding: 24,
  },
  input: {
    marginBottom: 16,
  },
  resetButton: {
    marginTop: 8,
    marginBottom: 16,
    borderRadius: 8,
  },
  tokenButton: {
    marginTop: 8,
    marginBottom: 16,
    borderRadius: 8,
  },
  backButton: {
    marginTop: 8,
  },
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 16,
  },
  dividerText: {
    fontSize: 14,
    marginHorizontal: 16,
  },
  dividerLine: {
    flex: 1,
    height: 1,
  },
  buttonContent: {
    height: 48,
  },
  infoText: {
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 24,
  },
  snackbar: {
    backgroundColor: '#f44336',
  },
});

export default ForgotPasswordScreen;
