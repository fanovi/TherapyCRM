import React, {useState, useCallback} from 'react';
import {View, StyleSheet, ScrollView, Alert} from 'react-native';
import {
  Text,
  Button,
  useTheme,
  Card,
  Avatar,
  Chip,
  Divider,
  Switch,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {loginService} from '../../services/loginService';
import {biometricService} from '../../services/biometricService';
import {setBiometricRegistered} from '../../slices/authSlice';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistProfileScreen = () => {
  const dispatch = useDispatch();
  const {user, token, biometricAvailable, biometricRegistered, biometricBiometryType} =
    useSelector(state => state.auth);
  const theme = useTheme();
  const [biometricLoading, setBiometricLoading] = useState(false);

  const biometricLabel = biometricService.getBiometricLabel(biometricBiometryType);

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

  const handleBiometricToggle = useCallback(async () => {
    setBiometricLoading(true);
    try {
      if (biometricRegistered) {
        // Disabilita - la pulizia locale avviene sempre
        await biometricService.disableBiometric();
        await biometricService.setEnrollmentDeclined();
        dispatch(setBiometricRegistered(false));
      } else {
        // Abilita
        const result = await biometricService.registerBiometric(
          token,
          biometricLabel,
          user?.id ?? null,
        );
        if (result.success) {
          dispatch(setBiometricRegistered(true));
        } else {
          Alert.alert('Errore', result.error || 'Impossibile abilitare la biometria');
        }
      }
    } catch (error) {
      Alert.alert('Errore', 'Si è verificato un errore');
    } finally {
      setBiometricLoading(false);
    }
  }, [biometricRegistered, token, dispatch, biometricLabel]);

  return (
    <ScreenTemplate
      title="Profilo"
      subtitle={`Dr. ${user?.firstName || user?.name || 'Terapista'} ${
        user?.lastName || ''
      }`}>
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Header Profilo */}
        <Card style={styles.profileCard}>
          <Card.Content style={styles.profileContent}>
            <View style={styles.avatarContainer}>
              <Avatar.Icon
                size={80}
                icon="doctor"
                style={[styles.avatar, {backgroundColor: theme.colors.primary}]}
              />
            </View>

            <View style={styles.profileInfo}>
              <Text style={[styles.fullName, {color: theme.colors.onSurface}]}>
                Dr. {user?.firstName || user?.name || 'Nome'}{' '}
                {user?.lastName || 'Cognome'}
              </Text>

              <Text
                style={[styles.role, {color: theme.colors.onSurfaceVariant}]}>
                Terapista
              </Text>

              {user?.specialization && (
                <View style={styles.chipContainer}>
                  <Chip
                    icon="medical-bag"
                    style={[
                      styles.chip,
                      {backgroundColor: theme.colors.primaryContainer},
                    ]}
                    textStyle={{color: theme.colors.onPrimaryContainer}}>
                    {user.specialization}
                  </Chip>
                </View>
              )}
            </View>
          </Card.Content>
        </Card>

        {/* Informazioni Personali */}
        <Card style={styles.infoCard}>
          <Card.Content>
            <Text
              style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
              Informazioni Personali
            </Text>

            <View style={styles.infoRow}>
              <Text
                style={[
                  styles.infoLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Nome Completo
              </Text>
              <Text style={[styles.infoValue, {color: theme.colors.onSurface}]}>
                {user?.firstName || user?.name || 'N/A'} {user?.lastName || ''}
              </Text>
            </View>

            <Divider style={styles.divider} />

            <View style={styles.infoRow}>
              <Text
                style={[
                  styles.infoLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Email
              </Text>
              <Text style={[styles.infoValue, {color: theme.colors.onSurface}]}>
                {user?.email || 'Non disponibile'}
              </Text>
            </View>

            <Divider style={styles.divider} />

            {user?.phone && (
              <>
                <Divider style={styles.divider} />
                <View style={styles.infoRow}>
                  <Text
                    style={[
                      styles.infoLabel,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Telefono
                  </Text>
                  <Text
                    style={[styles.infoValue, {color: theme.colors.onSurface}]}>
                    {user.phone}
                  </Text>
                </View>
              </>
            )}

            {user?.specialization && (
              <>
                <Divider style={styles.divider} />
                <View style={styles.infoRow}>
                  <Text
                    style={[
                      styles.infoLabel,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Specializzazione
                  </Text>
                  <Text
                    style={[styles.infoValue, {color: theme.colors.onSurface}]}>
                    {user.specialization}
                  </Text>
                </View>
              </>
            )}
          </Card.Content>
        </Card>

        {/* Sicurezza */}
        {biometricAvailable && (
          <Card style={styles.securityCard}>
            <Card.Content>
              <Text
                style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
                Sicurezza
              </Text>

              <View style={styles.biometricRow}>
                <View style={styles.biometricInfo}>
                  <Text
                    style={[
                      styles.biometricLabel,
                      {color: theme.colors.onSurface},
                    ]}>
                    Accesso con {biometricLabel}
                  </Text>
                  <Text
                    style={[
                      styles.biometricDescription,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Accedi rapidamente senza inserire le credenziali
                  </Text>
                </View>
                <Switch
                  value={biometricRegistered}
                  onValueChange={handleBiometricToggle}
                  disabled={biometricLoading}
                />
              </View>
            </Card.Content>
          </Card>
        )}

        {/* Azioni */}
        <Card style={styles.actionsCard}>
          <Card.Content>
            <Text
              style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
              Azioni
            </Text>

            <View style={styles.actionButtons}>
              <Button
                mode="contained"
                icon="logout"
                style={[styles.actionButton, styles.logoutButton]}
                buttonColor={theme.colors.error}
                onPress={handleLogout}>
                Esci dall'App
              </Button>
            </View>
          </Card.Content>
        </Card>
      </ScrollView>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
  },
  profileCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  profileContent: {
    alignItems: 'center',
    paddingVertical: 20,
  },
  avatarContainer: {
    marginBottom: 16,
  },
  avatar: {
    marginBottom: 8,
  },
  profileInfo: {
    alignItems: 'center',
  },
  fullName: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 4,
    textAlign: 'center',
  },
  role: {
    fontSize: 16,
    marginBottom: 12,
  },
  chipContainer: {
    marginTop: 8,
  },
  chip: {
    borderRadius: 20,
  },
  infoCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
  },
  infoLabel: {
    fontSize: 14,
    flex: 1,
  },
  infoValue: {
    fontSize: 14,
    fontWeight: '500',
    flex: 1,
    textAlign: 'right',
  },
  divider: {
    marginVertical: 8,
  },
  securityCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  biometricRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
  },
  biometricInfo: {
    flex: 1,
    marginRight: 16,
  },
  biometricLabel: {
    fontSize: 15,
    fontWeight: '500',
    marginBottom: 4,
  },
  biometricDescription: {
    fontSize: 13,
  },
  actionsCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  actionButtons: {
    gap: 12,
  },
  actionButton: {
    borderRadius: 8,
  },
  logoutButton: {
    marginTop: 8,
  },
});

export default TherapistProfileScreen;
