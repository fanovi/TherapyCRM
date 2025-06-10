import React, {useState, useEffect} from 'react';
import {View, StyleSheet, ScrollView, Alert} from 'react-native';
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
} from 'react-native-paper';
import {Calendar} from 'react-native-calendars';
import {useSelector} from 'react-redux';
import moment from 'moment';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getAppointmentsByDate,
  getMarkedDates,
  canCancelAppointment,
} from '../../data/mockAppointments';

const PatientCalendarScreen = () => {
  const theme = useTheme();
  const {user} = useSelector(state => state.auth);

  const [selectedDate, setSelectedDate] = useState(
    moment().format('YYYY-MM-DD'),
  );
  const [dayAppointments, setDayAppointments] = useState([]);
  const [markedDates, setMarkedDates] = useState({});
  const [cancelDialog, setCancelDialog] = useState({
    visible: false,
    appointment: null,
  });

  useEffect(() => {
    if (user?.email) {
      // Carica appuntamenti del giorno selezionato
      const appointments = getAppointmentsByDate(
        user.email,
        'patient',
        selectedDate,
      );
      setDayAppointments(appointments);

      // Carica giorni con appuntamenti per evidenziarli
      const marked = getMarkedDates(user.email, 'patient');

      // Aggiungi selezione giorno corrente
      const finalMarked = {
        ...marked,
        [selectedDate]: {
          ...marked[selectedDate],
          selected: true,
          selectedColor: theme.colors.primary,
        },
      };

      setMarkedDates(finalMarked);
    }
  }, [selectedDate, user?.email, theme.colors.primary]);

  const handleDayPress = day => {
    setSelectedDate(day.dateString);
  };

  const handleCancelAppointment = appointment => {
    setCancelDialog({
      visible: true,
      appointment,
    });
  };

  const confirmCancelAppointment = () => {
    const {appointment} = cancelDialog;

    // Qui normalmente faresti una chiamata API
    Alert.alert(
      'Appuntamento Cancellato',
      `Il tuo appuntamento del ${moment(appointment.date).format(
        'DD/MM/YYYY',
      )} alle ${appointment.time} con ${
        appointment.therapist.name
      } è stato cancellato.`,
      [
        {
          text: 'OK',
          onPress: () => {
            // Ricarica gli appuntamenti
            const updatedAppointments = getAppointmentsByDate(
              user.email,
              'patient',
              selectedDate,
            );
            setDayAppointments(updatedAppointments);
          },
        },
      ],
    );

    setCancelDialog({visible: false, appointment: null});
  };

  const getStatusColor = status => {
    switch (status) {
      case 'confermato':
        return theme.colors.primary;
      case 'completato':
        return theme.colors.success;
      case 'annullato':
        return theme.colors.error;
      default:
        return theme.colors.onSurfaceVariant;
    }
  };

  const getStatusLabel = status => {
    switch (status) {
      case 'confermato':
        return 'Confermato';
      case 'completato':
        return 'Completato';
      case 'annullato':
        return 'Annullato';
      default:
        return status;
    }
  };

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

  return (
    <ScreenTemplate title="Calendario" subtitle="I tuoi appuntamenti">
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Calendario */}
        <Card style={styles.calendarCard}>
          <Card.Content>
            <Calendar
              current={selectedDate}
              onDayPress={handleDayPress}
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

          {dayAppointments.length === 0 ? (
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
                  style={[
                    styles.emptyText,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Nessun appuntamento{'\n'}per questo giorno
                </Text>
              </Card.Content>
            </Card>
          ) : (
            dayAppointments.map(appointment => (
              <Card key={appointment.id} style={styles.appointmentCard}>
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
                            backgroundColor: `${getStatusColor(
                              appointment.status,
                            )}20`,
                          },
                        ]}
                        textStyle={{
                          color: getStatusColor(appointment.status),
                          fontSize: 12,
                        }}>
                        {getStatusLabel(appointment.status)}
                      </Chip>
                    </View>
                    <Avatar.Image
                      size={48}
                      source={{uri: appointment.therapist.avatar}}
                      style={styles.therapistAvatar}
                    />
                  </View>

                  <View style={styles.appointmentDetails}>
                    <Text
                      style={[
                        styles.therapistName,
                        {color: theme.colors.onSurface},
                      ]}>
                      {appointment.therapist.name}
                    </Text>
                    <Text
                      style={[
                        styles.appointmentType,
                        {color: theme.colors.primary},
                      ]}>
                      {appointment.type}
                    </Text>
                    <Text
                      style={[
                        styles.specialization,
                        {color: theme.colors.onSurfaceVariant},
                      ]}>
                      {appointment.therapist.specialization}
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
                      <Text
                        style={[
                          styles.notesText,
                          {color: theme.colors.onSurface},
                        ]}>
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
                    <Button
                      mode="outlined"
                      style={styles.actionButton}
                      icon="phone"
                      compact>
                      Chiama
                    </Button>

                    {appointment.status === 'confermato' &&
                      canCancelAppointment(appointment.date) && (
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
            ))
          )}
        </View>
      </ScrollView>

      {/* Dialog conferma cancellazione */}
      <Portal>
        <Dialog
          visible={cancelDialog.visible}
          onDismiss={() =>
            setCancelDialog({visible: false, appointment: null})
          }>
          <Dialog.Title>Conferma Cancellazione</Dialog.Title>
          <Dialog.Content>
            <Paragraph>
              Sei sicuro di voler cancellare l'appuntamento del{' '}
              {cancelDialog.appointment &&
                moment(cancelDialog.appointment.date).format('DD/MM/YYYY')}{' '}
              alle {cancelDialog.appointment?.time}?
            </Paragraph>
          </Dialog.Content>
          <Dialog.Actions>
            <Button
              onPress={() =>
                setCancelDialog({visible: false, appointment: null})
              }>
              Annulla
            </Button>
            <Button
              onPress={confirmCancelAppointment}
              buttonColor={theme.colors.error}>
              Conferma
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
    padding: 16,
  },
  dayTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
    textTransform: 'capitalize',
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
});

export default PatientCalendarScreen;
