import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  RefreshControl,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
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

const TherapistDashboardScreen = ({navigation}) => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const [dashboardData, setDashboardData] = useState(null);
  const [weeklyHoursData, setWeeklyHoursData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

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

  const loadDashboardData = async () => {
    try {
      setError(null);
      const response = await therapistService.getDashboardData();
      console.log('response dashboard dashboard screen', response);

      if (response.success) {
        setDashboardData(response.data);
        console.log('dashboard data dashboard screen', dashboardData);

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
          <TouchableOpacity
            style={styles.statCard}
            onPress={() => navigation.navigate('TherapistPatientsScreen')}>
            <Card style={styles.statCardInner}>
              <Card.Content style={styles.statContent}>
                <Avatar.Icon
                  size={48}
                  icon="account-group"
                  style={[
                    styles.statIcon,
                    {backgroundColor: theme.colors.secondaryContainer},
                  ]}
                />
                <Text
                  style={[styles.statValue, {color: theme.colors.onSurface}]}>
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
          </TouchableOpacity>

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

        {/* Card Ore Settimana - Occupa tutta la larghezza */}
        <Card style={styles.fullWidthCard}>
          <Card.Content style={styles.weeklyHoursContent}>
            <View style={styles.weeklyHoursHeader}>
              <Avatar.Icon
                size={48}
                icon="clock"
                style={[
                  styles.statIcon,
                  {backgroundColor: theme.colors.tertiaryContainer},
                ]}
              />
              <View style={styles.weeklyHoursData}>
                <Text
                  style={[
                    styles.weeklyHoursValue,
                    {color: theme.colors.onSurface},
                  ]}>
                  {weeklyHoursData
                    ? `${weeklyHoursData.totalHours}/${weeklyHoursData.contractHours}h`
                    : '0/0h'}
                </Text>
                <Text
                  style={[
                    styles.weeklyHoursLabel,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Ore Lavorate / Contratto
                </Text>
                {weeklyHoursData && (
                  <Text
                    style={[
                      styles.weekPeriod,
                      {color: theme.colors.onSurfaceVariant},
                    ]}>
                    Settimana:{' '}
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
              </View>
            </View>
            {weeklyHoursData && (
              <View style={styles.progressContainer}>
                <View
                  style={[
                    styles.progressBar,
                    {backgroundColor: theme.colors.surfaceVariant},
                  ]}>
                  <View
                    style={[
                      styles.progressFill,
                      {
                        backgroundColor: weeklyHoursData.isOverContract
                          ? theme.colors.error
                          : theme.colors.primary,
                        width: `${Math.min(
                          (weeklyHoursData.totalHours /
                            weeklyHoursData.contractHours) *
                            100,
                          100,
                        )}%`,
                      },
                    ]}
                  />
                </View>
              </View>
            )}
          </Card.Content>
        </Card>
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
                  <View style={styles.appointmentTimeContainer}>
                    <Avatar.Icon
                      size={36}
                      icon={getSettingIcon(appointment.setting_id)}
                      style={[
                        styles.settingIcon,
                        {backgroundColor: theme.colors.surfaceVariant},
                      ]}
                    />
                    <View>
                      <Text
                        style={[
                          styles.appointmentDate,
                          {color: theme.colors.primary},
                        ]}>
                        {appointment.datetime
                          ? new Date(appointment.datetime).toLocaleDateString(
                              'it-IT',
                              {
                                weekday: 'short',
                                day: '2-digit',
                                month: '2-digit',
                              },
                            )
                          : 'Data non disponibile'}
                      </Text>
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
                  </View>
                  <Avatar.Text
                    size={40}
                    label={appointment.patient.name
                      .split(' ')
                      .map(name => name.charAt(0))
                      .join('')
                      .toUpperCase()
                      .substring(0, 2)}
                    style={[
                      styles.patientAvatar,
                      {backgroundColor: theme.colors.primaryContainer},
                    ]}
                    labelStyle={{color: theme.colors.onPrimaryContainer}}
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
  statCardInner: {
    borderRadius: 12,
    elevation: 2,
  },
  fullWidthCard: {
    borderRadius: 12,
    elevation: 2,
    marginTop: 12,
  },
  weeklyHoursContent: {
    paddingVertical: 20,
  },
  weeklyHoursHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  weeklyHoursData: {
    marginLeft: 16,
    flex: 1,
  },
  weeklyHoursValue: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  weeklyHoursLabel: {
    fontSize: 14,
    marginBottom: 4,
  },
  progressContainer: {
    marginTop: 8,
  },
  progressBar: {
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 4,
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
  weekPeriod: {
    fontSize: 10,
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
  appointmentTimeContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  settingIcon: {
    borderRadius: 8,
  },
  appointmentDate: {
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 2,
    textTransform: 'uppercase',
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
