import React, {useState} from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  Modal,
  FlatList,
  StyleSheet,
  Animated,
} from 'react-native';
import {useDispatch, useSelector} from 'react-redux';
import {selectPatient} from '../slices/patientSlice';
import {useTheme} from 'react-native-paper';

const FloatingPatientSelector = () => {
  const dispatch = useDispatch();
  const {patients, currentPatient} = useSelector(state => state.patient);
  const [modalVisible, setModalVisible] = useState(false);
  const [animation] = useState(new Animated.Value(0));
  const theme = useTheme();

  React.useEffect(() => {
    Animated.spring(animation, {
      toValue: 1,
      useNativeDriver: true,
      tension: 50,
      friction: 8,
    }).start();
  }, []);

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

  if (!patients || patients.length <= 1) {
    return null;
  }

  return (
    <>
      {/* Floating Button */}
      <Animated.View
        style={[
          styles.floatingButton,
          {
            transform: [
              {
                scale: animation,
              },
            ],
          },
        ]}>
        <TouchableOpacity
          style={styles.floatingButtonInner}
          onPress={() => setModalVisible(true)}
          activeOpacity={0.8}>
          <Text style={styles.floatingButtonText}>👤</Text>
          <Text
            style={[styles.floatingButtonLabel, {color: theme.colors.primary}]}>
            Cambia
          </Text>
        </TouchableOpacity>
      </Animated.View>

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
              showsVerticalScrollIndicator={false}
            />
          </View>
        </View>
      </Modal>
    </>
  );
};

const styles = StyleSheet.create({
  floatingButton: {
    position: 'absolute',
    bottom: 20,
    right: 20,
    zIndex: 1000,
  },
  floatingButtonInner: {
    backgroundColor: 'white',
    borderRadius: 28,
    width: 56,
    height: 56,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.3,
    shadowRadius: 4.65,
    elevation: 8,
  },
  floatingButtonText: {
    fontSize: 20,
    color: '#fff',
  },
  floatingButtonLabel: {
    fontSize: 8,

    fontWeight: '500',
    marginTop: -2,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContainer: {
    backgroundColor: '#fff',
    borderRadius: 16,
    width: '90%',
    maxHeight: '70%',
    overflow: 'hidden',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
    backgroundColor: '#f8f9fa',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
  },
  closeButton: {
    padding: 8,
    borderRadius: 20,
    backgroundColor: '#e9ecef',
  },
  closeButtonText: {
    fontSize: 16,
    color: '#666',
    fontWeight: 'bold',
  },
  patientList: {
    maxHeight: 400,
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
    fontWeight: '600',
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
    fontSize: 20,
    color: '#2196F3',
    fontWeight: 'bold',
  },
});

export default FloatingPatientSelector;
