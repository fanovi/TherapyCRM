import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  TextInput,
  HelperText,
  ActivityIndicator,
  RadioButton,
  Divider,
} from 'react-native-paper';
import ScreenTemplate from '../../components/ScreenTemplate';
import {useCurrentPatient} from '../../hooks/useCurrentPatient';
import {getRequestTypes, createRequest} from '../../api/requests';

const CreateRequestScreen = ({navigation}) => {
  const {currentPatient, patientId} = useCurrentPatient();
  const hasSelectedPatient = !!currentPatient; // Valore primitivo invece di funzione

  // Stati per tipologie richieste
  const [requestTypes, setRequestTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedType, setSelectedType] = useState(null);

  // Stati per form
  const [formData, setFormData] = useState({
    notes: '',
    therapeutic_plan_id: '',
    therapy_id: '',
  });

  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (hasSelectedPatient && patientId) {
      loadRequestTypes();
    } else {
      setLoading(false);
    }
  }, [hasSelectedPatient, patientId]);

  const loadRequestTypes = async () => {
    try {
      setLoading(true);
      const response = await getRequestTypes();

      if (response.success) {
        setRequestTypes(response.data);
      }
    } catch (error) {
      console.error('Errore caricamento tipologie:', error);
      Alert.alert('Errore', 'Impossibile caricare le tipologie di richieste');
    } finally {
      setLoading(false);
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!selectedType) {
      newErrors.type = 'Seleziona una tipologia di richiesta';
    }

    if (!patientId) {
      newErrors.patient = 'Nessun paziente selezionato';
    }

    // Validazione dinamica basata sui requisiti del tipo
    if (selectedType) {
      if (selectedType.require_notes && !formData.notes.trim()) {
        newErrors.notes = 'Le note sono obbligatorie per questa tipologia';
      }

      if (
        selectedType.therapeutic_plan_rule === 'required' &&
        !formData.therapeutic_plan_id
      ) {
        newErrors.therapeutic_plan_id =
          'Il piano terapeutico è obbligatorio per questa tipologia';
      }

      if (selectedType.require_therapy_assignment && !formData.therapy_id) {
        newErrors.therapy_id =
          "L'assegnazione terapia è obbligatoria per questa tipologia";
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    if (!validateForm()) {
      return;
    }

    try {
      setSubmitting(true);

      const requestData = {
        request_type_id: selectedType.id,
        patient_id: patientId,
        notes: formData.notes.trim() || null,
      };

      // Aggiungi campi opzionali solo se specificati
      if (formData.therapeutic_plan_id) {
        requestData.therapeutic_plan_id = parseInt(
          formData.therapeutic_plan_id,
        );
      }

      if (formData.therapy_id) {
        requestData.therapy_id = parseInt(formData.therapy_id);
      }

      console.log('Invio richiesta:', requestData);

      const response = await createRequest(requestData);

      if (response.success) {
        Alert.alert(
          'Richiesta Creata',
          response.message || 'La tua richiesta è stata inviata con successo!',
          [
            {
              text: 'OK',
              onPress: () => navigation.goBack(),
            },
          ],
        );
      }
    } catch (error) {
      console.error('Errore creazione richiesta:', error);

      // Gestisci errori specifici del backend
      if (error.response?.data?.code === 'MISSING_REQUIRED_FIELD') {
        const details = error.response.data.details;
        if (details) {
          setErrors(details);
          Alert.alert('Errore di Validazione', 'Controlla i campi evidenziati');
          return;
        }
      }

      if (error.response?.data?.code === 'ACCESS_DENIED') {
        Alert.alert('Accesso Negato', error.response.data.error);
        return;
      }

      if (error.response?.data?.code === 'INVALID_REQUEST_TYPE') {
        Alert.alert('Errore', 'Tipologia di richiesta non valida');
        return;
      }

      Alert.alert(
        'Errore',
        'Impossibile creare la richiesta. Riprova più tardi.',
      );
    } finally {
      setSubmitting(false);
    }
  };

  const getCategoryIcon = category => {
    const icons = {
      medical: 'medical-bag',
      therapy: 'account-heart',
      fitness: 'dumbbell',
      appointment: 'calendar-clock',
    };
    return icons[category] || 'file-document';
  };

  const getCategoryColor = category => {
    const colors = {
      medical: '#F44336',
      therapy: '#2196F3',
      fitness: '#FF9800',
      appointment: '#4CAF50',
    };
    return colors[category] || '#9C27B0';
  };

  const renderRequestTypeCard = type => (
    <Card
      key={type.id}
      style={[
        styles.typeCard,
        selectedType?.id === type.id && styles.selectedTypeCard,
      ]}
      onPress={() => setSelectedType(type)}>
      <Card.Content style={styles.typeContent}>
        <RadioButton
          value={type.id.toString()}
          status={selectedType?.id === type.id ? 'checked' : 'unchecked'}
          onPress={() => setSelectedType(type)}
        />

        <Avatar.Icon
          size={40}
          icon={getCategoryIcon(type.category)}
          style={[
            styles.typeIcon,
            {backgroundColor: getCategoryColor(type.category) + '20'},
          ]}
        />

        <View style={styles.typeInfo}>
          <Text style={styles.typeName}>{type.name}</Text>
          <Text style={styles.typeDescription} numberOfLines={2}>
            {type.description}
          </Text>

          <View style={styles.typeMetadata}>
            <Text style={styles.categoryChip}>{type.category}</Text>
            {type.estimated_days && (
              <Text style={styles.estimatedDays}>
                ~{type.estimated_days} giorni
              </Text>
            )}
          </View>
        </View>
      </Card.Content>
    </Card>
  );

  const renderRequirements = () => {
    if (!selectedType) return null;

    const requirements = [];

    if (selectedType.require_notes) {
      requirements.push('Note obbligatorie');
    }

    if (selectedType.therapeutic_plan_rule === 'required') {
      requirements.push('Piano terapeutico richiesto');
    }

    if (selectedType.require_therapy_assignment) {
      requirements.push('Assegnazione terapia richiesta');
    }

    if (requirements.length === 0) {
      return null;
    }

    return (
      <Card style={styles.requirementsCard}>
        <Card.Content>
          <Text style={styles.requirementsTitle}>
            Requisiti per questa tipologia:
          </Text>
          {requirements.map((req, index) => (
            <Text key={index} style={styles.requirementItem}>
              • {req}
            </Text>
          ))}
        </Card.Content>
      </Card>
    );
  };

  // Mostra messaggio se nessun paziente è selezionato
  if (!hasSelectedPatient) {
    return (
      <ScreenTemplate title="Nuova Richiesta">
        <View style={styles.noPatientContainer}>
          <Avatar.Icon
            size={80}
            icon="account-alert"
            style={styles.noPatientIcon}
          />
          <Text style={styles.noPatientTitle}>Nessun Paziente Selezionato</Text>
          <Text style={styles.noPatientSubtitle}>
            Seleziona un paziente per creare una richiesta
          </Text>
          <Button
            mode="contained"
            onPress={() => navigation.navigate('PatientSelection')}
            style={styles.selectPatientButton}>
            Seleziona Paziente
          </Button>
        </View>
      </ScreenTemplate>
    );
  }

  return (
    <ScreenTemplate
      title="Nuova Richiesta"
      subtitle={
        currentPatient
          ? `${currentPatient.first_name} ${currentPatient.last_name}`
          : ''
      }>
      <KeyboardAvoidingView
        style={styles.container}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
        <ScrollView style={styles.scrollContainer}>
          {loading ? (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" />
              <Text style={styles.loadingText}>Caricamento tipologie...</Text>
            </View>
          ) : (
            <>
              {/* Selezione Tipologia */}
              <Card style={styles.sectionCard}>
                <Card.Content>
                  <Text style={styles.sectionTitle}>
                    Seleziona Tipologia Richiesta
                  </Text>
                  <HelperText type="info" style={styles.sectionHelper}>
                    Scegli il tipo di documento o certificato che desideri
                    richiedere
                  </HelperText>

                  {requestTypes.map(renderRequestTypeCard)}

                  {errors.type && (
                    <HelperText type="error">{errors.type}</HelperText>
                  )}
                </Card.Content>
              </Card>

              {/* Requisiti */}
              {renderRequirements()}

              {/* Form Dettagli */}
              {selectedType && (
                <Card style={styles.sectionCard}>
                  <Card.Content>
                    <Text style={styles.sectionTitle}>Dettagli Richiesta</Text>

                    {/* Note */}
                    <TextInput
                      label={
                        selectedType.require_notes
                          ? 'Note (Obbligatorie)'
                          : 'Note (Opzionali)'
                      }
                      value={formData.notes}
                      onChangeText={text =>
                        setFormData(prev => ({...prev, notes: text}))
                      }
                      multiline
                      numberOfLines={4}
                      style={styles.textInput}
                      error={!!errors.notes}
                      placeholder="Inserisci eventuali note o dettagli aggiuntivi..."
                    />
                    {errors.notes && (
                      <HelperText type="error">{errors.notes}</HelperText>
                    )}

                    {/* Piano Terapeutico */}
                    {selectedType.therapeutic_plan_rule !== 'not_allowed' && (
                      <>
                        <TextInput
                          label={
                            selectedType.therapeutic_plan_rule === 'required'
                              ? 'ID Piano Terapeutico (Obbligatorio)'
                              : 'ID Piano Terapeutico (Opzionale)'
                          }
                          value={formData.therapeutic_plan_id}
                          onChangeText={text =>
                            setFormData(prev => ({
                              ...prev,
                              therapeutic_plan_id: text,
                            }))
                          }
                          keyboardType="numeric"
                          style={styles.textInput}
                          error={!!errors.therapeutic_plan_id}
                          placeholder="Inserisci l'ID del piano terapeutico"
                        />
                        {errors.therapeutic_plan_id && (
                          <HelperText type="error">
                            {errors.therapeutic_plan_id}
                          </HelperText>
                        )}
                      </>
                    )}

                    {/* Terapia */}
                    {selectedType.require_therapy_assignment && (
                      <>
                        <TextInput
                          label="ID Terapia (Obbligatorio)"
                          value={formData.therapy_id}
                          onChangeText={text =>
                            setFormData(prev => ({...prev, therapy_id: text}))
                          }
                          keyboardType="numeric"
                          style={styles.textInput}
                          error={!!errors.therapy_id}
                          placeholder="Inserisci l'ID della terapia"
                        />
                        {errors.therapy_id && (
                          <HelperText type="error">
                            {errors.therapy_id}
                          </HelperText>
                        )}
                      </>
                    )}
                  </Card.Content>
                </Card>
              )}

              {/* Pulsanti Azione */}
              <View style={styles.actionsContainer}>
                <Button
                  mode="outlined"
                  onPress={() => navigation.goBack()}
                  style={styles.cancelButton}
                  disabled={submitting}>
                  Annulla
                </Button>

                <Button
                  mode="contained"
                  onPress={handleSubmit}
                  loading={submitting}
                  disabled={!selectedType || submitting}
                  style={styles.submitButton}>
                  {submitting ? 'Invio...' : 'Invia Richiesta'}
                </Button>
              </View>

              <View style={styles.bottomSpacing} />
            </>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContainer: {
    flex: 1,
    padding: 16,
  },
  noPatientContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  noPatientIcon: {
    backgroundColor: '#FF9800',
    marginBottom: 24,
  },
  noPatientTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 12,
    textAlign: 'center',
  },
  noPatientSubtitle: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 32,
  },
  selectPatientButton: {
    paddingHorizontal: 24,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#666',
  },
  sectionCard: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  sectionHelper: {
    marginBottom: 16,
  },
  typeCard: {
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E0E0E0',
  },
  selectedTypeCard: {
    borderColor: '#2196F3',
    borderWidth: 2,
  },
  typeContent: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
  },
  typeIcon: {
    marginLeft: 8,
    marginRight: 16,
  },
  typeInfo: {
    flex: 1,
  },
  typeName: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  typeDescription: {
    fontSize: 14,
    color: '#666',
    marginBottom: 8,
  },
  typeMetadata: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  categoryChip: {
    fontSize: 12,
    color: '#2196F3',
    backgroundColor: '#E3F2FD',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
    textTransform: 'capitalize',
  },
  estimatedDays: {
    fontSize: 12,
    color: '#FF9800',
    fontWeight: '500',
  },
  requirementsCard: {
    marginBottom: 16,
    backgroundColor: '#FFF3E0',
  },
  requirementsTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 8,
    color: '#F57C00',
  },
  requirementItem: {
    fontSize: 14,
    color: '#E65100',
    marginBottom: 4,
  },
  textInput: {
    marginBottom: 8,
  },
  actionsContainer: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 24,
  },
  cancelButton: {
    flex: 1,
  },
  submitButton: {
    flex: 2,
  },
  bottomSpacing: {
    height: 32,
  },
});

export default CreateRequestScreen;
