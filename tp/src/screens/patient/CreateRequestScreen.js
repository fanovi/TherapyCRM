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
import {useSelector} from 'react-redux';
// DatePicker sostituito con Modal e DatePickerIOS/DatePickerAndroid

import ScreenTemplate from '../../components/ScreenTemplate';
import {getRequestTypes, createRequest} from '../../api/requests';

const CreateRequestScreen = ({navigation}) => {
  const {currentPatient} = useSelector(state => state.patient);

  // Stati per tipologie richieste
  const [requestTypes, setRequestTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedType, setSelectedType] = useState(null);

  // Stati per form
  const [formData, setFormData] = useState({
    reason: '',
    notes: '',
    date_from: new Date(),
    date_to: new Date(),
  });

  const [showDateFromPicker, setShowDateFromPicker] = useState(false);
  const [showDateToPicker, setShowDateToPicker] = useState(false);
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    loadRequestTypes();
  }, []);

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

    if (selectedType?.requires_reason && !formData.reason.trim()) {
      newErrors.reason = 'Il motivo è obbligatorio per questa tipologia';
    }

    if (selectedType?.requires_date_range) {
      if (formData.date_from >= formData.date_to) {
        newErrors.date_range =
          'La data di fine deve essere successiva alla data di inizio';
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
        type_id: selectedType.id,
        reason: formData.reason.trim(),
        notes: formData.notes.trim(),
      };

      if (selectedType.requires_date_range) {
        requestData.date_from = formData.date_from.toISOString().split('T')[0];
        requestData.date_to = formData.date_to.toISOString().split('T')[0];
      }

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
      Alert.alert(
        'Errore',
        'Impossibile creare la richiesta. Riprova più tardi.',
      );
    } finally {
      setSubmitting(false);
    }
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
          icon={type.icon}
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
            <Text style={styles.estimatedDays}>
              📅 {type.estimated_days} giorni
            </Text>
            {type.requires_reason && (
              <Text style={styles.requiresReason}>📝 Motivo richiesto</Text>
            )}
            {type.requires_date_range && (
              <Text style={styles.requiresDateRange}>📆 Date richieste</Text>
            )}
          </View>
        </View>
      </Card.Content>
    </Card>
  );

  const getCategoryColor = category => {
    const colors = {
      medical: '#4CAF50',
      therapy: '#2196F3',
      fitness: '#FF9800',
      appointment: '#9C27B0',
    };
    return colors[category] || '#9E9E9E';
  };

  const formatDate = date => {
    return date.toLocaleDateString('it-IT');
  };

  if (loading) {
    return (
      <ScreenTemplate title="Nuova Richiesta">
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" />
          <Text style={styles.loadingText}>
            Caricamento tipologie richieste...
          </Text>
        </View>
      </ScreenTemplate>
    );
  }

  return (
    <ScreenTemplate
      title="Nuova Richiesta"
      subtitle="Seleziona il tipo di documento o certificato">
      <KeyboardAvoidingView
        style={styles.container}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
        <ScrollView style={styles.scrollView}>
          {/* Selezione Tipologia */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Tipologia Richiesta</Text>
            <Text style={styles.sectionSubtitle}>
              Scegli il tipo di documento o certificato di cui hai bisogno
            </Text>

            {errors.type && (
              <HelperText type="error" visible={true}>
                {errors.type}
              </HelperText>
            )}

            <View style={styles.typesList}>
              {requestTypes.map(renderRequestTypeCard)}
            </View>
          </View>

          {/* Form Dinamico */}
          {selectedType && (
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Dettagli Richiesta</Text>

              {/* Motivo (se richiesto) */}
              {selectedType.requires_reason && (
                <View style={styles.formField}>
                  <TextInput
                    label="Motivo della richiesta *"
                    value={formData.reason}
                    onChangeText={text =>
                      setFormData(prev => ({...prev, reason: text}))
                    }
                    multiline
                    numberOfLines={3}
                    style={styles.textInput}
                    error={!!errors.reason}
                  />
                  {errors.reason && (
                    <HelperText type="error" visible={true}>
                      {errors.reason}
                    </HelperText>
                  )}
                </View>
              )}

              {/* Range Date (se richiesto) */}
              {selectedType.requires_date_range && (
                <View style={styles.formField}>
                  <Text style={styles.fieldLabel}>
                    Periodo di riferimento *
                  </Text>

                  <View style={styles.dateRow}>
                    <Button
                      mode="outlined"
                      icon="calendar"
                      onPress={() =>
                        Alert.alert(
                          'Date Picker',
                          'Funzionalità in sviluppo. Per ora vengono usate le date predefinite.',
                        )
                      }
                      style={styles.dateButton}>
                      Da: {formatDate(formData.date_from)}
                    </Button>

                    <Button
                      mode="outlined"
                      icon="calendar"
                      onPress={() =>
                        Alert.alert(
                          'Date Picker',
                          'Funzionalità in sviluppo. Per ora vengono usate le date predefinite.',
                        )
                      }
                      style={styles.dateButton}>
                      A: {formatDate(formData.date_to)}
                    </Button>
                  </View>

                  {errors.date_range && (
                    <HelperText type="error" visible={true}>
                      {errors.date_range}
                    </HelperText>
                  )}
                </View>
              )}

              {/* Note Aggiuntive */}
              <View style={styles.formField}>
                <TextInput
                  label="Note aggiuntive (opzionale)"
                  value={formData.notes}
                  onChangeText={text =>
                    setFormData(prev => ({...prev, notes: text}))
                  }
                  multiline
                  numberOfLines={2}
                  style={styles.textInput}
                />
                <HelperText type="info">
                  Aggiungi informazioni specifiche se necessario
                </HelperText>
              </View>

              <Divider style={styles.divider} />

              {/* Riepilogo */}
              <View style={styles.summarySection}>
                <Text style={styles.summaryTitle}>Riepilogo</Text>

                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Documento:</Text>
                  <Text style={styles.summaryValue}>{selectedType.name}</Text>
                </View>

                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabel}>Tempi stimati:</Text>
                  <Text style={styles.summaryValue}>
                    {selectedType.estimated_days} giorni lavorativi
                  </Text>
                </View>

                {formData.reason.trim() && (
                  <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Motivo:</Text>
                    <Text style={styles.summaryValue} numberOfLines={2}>
                      {formData.reason}
                    </Text>
                  </View>
                )}
              </View>
            </View>
          )}
        </ScrollView>

        {/* Pulsanti Azione */}
        {selectedType && (
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
              style={styles.submitButton}
              loading={submitting}
              disabled={submitting}>
              {submitting ? 'Invio...' : 'Invia Richiesta'}
            </Button>
          </View>
        )}

        {/* Date Pickers - Versione semplificata con Alert */}
        {/* Per una implementazione completa, installare react-native-date-picker */}
        {/* 
        Per ora le date vengono gestite tramite i pulsanti che mostrano la data corrente
        In una implementazione completa, sostituire con un DatePicker nativo
        */}
      </KeyboardAvoidingView>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8F9FA',
  },
  scrollView: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#666',
  },
  section: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
    marginBottom: 4,
  },
  sectionSubtitle: {
    fontSize: 14,
    color: '#666',
    marginBottom: 16,
  },
  typesList: {
    gap: 12,
  },
  typeCard: {
    borderRadius: 12,
    elevation: 2,
    borderWidth: 1,
    borderColor: 'transparent',
  },
  selectedTypeCard: {
    borderColor: '#2196F3',
    borderWidth: 2,
  },
  typeContent: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    paddingVertical: 8,
  },
  typeIcon: {
    marginHorizontal: 12,
  },
  typeInfo: {
    flex: 1,
  },
  typeName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
    marginBottom: 4,
  },
  typeDescription: {
    fontSize: 14,
    color: '#666',
    lineHeight: 20,
    marginBottom: 8,
  },
  typeMetadata: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  estimatedDays: {
    fontSize: 12,
    color: '#2196F3',
    fontWeight: '500',
  },
  requiresReason: {
    fontSize: 12,
    color: '#FF9800',
    fontWeight: '500',
  },
  requiresDateRange: {
    fontSize: 12,
    color: '#4CAF50',
    fontWeight: '500',
  },
  formField: {
    marginBottom: 16,
  },
  fieldLabel: {
    fontSize: 16,
    fontWeight: '500',
    color: '#333',
    marginBottom: 8,
  },
  textInput: {
    backgroundColor: '#FFFFFF',
  },
  dateRow: {
    flexDirection: 'row',
    gap: 12,
  },
  dateButton: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  divider: {
    marginVertical: 16,
  },
  summarySection: {
    backgroundColor: '#F5F5F5',
    padding: 16,
    borderRadius: 8,
  },
  summaryTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
    marginBottom: 12,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
    alignItems: 'flex-start',
  },
  summaryLabel: {
    fontSize: 14,
    color: '#666',
    flex: 0.4,
  },
  summaryValue: {
    fontSize: 14,
    color: '#333',
    fontWeight: '500',
    flex: 0.6,
    textAlign: 'right',
  },
  actionsContainer: {
    flexDirection: 'row',
    padding: 20,
    gap: 12,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#E0E0E0',
  },
  cancelButton: {
    flex: 1,
  },
  submitButton: {
    flex: 2,
  },
});

export default CreateRequestScreen;
