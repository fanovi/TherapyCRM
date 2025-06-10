import React, {useState, useEffect} from 'react';
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
  HelperText,
  useTheme,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {useNavigation} from '@react-navigation/native';
import LinearGradient from 'react-native-linear-gradient';
import {resetPassword, clearError} from '../../store/authSlice';

const ResetPasswordScreen = () => {
  const navigation = useNavigation();
  const dispatch = useDispatch();
  const {isLoading, error, user} = useSelector(state => state.auth);
  const theme = useTheme();

  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const isValidPassword = password.length >= 6;
  const passwordsMatch = password === confirmPassword;
  const isFormValid = isValidPassword && passwordsMatch && password.trim();

  // Monitora il successo del reset password
  useEffect(() => {
    // Se l'utente ha completato il reset password, l'AppNavigator
    // gestirà automaticamente la navigazione alla dashboard corretta
    // Non serve fare nulla qui, l'AppNavigator rileverà che
    // isPasswordResetRequired è ora false
  }, [user]);

  const handleResetPassword = () => {
    if (!isFormValid) return;

    dispatch(
      resetPassword({
        userId: user.id,
        data: {password, confirmPassword},
      }),
    );
  };

  const handleClearError = () => {
    dispatch(clearError());
  };

  return (
    <LinearGradient
      colors={[theme.colors.primary, theme.colors.primaryContainer]}
      style={styles.container}>
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
            <Text style={styles.title}>Cambia Password</Text>
            <Text style={styles.subtitle}>
              Per la tua sicurezza, devi cambiare la password al primo accesso
            </Text>
          </View>

          {/* Form Card */}
          <Card style={styles.card}>
            <Card.Content style={styles.cardContent}>
              <View
                style={[
                  styles.userInfo,
                  {backgroundColor: theme.colors.surface},
                ]}>
                <Text
                  style={[
                    styles.userLabel,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Utente:
                </Text>
                <Text
                  style={[styles.userName, {color: theme.colors.onSurface}]}>
                  {user?.firstName} {user?.lastName}
                </Text>
                <Text
                  style={[
                    styles.userEmail,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  {user?.email}
                </Text>
              </View>

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
                error={password.length > 0 && !isValidPassword}
              />

              <HelperText
                type="error"
                visible={password.length > 0 && !isValidPassword}>
                La password deve essere di almeno 6 caratteri
              </HelperText>

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
                    onPress={() => setShowConfirmPassword(!showConfirmPassword)}
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
                      color: isValidPassword
                        ? theme.colors.primary
                        : theme.colors.onSurfaceVariant,
                    },
                  ]}>
                  • Almeno 6 caratteri
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
        duration={4000}
        style={styles.snackbar}>
        {error}
      </Snackbar>
    </LinearGradient>
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
  },
  cardContent: {
    padding: 24,
  },
  userInfo: {
    padding: 16,
    borderRadius: 8,
    marginBottom: 24,
    alignItems: 'center',
  },
  userLabel: {
    fontSize: 14,
    marginBottom: 4,
  },
  userName: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  userEmail: {
    fontSize: 14,
  },
  input: {
    marginBottom: 4,
  },
  resetButton: {
    marginTop: 16,
    marginBottom: 24,
    borderRadius: 8,
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

export default ResetPasswordScreen;
