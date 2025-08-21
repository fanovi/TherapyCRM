import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Alert,
} from 'react-native';
import {
  Text,
  Card,
  Avatar,
  Chip,
  IconButton,
  useTheme,
  ActivityIndicator,
} from 'react-native-paper';
import {useSelector, useDispatch} from 'react-redux';
import ScreenTemplate from '../../components/ScreenTemplate';
import DebugPatientInfo from '../../components/DebugPatientInfo';
import DashboardNotificationBanner from '../../components/DashboardNotificationBanner';
import {loginService} from '../../services/loginService';
import {getPatientUpcomingAppointments} from '../../api/calendar';

const PatientHomeScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const {currentPatient} = useSelector(state => state.patient);
  const theme = useTheme();

  // Stati per gli appuntamenti
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

  // Funzione per caricare i prossimi appuntamenti
  const loadUpcomingAppointments = async (isRefresh = false) => {
    if (!currentPatient?.id) {
      console.log('❌ Paziente non selezionato');
      setLoading(false);
      return;
    }

    try {
      if (isRefresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }
      setError(null);

      console.log(
        '🔄 Caricamento prossimi appuntamenti per paziente:',
        currentPatient.id,
      );
      console.log(
        '🌐 URL CHIAMATO: https://app-cgm.badil.it/api/calendar/patient-upcoming-appointments',
      );
      console.log('📋 PARAMETRI:', {
        patient_id: currentPatient.id,
        limit: 3,
      });
      const response = await getPatientUpcomingAppointments(
        currentPatient.id,
        3,
      );

      if (response.success && response.data) {
        setAppointments(response.data);
        console.log('✅ Appuntamenti caricati:', response.data);
      } else {
        setAppointments([]);
        console.log('⚠️ Nessun appuntamento trovato');
      }
    } catch (err) {
      console.error('❌ Errore caricamento appuntamenti:', err);
      setError(err.message || 'Errore nel caricamento degli appuntamenti');
      setAppointments([]);

      // Mostra alert solo se non è un errore di rete
      if (err.type !== 'NETWORK_ERROR') {
        Alert.alert(
          'Errore',
          err.message || 'Impossibile caricare gli appuntamenti',
          [{text: 'OK'}],
        );
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  // Carica gli appuntamenti all'avvio
  useEffect(() => {
    loadUpcomingAppointments();
  }, [currentPatient?.id]);

  // Funzione per formattare la data
  const formatAppointmentDate = (date, time) => {
    if (!date) return 'Data non disponibile';

    try {
      const appointmentDate = new Date(date);
      const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      };
      return appointmentDate.toLocaleDateString('it-IT', options);
    } catch (error) {
      return date;
    }
  };

  // Funzione per formattare l'orario
  const formatAppointmentTime = (time, duration = null) => {
    if (!time) return 'Orario non disponibile';

    try {
      // Se time è già in formato HH:MM, usalo direttamente
      let formattedTime = time;
      if (time.includes(':')) {
        formattedTime = time.substring(0, 5); // Prendi solo HH:MM
      }

      if (duration) {
        const [hours, minutes] = formattedTime.split(':');
        const startTime = new Date();
        startTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);
        const endTime = new Date(startTime.getTime() + duration * 60000);
        const endTimeStr = endTime.toTimeString().substring(0, 5);
        return `${formattedTime} - ${endTimeStr}`;
      }

      return formattedTime;
    } catch (error) {
      return time;
    }
  };

  // Funzione per ottenere il colore dello stato
  const getStatusColor = status => {
    switch (status?.toLowerCase()) {
      case 'confermato':
        return '#E3F2FD';
      case 'completato':
        return '#E8F5E8';
      case 'annullato':
        return '#FFEBEE';
      case 'assente_giustificato':
        return '#FFF3E0';
      case 'assente_non_giustificato':
        return '#FFEBEE';
      default:
        return '#F5F5F5';
    }
  };

  // Funzione per refresh
  const onRefresh = () => {
    loadUpcomingAppointments(true);
  };

  return (
    <ScreenTemplate
      title="Dashboard Paziente"
      subtitle="Gestisci i tuoi appuntamenti e monitora la tua salute"
      headerRight={
        <IconButton
          icon="logout"
          iconColor="#757575"
          size={24}
          onPress={handleLogout}
        />
      }>
      {/* Banner per i permessi delle notifiche */}
      <DashboardNotificationBanner />

      {/* Componente Debug temporaneo */}
      {/* <DebugPatientInfo /> */}
      {/* Quick Actions */}
      {/* <View style={styles.section}>
        <Text style={styles.sectionTitle}>Azioni Rapide</Text>

        <View style={styles.actionsRow}>
          <Card style={styles.actionCard}>
            <Card.Content style={styles.actionContent}>
              <Avatar.Icon
                size={40}
                icon="calendar-plus"
                style={styles.actionIcon}
              />
              <Text style={styles.actionTitle}>Prenota</Text>
              <Text style={styles.actionSubtitle}>Nuovo appuntamento</Text>
            </Card.Content>
          </Card>

          <Card style={styles.actionCard}>
            <Card.Content style={styles.actionContent}>
              <Avatar.Icon
                size={40}
                icon="file-document"
                style={styles.actionIcon}
              />
              <Text style={styles.actionTitle}>Referti</Text>
              <Text style={styles.actionSubtitle}>Visualizza risultati</Text>
            </Card.Content>
          </Card>
        </View>

        <View style={styles.actionsRow}>
          <Card style={styles.actionCard}>
            <Card.Content style={styles.actionContent}>
              <Avatar.Icon size={40} icon="chat" style={styles.actionIcon} />
              <Text style={styles.actionTitle}>Messaggi</Text>
              <Text style={styles.actionSubtitle}>Chat con terapista</Text>
            </Card.Content>
          </Card>

          <Card style={styles.actionCard}>
            <Card.Content style={styles.actionContent}>
              <Avatar.Icon
                size={40}
                icon="heart-pulse"
                style={styles.actionIcon}
              />
              <Text style={styles.actionTitle}>Stato</Text>
              <Text style={styles.actionSubtitle}>Condizioni di salute</Text>
            </Card.Content>
          </Card>
        </View>
      </View> */}

      {/* Prossimi Appuntamenti */}
      <ScrollView
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        showsVerticalScrollIndicator={false}>
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Prossimi Appuntamenti</Text>

          {loading && !refreshing ? (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" color={theme.colors.primary} />
              <Text style={styles.loadingText}>
                Caricamento appuntamenti...
              </Text>
            </View>
          ) : error && appointments.length === 0 ? (
            <Card style={styles.errorCard}>
              <Card.Content>
                <View style={styles.errorContainer}>
                  <Avatar.Icon
                    size={48}
                    icon="alert-circle-outline"
                    style={styles.errorIcon}
                  />
                  <Text style={styles.errorText}>{error}</Text>
                  <Text style={styles.errorSubtext}>
                    Scorri verso il basso per riprovare
                  </Text>
                </View>
              </Card.Content>
            </Card>
          ) : appointments.length === 0 ? (
            <Card style={styles.emptyCard}>
              <Card.Content>
                <View style={styles.emptyContainer}>
                  <Avatar.Icon
                    size={48}
                    icon="calendar-blank"
                    style={styles.emptyIcon}
                  />
                  <Text style={styles.emptyText}>
                    Nessun appuntamento programmato
                  </Text>
                  <Text style={styles.emptySubtext}>
                    I tuoi prossimi appuntamenti appariranno qui
                  </Text>
                </View>
              </Card.Content>
            </Card>
          ) : (
            appointments.map((appointment, index) => (
              <Card
                key={appointment.id || index}
                style={styles.appointmentCard}>
                <Card.Content>
                  <View style={styles.appointmentHeader}>
                    <View>
                      <Text style={styles.appointmentDate}>
                        {formatAppointmentDate(appointment.date)}
                      </Text>
                      <Text style={styles.appointmentTime}>
                        {formatAppointmentTime(
                          appointment.time,
                          appointment.duration_minutes,
                        )}
                      </Text>
                    </View>
                    <Chip
                      icon="doctor"
                      style={[
                        styles.statusChip,
                        {backgroundColor: getStatusColor(appointment.status)},
                      ]}>
                      {appointment.status || 'Confermato'}
                    </Chip>
                  </View>

                  <View style={styles.appointmentDetails}>
                    <Avatar.Icon
                      size={40}
                      icon="account-tie"
                      style={styles.doctorAvatar}
                    />
                    <View style={styles.doctorInfo}>
                      <Text style={styles.doctorName}>
                        {appointment.therapist?.name ||
                          `${appointment.therapist?.first_name || ''} ${
                            appointment.therapist?.last_name || ''
                          }`.trim() ||
                          'Terapista non assegnato'}
                      </Text>
                      <Text style={styles.appointmentType}>
                        {appointment.type ||
                          appointment.appointment_type ||
                          'Terapia'}
                      </Text>
                      {appointment.location && (
                        <Text style={styles.appointmentLocation}>
                          📍 {appointment.location}
                        </Text>
                      )}
                    </View>
                  </View>

                  {appointment.notes && (
                    <View style={styles.notesContainer}>
                      <Text style={styles.notesLabel}>Note:</Text>
                      <Text style={styles.notesText}>{appointment.notes}</Text>
                    </View>
                  )}
                </Card.Content>
              </Card>
            ))
          )}
        </View>
      </ScrollView>

      {/* Stato Salute */}
      {/* <View style={styles.section}>
        <Text style={styles.sectionTitle}>Stato di Salute</Text>

        <Card style={styles.healthCard}>
          <Card.Content>
            <View style={styles.healthMetric}>
              <Avatar.Icon size={32} icon="heart" style={styles.healthIcon} />
              <View style={styles.healthInfo}>
                <Text style={styles.healthLabel}>Battito Cardiaco</Text>
                <Text style={styles.healthValue}>72 bpm</Text>
              </View>
              <Chip style={styles.healthStatus}>Normale</Chip>
            </View>

            <View style={styles.healthMetric}>
              <Avatar.Icon
                size={32}
                icon="scale-bathroom"
                style={styles.healthIcon}
              />
              <View style={styles.healthInfo}>
                <Text style={styles.healthLabel}>Peso</Text>
                <Text style={styles.healthValue}>68 kg</Text>
              </View>
              <Chip style={styles.healthStatus}>Stabile</Chip>
            </View>
          </Card.Content>
        </Card>
      </View> */}
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  section: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 16,
  },
  actionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  actionCard: {
    flex: 0.48,
    borderRadius: 12,
    elevation: 2,
  },
  actionContent: {
    alignItems: 'center',
    paddingVertical: 16,
  },
  actionIcon: {
    backgroundColor: '#E3F2FD',
    marginBottom: 8,
  },
  actionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  actionSubtitle: {
    fontSize: 12,
    color: '#666',
    textAlign: 'center',
  },
  appointmentCard: {
    borderRadius: 12,
    elevation: 2,
    marginBottom: 12,
  },
  appointmentHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  appointmentDate: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  appointmentTime: {
    fontSize: 14,
    color: '#666',
  },
  statusChip: {
    backgroundColor: '#E8F5E8',
  },
  appointmentDetails: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  doctorAvatar: {
    backgroundColor: '#F3E5F5',
  },
  doctorInfo: {
    marginLeft: 12,
    flex: 1,
  },
  doctorName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  appointmentType: {
    fontSize: 14,
    color: '#666',
  },
  appointmentButton: {
    borderRadius: 8,
  },
  healthCard: {
    borderRadius: 12,
    elevation: 2,
  },
  healthMetric: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F0F0F0',
  },
  healthIcon: {
    backgroundColor: '#FFF3E0',
  },
  healthInfo: {
    marginLeft: 12,
    flex: 1,
  },
  healthLabel: {
    fontSize: 14,
    color: '#666',
  },
  healthValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  healthStatus: {
    backgroundColor: '#E8F5E8',
  },
  // Nuovi stili per gli appuntamenti dinamici
  loadingContainer: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#666',
  },
  errorCard: {
    borderRadius: 12,
    elevation: 2,
    marginBottom: 12,
    borderLeftWidth: 4,
    borderLeftColor: '#F44336',
  },
  errorContainer: {
    alignItems: 'center',
    paddingVertical: 24,
  },
  errorIcon: {
    backgroundColor: '#FFEBEE',
    marginBottom: 12,
  },
  errorText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#D32F2F',
    textAlign: 'center',
    marginBottom: 8,
  },
  errorSubtext: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
  },
  emptyCard: {
    borderRadius: 12,
    elevation: 2,
    marginBottom: 12,
  },
  emptyContainer: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  emptyIcon: {
    backgroundColor: '#F5F5F5',
    marginBottom: 16,
  },
  emptyText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    textAlign: 'center',
    marginBottom: 8,
  },
  emptySubtext: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
  },
  appointmentLocation: {
    fontSize: 12,
    color: '#888',
    marginTop: 2,
  },
  notesContainer: {
    marginTop: 12,
    padding: 12,
    backgroundColor: '#F8F9FA',
    borderRadius: 8,
  },
  notesLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#666',
    marginBottom: 4,
  },
  notesText: {
    fontSize: 14,
    color: '#333',
    lineHeight: 20,
  },
});

export default PatientHomeScreen;
