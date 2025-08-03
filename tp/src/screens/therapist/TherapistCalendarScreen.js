import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  RefreshControl,
  FlatList,
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
} from '../../api/calendar';
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

    setMenuVisible({});
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

    setIsSubmittingAbsence(true);

    try {
      const finalReason =
        absenceForm.reason === 'Altro'
          ? absenceForm.customReason.trim()
          : absenceForm.reason;

      const response = await markPatientAbsent(
        appointment.id,
        absenceForm.absenceType,
        finalReason,
        absenceForm.notes.trim(),
      );

      if (response.success) {
        const absenceTypeLabel =
          absenceForm.absenceType === 'justified'
            ? 'giustificata'
            : 'non giustificata';

        Alert.alert(
          'Assenza Segnalata',
          `L'assenza ${absenceTypeLabel} del paziente ${
            appointment.patient.name
          } per l'appuntamento del ${moment(appointment.datetime).format(
            'DD/MM/YYYY',
          )} alle ${appointment.time} è stata registrata con successo.`,
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
    // Calcola se l'appuntamento può essere completato
    const now = moment();
    const appointmentStart = moment(appointment.datetime);
    const appointmentEnd = moment(appointment.datetime).add(
      appointment.duration_minutes,
      'minutes',
    );
    const fifteenMinutesAfterEnd = moment(appointmentEnd).add(15, 'minutes');

    // Può essere completato se:
    // 1. È in stato confermato
    // 2. È già iniziato
    // 3. Non sono passati più di 15 minuti dalla fine
    const canComplete =
      appointment.status === 'confermato' &&
      now.isAfter(appointmentStart) &&
      now.isBefore(fifteenMinutesAfterEnd);

    // Può segnare assente se:
    // 1. È in stato confermato
    // 2. Non è nel passato (l'appuntamento non è ancora iniziato)
    const appointmentDate = moment(appointment.datetime);
    const yesterday = moment().subtract(1, 'day').startOf('day');
    const tomorrow = moment().add(1, 'day').endOf('day');

    const canMarkAbsent =
      appointment.status === 'confermato' &&
      appointmentDate.isAfter(yesterday) &&
      appointmentDate.isBefore(tomorrow);

    // Mostra il menu se almeno un'azione è disponibile
    const showMenu = canMarkAbsent || true; // Sempre mostrare per le note

    return (
      <Card style={styles.appointmentCard}>
        <Card.Content>
          <View style={styles.appointmentHeader}>
            <View style={styles.timeSection}>
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
                  <Menu.Item
                    onPress={() => handleAddNote(appointment)}
                    title="Aggiungi Note"
                    leadingIcon="note-text"
                  />
                  {/* Commento le azioni non richieste
                  <Menu.Item
                    onPress={() => openActionDialog(appointment, 'reschedule')}
                    title="Riprogramma"
                    leadingIcon="calendar-edit"
                  />
                  <Menu.Item
                    onPress={() => openActionDialog(appointment, 'cancel')}
                    title="Cancella"
                    leadingIcon="cancel"
                  />
                  */}
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
            {appointment.patient.phone && (
              <Button
                mode="outlined"
                style={styles.actionButton}
                icon="phone"
                compact
                onPress={() =>
                  Linking.openURL(`tel:${appointment.patient.phone}`)
                }>
                Chiama
              </Button>
            )}

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

        {/* Dialog segnalazione assenza */}
        <Dialog
          visible={absenceDialog.visible}
          onDismiss={() =>
            setAbsenceDialog({visible: false, appointment: null})
          }
          style={styles.absenceDialog}>
          <Dialog.Title style={{fontSize: 18}}>Segnala Assenza</Dialog.Title>
          <Dialog.Content>
            <View style={styles.dialogContent}>
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

              <Divider style={styles.compactDivider} />

              {/* Tipo di assenza */}
              <Text style={styles.compactSectionTitle}>Tipo</Text>
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
              <Text style={styles.compactSectionTitle}>Motivo</Text>
              <ScrollView
                style={styles.reasonsScroll}
                showsVerticalScrollIndicator={false}>
                {getAbsenceReasons().map(reason => (
                  <View key={reason} style={styles.compactReasonItem}>
                    <RadioButton
                      value={reason}
                      status={
                        absenceForm.reason === reason ? 'checked' : 'unchecked'
                      }
                      onPress={() =>
                        setAbsenceForm(prev => ({...prev, reason}))
                      }
                      color={theme.colors.primary}
                    />
                    <Text
                      style={styles.compactReasonText}
                      onPress={() =>
                        setAbsenceForm(prev => ({...prev, reason}))
                      }>
                      {reason}
                    </Text>
                  </View>
                ))}
              </ScrollView>

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
            </View>
          </Dialog.Content>
          <Dialog.Actions style={styles.dialogActions}>
            <Button
              onPress={() =>
                setAbsenceDialog({visible: false, appointment: null})
              }
              disabled={isSubmittingAbsence}>
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
              mode="contained">
              Conferma
            </Button>
          </Dialog.Actions>
        </Dialog>

        {/* Dialog aggiunta note */}
        <Dialog
          visible={noteDialog.visible}
          onDismiss={() => setNoteDialog({visible: false, appointment: null})}
          style={styles.noteDialog}>
          <Dialog.Title>Aggiungi Note Appuntamento</Dialog.Title>
          <Dialog.Content>
            <ScrollView style={styles.dialogContent}>
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
                style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
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
          </Dialog.Content>
          <Dialog.Actions style={styles.dialogActions}>
            <Button
              onPress={() => setNoteDialog({visible: false, appointment: null})}
              disabled={isSubmittingNote}>
              Annulla
            </Button>
            <Button
              onPress={confirmAddNote}
              buttonColor={theme.colors.primary}
              loading={isSubmittingNote}
              disabled={isSubmittingNote || !noteForm.note.trim()}
              mode="contained">
              {isSubmittingNote ? 'Salvataggio...' : 'Salva Note'}
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
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
  absenceDialog: {
    maxHeight: '80%',
  },
  dialogContent: {
    maxHeight: 400,
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
  absenceTypeContainer: {
    marginBottom: 16,
  },
  absenceTypeItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
  },
  absenceTypeText: {
    fontSize: 16,
    marginLeft: 8,
    flex: 1,
  },
  reasonsContainer: {
    marginBottom: 16,
  },
  reasonItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
  },
  reasonText: {
    fontSize: 16,
    marginLeft: 8,
    flex: 1,
  },
  customReasonInput: {
    marginBottom: 16,
  },
  notesInput: {
    marginBottom: 16,
  },

  // Stili per la modale delle note
  noteDialog: {
    maxHeight: '80%',
  },
  noteInput: {
    marginBottom: 16,
  },
  // Stili per la modale compatta
  absenceDialog: {
    maxHeight: '85%',
  },
  dialogContent: {
    paddingHorizontal: 0,
  },
  patientInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F5F7FA',
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
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
    marginVertical: 12,
  },
  compactSectionTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: '#666',
    marginBottom: 8,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  radioRow: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  radioOption: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  radioText: {
    fontSize: 14,
    marginLeft: 4,
  },
  reasonsScroll: {
    maxHeight: 120,
    marginBottom: 12,
  },
  compactReasonItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 6,
  },
  compactReasonText: {
    fontSize: 14,
    marginLeft: 8,
    flex: 1,
  },
  compactCustomInput: {
    marginTop: 8,
    marginBottom: 12,
  },
  compactNotesInput: {
    marginTop: 8,
  },
  dialogActions: {
    justifyContent: 'space-between',
    paddingHorizontal: 16,
  },
});

export default TherapistCalendarScreen;
