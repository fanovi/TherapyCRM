import React, {useState, useEffect} from 'react';
import {View, StyleSheet, RefreshControl, ScrollView} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  IconButton,
  useTheme,
  ActivityIndicator,
} from 'react-native-paper';
import {useSelector, useDispatch} from 'react-redux';
import {loginService} from '../../services/loginService';
import {therapistService} from '../../services/therapistService';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistDashboardScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const [dashboardData, setDashboardData] = useState(null);
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

  if (loading) {
    return (
      <View style={[styles.loadingContainer]}>
        <ActivityIndicator size="large" color={theme.colors.primary} />
        <Text style={[styles.loadingText, {color: theme.colors.onSurface}]}>
          Caricamento dashboard...
        </Text>
      </View>
    );
  }

  return (
    <ScreenTemplate
      title="Dashboard"
      subtitle={`Benvenuto, ${user?.firstName || user?.name || 'Dottore'}`}
      headerRight={
        <IconButton
          icon="logout"
          size={24}
          iconColor={theme.colors.secondary}
          onPress={handleLogout}
        />
      }
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
      }>
      {error && (
        <View
          style={[
            styles.errorContainer,
            {backgroundColor: theme.colors.errorContainer},
          ]}>
          <Text style={[styles.errorText, {color: theme.colors.error}]}>
            {error}
          </Text>
          <Button mode="outlined" onPress={loadDashboardData}>
            Riprova
          </Button>
        </View>
      )}

      {/* Statistiche */}
      <View style={styles.section}>
        <View style={styles.statsGrid}>
          <Card style={styles.statCard}>
            <Card.Content style={styles.statContent}>
              <Avatar.Icon
                size={48}
                icon="account-group"
                style={[
                  styles.statIcon,
                  {backgroundColor: theme.colors.secondaryContainer},
                ]}
              />
              <Text style={[styles.statValue, {color: theme.colors.onSurface}]}>
                {dashboardData?.stats?.activePatientsCount || 0}
              </Text>
              <Text
                style={[
                  styles.statLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Pazienti Attivi
              </Text>
            </Card.Content>
          </Card>

          <Card style={styles.statCard}>
            <Card.Content style={styles.statContent}>
              <Avatar.Icon
                size={48}
                icon="calendar-today"
                style={[
                  styles.statIcon,
                  {backgroundColor: theme.colors.primaryContainer},
                ]}
              />
              <Text style={[styles.statValue, {color: theme.colors.onSurface}]}>
                {dashboardData?.stats?.todayCompletedAppointments || 0}/
                {dashboardData?.stats?.todayAppointments || 0}
              </Text>
              <Text
                style={[
                  styles.statLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Oggi
              </Text>
            </Card.Content>
          </Card>
        </View>

        <View style={styles.statsGrid}>
          <Card style={styles.statCard}>
            <Card.Content style={styles.statContent}>
              <Avatar.Icon
                size={48}
                icon="clock"
                style={[
                  styles.statIcon,
                  {backgroundColor: theme.colors.tertiaryContainer},
                ]}
              />
              <Text style={[styles.statValue, {color: theme.colors.onSurface}]}>
                {dashboardData?.stats?.weeklyHours || 0}h
              </Text>
              <Text
                style={[
                  styles.statLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Ore Settimana
              </Text>
            </Card.Content>
          </Card>

          <Card style={styles.statCard}>
            <Card.Content style={styles.statContent}>
              <Avatar.Icon
                size={48}
                icon="thumb-up"
                style={[styles.statIcon, {backgroundColor: '#E8F5E8'}]}
              />
              <Text style={[styles.statValue, {color: theme.colors.onSurface}]}>
                {dashboardData?.stats?.satisfactionRate || 0}%
              </Text>
              <Text
                style={[
                  styles.statLabel,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Soddisfazione
              </Text>
            </Card.Content>
          </Card>
        </View>
      </View>

      {/* Prossimi Appuntamenti */}
      <View style={styles.section}>
        <Text style={[styles.sectionTitle, {color: theme.colors.onSurface}]}>
          Prossimi Appuntamenti
        </Text>

        {dashboardData?.upcomingAppointments?.length > 0 ? (
          dashboardData.upcomingAppointments.map((appointment, index) => (
            <Card key={appointment.id || index} style={styles.appointmentCard}>
              <Card.Content>
                <View style={styles.appointmentHeader}>
                  <View>
                    <Text
                      style={[
                        styles.appointmentTime,
                        {color: theme.colors.onSurface},
                      ]}>
                      {appointment.time}
                    </Text>
                    <Text
                      style={[
                        styles.appointmentType,
                        {color: theme.colors.onSurfaceVariant},
                      ]}>
                      {appointment.type}
                    </Text>
                  </View>
                  <Avatar.Image
                    size={40}
                    source={{
                      uri: `https://i.pravatar.cc/150?img=${appointment.patient.id}`,
                    }}
                    style={styles.patientAvatar}
                  />
                </View>

                <View style={styles.appointmentDetails}>
                  <View style={styles.patientInfo}>
                    <Text
                      style={[
                        styles.patientName,
                        {color: theme.colors.onSurface},
                      ]}>
                      {appointment.patient.name}
                    </Text>
                    {appointment.notes && (
                      <Text
                        style={[
                          styles.appointmentNote,
                          {color: theme.colors.onSurfaceVariant},
                        ]}>
                        Note: {appointment.notes}
                      </Text>
                    )}
                  </View>
                </View>

                <View style={styles.appointmentActions}>
                  {appointment.patient.phone && (
                    <Button
                      mode="outlined"
                      style={styles.actionButton}
                      labelStyle={{color: theme.colors.secondary}}
                      icon="phone">
                      Chiama
                    </Button>
                  )}
                  <Button
                    mode="contained"
                    style={[
                      styles.actionButton,
                      {backgroundColor: theme.colors.secondary},
                    ]}
                    icon="folder-open">
                    Cartella
                  </Button>
                </View>
              </Card.Content>
            </Card>
          ))
        ) : (
          <Card style={styles.appointmentCard}>
            <Card.Content>
              <Text
                style={[
                  styles.noAppointmentsText,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                Nessun appuntamento in programma
              </Text>
            </Card.Content>
          </Card>
        )}
      </View>
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
    marginBottom: 16,
  },
  statsGrid: {
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
    paddingVertical: 20,
  },
  statIcon: {
    marginBottom: 12,
  },
  statValue: {
    fontSize: 28,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
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
    marginBottom: 12,
  },
  appointmentTime: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  appointmentType: {
    fontSize: 14,
    marginTop: 2,
  },
  patientAvatar: {
    borderWidth: 2,
    borderColor: '#E0E0E0',
  },
  appointmentDetails: {
    marginBottom: 16,
  },
  patientInfo: {
    flex: 1,
  },
  patientName: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  appointmentNote: {
    fontSize: 14,
    lineHeight: 20,
  },
  appointmentActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    flex: 0.48,
    borderRadius: 8,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  loadingText: {
    marginTop: 10,
  },
  errorContainer: {
    padding: 15,
    borderRadius: 10,
    marginHorizontal: 20,
    marginBottom: 10,
    alignItems: 'center',
  },
  errorText: {
    textAlign: 'center',
    marginBottom: 10,
  },
  noAppointmentsText: {
    textAlign: 'center',
    paddingVertical: 20,
  },
});

export default TherapistDashboardScreen;
