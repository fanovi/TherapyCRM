import React, {useState} from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  Modal,
  FlatList,
  StyleSheet,
  Alert,
} from 'react-native';
import {useDispatch, useSelector} from 'react-redux';
import {selectPatient} from '../slices/patientSlice';

const PatientSelector = ({style}) => {
  const dispatch = useDispatch();
  const {patients, currentPatient} = useSelector(state => state.patient);
  const {user} = useSelector(state => state.auth);
  const [modalVisible, setModalVisible] = useState(false);

  const handlePatientSelect = patient => {
    dispatch(selectPatient(patient.patient_id || patient.account_patient_id));
    setModalVisible(false);
  };

  const renderPatientItem = ({item}) => (
    <TouchableOpacity
      style={[
        styles.patientItem,
        currentPatient?.patient_id === item.patient_id &&
          styles.selectedPatient,
      ]}
      onPress={() => handlePatientSelect(item)}>
      <View style={styles.patientInfo}>
        <Text style={styles.patientName}>{item.patient_name}</Text>
        <Text style={styles.patientRelation}>
          Relazione:{' '}
          {item.relationship === 'parent' ? 'Genitore' : item.relationship}
        </Text>
        {item.has_parental_authority && (
          <Text style={styles.parentalAuthority}>✓ Autorità parentale</Text>
        )}
      </View>
      {currentPatient?.patient_id === item.patient_id && (
        <Text style={styles.checkmark}>✓</Text>
      )}
    </TouchableOpacity>
  );

  // Mostra sempre il benvenuto dell'utente, anche senza pazienti
  if (!user) {
    return null;
  }

  // Se non ci sono pazienti, mostra solo il benvenuto
  if (!patients || patients.length === 0) {
    return (
      <View style={[styles.container, style]}>
        <View style={styles.userHeader}>
          <Text style={styles.welcomeText}>
            Benvenuto, {user?.firstName} {user?.lastName}
          </Text>
          <Text style={styles.noPatientText}>
            Nessun paziente associato al tuo account
          </Text>
        </View>
      </View>
    );
  }

  return (
    <View style={[styles.container, style]}>
      {/* Header con nome utente */}
      <View style={styles.userHeader}>
        <Text style={styles.welcomeText}>
          Benvenuto, {user?.firstName} {user?.lastName}
        </Text>
      </View>

      {/* Paziente attivo */}
      <TouchableOpacity
        style={styles.currentPatientContainer}
        onPress={() => patients.length > 1 && setModalVisible(true)}
        disabled={patients.length <= 1}>
        <View style={styles.currentPatientInfo}>
          <Text style={styles.currentPatientLabel}>Paziente attivo:</Text>
          <Text style={styles.currentPatientName}>
            {currentPatient?.patient_name || 'Nessun paziente selezionato'}
          </Text>
        </View>
        {patients.length > 1 && (
          <Text style={styles.changeIndicator}>Tocca per cambiare ▼</Text>
        )}
      </TouchableOpacity>

      {/* Modal per selezione paziente */}
      <Modal
        animationType="slide"
        transparent={true}
        visible={modalVisible}
        onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Seleziona Paziente</Text>
              <TouchableOpacity
                style={styles.closeButton}
                onPress={() => setModalVisible(false)}>
                <Text style={styles.closeButtonText}>✕</Text>
              </TouchableOpacity>
            </View>

            <FlatList
              data={patients}
              renderItem={renderPatientItem}
              keyExtractor={item =>
                item.patient_id?.toString() ||
                item.account_patient_id?.toString()
              }
              style={styles.patientList}
            />
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 3.84,
    elevation: 5,
  },
  userHeader: {
    marginBottom: 12,
  },
  welcomeText: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
  },
  currentPatientContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  currentPatientInfo: {
    flex: 1,
  },
  currentPatientLabel: {
    fontSize: 14,
    color: '#666',
    marginBottom: 4,
  },
  currentPatientName: {
    fontSize: 16,
    fontWeight: '500',
    color: '#2196F3',
  },
  changeIndicator: {
    fontSize: 12,
    color: '#999',
    fontStyle: 'italic',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContainer: {
    backgroundColor: '#fff',
    borderRadius: 12,
    width: '90%',
    maxHeight: '70%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
  },
  closeButton: {
    padding: 4,
  },
  closeButtonText: {
    fontSize: 18,
    color: '#666',
  },
  patientList: {
    maxHeight: 300,
  },
  patientItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  selectedPatient: {
    backgroundColor: '#e3f2fd',
  },
  patientInfo: {
    flex: 1,
  },
  patientName: {
    fontSize: 16,
    fontWeight: '500',
    color: '#333',
    marginBottom: 4,
  },
  patientRelation: {
    fontSize: 14,
    color: '#666',
    marginBottom: 2,
  },
  parentalAuthority: {
    fontSize: 12,
    color: '#4CAF50',
    fontWeight: '500',
  },
  checkmark: {
    fontSize: 18,
    color: '#2196F3',
    fontWeight: 'bold',
  },
  noPatientText: {
    fontSize: 14,
    color: '#999',
    fontStyle: 'italic',
    marginTop: 4,
  },
});

export default PatientSelector;
