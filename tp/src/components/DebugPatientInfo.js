import React from 'react';
import {View, Text, StyleSheet} from 'react-native';
import {useSelector} from 'react-redux';

const DebugPatientInfo = () => {
  const {user} = useSelector(state => state.auth);
  const {patients, currentPatient} = useSelector(state => state.patient);

  return (
    <View style={styles.container}>
      <Text style={styles.title}>🔍 DEBUG INFO</Text>

      <Text style={styles.section}>USER DATA:</Text>
      <Text>Nome: {user?.firstName || 'N/A'}</Text>
      <Text>Cognome: {user?.lastName || 'N/A'}</Text>
      <Text>Email: {user?.email || 'N/A'}</Text>
      <Text>User Type: {user?.user_type || user?.role || 'N/A'}</Text>
      <Text>Patients in user: {user?.patients?.length || 0}</Text>

      <Text style={styles.section}>PATIENT STORE:</Text>
      <Text>Patients count: {patients?.length || 0}</Text>
      <Text>Current patient: {currentPatient?.patient_name || 'None'}</Text>

      {patients && patients.length > 0 && (
        <View>
          <Text style={styles.section}>PATIENTS LIST:</Text>
          {patients.map((patient, index) => (
            <Text key={index}>
              {index + 1}. {patient.patient_name} (ID: {patient.patient_id})
            </Text>
          ))}
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#f0f0f0',
    padding: 16,
    margin: 16,
    borderRadius: 8,
    borderWidth: 2,
    borderColor: '#ff0000',
  },
  title: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#ff0000',
    marginBottom: 8,
  },
  section: {
    fontSize: 14,
    fontWeight: 'bold',
    marginTop: 12,
    marginBottom: 4,
    color: '#333',
  },
});

export default DebugPatientInfo;
