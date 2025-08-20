import React from 'react';
import {View, StyleSheet, ScrollView} from 'react-native';
import {
  Text,
  Button,
  useTheme,
  Card,
  Avatar,
  Chip,
  Divider,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {loginService} from '../../services/loginService';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistProfileScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

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

              <View style={styles.chipContainer}>
                <Chip
                  icon="medical-bag"
                  style={[
                    styles.chip,
                    {backgroundColor: theme.colors.primaryContainer},
                  ]}
                  textStyle={{color: theme.colors.onPrimaryContainer}}>
                  Professionista Sanitario
                </Chip>
              </View>
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

        {/* Informazioni Account */}
        <Card style={styles.infoCard}>
          <Card.Content>
            <Text
              style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
              Informazioni Account
            </Text>

            <View style={styles.infoRow}>
              <Text
                style={[
                  styles.infoLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Ruolo
              </Text>
              <Text style={[styles.infoValue, {color: theme.colors.onSurface}]}>
                {user?.role || 'Terapista'}
              </Text>
            </View>

            <Divider style={styles.divider} />

            <View style={styles.infoRow}>
              <Text
                style={[
                  styles.infoLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Stato Account
              </Text>
              <Chip
                icon="check-circle"
                style={[
                  styles.statusChip,
                  {backgroundColor: theme.colors.surfaceVariant},
                ]}
                textStyle={{color: theme.colors.onSurfaceVariant}}>
                Attivo
              </Chip>
            </View>

            {user?.lastLogin && (
              <>
                <Divider style={styles.divider} />
                <View style={styles.infoRow}>
                  <Text
                    style={[
                      styles.infoLabel,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Ultimo Accesso
                  </Text>
                  <Text
                    style={[styles.infoValue, {color: theme.colors.onSurface}]}>
                    {new Date(user.lastLogin).toLocaleDateString('it-IT')}
                  </Text>
                </View>
              </>
            )}
          </Card.Content>
        </Card>

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
  statusChip: {
    borderRadius: 16,
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
