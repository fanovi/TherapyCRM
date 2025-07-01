import React, {useState, useEffect} from 'react';
import {useSelector} from 'react-redux';
import {
  View,
  StyleSheet,
  ScrollView,
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
  Avatar,
  TextInput,
  HelperText,
  ActivityIndicator,
  RadioButton,
  Divider,
  TouchableRipple,
  useTheme,
} from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialIcons';
import ScreenTemplate from '../../components/ScreenTemplate';
import {getRequestTypes, createRequest} from '../../api/requests';

// Configurazione icone e colori per ogni tipo di richiesta
const REQUEST_TYPE_CONFIG = {
  'Copia Piano Terapeutico': {
    icon: 'content-copy',
    color: '#E3F2FD', // Azzurro pastello
    backgroundColor: '#BBDEFB',
    iconColor: '#1976D2',
  },
  'Relazione terapista': {
    icon: 'assignment',
    color: '#F3E5F5', // Viola pastello
    backgroundColor: '#E1BEE7',
    iconColor: '#7B1FA2',
  },
  'Relazione visita specialistica': {
    icon: 'medical-services',
    color: '#E8F5E9', // Verde pastello
    backgroundColor: '#C8E6C9',
    iconColor: '#388E3C',
  },
  'Attestato frequenza': {
    icon: 'verified',
    color: '#FFF3E0', // Arancione pastello
    backgroundColor: '#FFE0B2',
    iconColor: '#F57C00',
  },
  Altro: {
    icon: 'more-horiz',
    color: '#FAFAFA', // Grigio pastello
    backgroundColor: '#F5F5F5',
    iconColor: '#616161',
  },
};

