import React, {useState, useEffect} from 'react';
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
  useTheme,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {useNavigation} from '@react-navigation/native';
import LinearGradient from 'react-native-linear-gradient';
import {loginUser, clearError} from '../../store/authSlice';

const LoginScreen = () => {
  const navigation = useNavigation();
  const dispatch = useDispatch();
  const {isLoading, error, user} = useSelector(state => state.auth);
  const theme = useTheme();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    // Se l'utente ha effettuato il login ma deve cambiare password
    if (user && user.isPasswordResetRequired) {
      navigation.navigate('ResetPassword');
    }
  }, [user, navigation]);

  const handleLogin = () => {
    if (!email.trim() || !password.trim()) {
      return;
    }

    dispatch(loginUser({email: email.toLowerCase().trim(), password}));
  };

  const handleClearError = () => {
    dispatch(clearError());
  };

  const fillDemoCredentials = role => {
    if (role === 'patient') {
      setEmail('paziente@test.com');
    } else {
      setEmail('terapista@test.com');
    }
    setPassword('test123');
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
              <Text style={styles.logoText}>🏥</Text>
            </View>
            <Text style={[styles.title, {color: theme.colors.onSurface}]}>
              CMS Terapisti
            </Text>
            <Text
              style={[styles.subtitle, {color: theme.colors.onSurfaceVariant}]}>
              Accedi alla tua area riservata
            </Text>
          </View>

          {/* Form Card */}
          <Card style={styles.card}>
            <Card.Content style={styles.cardContent}>
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

              <TextInput
                label="Password"
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
              />

              <Button
                mode="contained"
                onPress={handleLogin}
                style={styles.loginButton}
                contentStyle={styles.buttonContent}
                disabled={isLoading}
                icon={isLoading ? undefined : 'login'}>
                {isLoading ? (
                  <ActivityIndicator color="white" size="small" />
                ) : (
                  'Accedi'
                )}
              </Button>

              {/* Demo Buttons */}
              <View style={styles.demoSection}>
                <Text
                  style={[
                    styles.demoTitle,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Account di prova:
                </Text>
                <View style={styles.demoButtons}>
                  <Button
                    mode="outlined"
                    onPress={() => fillDemoCredentials('patient')}
                    style={styles.demoButton}
                    compact>
                    Paziente
                  </Button>
                  <Button
                    mode="outlined"
                    onPress={() => fillDemoCredentials('therapist')}
                    style={styles.demoButton}
                    compact>
                    Terapista
                  </Button>
                </View>
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
  loginButton: {
    marginTop: 8,
    marginBottom: 24,
    borderRadius: 8,
  },
  buttonContent: {
    height: 48,
  },
  demoSection: {
    alignItems: 'center',
  },
  demoTitle: {
    fontSize: 14,
    marginBottom: 12,
  },
  demoButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  demoButton: {
    borderRadius: 20,
  },
  snackbar: {
    backgroundColor: '#f44336',
  },
});

export default LoginScreen;
