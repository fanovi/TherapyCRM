import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  RefreshControl,
  FlatList,
  TouchableOpacity,
  Modal,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  IconButton,
  useTheme,
  Dialog,
  Portal,
  Paragraph,
  Menu,
  ActivityIndicator,
  TextInput,
  RadioButton,
  Divider,
  List,
} from 'react-native-paper';
import {Calendar} from 'react-native-calendars';
import {useSelector} from 'react-redux';
import moment from 'moment';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getTherapistAppointments,
  getTherapistMarkedDates,
  getAppointmentStatusColor,
  getAppointmentStatusLabel,
  generateMarkedDates,
  markPatientAbsent,
  getAbsenceReasons,
  removeAbsence,
  canRemoveAbsence,
} from '../../api/calendar';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';
import {therapistService} from '../../services/therapistService';

const TherapistCalendarScreen = () => {
  const theme = useTheme();
  const {user} = useSelector(state => state.auth);

  const [selectedDate, setSelectedDate] = useState(
    moment().format('YYYY-MM-DD'),
  );
  const [dayAppointments, setDayAppointments] = useState([]);
  const [markedDates, setMarkedDates] = useState({});
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [actionDialog, setActionDialog] = useState({
    visible: false,
    appointment: null,
    action: null,
  });
  const [menuVisible, setMenuVisible] = useState({});

  // Mappa degli ID setting alle icone
  const SETTING_ICONS = {
    1: 'hospital-building', // Ambulatoriale
    2: 'home', // Domiciliare
    3: 'account-group', // Piccolo gruppo (PG)
    4: 'hospital-building', // Ambulatoriale + PG
    5: 'office-building', // Centro diurno
    6: 'home-city', // Semiconvitto
  };

  const DEFAULT_SETTING_ICON = 'calendar-check'; // Icona di default

  // Funzione per ottenere l'icona in base al setting_id
  const getSettingIcon = settingId => {
    return SETTING_ICONS[settingId] || DEFAULT_SETTING_ICON;
  };

  const [applyToGroup, setApplyToGroup] = useState(false);
  const [selectedGroupPatient, setSelectedGroupPatient] = useState(null);
  const [isSelectingPatient, setIsSelectingPatient] = useState(false);

  // Stato per la modale di segnalazione assenza
  const [absenceDialog, setAbsenceDialog] = useState({
    visible: false,
    appointment: null,
  });

  const [absenceForm, setAbsenceForm] = useState({
    absenceType: '',
    reason: '',
    customReason: '',
    notes: '',
  });

  const [isSubmittingAbsence, setIsSubmittingAbsence] = useState(false);

  // Stato per la modale di rimozione assenza
  const [removeAbsenceDialog, setRemoveAbsenceDialog] = useState({
    visible: false,
    appointment: null,
  });
  const [removeAbsenceNotes, setRemoveAbsenceNotes] = useState('');
  const [isSubmittingRemoveAbsence, setIsSubmittingRemoveAbsence] = useState(false);

  // Stato per la modale di aggiunta note
  const [noteDialog, setNoteDialog] = useState({
    visible: false,
    appointment: null,
  });

  const [noteForm, setNoteForm] = useState({
    note: '',
  });

  const [isSubmittingNote, setIsSubmittingNote] = useState(false);

  useEffect(() => {
    // Il terapista è autenticato, non serve recuperare l'ID
    loadAppointments();
    loadMarkedDates();
  }, [selectedDate]);

  const loadAppointments = async () => {
    try {
      setLoading(true);
      const response = await getTherapistAppointments(selectedDate);
      console.log('response therapist calendar screen', response);
      if (response.success) {
        setDayAppointments(response.data || []);
      } else {
        console.warn('Backend error:', response.error);
        Alert.alert(
          'Errore',
          response.error || 'Errore nel caricamento degli appuntamenti',
        );
      }
    } catch (error) {
      console.error('Errore caricamento appuntamenti:', error);

      // Gestione errori specifica
      if (error.type === 'AUTH_ERROR') {
        console.log('Errore di autenticazione, logout automatico in corso...');
      } else if (error.type === 'PERMISSION_ERROR') {
        Alert.alert('Accesso Negato', error.message);
      } else if (error.type === 'CALENDAR_ERROR') {
        Alert.alert('Errore Calendario', error.message);
      } else if (error.type === 'NETWORK_ERROR') {
        Alert.alert(
          'Errore di Connessione',
          'Verifica la tua connessione internet e riprova.',
        );
      } else {
        Alert.alert(
          'Errore',
          'Impossibile caricare gli appuntamenti. Riprova più tardi.',
        );
      }
    } finally {
      setLoading(false);
    }
  };

  const loadMarkedDates = async () => {
    try {
      const currentMonth = moment(selectedDate).format('YYYY-MM');
      const response = await getTherapistMarkedDates(currentMonth);

      if (response.success) {
        const finalMarked = generateMarkedDates(
          response.data || {},
          selectedDate,
          theme.colors.secondary,
        );
        setMarkedDates(finalMarked);
      } else {
        console.warn('Backend error for marked dates:', response.error);
      }
    } catch (error) {
      console.error('Errore caricamento date marcate:', error);

      if (error.type === 'AUTH_ERROR') {
        console.log(
          'Errore di autenticazione per date marcate, logout automatico in corso...',
        );
      } else if (error.type === 'PERMISSION_ERROR') {
        console.warn('Errore di permessi per date marcate:', error.message);
      } else {
        console.warn('Errore non critico per date marcate:', error.message);
      }
    }
  };

  const handleDayPress = day => {
    setSelectedDate(day.dateString);
  };

  const handleMonthChange = month => {
    // Quando cambia il mese, ricarica le date marcate
    const newMonth = moment(month.dateString).format('YYYY-MM');
    loadMarkedDatesForMonth(newMonth);
  };

  const loadMarkedDatesForMonth = async month => {
    try {
      const response = await getTherapistMarkedDates(month);
      if (response.success) {
        const finalMarked = generateMarkedDates(
          response.data || {},
          selectedDate,
          theme.colors.secondary,
        );
        setMarkedDates(finalMarked);
      } else {
        console.warn(
          'Backend error for marked dates (month change):',
          response.error,
        );
      }
    } catch (error) {
      console.error('Errore caricamento date marcate mese:', error);

      if (error.type === 'AUTH_ERROR') {
        console.log(
          'Errore di autenticazione per cambio mese, logout automatico in corso...',
        );
      } else if (error.type === 'PERMISSION_ERROR') {
        console.warn('Errore di permessi per cambio mese:', error.message);
      } else {
        console.warn('Errore non critico per cambio mese:', error.message);
      }
    }
  };

  const handleRefresh = async () => {
    setRefreshing(true);
    await Promise.all([loadAppointments(), loadMarkedDates()]);
    setRefreshing(false);
  };

  const openActionDialog = (appointment, action) => {
    setActionDialog({visible: true, appointment, action});
    setMenuVisible({});
  };

  const handleMarkAbsent = appointment => {
    setAbsenceDialog({
      visible: true,
      appointment,
    });

    // Reset form
    setAbsenceForm({
      absenceType: '',
      reason: '',
      customReason: '',
      notes: '',
    });

    // Se è un gruppo, default a true per applicare a tutti
    setApplyToGroup(appointment.is_group);
    setSelectedGroupPatient(null);
    setIsSelectingPatient(false);

    setMenuVisible({});
  };

  const handleRemoveAbsence = appointment => {
    setRemoveAbsenceDialog({
      visible: true,
      appointment,
    });
    setRemoveAbsenceNotes('');
    setMenuVisible({});
  };

  const confirmRemoveAbsence = async () => {
    const {appointment} = removeAbsenceDialog;

    setIsSubmittingRemoveAbsence(true);

    try {
      const response = await removeAbsence(
        appointment.id,
        removeAbsenceNotes.trim(),
      );

      if (response.success) {
        Alert.alert(
          'Assenza Rimossa',
          `L'appuntamento di ${appointment.patient.name} del ${moment(
            appointment.datetime,
          ).format('DD/MM/YYYY')} alle ${
            appointment.time
          } è stato ripristinato come confermato.`,
          [
            {
              text: 'OK',
              onPress: () => {
                loadAppointments();
                loadMarkedDates();
              },
            },
          ],
        );
      } else {
        Alert.alert(
          'Errore',
          response.error || "Errore durante la rimozione dell'assenza",
        );
      }
    } catch (error) {
      console.error('Errore rimozione assenza:', error);

      if (error.type === 'AUTH_ERROR') {
        console.log('Errore di autenticazione, logout automatico in corso...');
      } else if (error.type === 'REMOVE_ABSENCE_ERROR') {
        Alert.alert('Errore', error.message);
      } else {
        Alert.alert(
          'Errore',
          "Impossibile rimuovere l'assenza. Riprova più tardi.",
        );
      }
    } finally {
      setIsSubmittingRemoveAbsence(false);
      setRemoveAbsenceDialog({visible: false, appointment: null});
    }
  };

  const handleAddNote = appointment => {
    setNoteDialog({
      visible: true,
      appointment,
    });

    // Prepopola con le note esistenti se presenti
    setNoteForm({
      note: appointment.notes || '',
    });

    setMenuVisible({});
  };

  const confirmMarkAbsent = async () => {
    const {appointment} = absenceDialog;

    // Validazione
    if (!absenceForm.absenceType) {
      Alert.alert('Errore', 'Seleziona il tipo di assenza');
      return;
    }

    if (!absenceForm.reason) {
      Alert.alert('Errore', "Seleziona un motivo per l'assenza");
      return;
    }

    if (absenceForm.reason === 'Altro' && !absenceForm.customReason.trim()) {
      Alert.alert('Errore', 'Specifica il motivo personalizzato');
      return;
    }

    // Per gruppi, verifica se è stato selezionato un paziente specifico
    if (appointment.is_group && !applyToGroup && !selectedGroupPatient) {
      Alert.alert('Errore', 'Seleziona un paziente del gruppo');
      return;
    }

    // Verifica se il paziente selezionato è già assente
    if (appointment.is_group && !applyToGroup && selectedGroupPatient) {
      if (
        selectedGroupPatient.status === 'assente_giustificato' ||
        selectedGroupPatient.status === 'assente_non_giustificato'
      ) {
        Alert.alert(
          'Paziente già assente',
          `${selectedGroupPatient.name} è già stato segnato come assente.`,
        );
        return;
      }
    }

    setIsSubmittingAbsence(true);

    try {
      const finalReason =
        absenceForm.reason === 'Altro'
          ? absenceForm.customReason.trim()
          : absenceForm.reason;

      // Determina quale appointment ID usare
      let appointmentIdToUse = appointment.id;
      let patientName = appointment.patient.name;

      if (appointment.is_group && !applyToGroup && selectedGroupPatient) {
        // Usa l'ID dell'appuntamento del paziente specifico
        appointmentIdToUse = selectedGroupPatient.appointment_id;
        patientName = selectedGroupPatient.name;
      }

      const response = await markPatientAbsent(
        appointmentIdToUse,
        absenceForm.absenceType,
        finalReason,
        absenceForm.notes.trim(),
        appointment.is_group ? applyToGroup : false, // Passa applyToGroup solo per gruppi
      );

      if (response.success) {
        const absenceTypeLabel =
          absenceForm.absenceType === 'justified'
            ? 'giustificata'
            : 'non giustificata';

        let message = '';
        if (appointment.is_group && applyToGroup) {
          message = `L'assenza ${absenceTypeLabel} è stata registrata per tutti i ${appointment.patients_count} pazienti del gruppo.`;
        } else if (appointment.is_group && !applyToGroup) {
          message = `L'assenza ${absenceTypeLabel} di ${patientName} è stata registrata con successo.`;
        } else {
          message = `L'assenza ${absenceTypeLabel} del paziente ${patientName} per l'appuntamento del ${moment(
            appointment.datetime,
          ).format('DD/MM/YYYY')} alle ${
            appointment.time
          } è stata registrata con successo.`;
        }

        Alert.alert('Assenza Segnalata', message, [
          {
            text: 'OK',
            onPress: () => {
              // Ricarica gli appuntamenti
              loadAppointments();
              loadMarkedDates();
            },
          },
        ]);
      } else {
        Alert.alert(
          'Errore',
          response.error || "Errore durante la segnalazione dell'assenza",
        );
      }
    } catch (error) {
      console.error('Errore segnalazione assenza:', error);

      if (error.type === 'AUTH_ERROR') {
        console.log('Errore di autenticazione, logout automatico in corso...');
      } else if (error.type === 'ABSENCE_ERROR') {
        Alert.alert('Errore Segnalazione', error.message);
      } else {
        Alert.alert(
          'Errore',
          "Impossibile segnalare l'assenza. Riprova più tardi.",
        );
      }
    } finally {
      setIsSubmittingAbsence(false);
      setAbsenceDialog({visible: false, appointment: null});
      setApplyToGroup(false);
      setSelectedGroupPatient(null);
    }
  };

  const confirmAddNote = async () => {
    const {appointment} = noteDialog;

    // Validazione
    if (!noteForm.note.trim()) {
      Alert.alert('Errore', 'Inserisci una nota');
      return;
    }

    setIsSubmittingNote(true);

    try {
      const response = await therapistService.setAppointmentNote(
        appointment.id,
        noteForm.note.trim(),
      );

      if (response.success) {
        Alert.alert(
          'Note Aggiornate',
          "Le note dell'appuntamento sono state aggiornate con successo.",
          [
            {
              text: 'OK',
              onPress: () => {
                // Ricarica gli appuntamenti
                loadAppointments();
                loadMarkedDates();
              },
            },
          ],
        );
      } else {
        Alert.alert(
          'Errore',
          response.error || "Errore durante l'aggiornamento delle note",
        );
      }
    } catch (error) {
      console.error('Errore aggiornamento note:', error);

      if (error.type === 'AUTH_ERROR') {
        console.log('Errore di autenticazione, logout automatico in corso...');
      } else if (error.type === 'NOTE_ERROR') {
        Alert.alert('Errore Note', error.message);
      } else {
        Alert.alert(
          'Errore',
          'Impossibile aggiornare le note. Riprova più tardi.',
        );
      }
    } finally {
      setIsSubmittingNote(false);
      setNoteDialog({visible: false, appointment: null});
    }
  };

  const confirmAction = () => {
    const {appointment, action} = actionDialog;

    let title = '';
    let message = '';

    switch (action) {
      case 'absent':
        title = 'Assenza Registrata';
        message = `Hai segnalato l'assenza di ${appointment.patient.name}.`;
        break;
      case 'reschedule':
        title = 'Da Riprogrammare';
        message = `Appuntamento segnalato per riprogrammazione.`;
        break;
      case 'cancel':
        title = 'Appuntamento Cancellato';
        message = `Appuntamento cancellato.`;
        break;
    }

    Alert.alert(title, message);
    setActionDialog({visible: false, appointment: null, action: null});
  };

  const renderAppointmentItem = ({item: appointment}) => {
    const now = moment();
    const appointmentStart = moment(appointment.datetime);
    const appointmentEnd = moment(appointment.datetime).add(
      appointment.duration_minutes,
      'minutes',
    );
    const fifteenMinutesAfterEnd = moment(appointmentEnd).add(15, 'minutes');

    // Logica semplice per segnare assente
    const appointmentDate = moment(appointment.datetime);
    const yesterday = moment().subtract(1, 'day').startOf('day');
    const fourteenDaysAfter = moment().add(14, 'days').endOf('day');

    // Per i gruppi, controlla se almeno un paziente ha stato 'confermato' o 'scheduled'
    const canMarkAbsentGroup =
      appointment.is_group && appointment.group_patients
        ? appointment.group_patients.some(
            patient =>
              (patient.status === 'confermato' ||
                patient.status === 'scheduled') &&
              moment(appointment.datetime).isAfter(yesterday) &&
              moment(appointment.datetime).isBefore(fourteenDaysAfter),
          )
        : false;

    const canMarkAbsent =
      (appointment.status === 'confermato' || canMarkAbsentGroup) &&
      appointmentDate.isAfter(yesterday) &&
      appointmentDate.isBefore(fourteenDaysAfter);

    // Debug temporaneo per vedere gli stati
    if (appointment.is_group && appointment.group_patients) {
      console.log(
        'Gruppo pazienti stati:',
        appointment.group_patients.map(p => ({name: p.name, status: p.status})),
      );
      console.log('canMarkAbsentGroup:', canMarkAbsentGroup);
      console.log('canMarkAbsent:', canMarkAbsent);
    }

    const canComplete =
      appointment.status === 'confermato' &&
      now.isAfter(appointmentStart) &&
      now.isBefore(fifteenMinutesAfterEnd);

    const canRemoveAbsenceCheck = canRemoveAbsence(appointment);

    const showMenu = canMarkAbsent || canRemoveAbsenceCheck || true; // Sempre mostrare per le note

    // Se è un gruppo, mostra come gruppo
    if (appointment.is_group) {
      return (
        <Card style={styles.appointmentCard}>
          <Card.Content>
            <View style={styles.appointmentHeader}>
              <View style={styles.timeSection}>
                <View style={styles.timeWithIcon}>
                  <Avatar.Icon
                    size={32}
                    icon={getSettingIcon(appointment.setting_id)}
                    style={styles.settingIconSmall}
                  />
                  <View>
                    <Text
                      style={[
                        styles.appointmentTime,
                        {color: theme.colors.onSurface},
                      ]}>
                      {appointment.time}
                    </Text>
                    <View style={styles.chipContainer}>
                      <Chip
                        style={[
                          styles.statusChip,
                          {
                            backgroundColor: `${getAppointmentStatusColor(
                              appointment.status,
                            )}20`,
                          },
                        ]}
                        textStyle={{
                          color: getAppointmentStatusColor(appointment.status),
                          fontSize: 12,
                        }}>
                        {getAppointmentStatusLabel(appointment.status)}
                      </Chip>
                      <Chip
                        style={[
                          styles.groupChip,
                          {backgroundColor: theme.colors.primary + '20'},
                        ]}
                        textStyle={{
                          color: theme.colors.primary,
                          fontSize: 11,
                        }}
                        icon="account-group">
                        Gruppo ({appointment.patients_count})
                      </Chip>
                    </View>
                  </View>
                </View>
              </View>
              <View style={styles.headerActions}>
                <Avatar.Icon
                  size={48}
                  icon="account-group"
                  style={[
                    styles.groupAvatar,
                    {backgroundColor: theme.colors.primary},
                  ]}
                />
                {showMenu && (
                  <Menu
                    visible={menuVisible[appointment.id] || false}
                    onDismiss={() => toggleMenu(appointment.id)}
                    anchor={
                      <IconButton
                        icon="dots-vertical"
                        size={20}
                        onPress={() => toggleMenu(appointment.id)}
                      />
                    }>
                    {canMarkAbsent && (
                      <Menu.Item
                        onPress={() => handleMarkAbsent(appointment)}
                        title="Segna Assente"
                        leadingIcon="account-remove"
                      />
                    )}
                    {canRemoveAbsenceCheck && (
                      <Menu.Item
                        onPress={() => handleRemoveAbsence(appointment)}
                        title="Rimuovi Assenza"
                        leadingIcon="account-check"
                      />
                    )}
                    <Menu.Item
                      onPress={() => handleAddNote(appointment)}
                      title="Aggiungi Note"
                      leadingIcon="note-text"
                    />
                  </Menu>
                )}
              </View>
            </View>

            <View style={styles.appointmentDetails}>
              <Text
                style={[styles.patientName, {color: theme.colors.onSurface}]}>
                Appuntamento di Gruppo
              </Text>
              <Text
                style={[
                  styles.appointmentType,
                  {color: theme.colors.secondary},
                ]}>
                {appointment.type} • {appointment.patients_count} pazienti
              </Text>

              {/* Stati dei pazienti del gruppo */}
              {appointment.group_patients &&
                appointment.group_patients.length > 0 && (
                  <View style={styles.groupPatientsStatus}>
                    <View style={styles.groupPatientsHeader}>
                      <Text
                        style={[
                          styles.groupPatientsTitle,
                          {color: theme.colors.onSurfaceVariant},
                        ]}>
                        Stati pazienti:
                      </Text>
                      {canMarkAbsentGroup && (
                        <Chip
                          style={[
                            styles.groupActionChip,
                            {backgroundColor: theme.colors.primary + '20'},
                          ]}
                          textStyle={{
                            color: theme.colors.primary,
                            fontSize: 10,
                          }}>
                          Azione disponibile
                        </Chip>
                      )}
                    </View>
                    <View style={styles.groupPatientsList}>
                      {appointment.group_patients.map((patient, index) => (
                        <View key={patient.id} style={styles.groupPatientItem}>
                          <View style={styles.groupPatientInfo}>
                            <Text
                              style={[
                                styles.groupPatientName,
                                {
                                  color:
                                    patient.status === 'absent_justified' ||
                                    patient.status === 'absent_not_justified'
                                      ? '#999'
                                      : theme.colors.onSurface,
                                },
                              ]}>
                              {patient.name}
                              {(patient.status === 'absent_justified' ||
                                patient.status === 'absent_not_justified') &&
                                ' (già assente)'}
                            </Text>
                            <Chip
                              style={[
                                styles.groupPatientStatusChip,
                                {
                                  backgroundColor: `${getAppointmentStatusColor(
                                    patient.status,
                                  )}20`,
                                },
                              ]}
                              textStyle={{
                                color: getAppointmentStatusColor(
                                  patient.status,
                                ),
                                fontSize: 10,
                              }}>
                              {getAppointmentStatusLabel(patient.status)}
                            </Chip>
                          </View>
                        </View>
                      ))}
                    </View>
                  </View>
                )}
            </View>

            {appointment.notes && (
              <View style={styles.notesSection}>
                <Text
                  style={[
                    styles.notesLabel,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Note:
                </Text>
                <Text
                  style={[styles.notesText, {color: theme.colors.onSurface}]}>
                  {appointment.notes}
                </Text>
              </View>
            )}

            <View style={styles.appointmentActions}>
              {canComplete && (
                <Button
                  mode="contained"
                  style={[
                    styles.actionButton,
                    {backgroundColor: theme.colors.secondary},
                  ]}
                  icon="check"
                  compact
                  onPress={() => handleCompleteAppointment(appointment)}>
                  Completa
                </Button>
              )}
            </View>
          </Card.Content>
        </Card>
      );
    }

    // Appuntamento singolo - codice originale
    return (
      <Card style={styles.appointmentCard}>
        <Card.Content>
          <View style={styles.appointmentHeader}>
            <View style={styles.timeSection}>
              <View style={styles.timeWithIcon}>
                <Avatar.Icon
                  size={32}
                  icon={getSettingIcon(appointment.setting_id)}
                  style={styles.settingIconSmall}
                />
                <View>
                  <Text
                    style={[
                      styles.appointmentTime,
                      {color: theme.colors.onSurface},
                    ]}>
                    {appointment.time}
                  </Text>
                  <Chip
                    style={[
                      styles.statusChip,
                      {
                        backgroundColor: `${getAppointmentStatusColor(
                          appointment.status,
                        )}20`,
                      },
                    ]}
                    textStyle={{
                      color: getAppointmentStatusColor(appointment.status),
                      fontSize: 12,
                    }}>
                    {getAppointmentStatusLabel(appointment.status)}
                  </Chip>
                </View>
              </View>
            </View>
            <View style={styles.headerActions}>
              <Avatar.Image
                size={48}
                source={{uri: appointment.patient.avatar}}
                style={styles.patientAvatar}
              />
              {showMenu && (
                <Menu
                  visible={menuVisible[appointment.id] || false}
                  onDismiss={() => toggleMenu(appointment.id)}
                  anchor={
                    <IconButton
                      icon="dots-vertical"
                      size={20}
                      onPress={() => toggleMenu(appointment.id)}
                    />
                  }>
                  {canMarkAbsent && (
                    <Menu.Item
                      onPress={() => handleMarkAbsent(appointment)}
                      title="Segna Assente"
                      leadingIcon="account-remove"
                    />
                  )}
                  {canRemoveAbsenceCheck && (
                    <Menu.Item
                      onPress={() => handleRemoveAbsence(appointment)}
                      title="Rimuovi Assenza"
                      leadingIcon="account-check"
                    />
                  )}
                  <Menu.Item
                    onPress={() => handleAddNote(appointment)}
                    title="Aggiungi Note"
                    leadingIcon="note-text"
                  />
                </Menu>
              )}
            </View>
          </View>

          <View style={styles.appointmentDetails}>
            <Text style={[styles.patientName, {color: theme.colors.onSurface}]}>
              {appointment.patient.name}
            </Text>
            <Text
              style={[styles.appointmentType, {color: theme.colors.secondary}]}>
              {appointment.type}
            </Text>
            {appointment.patient.phone && (
              <Text
                style={[
                  styles.patientPhone,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                {appointment.patient.phone}
              </Text>
            )}
          </View>

          {appointment.notes && (
            <View style={styles.notesSection}>
              <Text
                style={[
                  styles.notesLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Note:
              </Text>
              <Text style={[styles.notesText, {color: theme.colors.onSurface}]}>
                {appointment.notes}
              </Text>
            </View>
          )}

          <View style={styles.appointmentActions}>
            {canComplete && (
              <Button
                mode="contained"
                style={[
                  styles.actionButton,
                  {backgroundColor: theme.colors.secondary},
                ]}
                icon="check"
                compact
                onPress={() => handleCompleteAppointment(appointment)}>
                Completa
              </Button>
            )}
          </View>
        </Card.Content>
      </Card>
    );
  };

  const renderEmptyState = () => (
    <Card style={styles.emptyCard}>
      <Card.Content style={styles.emptyContent}>
        <Avatar.Icon
          size={64}
          icon="calendar-blank"
          style={[
            styles.emptyIcon,
            {backgroundColor: theme.colors.surfaceVariant},
          ]}
        />
        <Text
          style={[styles.emptyText, {color: theme.colors.onSurfaceVariant}]}>
          Nessun appuntamento{'\n'}per questo giorno
        </Text>
      </Card.Content>
    </Card>
  );

  const toggleMenu = appointmentId => {
    setMenuVisible(prev => ({
      ...prev,
      [appointmentId]: !prev[appointmentId],
    }));
  };

  const calendarTheme = {
    backgroundColor: theme.colors.background,
    calendarBackground: theme.colors.surface,
    textSectionTitleColor: theme.colors.onSurface,
    selectedDayBackgroundColor: theme.colors.secondary,
    selectedDayTextColor: theme.colors.onSecondary,
    todayTextColor: theme.colors.secondary,
    dayTextColor: theme.colors.onSurface,
    textDisabledColor: theme.colors.onSurfaceVariant,
    dotColor: theme.colors.secondary,
    selectedDotColor: theme.colors.onSecondary,
    arrowColor: theme.colors.secondary,
    monthTextColor: theme.colors.onSurface,
    indicatorColor: theme.colors.secondary,
    textDayFontFamily: 'System',
    textMonthFontFamily: 'System',
    textDayHeaderFontFamily: 'System',
    textDayFontSize: 16,
    textMonthFontSize: 18,
    textDayHeaderFontSize: 14,
  };

  // Il controllo del terapista non è più necessario perché viene gestito dal backend

  return (
    <ScreenTemplate title="Agenda" subtitle="I tuoi appuntamenti">
      <View style={styles.container}>
        {/* Calendario */}
        <Card style={styles.calendarCard}>
          <Card.Content>
            <Calendar
              current={selectedDate}
              onDayPress={handleDayPress}
              onMonthChange={handleMonthChange}
              markedDates={markedDates}
              theme={calendarTheme}
              firstDay={1}
              enableSwipeMonths={true}
              hideExtraDays={true}
              monthFormat={'MMMM yyyy'}
            />
          </Card.Content>
        </Card>

        {/* Appuntamenti del giorno */}
        <View style={styles.daySection}>
          <Text style={[styles.dayTitle, {color: theme.colors.onSurface}]}>
            {moment(selectedDate).format('dddd DD MMMM YYYY')}
          </Text>

          {loading ? (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" color={theme.colors.secondary} />
              <Text
                style={[
                  styles.loadingText,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Caricamento appuntamenti...
              </Text>
            </View>
          ) : (
            <FlatList
              data={dayAppointments}
              renderItem={renderAppointmentItem}
              keyExtractor={item => item.id.toString()}
              ListEmptyComponent={renderEmptyState}
              refreshControl={
                <RefreshControl
                  refreshing={refreshing}
                  onRefresh={handleRefresh}
                  colors={[theme.colors.secondary]}
                />
              }
              showsVerticalScrollIndicator={false}
              contentContainerStyle={styles.listContainer}
            />
          )}
        </View>
      </View>

      <Portal>
        <Dialog
          visible={actionDialog.visible}
          onDismiss={() =>
            setActionDialog({visible: false, appointment: null, action: null})
          }>
          <Dialog.Title>Conferma Azione</Dialog.Title>
          <Dialog.Content>
            <Paragraph>
              Confermi l'operazione per {actionDialog.appointment?.patient.name}
              ?
            </Paragraph>
          </Dialog.Content>
          <Dialog.Actions>
            <Button
              onPress={() =>
                setActionDialog({
                  visible: false,
                  appointment: null,
                  action: null,
                })
              }>
              Annulla
            </Button>
            <Button onPress={confirmAction}>Conferma</Button>
          </Dialog.Actions>
        </Dialog>

        {/* Dialog rimozione assenza */}
        <Dialog
          visible={removeAbsenceDialog.visible}
          onDismiss={() =>
            setRemoveAbsenceDialog({visible: false, appointment: null})
          }
          style={styles.cancellationDialog}>
          <Dialog.Title>Rimuovi Assenza</Dialog.Title>
          <Dialog.Content>
            <Paragraph style={styles.appointmentInfo}>
              {removeAbsenceDialog.appointment?.patient.name}
              {'\n'}
              {removeAbsenceDialog.appointment &&
                moment(removeAbsenceDialog.appointment.datetime).format(
                  'DD/MM/YYYY',
                )}{' '}
              alle {removeAbsenceDialog.appointment?.time}
            </Paragraph>

            <Paragraph>
              L'appuntamento verrà ripristinato come confermato.
            </Paragraph>

            <Divider style={styles.divider} />

            <Text
              style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
              Note (opzionale)
            </Text>

            <TextInput
              label="Aggiungi note se necessario"
              value={removeAbsenceNotes}
              onChangeText={setRemoveAbsenceNotes}
              mode="outlined"
              style={styles.notesInput}
              multiline
              numberOfLines={3}
              placeholder="Es. Motivo della rimozione dell'assenza..."
            />
          </Dialog.Content>
          <Dialog.Actions style={styles.dialogActions}>
            <Button
              onPress={() =>
                setRemoveAbsenceDialog({visible: false, appointment: null})
              }
              disabled={isSubmittingRemoveAbsence}>
              Annulla
            </Button>
            <Button
              onPress={confirmRemoveAbsence}
              buttonColor={theme.colors.primary}
              loading={isSubmittingRemoveAbsence}
              disabled={isSubmittingRemoveAbsence}
              mode="contained">
              {isSubmittingRemoveAbsence ? 'Ripristino...' : 'Conferma'}
            </Button>
          </Dialog.Actions>
        </Dialog>

        {/* Modal segnalazione assenza */}
        <Modal
          visible={absenceDialog.visible}
          transparent={true}
          animationType="slide"
          onRequestClose={() =>
            setAbsenceDialog({visible: false, appointment: null})
          }>
          <View style={styles.modalOverlay}>
            <View style={styles.modalContainer}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Segnala Assenza</Text>
                <TouchableOpacity
                  onPress={() =>
                    setAbsenceDialog({visible: false, appointment: null})
                  }
                  style={styles.closeButton}>
                  <Icon name="close" size={24} color="#666" />
                </TouchableOpacity>
              </View>
              <ScrollView
                style={styles.modalContent}
                showsVerticalScrollIndicator={false}>
                {/* Info paziente compatta */}
                <View style={styles.patientInfoRow}>
                  <Avatar.Image
                    size={36}
                    source={{uri: absenceDialog.appointment?.patient.avatar}}
                    style={styles.miniAvatar}
                  />
                  <View style={styles.patientInfoText}>
                    <Text style={styles.patientNameInfo}>
                      {absenceDialog.appointment?.patient.name}
                    </Text>
                    <Text style={styles.appointmentTimeInfo}>
                      {absenceDialog.appointment &&
                        moment(absenceDialog.appointment.datetime).format(
                          'DD/MM',
                        )}{' '}
                      alle {absenceDialog.appointment?.time}
                    </Text>
                  </View>
                </View>

                {/* Opzione per gruppi */}
                {/* Opzione per gruppi */}
                {absenceDialog.appointment?.is_group && (
                  <>
                    <View style={styles.groupOptionContainer}>
                      <View style={styles.groupOptionHeader}>
                        <Icon
                          name="account-group"
                          size={20}
                          color={theme.colors.primary}
                        />
                        <Text style={styles.groupOptionTitle}>
                          Appuntamento di Gruppo
                        </Text>
                      </View>

                      <View style={styles.groupOptionContent}>
                        <Text style={styles.groupOptionDescription}>
                          Questo è un appuntamento di gruppo con{' '}
                          {absenceDialog.appointment.patients_count} pazienti.
                        </Text>

                        {/* Radio buttons per la scelta */}
                        <View style={styles.groupChoiceContainer}>
                          <TouchableOpacity
                            style={[
                              styles.groupChoiceOption,
                              applyToGroup && styles.groupChoiceOptionSelected,
                            ]}
                            onPress={() => {
                              setApplyToGroup(true);
                              setIsSelectingPatient(false);
                              setSelectedGroupPatient(null);
                            }}>
                            <RadioButton
                              value="all"
                              status={applyToGroup ? 'checked' : 'unchecked'}
                              color={theme.colors.primary}
                            />
                            <View style={styles.groupChoiceTextContainer}>
                              <Text style={styles.groupChoiceMainText}>
                                Tutto il gruppo
                              </Text>
                              <Text style={styles.groupChoiceSubText}>
                                Applica a tutti i{' '}
                                {absenceDialog.appointment.patients_count}{' '}
                                pazienti
                              </Text>
                            </View>
                          </TouchableOpacity>

                          <TouchableOpacity
                            style={[
                              styles.groupChoiceOption,
                              !applyToGroup && styles.groupChoiceOptionSelected,
                            ]}
                            onPress={() => {
                              setApplyToGroup(false);
                              setIsSelectingPatient(true);
                              if (
                                !selectedGroupPatient &&
                                absenceDialog.appointment.group_patients
                                  ?.length > 0
                              ) {
                                setSelectedGroupPatient(
                                  absenceDialog.appointment.group_patients[0],
                                );
                              }
                            }}>
                            <RadioButton
                              value="single"
                              status={!applyToGroup ? 'checked' : 'unchecked'}
                              color={theme.colors.primary}
                            />
                            <View style={styles.groupChoiceTextContainer}>
                              <Text style={styles.groupChoiceMainText}>
                                Paziente specifico
                              </Text>
                              <Text style={styles.groupChoiceSubText}>
                                Seleziona un singolo paziente
                              </Text>
                            </View>
                          </TouchableOpacity>
                        </View>

                        {/* Dropdown per selezione paziente */}
                        {!applyToGroup &&
                          absenceDialog.appointment.group_patients && (
                            <View style={styles.patientDropdownContainer}>
                              <TouchableOpacity
                                style={styles.patientDropdownHeader}
                                onPress={() =>
                                  setIsSelectingPatient(!isSelectingPatient)
                                }>
                                <View style={styles.patientDropdownTitle}>
                                  <Icon
                                    name="account"
                                    size={20}
                                    color={theme.colors.primary}
                                  />
                                  <Text style={styles.patientDropdownTitleText}>
                                    {selectedGroupPatient
                                      ? selectedGroupPatient.name
                                      : 'Seleziona paziente'}
                                  </Text>
                                </View>
                                <Icon
                                  name={
                                    isSelectingPatient
                                      ? 'chevron-up'
                                      : 'chevron-down'
                                  }
                                  size={20}
                                  color="#666"
                                />
                              </TouchableOpacity>
                              {isSelectingPatient && (
                                <View style={styles.patientListContainer}>
                                  {absenceDialog.appointment.group_patients.map(
                                    patient => (
                                      <TouchableOpacity
                                        key={patient.id}
                                        style={[
                                          styles.patientListItem,
                                          selectedGroupPatient?.id ===
                                            patient.id &&
                                            styles.patientListItemSelected,
                                          (patient.status ===
                                            'assente_giustificato' ||
                                            patient.status ===
                                              'assente_non_giustificato') &&
                                            styles.patientListItemDisabled,
                                        ]}
                                        onPress={() => {
                                          // Non permettere selezione se già assente
                                          if (
                                            patient.status !==
                                              'assente_giustificato' &&
                                            patient.status !==
                                              'assente_non_giustificato'
                                          ) {
                                            setSelectedGroupPatient(patient);
                                            setIsSelectingPatient(false);
                                          }
                                        }}>
                                        <View
                                          style={styles.patientListItemContent}>
                                          <Icon
                                            name={
                                              patient.status ===
                                                'assente_giustificato' ||
                                              patient.status ===
                                                'assente_non_giustificato'
                                                ? 'account-remove'
                                                : selectedGroupPatient?.id ===
                                                  patient.id
                                                ? 'check-circle'
                                                : 'account-circle'
                                            }
                                            size={20}
                                            color={
                                              patient.status ===
                                                'assente_giustificato' ||
                                              patient.status ===
                                                'assente_non_giustificato'
                                                ? '#999'
                                                : selectedGroupPatient?.id ===
                                                  patient.id
                                                ? theme.colors.primary
                                                : '#666'
                                            }
                                          />
                                          <View
                                            style={styles.patientListItemText}>
                                            <Text
                                              style={[
                                                styles.patientListItemTitle,
                                                {
                                                  color:
                                                    patient.status ===
                                                      'assente_giustificato' ||
                                                    patient.status ===
                                                      'assente_non_giustificato'
                                                      ? '#999'
                                                      : theme.colors.onSurface,
                                                },
                                              ]}>
                                              {patient.name}
                                              {(patient.status ===
                                                'assente_giustificato' ||
                                                patient.status ===
                                                  'assente_non_giustificato') &&
                                                ' (già assente)'}
                                            </Text>
                                            {patient.status !==
                                              'confermato' && (
                                              <Text
                                                style={
                                                  styles.patientListItemDescription
                                                }>
                                                {getAppointmentStatusLabel(
                                                  patient.status,
                                                )}
                                              </Text>
                                            )}
                                          </View>
                                        </View>
                                      </TouchableOpacity>
                                    ),
                                  )}
                                </View>
                              )}
                            </View>
                          )}
                      </View>
                    </View>

                    <Divider style={styles.compactDivider} />
                  </>
                )}

                {/* Tipo di assenza */}
                <View style={styles.sectionHeader}>
                  <Icon
                    name="radiobox-marked"
                    size={16}
                    color={theme.colors.primary}
                  />
                  <Text
                    style={[
                      styles.compactSectionTitle,
                      {color: theme.colors.onSurface},
                    ]}>
                    Tipo *
                  </Text>
                </View>
                <View style={styles.radioRow}>
                  <View style={styles.radioOption}>
                    <RadioButton
                      value="justified"
                      status={
                        absenceForm.absenceType === 'justified'
                          ? 'checked'
                          : 'unchecked'
                      }
                      onPress={() =>
                        setAbsenceForm(prev => ({
                          ...prev,
                          absenceType: 'justified',
                        }))
                      }
                      color={theme.colors.primary}
                    />
                    <Text
                      style={styles.radioText}
                      onPress={() =>
                        setAbsenceForm(prev => ({
                          ...prev,
                          absenceType: 'justified',
                        }))
                      }>
                      Giustificata
                    </Text>
                  </View>
                  <View style={styles.radioOption}>
                    <RadioButton
                      value="not_justified"
                      status={
                        absenceForm.absenceType === 'not_justified'
                          ? 'checked'
                          : 'unchecked'
                      }
                      onPress={() =>
                        setAbsenceForm(prev => ({
                          ...prev,
                          absenceType: 'not_justified',
                        }))
                      }
                      color={theme.colors.primary}
                    />
                    <Text
                      style={styles.radioText}
                      onPress={() =>
                        setAbsenceForm(prev => ({
                          ...prev,
                          absenceType: 'not_justified',
                        }))
                      }>
                      Non Giustificata
                    </Text>
                  </View>
                </View>

                {/* Motivo */}
                <View style={styles.sectionHeader}>
                  <Icon
                    name="clipboard-text"
                    size={16}
                    color={theme.colors.primary}
                  />
                  <Text
                    style={[
                      styles.compactSectionTitle,
                      {color: theme.colors.onSurface},
                    ]}>
                    Motivo *
                  </Text>
                </View>
                <View style={styles.reasonsContainer}>
                  {getAbsenceReasons().map(reason => (
                    <View key={reason} style={styles.reasonItem}>
                      <RadioButton
                        value={reason}
                        status={
                          absenceForm.reason === reason
                            ? 'checked'
                            : 'unchecked'
                        }
                        onPress={() =>
                          setAbsenceForm(prev => ({...prev, reason}))
                        }
                        color={theme.colors.primary}
                      />
                      <Text
                        style={styles.reasonText}
                        onPress={() =>
                          setAbsenceForm(prev => ({...prev, reason}))
                        }>
                        {reason}
                      </Text>
                    </View>
                  ))}
                </View>

                {absenceForm.reason === 'Altro' && (
                  <TextInput
                    label="Specifica"
                    value={absenceForm.customReason}
                    onChangeText={text =>
                      setAbsenceForm(prev => ({...prev, customReason: text}))
                    }
                    mode="outlined"
                    style={styles.compactCustomInput}
                    dense
                    multiline
                    numberOfLines={2}
                  />
                )}

                {/* Note */}
                <TextInput
                  label="Note (opzionale)"
                  value={absenceForm.notes}
                  onChangeText={text =>
                    setAbsenceForm(prev => ({...prev, notes: text}))
                  }
                  mode="outlined"
                  style={styles.compactNotesInput}
                  dense
                  multiline
                  numberOfLines={2}
                />
              </ScrollView>
              <View style={styles.modalActions}>
                <Button
                  onPress={() =>
                    setAbsenceDialog({visible: false, appointment: null})
                  }
                  disabled={isSubmittingAbsence}
                  mode="outlined"
                  style={styles.cancelButton}>
                  Annulla
                </Button>
                <Button
                  onPress={confirmMarkAbsent}
                  buttonColor={theme.colors.error}
                  loading={isSubmittingAbsence}
                  disabled={
                    isSubmittingAbsence ||
                    !absenceForm.absenceType ||
                    !absenceForm.reason
                  }
                  mode="contained"
                  style={styles.confirmButton}>
                  Conferma
                </Button>
              </View>
            </View>
          </View>
        </Modal>

        {/* Modal aggiunta note */}
        <Modal
          visible={noteDialog.visible}
          transparent={true}
          animationType="slide"
          onRequestClose={() =>
            setNoteDialog({visible: false, appointment: null})
          }>
          <View style={styles.modalOverlay}>
            <View style={styles.modalContainer}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>
                  Aggiungi Note Appuntamento
                </Text>
                <TouchableOpacity
                  onPress={() =>
                    setNoteDialog({visible: false, appointment: null})
                  }
                  style={styles.closeButton}>
                  <Icon name="close" size={24} color="#666" />
                </TouchableOpacity>
              </View>
              <ScrollView style={styles.modalContent}>
                <Paragraph style={styles.appointmentInfo}>
                  Stai aggiungendo note per l'appuntamento di{' '}
                  {noteDialog.appointment?.patient.name} del{' '}
                  {noteDialog.appointment &&
                    moment(noteDialog.appointment.datetime).format(
                      'DD/MM/YYYY',
                    )}{' '}
                  alle {noteDialog.appointment?.time}
                </Paragraph>

                <Divider style={styles.divider} />

                <Text
                  style={[
                    styles.sectionTitle,
                    {color: theme.colors.onSurface},
                  ]}>
                  Note dell'appuntamento
                </Text>

                <TextInput
                  label="Inserisci le note per questo appuntamento"
                  value={noteForm.note}
                  onChangeText={text =>
                    setNoteForm(prev => ({...prev, note: text}))
                  }
                  mode="outlined"
                  style={styles.noteInput}
                  multiline
                  numberOfLines={6}
                  placeholder="Es. Osservazioni, progressi, comportamenti, ecc..."
                />
              </ScrollView>
              <View style={styles.modalActions}>
                <Button
                  onPress={() =>
                    setNoteDialog({visible: false, appointment: null})
                  }
                  disabled={isSubmittingNote}
                  mode="outlined"
                  style={styles.cancelButton}>
                  Annulla
                </Button>
                <Button
                  onPress={confirmAddNote}
                  buttonColor={theme.colors.primary}
                  loading={isSubmittingNote}
                  disabled={isSubmittingNote || !noteForm.note.trim()}
                  mode="contained"
                  style={styles.confirmButton}>
                  {isSubmittingNote ? 'Salvataggio...' : 'Salva Note'}
                </Button>
              </View>
            </View>
          </View>
        </Modal>
      </Portal>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  chipContainer: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 4,
  },
  groupChip: {
    alignSelf: 'flex-start',
  },
  groupAvatar: {
    borderWidth: 2,
    borderColor: '#E0E0E0',
  },
  // ... e gli stili per l'opzione gruppo nella modale come prima
  groupOptionContainer: {
    marginBottom: 8,
    backgroundColor: '#F5F7FA',
    borderRadius: 8,
    padding: 10,
    borderWidth: 1,
    borderColor: '#E0E0E0',
  },
  groupOptionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  groupOptionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginLeft: 4,
  },
  groupOptionContent: {
    marginTop: 4,
  },
  groupOptionDescription: {
    fontSize: 12,
    marginBottom: 6,
    lineHeight: 16,
  },
  groupRadioContainer: {
    backgroundColor: 'white',
    borderRadius: 6,
    padding: 8,
  },
  calendarCard: {
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 12,
    elevation: 2,
  },
  daySection: {
    flex: 1,
    padding: 16,
  },
  dayTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
    textTransform: 'capitalize',
  },
  listContainer: {
    flexGrow: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 40,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  errorIcon: {
    marginBottom: 16,
  },
  errorText: {
    fontSize: 18,
    textAlign: 'center',
  },
  emptyCard: {
    borderRadius: 12,
    elevation: 1,
  },
  emptyContent: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  emptyIcon: {
    marginBottom: 16,
  },
  emptyText: {
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 24,
  },
  appointmentCard: {
    marginBottom: 12,
    borderRadius: 12,
    elevation: 2,
  },
  appointmentHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  timeSection: {
    flex: 1,
  },
  timeWithIcon: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  settingIconSmall: {
    backgroundColor: '#E3F2FD',
  },
  appointmentTime: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  statusChip: {
    alignSelf: 'flex-start',
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  patientAvatar: {
    borderWidth: 2,
    borderColor: '#E0E0E0',
    marginRight: 8,
  },
  appointmentDetails: {
    marginBottom: 12,
  },
  patientName: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  appointmentType: {
    fontSize: 16,
    fontWeight: '500',
    marginBottom: 2,
  },
  patientPhone: {
    fontSize: 14,
  },
  notesSection: {
    marginBottom: 12,
    padding: 12,
    backgroundColor: '#F5F5F5',
    borderRadius: 8,
  },
  notesLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  notesText: {
    fontSize: 14,
    lineHeight: 20,
  },
  appointmentActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    flex: 0.32,
    borderRadius: 8,
  },
  // Stili per la modale di segnalazione assenza
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContainer: {
    backgroundColor: 'white',
    borderRadius: 16,
    width: '90%',
    maxHeight: '85%',
    elevation: 5,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.25,
    shadowRadius: 3.84,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#E0E0E0',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  closeButton: {
    padding: 4,
  },
  modalContent: {
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderTopWidth: 1,
    borderTopColor: '#E0E0E0',
  },
  cancelButton: {
    flex: 1,
    marginRight: 8,
  },
  confirmButton: {
    flex: 1,
    marginLeft: 8,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  appointmentInfo: {
    fontSize: 16,
    marginBottom: 16,
    textAlign: 'center',
    fontWeight: '500',
  },
  divider: {
    marginVertical: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 12,
    marginTop: 8,
  },

  // Stili per la modale delle note
  noteInput: {
    marginBottom: 16,
  },
  patientInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F5F7FA',
    padding: 10,
    borderRadius: 8,
    marginBottom: 8,
  },
  miniAvatar: {
    marginRight: 12,
  },
  patientInfoText: {
    flex: 1,
  },
  patientNameInfo: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  appointmentTimeInfo: {
    fontSize: 14,
    color: '#666',
    marginTop: 2,
  },
  compactDivider: {
    marginVertical: 6,
  },
  compactSectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginLeft: 6,
  },
  radioRow: {
    flexDirection: 'row',
    marginBottom: 8,
    backgroundColor: '#F8F9FA',
    borderRadius: 8,
    padding: 8,
  },
  radioOption: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    paddingVertical: 4,
  },
  radioText: {
    fontSize: 14,
    marginLeft: 4,
    fontWeight: '500',
  },
  reasonsContainer: {
    marginBottom: 8,
    backgroundColor: '#F8F9FA',
    borderRadius: 8,
    padding: 8,
  },
  reasonItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 4,
  },
  reasonText: {
    fontSize: 14,
    marginLeft: 4,
    flex: 1,
    fontWeight: '500',
  },
  compactCustomInput: {
    marginTop: 6,
    marginBottom: 8,
  },
  compactNotesInput: {
    marginTop: 6,
    marginBottom: 6,
  },

  // Aggiungi questi stili per la selezione paziente
  patientSelectionContainer: {
    marginTop: 12,
    backgroundColor: '#F5F5F5',
    borderRadius: 6,
    padding: 8,
  },
  patientSelectionTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: '#666',
    marginBottom: 8,
  },
  patientSelectionScroll: {
    maxHeight: 150,
  },
  patientSelectionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 6,
    paddingHorizontal: 4,
    borderRadius: 4,
  },
  patientSelectionItemSelected: {
    backgroundColor: '#E3F2FD',
  },
  patientSelectionInfo: {
    flex: 1,
    marginLeft: 8,
  },
  patientSelectionName: {
    fontSize: 14,
    color: '#333',
  },
  patientSelectionStatus: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  // Stili per la selezione gruppo migliorata
  groupChoiceContainer: {
    marginTop: 6,
  },
  groupChoiceOption: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'white',
    borderRadius: 8,
    padding: 10,
    marginBottom: 6,
    borderWidth: 1,
    borderColor: '#E0E0E0',
  },
  groupChoiceOptionSelected: {
    borderWidth: 2,
    borderColor: '#2196F3',
    backgroundColor: '#F3F9FF',
  },
  groupChoiceTextContainer: {
    flex: 1,
    marginLeft: 8,
  },
  groupChoiceMainText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  groupChoiceSubText: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  patientDropdownContainer: {
    marginTop: 6,
    backgroundColor: 'white',
    borderRadius: 8,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#E0E0E0',
    elevation: 1,
  },
  patientDropdownHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: 'white',
  },
  patientDropdownTitle: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  patientDropdownTitleText: {
    fontSize: 14,
    color: '#333',
    marginLeft: 8,
  },
  patientListContainer: {
    backgroundColor: '#F5F5F5',
  },
  patientListItem: {
    backgroundColor: 'white',
    marginHorizontal: 0,
    marginVertical: 1,
    borderRadius: 0,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  patientListItemSelected: {
    backgroundColor: '#F3F9FF',
  },
  patientListItemDisabled: {
    backgroundColor: '#F5F5F5',
    opacity: 0.6,
  },
  patientListItemContent: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  patientListItemText: {
    flex: 1,
    marginLeft: 8,
  },
  patientListItemTitle: {
    fontSize: 14,
    color: '#333',
    fontWeight: '500',
  },
  patientListItemDescription: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  // Stili per gli stati dei pazienti del gruppo
  groupPatientsStatus: {
    marginTop: 12,
    padding: 12,
    backgroundColor: '#F8F9FA',
    borderRadius: 8,
  },
  groupPatientsHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  groupPatientsTitle: {
    fontSize: 12,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  groupActionChip: {
    alignSelf: 'flex-start',
  },
  groupPatientsList: {
    gap: 6,
  },
  groupPatientItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  groupPatientInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  groupPatientName: {
    fontSize: 13,
    fontWeight: '500',
    flex: 1,
  },
  groupPatientStatusChip: {
    alignSelf: 'flex-start',
    marginLeft: 8,
  },
});

export default TherapistCalendarScreen;
