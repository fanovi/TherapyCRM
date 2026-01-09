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

  // Debug currentPatient
  console.log('🔍 === PATIENT PROFILE DEBUG ===');
  console.log('👤 user object:', JSON.stringify(user, null, 2));
  console.log('👥 patients array:', JSON.stringify(patients, null, 2));
  console.log('🎯 currentPatient:', JSON.stringify(currentPatient, null, 2));
  if (currentPatient) {
    console.log('📋 currentPatient fields:');
    console.log('   - patient_name:', currentPatient.patient_name);
    console.log('   - birth_date:', currentPatient.birth_date);
    console.log('   - fiscal_code:', currentPatient.fiscal_code);
    console.log('   - gender:', currentPatient.gender);
    console.log('   - phone_number:', currentPatient.phone_number);
    console.log('   - full_residence_address:', currentPatient.full_residence_address);
  }

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

  const getRelationshipLabel = relationship => {
    const labels = {
      self: 'Se stesso',
      parent: 'Genitore',
      tutor: 'Tutore',
      other: 'Altro',
    };
    return labels[relationship] || relationship;
  };

  return (
    <ScreenTemplate title="Profilo">
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Sezione Paziente Selezionato */}
        {currentPatient && (
          <Card style={styles.patientCard}>
            <Card.Content>
              <View style={styles.patientHeader}>
                <Avatar.Icon
                  size={60}
                  icon="account"
                  style={[styles.patientAvatar, {backgroundColor: theme.colors.primary}]}
                />
                <View style={styles.patientHeaderInfo}>
                  <Text style={styles.patientName}>
                    {currentPatient.patient_name}
                  </Text>
                  <View style={styles.relationshipBadge}>
                    <Text style={styles.relationshipText}>
                      {getRelationshipLabel(currentPatient.relationship)}
                    </Text>
                  </View>
                  {currentPatient.has_parental_authority && (
                    <Text style={styles.parentalAuthority}>
                      Autorità Genitoriale
                    </Text>
                  )}
                </View>
              </View>

              <Divider style={styles.patientDivider} />

              {/* Dati anagrafici del paziente */}
              <View style={styles.patientDetails}>
                {currentPatient.birth_date && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Data di Nascita:</Text>
                    <Text style={styles.infoValue}>
                      {formatDate(currentPatient.birth_date)}
                      {currentPatient.age ? ` (${currentPatient.age} anni)` : ''}
                    </Text>
                  </View>
                )}

                {currentPatient.birth_location && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Luogo di Nascita:</Text>
                    <Text style={styles.infoValue}>{currentPatient.birth_location}</Text>
                  </View>
                )}

                {currentPatient.gender && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Sesso:</Text>
                    <Text style={styles.infoValue}>
                      {currentPatient.gender === 'M' ? 'Maschio' : 'Femmina'}
                    </Text>
                  </View>
                )}

                {currentPatient.fiscal_code && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Codice Fiscale:</Text>
                    <Text style={styles.infoValue}>{currentPatient.fiscal_code}</Text>
                  </View>
                )}

                {currentPatient.phone_number && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Telefono:</Text>
                    <Text style={styles.infoValue}>{currentPatient.phone_number}</Text>
                  </View>
                )}

                {currentPatient.full_residence_address && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Indirizzo:</Text>
                    <Text style={styles.infoValue}>{currentPatient.full_residence_address}</Text>
                  </View>
                )}
              </View>
            </Card.Content>
          </Card>
        )}

        {/* Sezione Account Paziente */}
        <Card style={styles.sectionCard}>
          <Card.Content>
            <View style={styles.sectionHeader}>
              <Avatar.Icon
                size={24}
                icon="account-circle"
                style={styles.sectionIcon}
              />
              <Text style={styles.sectionTitle}>Account Paziente</Text>
            </View>
            <Divider style={styles.divider} />

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Nome:</Text>
              <Text style={styles.infoValue}>
                {getUserDisplayName(user)}
              </Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Email:</Text>
              <Text style={styles.infoValue}>
                {user?.email || 'Non disponibile'}
              </Text>
            </View>

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
              <Text style={styles.infoLabel}>Indirizzo:</Text>
              <Text style={styles.infoValue}>
                {getUserField(user, ['indirizzo', 'address'])}
              </Text>
            </View>
          </Card.Content>
        </Card>

        {/* Sezione Pazienti Associati */}
        {patients && patients.length > 1 && (
          <Card style={styles.sectionCard}>
            <Card.Content>
              <View style={styles.sectionHeader}>
                <Avatar.Icon
                  size={24}
                  icon="account-group"
                  style={styles.sectionIcon}
                />
                <Text style={styles.sectionTitle}>Pazienti Associati</Text>
              </View>
              <Divider style={styles.divider} />

              <View style={styles.statsContainer}>
                <View style={styles.statItem}>
                  <Text style={styles.statNumber}>{patients.length}</Text>
                  <Text style={styles.statLabel}>Totale</Text>
                </View>

                <View style={styles.statItem}>
                  <Text style={styles.statNumber}>
                    {patients.filter(p => p.has_parental_authority).length}
                  </Text>
                  <Text style={styles.statLabel}>Con Autorità</Text>
                </View>
              </View>

              {patients.map((patient, index) => (
                <View
                  key={patient.patient_id}
                  style={[
                    styles.patientListItem,
                    currentPatient?.patient_id === patient.patient_id &&
                      styles.patientListItemActive,
                  ]}>
                  <Text style={styles.patientListName}>
                    {patient.patient_name}
                  </Text>
                  <Text style={styles.patientListRelation}>
                    {getRelationshipLabel(patient.relationship)}
                  </Text>
                </View>
              ))}
            </Card.Content>
          </Card>
        )}

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
  // Sezione Paziente Selezionato
  patientCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 3,
    backgroundColor: '#E3F2FD',
  },
  patientHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  patientAvatar: {
    marginRight: 16,
  },
  patientHeaderInfo: {
    flex: 1,
  },
  patientName: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1565C0',
    marginBottom: 4,
  },
  relationshipBadge: {
    backgroundColor: '#1565C0',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    alignSelf: 'flex-start',
    marginBottom: 4,
  },
  relationshipText: {
    fontSize: 12,
    color: '#fff',
    fontWeight: '500',
  },
  parentalAuthority: {
    fontSize: 11,
    color: '#1565C0',
    fontStyle: 'italic',
  },
  patientDivider: {
    marginTop: 16,
    marginBottom: 12,
  },
  patientDetails: {
    marginTop: 4,
  },
  // Sezione Card
  sectionCard: {
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  sectionIcon: {
    backgroundColor: '#E0E0E0',
    marginRight: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  divider: {
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
    paddingVertical: 4,
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
    color: '#333',
  },
  // Statistiche
  statsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    marginBottom: 16,
    paddingVertical: 8,
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
  // Lista pazienti
  patientListItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 12,
    borderRadius: 8,
    marginBottom: 8,
    backgroundColor: '#F5F5F5',
  },
  patientListItemActive: {
    backgroundColor: '#E3F2FD',
    borderWidth: 1,
    borderColor: '#2196F3',
  },
  patientListName: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
  },
  patientListRelation: {
    fontSize: 12,
    color: '#666',
  },
  // Logout
  logoutSection: {
    marginTop: 8,
    marginBottom: 32,
  },
  logoutButton: {
    borderRadius: 8,
  },
});

export default PatientProfileScreen;
