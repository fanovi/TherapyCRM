import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  StatusBar,
  ScrollView,
} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  Snackbar,
  ActivityIndicator,
  HelperText,
  useTheme,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {useNavigation, useRoute} from '@react-navigation/native';
import LinearGradient from 'react-native-linear-gradient';
import {clearError} from '../../slices/authSlice';
import {loginService} from '../../services/loginService';

const ResetPasswordWithTokenScreen = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const dispatch = useDispatch();
  const {isLoading, error} = useSelector(state => state.auth);
  const theme = useTheme();

  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [resetToken, setResetToken] = useState('');

  // Get token from route params or URL
  useEffect(() => {
    const token = route.params?.token || '';
    setResetToken(token);
  }, [route.params]);

  // Validation according to backend requirements:
  // - At least 8 characters
  // - At least one uppercase letter
  // - At least one lowercase letter
  // - At least one number
  const isValidPassword = password => {
    if (password.length < 8) return false;
    if (!/[A-Z]/.test(password)) return false;
    if (!/[a-z]/.test(password)) return false;
    if (!/[0-9]/.test(password)) return false;
    return true;
  };

  const passwordValid = isValidPassword(password);
  const passwordsMatch = password === confirmPassword;
  const isFormValid =
    passwordValid && passwordsMatch && password.trim() && resetToken;

  const handleResetPassword = async () => {
    if (!isFormValid) return;

    try {
      await loginService.resetPassword(dispatch, {
        resetToken,
        newPassword: password,
        confirmPassword,
      });
      console.log('✅ Password reset completed successfully');

      // Navigate to main app after successful reset
      navigation.reset({
        index: 0,
        routes: [{name: 'MainApp'}],
      });
    } catch (error) {
      console.error('❌ Password reset failed:', error);
    }
  };

  const handleClearError = () => {
    dispatch(clearError());
  };

  const handleBackToLogin = () => {
    navigation.navigate('Login');
  };

  return (
    <ScrollView style={styles.container}>
      <LinearGradient
        colors={[theme.colors.primary, theme.colors.primaryContainer]}>
        <StatusBar
          barStyle="light-content"
          backgroundColor={theme.colors.primary}
        />

        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.keyboardView}>
          <View style={styles.content}>
            {/* Header */}
            <View style={styles.header}>
              <View style={styles.iconContainer}>
                <Text style={styles.iconText}>🔐</Text>
              </View>
              <Text style={styles.title}>Reset Password</Text>
              <Text style={styles.subtitle}>
                Crea una nuova password per il tuo account
              </Text>
            </View>

            {/* Form Card */}
            <Card style={styles.card}>
              <Card.Content style={styles.cardContent}>
                {!resetToken && (
                  <View
                    style={[
                      styles.warningBox,
                      {backgroundColor: theme.colors.errorContainer},
                    ]}>
                    <Text
                      style={[
                        styles.warningText,
                        {color: theme.colors.onErrorContainer},
                      ]}>
                      ⚠️ Token di reset non trovato. Assicurati di aver cliccato
                      sul link nell'email.
                    </Text>
                  </View>
                )}

                <TextInput
                  label="Nuova Password"
                  value={password}
                  onChangeText={setPassword}
                  mode="outlined"
                  style={styles.input}
                  secureTextEntry={!showPassword}
                  right={
                    <TextInput.Icon
                      icon={showPassword ? 'eye-off' : 'eye'}
                      onPress={() => setShowPassword(!showPassword)}
                    />
                  }
                  left={<TextInput.Icon icon="lock" />}
                  error={password.length > 0 && !passwordValid}
                />
                {password.length > 0 && !passwordValid && (
                  <HelperText
                    type="error"
                    visible={password.length > 0 && !passwordValid}>
                    La password deve essere di almeno 8 caratteri e contenere
                    almeno una lettera maiuscola, una minuscola e un numero
                  </HelperText>
                )}
                <TextInput
                  label="Conferma Password"
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  mode="outlined"
                  style={styles.input}
                  secureTextEntry={!showConfirmPassword}
                  right={
                    <TextInput.Icon
                      icon={showConfirmPassword ? 'eye-off' : 'eye'}
                      onPress={() =>
                        setShowConfirmPassword(!showConfirmPassword)
                      }
                    />
                  }
                  left={<TextInput.Icon icon="lock-check" />}
                  error={confirmPassword.length > 0 && !passwordsMatch}
                />

                <HelperText
                  type="error"
                  visible={confirmPassword.length > 0 && !passwordsMatch}>
                  Le password non coincidono
                </HelperText>

                <Button
                  mode="contained"
                  onPress={handleResetPassword}
                  style={[
                    styles.resetButton,
                    {
                      backgroundColor: theme.colors.primary,
                      opacity: isFormValid ? 1 : 0.6,
                    },
                  ]}
                  contentStyle={styles.buttonContent}
                  disabled={!isFormValid || isLoading}
                  icon={isLoading ? undefined : 'check'}>
                  {isLoading ? (
                    <ActivityIndicator color="white" size="small" />
                  ) : (
                    'Conferma Nuova Password'
                  )}
                </Button>

                <Button
                  mode="text"
                  onPress={handleBackToLogin}
                  style={styles.backButton}
                  icon="arrow-left">
                  Torna al Login
                </Button>

                <View
                  style={[
                    styles.requirements,
                    {backgroundColor: theme.colors.surfaceVariant},
                  ]}>
                  <Text
                    style={[
                      styles.requirementsTitle,
                      {color: theme.colors.onSurface},
                    ]}>
                    Requisiti password:
                  </Text>
                  <Text
                    style={[
                      styles.requirement,
                      {
                        color:
                          password.length >= 8
                            ? theme.colors.primary
                            : theme.colors.onSurfaceVariant,
                      },
                    ]}>
                    • Almeno 8 caratteri
                  </Text>
                  <Text
                    style={[
                      styles.requirement,
                      {
                        color: /[A-Z]/.test(password)
                          ? theme.colors.primary
                          : theme.colors.onSurfaceVariant,
                      },
                    ]}>
                    • Almeno una lettera maiuscola
                  </Text>
                  <Text
                    style={[
                      styles.requirement,
                      {
                        color: /[a-z]/.test(password)
                          ? theme.colors.primary
                          : theme.colors.onSurfaceVariant,
                      },
                    ]}>
                    • Almeno una lettera minuscola
                  </Text>
                  <Text
                    style={[
                      styles.requirement,
                      {
                        color: /[0-9]/.test(password)
                          ? theme.colors.primary
                          : theme.colors.onSurfaceVariant,
                      },
                    ]}>
                    • Almeno un numero
                  </Text>
                  <Text
                    style={[
                      styles.requirement,
                      {
                        color:
                          passwordsMatch && confirmPassword
                            ? theme.colors.primary
                            : theme.colors.onSurfaceVariant,
                      },
                    ]}>
                    • Le password devono coincidere
                  </Text>
                </View>
              </Card.Content>
            </Card>
          </View>
        </KeyboardAvoidingView>

        <Snackbar
          visible={!!error}
          onDismiss={handleClearError}
          duration={3000}
          style={styles.snackbar}>
          {error}
        </Snackbar>
      </LinearGradient>
    </ScrollView>
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
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  header: {
    alignItems: 'center',
    marginBottom: 40,
    marginTop: 20,
  },
  iconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  iconText: {
    fontSize: 40,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: 'white',
    marginBottom: 8,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: 'rgba(255, 255, 255, 0.9)',
    textAlign: 'center',
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
    marginBottom: 20,
  },
  cardContent: {
    padding: 24,
  },
  warningBox: {
    padding: 16,
    borderRadius: 8,
    marginBottom: 24,
  },
  warningText: {
    fontSize: 14,
    textAlign: 'center',
  },
  input: {
    marginBottom: 4,
  },
  resetButton: {
    marginTop: 16,
    marginBottom: 16,
    borderRadius: 8,
  },
  backButton: {
    marginBottom: 24,
  },
  buttonContent: {
    height: 48,
  },
  requirements: {
    padding: 16,
    borderRadius: 8,
  },
  requirementsTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  requirement: {
    fontSize: 14,
    marginBottom: 4,
  },
  snackbar: {
    backgroundColor: '#f44336',
  },
});

export default ResetPasswordWithTokenScreen;
