import React, {useState} from 'react';
import {View, StyleSheet, ScrollView, Alert} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  useTheme,
  ActivityIndicator,
} from 'react-native-paper';
import {useSelector} from 'react-redux';
import ScreenTemplate from '../../components/ScreenTemplate';
import {submitComplaint} from '../../api/complaints';

const ComplaintsScreen = ({navigation}) => {
  const theme = useTheme();
  const {user} = useSelector(state => state.auth);
  const {currentPatient} = useSelector(state => state.patient);

  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    // Validazione
    if (!title.trim()) {
      Alert.alert('Errore', 'Inserisci un titolo per il reclamo');
      return;
    }
    if (!description.trim()) {
      Alert.alert('Errore', 'Inserisci una descrizione per il reclamo');
      return;
    }

    const patientId =
      currentPatient?.patient_id || currentPatient?.account_patient_id;
    if (!patientId) {
      Alert.alert('Errore', 'Nessun paziente selezionato');
      return;
    }

    setLoading(true);
    try {
      const response = await submitComplaint({
        title: title.trim(),
        description: description.trim(),
        account_id: user?.id,
        patient_id: patientId,
      });

      if (response.success) {
        Alert.alert(
          'Reclamo Inviato',
          'Il tuo reclamo è stato inviato con successo. Riceverai una risposta il prima possibile.',
          [
            {
              text: 'OK',
              onPress: () => navigation.goBack(),
            },
          ],
        );
      } else {
        Alert.alert(
          'Errore',
          response.message || 'Impossibile inviare il reclamo',
        );
      }
    } catch (error) {
      Alert.alert(
        'Errore',
        error.message ||
          "Si è verificato un errore durante l'invio del reclamo",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScreenTemplate title="Reclami">
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Info paziente di riferimento */}
        <Card style={styles.patientCard}>
          <Card.Content>
            <Text style={styles.patientLabel}>Paziente di riferimento:</Text>
            <Text style={styles.patientName}>
              {currentPatient?.patient_name || 'Nessun paziente selezionato'}
            </Text>
          </Card.Content>
        </Card>

        {/* Form reclamo */}
        <Card style={styles.formCard}>
          <Card.Content>
            <Text style={styles.formTitle}>Nuovo Reclamo</Text>

            <TextInput
              label="Titolo"
              value={title}
              onChangeText={setTitle}
              mode="outlined"
              style={styles.input}
              placeholder="Inserisci il titolo del reclamo"
              maxLength={200}
            />

            <TextInput
              label="Descrizione"
              value={description}
              onChangeText={setDescription}
              mode="outlined"
              multiline
              numberOfLines={8}
              style={styles.textArea}
              placeholder="Descrivi nel dettaglio il motivo del reclamo..."
              maxLength={2000}
            />

            <Text style={styles.charCount}>
              {description.length}/2000 caratteri
            </Text>

            <Button
              mode="contained"
              onPress={handleSubmit}
              disabled={loading || !title.trim() || !description.trim()}
              style={styles.submitButton}
              icon={loading ? undefined : 'send'}>
              {loading ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                'Invia Reclamo'
              )}
            </Button>
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
  patientCard: {
    marginBottom: 16,
    borderRadius: 12,
    backgroundColor: '#E3F2FD',
  },
  patientLabel: {
    fontSize: 14,
    color: '#666',
    marginBottom: 4,
  },
  patientName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1565C0',
  },
  formCard: {
    borderRadius: 12,
    marginBottom: 24,
  },
  formTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 16,
  },
  input: {
    marginBottom: 16,
  },
  textArea: {
    marginBottom: 8,
    minHeight: 150,
  },
  charCount: {
    fontSize: 12,
    color: '#999',
    textAlign: 'right',
    marginBottom: 16,
  },
  submitButton: {
    borderRadius: 8,
    paddingVertical: 4,
  },
});

export default ComplaintsScreen;