const CreateRequestScreen = ({navigation}) => {
  // Ottieni il patient_id da Redux usando il selector corretto
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
      const response = await getRequestTypes();

      console.log('🔍 ANALISI DETTAGLIATA TIPI DI RICHIESTA:');
      console.log(JSON.stringify(response, null, 2));

      if (response.success) {
        setRequestTypes(response.data);
      }
    } catch (error) {
      console.error('❌ Errore caricamento tipologie:', error);
      Alert.alert('Errore', 'Impossibile caricare le tipologie di richieste');
    }
  };

  const validateForm = () => {
    if (!selectedType) {
      Alert.alert('Errore', 'Seleziona una tipologia di richiesta');
      return false;
    }

    const errors = [];

    // Controllo piano terapeutico
    if (
      selectedType.is_therapeutic_plan_required &&
      !formData.therapeutic_plan_id
    ) {
      errors.push('Piano terapeutico obbligatorio per questa tipologia');
    }

    // Controllo assegnazione terapia
    if (selectedType.require_therapy_assignment && !formData.therapy_id) {
      errors.push('Selezione terapia obbligatoria per questa tipologia');
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

  // Renderizza una singola opzione di tipo richiesta
  const renderRequestTypeOption = type => {
    const config =
      REQUEST_TYPE_CONFIG[type.name] || REQUEST_TYPE_CONFIG['Altro'];
    const isSelected = selectedType?.id === type.id;

    return (
      <TouchableRipple
        key={type.id}
        onPress={() => setSelectedType(type)}
        style={[
          styles.requestTypeOption,
          {backgroundColor: config.color},
          isSelected && {backgroundColor: config.backgroundColor},
        ]}>
        <View style={styles.requestTypeContent}>
          <View
            style={[
              styles.iconContainer,
              {backgroundColor: config.backgroundColor},
            ]}>
            <Icon name={config.icon} size={24} color={config.iconColor} />
          </View>
          <View style={styles.requestTypeInfo}>
            <Text style={styles.requestTypeName}>{type.name}</Text>
            <Text style={styles.requestTypeRule}>
              {type.therapeutic_plan_rule_label}
              {type.require_notes && ' • Note richieste'}
              {type.require_therapy_assignment && ' • Terapia richiesta'}
            </Text>
          </View>
          <Icon
            name={
              isSelected ? 'radio-button-checked' : 'radio-button-unchecked'
            }
            size={24}
            color={config.iconColor}
          />
        </View>
      </TouchableRipple>
    );
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
      <TouchableWithoutFeedback onPress={Keyboard.dismiss} accessible={false}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          keyboardVerticalOffset={Platform.OS === 'ios' ? 64 : 0}
          style={styles.container}>
          <ScrollView
            contentContainerStyle={styles.scrollContent}
            keyboardShouldPersistTaps="handled"
            keyboardDismissMode="on-drag">
            {/* Tipi di richiesta */}
            <Card style={styles.card}>
              <Card.Title
                title="Seleziona tipo di richiesta"
                left={props => <Icon name="category" size={24} color="#666" />}
              />
              <Card.Content>
                <View style={styles.requestTypesList}>
                  {requestTypes.map(type => renderRequestTypeOption(type))}
                </View>
              </Card.Content>
            </Card>

            {/* Piano terapeutico */}
            {selectedType && !selectedType.is_therapeutic_plan_not_allowed && (
              <Card style={styles.card}>
                <Card.Title
                  title="Piano terapeutico"
                  subtitle={
                    selectedType.is_therapeutic_plan_required
                      ? '(Obbligatorio)'
                      : '(Opzionale)'
                  }
                  left={props => (
                    <Icon
                      name="description"
                      size={24}
                      color={
                        selectedType.is_therapeutic_plan_required
                          ? '#F44336'
                          : '#666'
                      }
                    />
                  )}
                />
                <Card.Content>
                  <TextInput
                    mode="outlined"
                    label="Seleziona piano terapeutico"
                    value={formData.therapeutic_plan_id}
                    onChangeText={value =>
                      setFormData({...formData, therapeutic_plan_id: value})
                    }
                    error={
                      selectedType.is_therapeutic_plan_required &&
                      !formData.therapeutic_plan_id
                    }
                    keyboardType="numeric"
                    returnKeyType="next"
                    blurOnSubmit={false}
                  />
                </Card.Content>
              </Card>
            )}

            {/* Terapia */}
            {selectedType && selectedType.require_therapy_assignment && (
              <Card style={styles.card}>
                <Card.Title
                  title="Terapia"
                  subtitle="(Obbligatorio)"
                  left={props => (
                    <Icon name="medical-services" size={24} color="#F44336" />
                  )}
                />
                <Card.Content>
                  <TextInput
                    mode="outlined"
                    label="Seleziona terapia"
                    value={formData.therapy_id}
                    onChangeText={value =>
                      setFormData({...formData, therapy_id: value})
                    }
                    error={!formData.therapy_id}
                    keyboardType="numeric"
                    returnKeyType="next"
                    blurOnSubmit={false}
                  />
                </Card.Content>
              </Card>
            )}

            {/* Note */}
            <Card style={styles.card}>
              <Card.Title
                title="Note"
                subtitle={
                  selectedType?.require_notes ? '(Obbligatorio)' : '(Opzionale)'
                }
                left={props => (
                  <Icon
                    name="note"
                    size={24}
                    color={selectedType?.require_notes ? '#F44336' : '#666'}
                  />
                )}
              />
              <Card.Content>
                <TextInput
                  mode="outlined"
                  label="Note aggiuntive"
                  value={formData.notes}
                  onChangeText={value =>
                    setFormData({...formData, notes: value})
                  }
                  multiline
                  numberOfLines={3}
                  error={selectedType?.require_notes && !formData.notes.trim()}
                  blurOnSubmit={true}
                  returnKeyType="done"
                />
              </Card.Content>
            </Card>

            <Button
              mode="contained"
              onPress={handleSubmit}
              loading={submitting}
              disabled={submitting || !selectedType}
              style={styles.submitButton}
              icon={({size, color}) => (
                <Icon name="send" size={size} color={color} />
              )}>
              Invia richiesta
            </Button>
          </ScrollView>
        </KeyboardAvoidingView>
      </TouchableWithoutFeedback>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  scrollContent: {
    flexGrow: 1,
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
  card: {
    marginBottom: 16,
    elevation: 2,
  },
  requestTypesList: {
    marginTop: 8,
  },
  requestTypeOption: {
    borderRadius: 12,
    marginBottom: 8,
    overflow: 'hidden',
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
    marginRight: 16,
  },
  requestTypeInfo: {
    flex: 1,
  },
  requestTypeName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  requestTypeRule: {
    fontSize: 12,
    color: '#666',
  },
  submitButton: {
    marginTop: 24,
  },
});

export default CreateRequestScreen;
