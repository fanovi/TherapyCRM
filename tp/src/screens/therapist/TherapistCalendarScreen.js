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
  Menu,
} from 'react-native-paper';
import {Calendar} from 'react-native-calendars';
import {useSelector} from 'react-redux';
import moment from 'moment';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getAppointmentsByDate,
  getMarkedDates,
} from '../../data/mockAppointments';

const TherapistCalendarScreen = () => {
  const theme = useTheme();
  const {user} = useSelector(state => state.auth);

  const [selectedDate, setSelectedDate] = useState(
    moment().format('YYYY-MM-DD'),
  );
  const [dayAppointments, setDayAppointments] = useState([]);
  const [markedDates, setMarkedDates] = useState({});
  const [actionDialog, setActionDialog] = useState({
    visible: false,
    appointment: null,
    action: null,
  });
  const [menuVisible, setMenuVisible] = useState({});

  useEffect(() => {
    if (user?.email) {
      const appointments = getAppointmentsByDate(
        user.email,
        'therapist',
        selectedDate,
      );
      setDayAppointments(appointments);

      const marked = getMarkedDates(user.email, 'therapist');
      const finalMarked = {
        ...marked,
        [selectedDate]: {
          ...marked[selectedDate],
          selected: true,
          selectedColor: theme.colors.secondary,
        },
      };

      setMarkedDates(finalMarked);
    }
  }, [selectedDate, user?.email, theme.colors.secondary]);

  const handleDayPress = day => {
    setSelectedDate(day.dateString);
  };

  const openActionDialog = (appointment, action) => {
    setActionDialog({visible: true, appointment, action});
    setMenuVisible({});
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

  const getStatusColor = status => {
    switch (status) {
      case 'confermato':
        return theme.colors.secondary;
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

  const toggleMenu = appointmentId => {
    setMenuVisible(prev => ({
      ...prev,
      [appointmentId]: !prev[appointmentId],
    }));
  };

  return (
    <ScreenTemplate title="Agenda" subtitle="I tuoi appuntamenti">
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <Card style={styles.calendarCard}>
          <Card.Content>
            <Calendar
              current={selectedDate}
              onDayPress={handleDayPress}
              markedDates={markedDates}
              firstDay={1}
              enableSwipeMonths={true}
              hideExtraDays={true}
              monthFormat={'MMMM yyyy'}
            />
          </Card.Content>
        </Card>

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
                    <View style={styles.headerActions}>
                      <Avatar.Image
                        size={48}
                        source={{uri: appointment.patient.avatar}}
                        style={styles.patientAvatar}
                      />
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
                        <Menu.Item
                          onPress={() =>
                            openActionDialog(appointment, 'absent')
                          }
                          title="Segna Assente"
                          leadingIcon="account-remove"
                        />
                        <Menu.Item
                          onPress={() =>
                            openActionDialog(appointment, 'reschedule')
                          }
                          title="Riprogramma"
                          leadingIcon="calendar-edit"
                        />
                        <Menu.Item
                          onPress={() =>
                            openActionDialog(appointment, 'cancel')
                          }
                          title="Cancella"
                          leadingIcon="cancel"
                        />
                      </Menu>
                    </View>
                  </View>

                  <View style={styles.appointmentDetails}>
                    <Text
                      style={[
                        styles.patientName,
                        {color: theme.colors.onSurface},
                      ]}>
                      {appointment.patient.name}
                    </Text>
                    <Text
                      style={[
                        styles.appointmentType,
                        {color: theme.colors.secondary},
                      ]}>
                      {appointment.type}
                    </Text>
                    <Text
                      style={[
                        styles.patientPhone,
                        {color: theme.colors.onSurfaceVariant},
                      ]}>
                      {appointment.patient.phone}
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

                  <View style={styles.appointmentActions}>
                    <Button
                      mode="outlined"
                      style={styles.actionButton}
                      icon="phone"
                      compact>
                      Chiama
                    </Button>
                    <Button
                      mode="outlined"
                      style={styles.actionButton}
                      icon="folder-open"
                      compact>
                      Cartella
                    </Button>
                    <Button
                      mode="contained"
                      style={[
                        styles.actionButton,
                        {backgroundColor: theme.colors.secondary},
                      ]}
                      icon="check"
                      compact>
                      Completa
                    </Button>
                  </View>
                </Card.Content>
              </Card>
            ))
          )}
        </View>
      </ScrollView>

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
      </Portal>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {flex: 1},
  calendarCard: {
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 12,
    elevation: 2,
  },
  daySection: {padding: 16},
  dayTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
    textTransform: 'capitalize',
  },
  emptyCard: {borderRadius: 12, elevation: 1},
  emptyContent: {alignItems: 'center', paddingVertical: 32},
  emptyIcon: {marginBottom: 16},
  emptyText: {fontSize: 16, textAlign: 'center', lineHeight: 24},
  appointmentCard: {marginBottom: 12, borderRadius: 12, elevation: 2},
  appointmentHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  timeSection: {flex: 1},
  appointmentTime: {fontSize: 18, fontWeight: 'bold', marginBottom: 4},
  statusChip: {alignSelf: 'flex-start'},
  headerActions: {flexDirection: 'row', alignItems: 'center'},
  patientAvatar: {borderWidth: 2, borderColor: '#E0E0E0', marginRight: 8},
  appointmentDetails: {marginBottom: 12},
  patientName: {fontSize: 18, fontWeight: 'bold', marginBottom: 4},
  appointmentType: {fontSize: 16, fontWeight: '500', marginBottom: 2},
  patientPhone: {fontSize: 14},
  notesSection: {
    marginBottom: 12,
    padding: 12,
    backgroundColor: '#F5F5F5',
    borderRadius: 8,
  },
  notesLabel: {fontSize: 12, fontWeight: 'bold', marginBottom: 4},
  notesText: {fontSize: 14, lineHeight: 20},
  appointmentActions: {flexDirection: 'row', justifyContent: 'space-between'},
  actionButton: {flex: 0.32, borderRadius: 8},
});

export default TherapistCalendarScreen;
