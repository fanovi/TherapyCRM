import React, {useState, useEffect} from 'react';
import {useSelector} from 'react-redux';
import {
  View,
  StyleSheet,
  Alert,
  KeyboardAvoidingView,
  Platform,
  TouchableWithoutFeedback,
  Keyboard,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  TextInput,
  TouchableRipple,
  useTheme,
} from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialIcons';
import ScreenTemplate from '../../components/ScreenTemplate';
import {getRequestTypes, createRequest} from '../../api/requests';

// Configurazione icone e colori per ogni tipo di richiesta
const REQUEST_TYPE_CONFIG = {
  'Copia Piano Terapeutico': {
    icon: 'description',
    color: '#E3F2FD', // Azzurro pastello
    textColor: '#1976D2',
  },
  'Relazione terapista': {
    icon: 'psychology',
    color: '#F3E5F5', // Viola pastello
    textColor: '#9C27B0',
  },
  'Relazione visita specialistica': {
    icon: 'medical-services',
    color: '#E8F5E9', // Verde pastello
    textColor: '#43A047',
  },
  'Attestato frequenza semplice': {
    icon: 'calendar-today',
    color: '#FFF3E0', // Arancione pastello
    textColor: '#EF6C00',
  },
  Altro: {
    icon: 'more-horiz',
    color: '#ECEFF1', // Grigio pastello
    textColor: '#546E7A',
  },
  'Attestato frequenza con orario': {
    icon: 'schedule',
    color: '#E1F5FE', // Celeste pastello
    textColor: '#0288D1',
  },
  'Attestato frequenza con date': {
    icon: 'event',
    color: '#F1F8E9', // Verde chiaro pastello
    textColor: '#7CB342',
  },
  'Attestato frequenza certificato lavoro': {
    icon: 'work',
    color: '#FBE9E7', // Rosso pastello
    textColor: '#D84315',
  },
};

