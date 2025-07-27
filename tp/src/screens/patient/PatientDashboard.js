import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import {useSelector} from 'react-redux';

import {usePatientLogin} from '../../hooks/usePatientLogin';

const PatientDashboard = ({navigation}) => {
  const {user} = useSelector(state => state.auth);
  const {currentPatient} = useSelector(state => state.patient);
  const {logout} = usePatientLogin();

  const handleLogout = async () => {
    await logout();
    // Navigation sarà gestita dal navigator principale
  };

  return (
    <ScrollView style={styles.container}>
      {/* Contenuto specifico del paziente */}
      {currentPatient && (
        <View style={styles.patientContent}>
          <Text style={styles.sectionTitle}>
            Informazioni di {currentPatient.patient_name}
          </Text>

          <View style={styles.infoCard}>
            <Text style={styles.infoLabel}>ID Paziente:</Text>
            <Text style={styles.infoValue}>
              {currentPatient.patient_id || currentPatient.account_patient_id}
            </Text>
          </View>

          <View style={styles.infoCard}>
            <Text style={styles.infoLabel}>Relazione:</Text>
            <Text style={styles.infoValue}>
              {currentPatient.relationship === 'parent'
                ? 'Genitore'
                : currentPatient.relationship}
            </Text>
          </View>

          {currentPatient.has_parental_authority && (
            <View style={styles.infoCard}>
              <Text style={styles.authorityText}>
                ✓ Hai l'autorità parentale per questo paziente
              </Text>
            </View>
          )}

          {/* Sezioni specifiche dell'app */}
          <View style={styles.actionsContainer}>
            <TouchableOpacity style={styles.actionButton}>
              <Text style={styles.actionButtonText}>
                Visualizza Appuntamenti
              </Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.actionButton}>
              <Text style={styles.actionButtonText}>Storico Visite</Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.actionButton}>
              <Text style={styles.actionButtonText}>Documenti</Text>
            </TouchableOpacity>
          </View>
        </View>
      )}

      {/* Pulsante logout */}
      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Text style={styles.logoutButtonText}>Logout</Text>
      </TouchableOpacity>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    padding: 16,
  },
  patientContent: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '600',
    color: '#333',
    marginBottom: 16,
  },
  infoCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  infoLabel: {
    fontSize: 16,
    color: '#666',
    fontWeight: '500',
  },
  infoValue: {
    fontSize: 16,
    color: '#333',
    fontWeight: '400',
  },
  authorityText: {
    fontSize: 14,
    color: '#4CAF50',
    fontWeight: '500',
    textAlign: 'center',
  },
  actionsContainer: {
    marginTop: 20,
  },
  actionButton: {
    backgroundColor: '#2196F3',
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 8,
    marginBottom: 12,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '500',
    textAlign: 'center',
  },
  logoutButton: {
    backgroundColor: '#f44336',
    paddingVertical: 12,
    paddingHorizontal: 20,
    borderRadius: 8,
    marginBottom: 20,
  },
  logoutButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '500',
    textAlign: 'center',
  },
});

export default PatientDashboard;
