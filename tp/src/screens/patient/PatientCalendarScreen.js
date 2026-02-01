import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  Alert,
  RefreshControl,
  ScrollView,
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
  getPatientAppointments,
  getPatientMarkedDates,
  getAppointmentStatusColor,
  getAppointmentStatusLabel,
  generateMarkedDates,
  cancelPatientAppointment,
  getCancellationReasons,
} from '../../api/calendar';

const PatientCalendarScreen = () => {
  const theme = useTheme();

  const {currentPatient} = useSelector(state => state.patient);

  const [selectedDate, setSelectedDate] = useState(
    moment().format('YYYY-MM-DD'),
  );
  const [dayAppointments, setDayAppointments] = useState([]);
  const [markedDates, setMarkedDates] = useState({});
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [cancelDialog, setCancelDialog] = useState({
    visible: false,
    appointment: null,
  });

  const [cancellationForm, setCancellationForm] = useState({
    reason: '',
    customReason: '',
    notes: '',
  });

  const [isSubmittingCancellation, setIsSubmittingCancellation] =
    useState(false);

  // Recupera il patient_id dal currentPatient
  const patientId =
    currentPatient?.patient_id || currentPatient?.account_patient_id;

  useEffect(() => {
    if (patientId) {
      loadAppointments();
      loadMarkedDates();
    }
  }, [selectedDate, patientId]);

  const loadAppointments = async () => {
    if (!patientId) return;

    try {
      setLoading(true);
      const response = await getPatientAppointments(patientId, selectedDate);

      if (response.success) {
        setDayAppointments(response.data || []);
      } else {
        // Errore dal backend ma non di autenticazione
        console.warn('Backend error:', response.error);
        Alert.alert(
          'Errore',
          response.error || 'Errore nel caricamento degli appuntamenti',
        );
      }
    } catch (error) {
      console.error('Errore caricamento appuntamenti:', error);

      // Gestione errori più specifica
      if (error.type === 'AUTH_ERROR') {
        // Errore di autenticazione - l'interceptor gestirà il logout
        console.log('Errore di autenticazione, logout automatico in corso...');
        // Non mostrare alert, l'utente verrà reindirizzato al login
      } else if (error.type === 'PERMISSION_ERROR') {
        // Errore di permessi - mostra messaggio ma non fare logout
        Alert.alert('Accesso Negato', error.message);
      } else if (error.type === 'CALENDAR_ERROR') {
        // Errore specifico del calendario - mostra messaggio ma non fare logout
        Alert.alert('Errore Calendario', error.message);
      } else if (error.type === 'NETWORK_ERROR') {
        // Errore di rete - messaggio specifico
        Alert.alert(
          'Errore di Connessione',
          'Verifica la tua connessione internet e riprova.',
        );
      } else {
        // Errore generico
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
    if (!patientId) return;

    try {
      const currentMonth = moment(selectedDate).format('YYYY-MM');
      const response = await getPatientMarkedDates(patientId, currentMonth);

      if (response.success) {
        const finalMarked = generateMarkedDates(
          response.data || {},
          selectedDate,
          theme.colors.primary,
        );
        setMarkedDates(finalMarked);
      } else {
        console.warn('Backend error for marked dates:', response.error);
        // Non mostrare alert per le date marcate, non è critico
      }
    } catch (error) {
      console.error('Errore caricamento date marcate:', error);

      // Gestione errori più specifica per date marcate
      if (error.type === 'AUTH_ERROR') {
        // Errore di autenticazione - l'interceptor gestirà il logout
        console.log(
          'Errore di autenticazione per date marcate, logout automatico in corso...',
        );
      } else if (error.type === 'PERMISSION_ERROR') {
        // Errore di permessi - log ma non mostrare alert (non critico)
        console.warn('Errore di permessi per date marcate:', error.message);
      } else {
        // Altri errori - non mostrare alert, non è critico per l'UX
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
    if (patientId) {
      loadMarkedDatesForMonth(newMonth);
    }
  };

  const loadMarkedDatesForMonth = async month => {
    try {
      const response = await getPatientMarkedDates(patientId, month);
      if (response.success) {
        const finalMarked = generateMarkedDates(
          response.data || {},
          selectedDate,
          theme.colors.primary,
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

      // Gestione errori specifica per cambio mese
      if (error.type === 'AUTH_ERROR') {
        console.log(
          'Errore di autenticazione per cambio mese, logout automatico in corso...',
        );
      } else if (error.type === 'PERMISSION_ERROR') {
        // Errore di permessi - log ma non mostrare alert (non critico)
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

  const handleCancelAppointment = appointment => {
    setCancelDialog({
      visible: true,
      appointment,
    });

    // Reset form
    setCancellationForm({
      reason: '',
      customReason: '',
      notes: '',
    });
  };

  const confirmCancelAppointment = async () => {
    const {appointment} = cancelDialog;

    // Validazione
    if (!cancellationForm.reason) {
      Alert.alert('Errore', 'Seleziona un motivo per la cancellazione');
      return;
    }

    if (
      cancellationForm.reason === 'Altro' &&
      !cancellationForm.customReason.trim()
    ) {
      Alert.alert('Errore', 'Specifica il motivo personalizzato');
      return;
    }

    setIsSubmittingCancellation(true);

    try {
      const finalReason =
        cancellationForm.reason === 'Altro'
          ? cancellationForm.customReason.trim()
          : cancellationForm.reason;

      const response = await cancelPatientAppointment(
        appointment.id,
        finalReason,
        cancellationForm.notes.trim(),
      );

      if (response.success) {
        Alert.alert(
          'Appuntamento Cancellato',
          `Il tuo appuntamento del ${moment(appointment.datetime).format(
            'DD/MM/YYYY',
          )} alle ${appointment.time} è stato cancellato con successo.`,
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
          response.error || 'Errore durante la cancellazione',
        );
      }
    } catch (error) {
      console.error('Errore cancellazione appuntamento:', error);

      if (error.type === 'AUTH_ERROR') {
        // L'interceptor gestirà il logout
        console.log('Errore di autenticazione, logout automatico in corso...');
      } else if (error.type === 'CANCELLATION_ERROR') {
        Alert.alert('Errore Cancellazione', error.message);
      } else {
        Alert.alert(
          'Errore',
          "Impossibile cancellare l'appuntamento. Riprova più tardi.",
        );
      }
    } finally {
      setIsSubmittingCancellation(false);
      setCancelDialog({visible: false, appointment: null});
    }
  };

  const renderAppointmentItem = ({item: appointment}) => (
    <Card style={styles.appointmentCard}>
      <Card.Content>
        <View style={styles.appointmentHeader}>
          <View style={styles.timeSection}>
            <Text
              style={[styles.appointmentTime, {color: theme.colors.onSurface}]}>
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
          {appointment.therapist && (
            <Avatar.Image
              size={48}
              source={{uri: appointment.therapist.avatar}}
              style={styles.therapistAvatar}
            />
          )}
        </View>

        <View style={styles.appointmentDetails}>
          {/* Nome terapista nascosto per privacy
          {appointment.therapist && (
            <Text
              style={[styles.therapistName, {color: theme.colors.onSurface}]}>
              {appointment.therapist.name}
            </Text>
          )}
          */}
          <Text style={[styles.appointmentType, {color: theme.colors.primary}]}>
            {appointment.type}
          </Text>
          {appointment.therapist?.specialization && (
            <Text
              style={[
                styles.specialization,
                {color: theme.colors.onSurfaceVariant},
              ]}>
              {appointment.therapist.specialization}
            </Text>
          )}
          <Text
            style={[styles.duration, {color: theme.colors.onSurfaceVariant}]}>
            Durata: {appointment.duration_minutes} minuti
          </Text>
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

        <View style={styles.locationSection}>
          <Avatar.Icon
            size={24}
            icon="map-marker"
            style={styles.locationIcon}
          />
          <Text
            style={[
              styles.locationText,
              {color: theme.colors.onSurfaceVariant},
            ]}>
            {appointment.location}
          </Text>
        </View>

        {/* Azioni */}
        <View style={styles.appointmentActions}>
          {appointment.status === 'confermato' &&
            !moment(appointment.datetime).isBefore(moment()) && (
              <Button
                mode="contained"
                style={[styles.actionButton, styles.cancelButton]}
                buttonColor={theme.colors.error}
                icon="cancel"
                onPress={() => handleCancelAppointment(appointment)}
                compact>
                Disdici
              </Button>
            )}
        </View>
      </Card.Content>
    </Card>
  );

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

  const calendarTheme = {
    backgroundColor: theme.colors.background,
    calendarBackground: theme.colors.surface,
    textSectionTitleColor: theme.colors.onSurface,
    selectedDayBackgroundColor: theme.colors.primary,
    selectedDayTextColor: theme.colors.onPrimary,
    todayTextColor: theme.colors.primary,
    dayTextColor: theme.colors.onSurface,
    textDisabledColor: theme.colors.onSurfaceVariant,
    dotColor: theme.colors.primary,
    selectedDotColor: theme.colors.onPrimary,
    arrowColor: theme.colors.primary,
    monthTextColor: theme.colors.onSurface,
    indicatorColor: theme.colors.primary,
    textDayFontFamily: 'System',
    textMonthFontFamily: 'System',
    textDayHeaderFontFamily: 'System',
    textDayFontSize: 16,
    textMonthFontSize: 18,
    textDayHeaderFontSize: 14,
  };

  if (!patientId) {
    return (
      <ScreenTemplate title="Calendario" subtitle="I tuoi appuntamenti">
        <View style={styles.errorContainer}>
          <Avatar.Icon
            size={64}
            icon="alert-circle"
            style={[
              styles.errorIcon,
              {backgroundColor: theme.colors.errorContainer},
            ]}
          />
          <Text style={[styles.errorText, {color: theme.colors.error}]}>
            Nessun paziente selezionato
          </Text>
        </View>
      </ScreenTemplate>
    );
  }

  return (
    <ScreenTemplate title="Calendario" subtitle="I tuoi appuntamenti">
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={true}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            colors={[theme.colors.primary]}
          />
        }>
        {/* Calendario */}
        <Card style={styles.calendarCard}>
          <Card.Content>
            <Calendar
              current={selectedDate}
              onDayPress={handleDayPress}
              onMonthChange={handleMonthChange}
              markedDates={markedDates}
              theme={calendarTheme}
              firstDay={1} // Lunedì come primo giorno
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
              <ActivityIndicator size="large" color={theme.colors.primary} />
              <Text
                style={[
                  styles.loadingText,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Caricamento appuntamenti...
              </Text>
            </View>
          ) : dayAppointments.length === 0 ? (
            renderEmptyState()
          ) : (
            <View style={styles.listContainer}>
              {dayAppointments.map(item => (
                <View key={item.id.toString()}>
                  {renderAppointmentItem({item})}
                </View>
              ))}
            </View>
          )}
        </View>
      </ScrollView>

      {/* Dialog conferma cancellazione */}
      <Portal>
        <Dialog
          visible={cancelDialog.visible}
          onDismiss={() => setCancelDialog({visible: false, appointment: null})}
          style={styles.cancellationDialog}>
          <Dialog.Title>Cancella Appuntamento</Dialog.Title>
          <Dialog.Content>
            <ScrollView style={styles.dialogContent}>
              <Paragraph style={styles.appointmentInfo}>
                Stai per cancellare l'appuntamento del{' '}
                {cancelDialog.appointment &&
                  moment(cancelDialog.appointment.datetime).format(
                    'DD/MM/YYYY',
                  )}{' '}
                alle {cancelDialog.appointment?.time}
              </Paragraph>

              <Divider style={styles.divider} />

              <Text
                style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
                Motivo della cancellazione *
              </Text>

              <View style={styles.reasonsContainer}>
                {getCancellationReasons().map(reason => (
                  <View key={reason} style={styles.reasonItem}>
                    <RadioButton
                      value={reason}
                      status={
                        cancellationForm.reason === reason
                          ? 'checked'
                          : 'unchecked'
                      }
                      onPress={() =>
                        setCancellationForm(prev => ({...prev, reason}))
                      }
                      color={theme.colors.primary}
                    />
                    <Text
                      style={[
                        styles.reasonText,
                        {color: theme.colors.onSurface},
                      ]}
                      onPress={() =>
                        setCancellationForm(prev => ({...prev, reason}))
                      }>
                      {reason}
                    </Text>
                  </View>
                ))}
              </View>

              {cancellationForm.reason === 'Altro' && (
                <TextInput
                  label="Specifica il motivo"
                  value={cancellationForm.customReason}
                  onChangeText={text =>
                    setCancellationForm(prev => ({...prev, customReason: text}))
                  }
                  mode="outlined"
                  style={styles.customReasonInput}
                  multiline
                  numberOfLines={2}
                />
              )}

              <Text
                style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
                Note aggiuntive (opzionale)
              </Text>

              <TextInput
                label="Aggiungi note se necessario"
                value={cancellationForm.notes}
                onChangeText={text =>
                  setCancellationForm(prev => ({...prev, notes: text}))
                }
                mode="outlined"
                style={styles.notesInput}
                multiline
                numberOfLines={3}
                placeholder="Es. Dettagli aggiuntivi sulla cancellazione..."
              />
            </ScrollView>
          </Dialog.Content>
          <Dialog.Actions style={styles.dialogActions}>
            <Button
              onPress={() =>
                setCancelDialog({visible: false, appointment: null})
              }
              disabled={isSubmittingCancellation}>
              Annulla
            </Button>
            <Button
              onPress={confirmCancelAppointment}
              buttonColor={theme.colors.error}
              loading={isSubmittingCancellation}
              disabled={isSubmittingCancellation || !cancellationForm.reason}
              mode="contained">
              {isSubmittingCancellation
                ? 'Cancellazione...'
                : 'Conferma Cancellazione'}
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
  scrollContent: {
    flexGrow: 1,
    paddingBottom: 20,
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
    alignItems: 'center',
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
  therapistAvatar: {
    borderWidth: 2,
    borderColor: '#E0E0E0',
  },
  appointmentDetails: {
    marginBottom: 12,
  },
  therapistName: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  appointmentType: {
    fontSize: 16,
    fontWeight: '500',
    marginBottom: 2,
  },
  specialization: {
    fontSize: 14,
    marginBottom: 2,
  },
  duration: {
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
  locationSection: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  locationIcon: {
    backgroundColor: 'transparent',
    marginRight: 8,
  },
  locationText: {
    fontSize: 14,
    flex: 1,
  },
  appointmentActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    flex: 0.48,
    borderRadius: 8,
  },
  cancelButton: {
    marginLeft: 8,
  },
  // Stili per la modale di cancellazione
  cancellationDialog: {
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
  dialogActions: {
    justifyContent: 'space-between',
    paddingHorizontal: 16,
  },
});

export default PatientCalendarScreen;
