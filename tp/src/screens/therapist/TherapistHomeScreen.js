import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  StatusBar,
  RefreshControl,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  IconButton,
  ActivityIndicator,
} from 'react-native-paper';
import {useSelector, useDispatch} from 'react-redux';
import LinearGradient from 'react-native-linear-gradient';
import {loginService} from '../../services/loginService';
import {therapistService} from '../../services/therapistService';
import DashboardNotificationBanner from '../../components/DashboardNotificationBanner';

const TherapistHomeScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);

  const [dashboardData, setDashboardData] = useState(null);
  const [weeklyHoursData, setWeeklyHoursData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const loadDashboardData = async () => {
    try {
      setError(null);
      const response = await therapistService.getDashboardData();
      console.log('response', response);

      if (response.success) {
        setDashboardData(response.data);

        // Recupera anche le ore settimanali
        const weeklyResponse = await therapistService.getWeeklyHours();
        console.log('weekly hours response', weeklyResponse);

        if (weeklyResponse.success) {
          setWeeklyHoursData(weeklyResponse.data);
        }
      } else {
        setError('Errore nel caricamento dei dati');
      }
    } catch (err) {
      setError('Errore di connessione');
      console.error('Errore caricamento dashboard:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadDashboardData();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    loadDashboardData();
  };

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

  const formatTime = datetime => {
    const date = new Date(datetime);
    return date.toLocaleTimeString('it-IT', {
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  if (loading) {
    return (
      <View style={[styles.container, styles.centered]}>
        <ActivityIndicator size="large" color="#4CAF50" />
        <Text style={styles.loadingText}>Caricamento dashboard...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#4CAF50" />

      {/* Header */}
      <LinearGradient colors={['#4CAF50', '#388E3C']} style={styles.header}>
        <View style={styles.headerContent}>
          <View style={styles.userInfo}>
            <Avatar.Icon size={48} icon="doctor" style={styles.avatar} />
            <View style={styles.userDetails}>
              <Text style={styles.greeting}>Benvenuto,</Text>
              <Text style={styles.userName}>
                {user?.firstName} {user?.lastName}
              </Text>
              <Chip icon="medical-bag" style={styles.roleChip}>
                Terapista
              </Chip>
            </View>
          </View>
          <IconButton
            icon="logout"
            iconColor="white"
            size={24}
            onPress={handleLogout}
          />
        </View>
      </LinearGradient>

      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
        }>
        {error && (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
            <Button mode="outlined" onPress={loadDashboardData}>
              Riprova
            </Button>
          </View>
        )}

        {/* Banner per i permessi delle notifiche */}
        <DashboardNotificationBanner />

        {/* Statistiche */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Dashboard</Text>

          <View style={styles.statsRow}>
            <Card style={styles.statCard}>
              <Card.Content style={styles.statContent}>
                <Avatar.Icon
                  size={32}
                  icon="account-group"
                  style={styles.statIcon}
                />
                <Text style={styles.statNumber}>
                  {dashboardData?.stats?.activePatientsCount || 0}
                </Text>
                <Text style={styles.statLabel}>Pazienti Attivi</Text>
              </Card.Content>
            </Card>

            <Card style={styles.statCard}>
              <Card.Content style={styles.statContent}>
                <Avatar.Icon
                  size={32}
                  icon="calendar-today"
                  style={styles.statIcon}
                />
                <Text style={styles.statNumber}>
                  {dashboardData?.stats?.todayCompletedAppointments || 0}/
                  {dashboardData?.stats?.todayAppointments || 0}
                </Text>
                <Text style={styles.statLabel}>Oggi</Text>
              </Card.Content>
            </Card>
          </View>

          <View style={styles.statsRow}>
            <Card style={styles.statCard}>
              <Card.Content style={styles.statContent}>
                <Avatar.Icon
                  size={32}
                  icon="calendar-clock"
                  style={styles.statIcon}
                />
                <Text style={styles.statNumber}>
                  {weeklyHoursData
                    ? `${weeklyHoursData.totalHours}/${weeklyHoursData.contractHours}`
                    : '0/0'}
                </Text>
                <Text style={styles.statLabel}>Ore Settimana</Text>
                {weeklyHoursData && (
                  <Text style={styles.weekPeriod}>
                    {new Date(weeklyHoursData.weekStart).toLocaleDateString(
                      'it-IT',
                      {day: '2-digit', month: '2-digit'},
                    )}{' '}
                    -{' '}
                    {new Date(weeklyHoursData.weekEnd).toLocaleDateString(
                      'it-IT',
                      {day: '2-digit', month: '2-digit'},
                    )}
                  </Text>
                )}
              </Card.Content>
            </Card>
          </View>
        </View>

        {/* Prossimi Appuntamenti */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Prossimi Appuntamenti</Text>

          {dashboardData?.upcomingAppointments?.length > 0 ? (
            dashboardData.upcomingAppointments.map((appointment, index) => (
              <Card
                key={appointment.id || index}
                style={styles.appointmentCard}>
                <Card.Content>
                  <View style={styles.appointmentHeader}>
                    <View>
                      <Text style={styles.appointmentTime}>
                        {appointment.time}
                      </Text>
                      <Text style={styles.appointmentType}>
                        {appointment.type}
                      </Text>
                    </View>
                    <Chip icon="account" style={styles.patientChip}>
                      {appointment.patient.name}
                    </Chip>
                  </View>

                  {appointment.notes && (
                    <View style={styles.appointmentNotes}>
                      <Text style={styles.notesLabel}>Note:</Text>
                      <Text style={styles.notesText}>{appointment.notes}</Text>
                    </View>
                  )}

                  <View style={styles.appointmentActions}>
                    <Button
                      mode="contained"
                      style={styles.actionButton}
                      icon="file-document"
                      compact>
                      Cartella
                    </Button>
                  </View>
                </Card.Content>
              </Card>
            ))
          ) : (
            <Card style={styles.appointmentCard}>
              <Card.Content>
                <Text style={styles.noAppointmentsText}>
                  Nessun appuntamento in programma
                </Text>
              </Card.Content>
            </Card>
          )}
        </View>

        {/* Azioni Rapide */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Azioni Rapide</Text>

          <View style={styles.quickActions}>
            <Button
              mode="contained"
              icon="account-plus"
              style={styles.quickButton}
              contentStyle={styles.quickButtonContent}>
              Nuovo Paziente
            </Button>

            <Button
              mode="outlined"
              icon="calendar-plus"
              style={styles.quickButton}
              contentStyle={styles.quickButtonContent}>
              Aggiungi Appuntamento
            </Button>
          </View>
        </View>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8F9FA',
  },
  centered: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    color: '#666',
  },
  errorContainer: {
    padding: 20,
    backgroundColor: '#FFE5E5',
    borderRadius: 10,
    marginHorizontal: 20,
    marginBottom: 10,
    alignItems: 'center',
  },
  errorText: {
    color: '#D32F2F',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 10,
  },
  header: {
    paddingTop: 20,
    paddingBottom: 20,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
  },
  userInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatar: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
  },
  userDetails: {
    marginLeft: 16,
  },
  greeting: {
    color: 'rgba(255, 255, 255, 0.8)',
    fontSize: 14,
  },
  userName: {
    color: 'white',
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  roleChip: {
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
  },
  content: {
    flex: 1,
  },
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
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  statCard: {
    flex: 0.48,
    borderRadius: 12,
    elevation: 2,
  },
  statContent: {
    alignItems: 'center',
    paddingVertical: 16,
  },
  statIcon: {
    backgroundColor: '#E8F5E8',
    marginBottom: 8,
  },
  statNumber: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: '#666',
    textAlign: 'center',
  },
  weekPeriod: {
    fontSize: 10,
    color: '#666',
    textAlign: 'center',
    marginTop: 2,
    fontStyle: 'italic',
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
    marginBottom: 12,
  },
  appointmentTime: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  appointmentType: {
    fontSize: 14,
    color: '#666',
  },
  patientChip: {
    backgroundColor: '#E3F2FD',
  },
  appointmentNotes: {
    marginBottom: 16,
  },
  notesLabel: {
    fontSize: 12,
    color: '#666',
    marginBottom: 4,
  },
  notesText: {
    fontSize: 14,
    color: '#333',
  },
  appointmentActions: {
    flexDirection: 'row',
    gap: 12,
  },
  actionButton: {
    flex: 1,
    borderRadius: 8,
  },
  quickActions: {
    gap: 12,
  },
  quickButton: {
    borderRadius: 8,
  },
  quickButtonContent: {
    height: 48,
  },
  noAppointmentsText: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    paddingVertical: 20,
  },
});

export default TherapistHomeScreen;
