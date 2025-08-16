import React from 'react';
import {View, StyleSheet, ScrollView} from 'react-native';
import {
  Text,
  Button,
  Card,
  Avatar,
  Divider,
  useTheme,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import {loginService} from '../../services/loginService';
import ScreenTemplate from '../../components/ScreenTemplate';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  debugUserData,
  getUserDisplayName,
  getUserInitials,
  getUserRole,
  getUserField,
} from '../../utils/userUtils';

const PatientProfileScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const {patients, currentPatient} = useSelector(state => state.patient);
  const theme = useTheme();

  // Debug dettagliato usando utility
  debugUserData(user, 'PatientProfileScreen User Debug');

  // Debug anche AsyncStorage
  const checkAsyncStorage = async () => {
    try {
      const userString = await AsyncStorage.getItem('user');
      console.log('💾 User from AsyncStorage raw:', userString);
      if (userString) {
        const parsedUser = JSON.parse(userString);
        console.log('💾 User from AsyncStorage parsed:', parsedUser);
        console.log('💾 AsyncStorage user keys:', Object.keys(parsedUser));
      }
    } catch (error) {
      console.error('❌ Error reading AsyncStorage:', error);
    }
  };

  React.useEffect(() => {
    checkAsyncStorage();
  }, []);

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

  // Le utility sono ora importate dal modulo utils/userUtils.js

  const formatDate = dateString => {
    if (!dateString) return 'Non specificata';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('it-IT');
    } catch {
      return 'Non specificata';
    }
  };

  return (
    <ScreenTemplate title="Profilo">
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Sezione Avatar e Info Principali */}
        <Card style={styles.profileCard}>
          <Card.Content style={styles.profileContent}>
            <View style={styles.avatarSection}>
              <Avatar.Text
                size={80}
                label={getUserInitials(getUserDisplayName(user))}
                style={[styles.avatar, {backgroundColor: theme.colors.primary}]}
              />
              <View style={styles.userInfo}>
                <Text style={styles.userName}>{getUserDisplayName(user)}</Text>
                <Text style={styles.userEmail}>
                  {user?.email || 'Email non disponibile'}
                </Text>
                <Text style={styles.userRole}>{getUserRole(user)}</Text>
              </View>
            </View>
          </Card.Content>
        </Card>

        {/* Sezione Dati Personali */}
        <Card style={styles.sectionCard}>
          <Card.Content>
            <Text style={styles.sectionTitle}>Dati Personali</Text>
            <Divider style={styles.divider} />

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Codice Fiscale:</Text>
              <Text style={styles.infoValue}>
                {getUserField(user, ['codiceFiscale', 'codice_fiscale'])}
              </Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Telefono:</Text>
              <Text style={styles.infoValue}>
                {getUserField(user, ['telefono', 'phone', 'telephone'])}
              </Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Data di Nascita:</Text>
              <Text style={styles.infoValue}>
                {formatDate(
                  getUserField(
                    user,
                    ['dataNascita', 'data_nascita', 'birth_date'],
                    null,
                  ),
                )}
              </Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Indirizzo:</Text>
              <Text style={styles.infoValue}>
                {getUserField(user, ['indirizzo', 'address'])}
              </Text>
            </View>
          </Card.Content>
        </Card>

        {/* Sezione Pazienti Associati */}
        {patients && patients.length > 0 && (
          <Card style={styles.sectionCard}>
            <Card.Content>
              <Text style={styles.sectionTitle}>Pazienti Associati</Text>
              <Divider style={styles.divider} />

              <View style={styles.statsContainer}>
                <View style={styles.statItem}>
                  <Text style={styles.statNumber}>{patients.length}</Text>
                  <Text style={styles.statLabel}>Totale Pazienti</Text>
                </View>

                <View style={styles.statItem}>
                  <Text style={styles.statNumber}>
                    {patients.filter(p => p.has_parental_authority).length}
                  </Text>
                  <Text style={styles.statLabel}>Con Autorità Parentale</Text>
                </View>
              </View>

              {currentPatient && (
                <View style={styles.currentPatientInfo}>
                  <Text style={styles.currentPatientLabel}>
                    Paziente Attuale:
                  </Text>
                  <Text style={styles.currentPatientName}>
                    {currentPatient.patient_name}
                  </Text>
                  <Text style={styles.currentPatientRelation}>
                    Relazione:{' '}
                    {currentPatient.relationship === 'parent'
                      ? 'Genitore'
                      : currentPatient.relationship}
                  </Text>
                </View>
              )}
            </Card.Content>
          </Card>
        )}

        {/* Sezione Statistiche App */}

        {/* Sezione Azioni */}

        {/* Pulsante Logout */}
        <View style={styles.logoutSection}>
          <Button
            mode="contained"
            onPress={handleLogout}
            buttonColor={theme.colors.error}
            icon="logout"
            style={styles.logoutButton}>
            Esci dall'App
          </Button>
        </View>
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
    padding: 20,
  },
  avatarSection: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatar: {
    marginRight: 16,
  },
  userInfo: {
    flex: 1,
  },
  userName: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  userEmail: {
    fontSize: 14,
    color: '#666',
    marginBottom: 4,
  },
  userRole: {
    fontSize: 12,
    color: '#999',
    textTransform: 'uppercase',
  },
  sectionCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 12,
  },
  divider: {
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  infoLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#666',
    flex: 1,
  },
  infoValue: {
    fontSize: 14,
    flex: 2,
    textAlign: 'right',
  },
  statsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    marginBottom: 16,
  },
  statItem: {
    alignItems: 'center',
  },
  statNumber: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#2196F3',
  },
  statLabel: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
    textAlign: 'center',
  },
  currentPatientInfo: {
    backgroundColor: '#E3F2FD',
    padding: 12,
    borderRadius: 8,
  },
  currentPatientLabel: {
    fontSize: 12,
    color: '#1565C0',
    marginBottom: 4,
  },
  currentPatientName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1565C0',
    marginBottom: 2,
  },
  currentPatientRelation: {
    fontSize: 12,
    color: '#1565C0',
  },
  actionButton: {
    marginBottom: 12,
  },
  logoutSection: {
    marginTop: 8,
    marginBottom: 32,
  },
  logoutButton: {
    borderRadius: 8,
  },
});

export default PatientProfileScreen;