const CreateRequestScreen = ({navigation}) => {
  const {patients, currentPatient} = useSelector(state => state.patient);
  const patient_id = currentPatient?.patient_id;

  const [requestTypes, setRequestTypes] = useState([]);
  const [selectedType, setSelectedType] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    request_type_id: '',
    therapeutic_plan_id: '',
    therapy_id: '',
    notes: '',
  });

  useEffect(() => {
    // Verifica che ci sia un paziente selezionato
    if (!patient_id) {
      Alert.alert(
        'Errore',
        'Nessun paziente selezionato. Impossibile procedere.',
        [{text: 'OK', onPress: () => navigation.goBack()}],
      );
      return;
    }

    loadRequestTypes();
  }, [patient_id]);

  const loadRequestTypes = async () => {
    try {
      if (!patient_id) {
        Alert.alert('Errore', 'ID paziente mancante');
        return;
      }

      const response = await getRequestTypes(patient_id);

      console.log('🔍 ANALISI DETTAGLIATA TIPI DI RICHIESTA:');
      console.log(JSON.stringify(response, null, 2));

      if (response.success) {
        setRequestTypes(response.data);

        // Controlla se ci sono errori di validazione
        if (
          response.validation_summary &&
          response.validation_summary.has_errors
        ) {
          console.log('⚠️ ERRORI DI VALIDAZIONE TROVATI:');
          console.log(
            JSON.stringify(response.validation_summary.errors_by_type, null, 2),
          );
        }

        // Log dati paziente
        if (response.patient_data) {
          console.log('👤 DATI PAZIENTE:');
          console.log(JSON.stringify(response.patient_data, null, 2));
        }
      }
    } catch (error) {
      console.error('❌ Errore caricamento tipologie:', error);

      // Gestisci errori specifici
      if (error.message?.includes('ID paziente mancante')) {
        Alert.alert(
          'Errore',
          'Seleziona un paziente prima di creare una richiesta',
        );
        navigation.goBack();
        return;
      }

      if (error.response?.status === 403) {
        Alert.alert(
          'Accesso Negato',
          'Non hai i permessi per accedere ai dati di questo paziente',
        );
        navigation.goBack();
        return;
      }

      if (error.response?.status === 404) {
        Alert.alert('Errore', 'Paziente non trovato');
        navigation.goBack();
        return;
      }

      Alert.alert('Errore', 'Impossibile caricare le tipologie di richieste');
    }
  };

  const validateForm = () => {
    if (!selectedType) {
      Alert.alert('Errore', 'Seleziona una tipologia di richiesta');
      return false;
    }

    // Verifica se il tipo è disponibile
    if (selectedType.is_available === false) {
      Alert.alert(
        'Errore',
        'Questa tipologia di richiesta non è disponibile per il paziente selezionato',
      );
      return false;
    }

    const errors = [];

    // Controllo piano terapeutico
    if (selectedType.is_therapeutic_plan_required) {
      if (!selectedType.therapeutic_plan_data) {
        errors.push(
          'Piano terapeutico obbligatorio non disponibile per questa tipologia',
        );
      } else if (!formData.therapeutic_plan_id) {
        errors.push('Piano terapeutico obbligatorio per questa tipologia');
      }
    }

    // Controllo assegnazione terapia
    if (selectedType.require_therapy_assignment) {
      if (
        !selectedType.therapies_data ||
        selectedType.therapies_data.length === 0
      ) {
        errors.push('Terapie non disponibili nel piano terapeutico');
      } else if (!formData.therapy_id) {
        errors.push('Selezione terapia obbligatoria per questa tipologia');
      }
    }

    // Controllo note
    if (selectedType.require_notes && !formData.notes.trim()) {
      errors.push('Note obbligatorie per questa tipologia');
    }

    if (errors.length > 0) {
      Alert.alert('Errore', errors.join('\n'));
      return false;
    }

    return true;
  };

  const handleSubmit = async () => {
    try {
      // Verifica che ci sia un paziente selezionato
      if (!patient_id) {
        Alert.alert(
          'Errore',
          'Nessun paziente selezionato. Impossibile procedere.',
        );
        return;
      }

      // Validazione specifica per Copia Piano Terapeutico
      if (selectedType.name === 'Copia Piano Terapeutico') {
        if (!formData.therapeutic_plan_id) {
          Alert.alert(
            'Campo Obbligatorio',
            'Per richiedere una copia del piano terapeutico, devi selezionare il piano terapeutico.',
          );
          return;
        }
      }

      setSubmitting(true);

      // Debug dei valori prima della preparazione della richiesta
      console.log('\n=== VALORI FORM ===');
      console.log('🔍 Selected Type:', selectedType);
      console.log('👤 Current Patient:', currentPatient);
      console.log('📝 Form Data:', formData);

      // Prepara i dati da inviare - converti undefined in null
      const requestData = {
        request_type_id: selectedType.id,
        patient_id: patient_id,
        therapeutic_plan_id: formData.therapeutic_plan_id || null,
        therapy_id: formData.therapy_id || null,
        notes: formData.notes.trim() || null,
      };

      // Log dettagliato dei dati che verranno inviati
      console.log('\n=== DATI DA INVIARE AL BACKEND ===');
      console.log('📤 Request Data:', {
        ...requestData,
        request_type_id: `${
          requestData.request_type_id
        } (${typeof requestData.request_type_id})`,
        patient_id: `${
          requestData.patient_id
        } (${typeof requestData.patient_id})`,
        therapeutic_plan_id: `${
          requestData.therapeutic_plan_id
        } (${typeof requestData.therapeutic_plan_id})`,
        therapy_id: `${
          requestData.therapy_id
        } (${typeof requestData.therapy_id})`,
        notes: `${requestData.notes} (${typeof requestData.notes})`,
      });

      // Effettua la richiesta
      const response = await createRequest(requestData);

      // Log della risposta
      console.log('\n=== RISPOSTA DAL BACKEND ===');
      console.log('✅ Response:', response);
      console.log('📊 Response Data:', response.data);
      console.log('🔑 Response Status:', response.status);

      Alert.alert(
        'Successo',
        'Richiesta inviata con successo!',
        [
          {
            text: 'OK',
            onPress: () => navigation.goBack(),
          },
        ],
        {cancelable: false},
      );
    } catch (error) {
      console.log('\n=== ERRORE DETTAGLIATO ===');
      console.log('❌ Errore completo:', error);
      console.log('📄 Response data:', error.response?.data);
      console.log('🔢 Status code:', error.response?.status);
      console.log('📋 Headers:', error.response?.headers);
      console.log('💭 Message:', error.message);
      console.log('🔍 Stack:', error.stack);

      // Gestione errori specifica per requisiti mancanti
      if (error.message?.includes('Requisiti obbligatori')) {
        Alert.alert(
          'Campi Obbligatori',
          'Per questa tipologia di richiesta, assicurati di aver compilato tutti i campi obbligatori:\n\n' +
            '- Piano Terapeutico\n' +
            (selectedType?.require_notes ? '- Note\n' : '') +
            (selectedType?.require_therapy_assignment ? '- Terapia\n' : ''),
        );
        return;
      }

      Alert.alert(
        'Errore',
        error.message ||
          "Si è verificato un errore durante l'invio della richiesta",
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

  const handleTypeSelect = type => {
    setSelectedType(type);

    // Reset form data quando si cambia tipo
    setFormData({
      request_type_id: type.id,
      therapeutic_plan_id: '',
      therapy_id: '',
      notes: '',
    });

    // Se il tipo ha un piano terapeutico disponibile, pre-selezionalo
    if (type.therapeutic_plan_data) {
      setFormData(prev => ({
        ...prev,
        therapeutic_plan_id: type.therapeutic_plan_data.id.toString(),
      }));
    }
  };

  const handleInputChange = (field, value) => {
    setFormData({...formData, [field]: value});
  };

  // Mostra messaggio se nessun paziente è selezionato
  if (!currentPatient) {
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
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.container}>
        <TouchableWithoutFeedback onPress={Keyboard.dismiss}>
          <View style={styles.innerContainer}>
            {/* Lista dei tipi di richiesta */}
            <View style={styles.requestTypesContainer}>
              <Text style={styles.sectionTitle}>
                Seleziona il tipo di richiesta:
              </Text>
              {requestTypes.map((type, index) => {
                const config = REQUEST_TYPE_CONFIG[type.name] || {
                  icon: 'description',
                  color: '#ECEFF1',
                  textColor: '#546E7A',
                };

                const isAvailable = type.is_available !== false;
                const hasErrors =
                  type.validation_errors && type.validation_errors.length > 0;

                return (
                  <TouchableRipple
                    key={`${type.id}-${index}`}
                    onPress={() =>
                      isAvailable ? handleTypeSelect(type) : null
                    }
                    disabled={!isAvailable}
                    style={[
                      styles.requestTypeCard,
                      {
                        backgroundColor: isAvailable ? config.color : '#F5F5F5',
                        borderColor:
                          selectedType?.id === type.id
                            ? config.textColor
                            : 'transparent',
                        opacity: isAvailable ? 1 : 0.6,
                      },
                    ]}>
                    <View style={styles.requestTypeContent}>
                      <View
                        style={[
                          styles.iconContainer,
                          {
                            backgroundColor: isAvailable
                              ? config.color
                              : '#E0E0E0',
                          },
                        ]}>
                        <Icon
                          name={hasErrors ? 'error' : config.icon}
                          size={24}
                          color={isAvailable ? config.textColor : '#9E9E9E'}
                        />
                      </View>
                      <View style={styles.requestTypeInfo}>
                        <Text
                          style={[
                            styles.requestTypeName,
                            {color: isAvailable ? config.textColor : '#9E9E9E'},
                          ]}>
                          {type.name}
                        </Text>
                        <Text
                          style={[
                            styles.requestTypeDescription,
                            {color: isAvailable ? '#666' : '#9E9E9E'},
                          ]}>
                          {type.therapeutic_plan_rule_label}
                        </Text>
                        {hasErrors && (
                          <Text style={styles.errorText}>
                            {type.validation_errors[0]}
                          </Text>
                        )}
                      </View>
                      <Icon
                        name={
                          !isAvailable
                            ? 'block'
                            : selectedType?.id === type.id
                            ? 'check-circle'
                            : 'radio-button-unchecked'
                        }
                        size={24}
                        color={isAvailable ? config.textColor : '#9E9E9E'}
                      />
                    </View>
                  </TouchableRipple>
                );
              })}
            </View>

            {/* Form dei dettagli */}
            {selectedType && (
              <View style={styles.formContainer}>
                <Text style={styles.sectionTitle}>Dettagli richiesta:</Text>

                {/* Piano Terapeutico */}
                {!selectedType.is_therapeutic_plan_not_allowed && (
                  <View style={styles.fieldContainer}>
                    <Text style={styles.fieldLabel}>
                      Piano Terapeutico
                      {selectedType.is_therapeutic_plan_required && ' *'}
                    </Text>
                    {selectedType.therapeutic_plan_data ? (
                      <View style={styles.dataDisplayContainer}>
                        <Text style={styles.dataDisplayText}>
                          Piano dal{' '}
                          {selectedType.therapeutic_plan_data.start_date} al{' '}
                          {selectedType.therapeutic_plan_data.end_date}
                        </Text>
                        <Text style={styles.dataDisplaySubtext}>
                          Durata:{' '}
                          {selectedType.therapeutic_plan_data.duration_days}{' '}
                          giorni
                        </Text>
                      </View>
                    ) : selectedType.is_therapeutic_plan_required ? (
                      <Text style={styles.errorText}>
                        Piano terapeutico non disponibile
                      </Text>
                    ) : (
                      <Text style={styles.infoText}>
                        Nessun piano terapeutico attivo
                      </Text>
                    )}
                  </View>
                )}

                {/* Terapia */}
                {selectedType.require_therapy_assignment && (
                  <View style={styles.fieldContainer}>
                    <Text style={styles.fieldLabel}>Terapia *</Text>
                    {selectedType.therapies_data &&
                    selectedType.therapies_data.length > 0 ? (
                      <View style={styles.therapyListContainer}>
                        {selectedType.therapies_data.map(
                          (therapy, therapyIndex) => (
                            <TouchableRipple
                              key={`therapy-${therapy.id}-${therapyIndex}`}
                              onPress={() =>
                                handleInputChange(
                                  'therapy_id',
                                  therapy.id.toString(),
                                )
                              }
                              style={[
                                styles.therapyItem,
                                {
                                  backgroundColor:
                                    formData.therapy_id ===
                                    therapy.id.toString()
                                      ? '#E3F2FD'
                                      : '#F5F5F5',
                                },
                              ]}>
                              <View style={styles.therapyItemContent}>
                                <Text style={styles.therapyName}>
                                  {therapy.treatment_type_name}
                                </Text>
                                <Text style={styles.therapyDetails}>
                                  {therapy.weekly_hours}h/settimana -{' '}
                                  {therapy.group_type}
                                </Text>
                                <Icon
                                  name={
                                    formData.therapy_id ===
                                    therapy.id.toString()
                                      ? 'check-circle'
                                      : 'radio-button-unchecked'
                                  }
                                  size={20}
                                  color="#2196F3"
                                />
                              </View>
                            </TouchableRipple>
                          ),
                        )}
                      </View>
                    ) : (
                      <Text style={styles.errorText}>
                        Nessuna terapia disponibile nel piano terapeutico
                      </Text>
                    )}
                  </View>
                )}

                {/* Note */}
                {selectedType.require_notes && (
                  <TextInput
                    label="Note"
                    value={formData.notes}
                    onChangeText={value => handleInputChange('notes', value)}
                    style={styles.input}
                    multiline
                    numberOfLines={3}
                    disabled={submitting}
                    required={true}
                  />
                )}

                <Button
                  mode="contained"
                  onPress={handleSubmit}
                  loading={submitting}
                  disabled={submitting}
                  style={styles.submitButton}>
                  Invia Richiesta
                </Button>
              </View>
            )}
          </View>
        </TouchableWithoutFeedback>
      </KeyboardAvoidingView>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  innerContainer: {
    flex: 1,
    padding: 16,
  },
  requestTypesContainer: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 16,
    color: '#333',
  },
  requestTypeCard: {
    marginBottom: 8,
    borderRadius: 8,
    borderWidth: 2,
    elevation: 2,
  },
  requestTypeContent: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
  },
  iconContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  requestTypeInfo: {
    flex: 1,
  },
  requestTypeName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  requestTypeDescription: {
    fontSize: 14,
    color: '#666',
  },
  errorText: {
    fontSize: 12,
    color: '#F44336',
    marginTop: 4,
    fontStyle: 'italic',
  },
  formContainer: {
    flex: 1,
  },
  input: {
    marginBottom: 16,
    backgroundColor: 'white',
  },
  submitButton: {
    marginTop: 24,
  },
  fieldContainer: {
    marginBottom: 16,
  },
  fieldLabel: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
    color: '#333',
  },
  dataDisplayContainer: {
    backgroundColor: '#F5F5F5',
    padding: 12,
    borderRadius: 8,
  },
  dataDisplayText: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
  },
  dataDisplaySubtext: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  infoText: {
    fontSize: 14,
    color: '#666',
    fontStyle: 'italic',
  },
  therapyListContainer: {
    gap: 8,
  },
  therapyItem: {
    borderRadius: 8,
    padding: 12,
  },
  therapyItemContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  therapyName: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
    flex: 1,
  },
  therapyDetails: {
    fontSize: 12,
    color: '#666',
    marginRight: 8,
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
});

export default CreateRequestScreen;
