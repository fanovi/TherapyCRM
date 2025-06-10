import React from 'react';
import {View, StyleSheet} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  IconButton,
  useTheme,
} from 'react-native-paper';
import {useSelector, useDispatch} from 'react-redux';
import {logoutUser} from '../../store/authSlice';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistDashboardScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const handleLogout = () => {
    dispatch(logoutUser());
  };

  return (
    <ScreenTemplate
      title="Dashboard"
      subtitle={`Benvenuto, ${user?.name || 'Dottore'}`}
      headerRight={
        <IconButton
          icon="logout"
          size={24}
          iconColor={theme.colors.secondary}
          onPress={handleLogout}
        />
      }>
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
                24
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
                8
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
                32
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
                96%
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

        <Card style={styles.appointmentCard}>
          <Card.Content>
            <View style={styles.appointmentHeader}>
              <View>
                <Text
                  style={[
                    styles.appointmentTime,
                    {color: theme.colors.onSurface},
                  ]}>
                  10:30 - 11:30
                </Text>
                <Text
                  style={[
                    styles.appointmentType,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Fisioterapia
                </Text>
              </View>
              <Avatar.Image
                size={40}
                source={{uri: 'https://i.pravatar.cc/150?img=1'}}
                style={styles.patientAvatar}
              />
            </View>

            <View style={styles.appointmentDetails}>
              <View style={styles.patientInfo}>
                <Text
                  style={[styles.patientName, {color: theme.colors.onSurface}]}>
                  Mario Rossi
                </Text>
                <Text
                  style={[
                    styles.appointmentNote,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Note: Controllo recupero post-operatorio ginocchio
                </Text>
              </View>
            </View>

            <View style={styles.appointmentActions}>
              <Button
                mode="outlined"
                style={styles.actionButton}
                labelStyle={{color: theme.colors.secondary}}
                icon="phone">
                Chiama
              </Button>
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

        <Card style={styles.appointmentCard}>
          <Card.Content>
            <View style={styles.appointmentHeader}>
              <View>
                <Text
                  style={[
                    styles.appointmentTime,
                    {color: theme.colors.onSurface},
                  ]}>
                  14:00 - 15:00
                </Text>
                <Text
                  style={[
                    styles.appointmentType,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Consulenza
                </Text>
              </View>
              <Avatar.Image
                size={40}
                source={{uri: 'https://i.pravatar.cc/150?img=5'}}
                style={styles.patientAvatar}
              />
            </View>

            <View style={styles.appointmentDetails}>
              <View style={styles.patientInfo}>
                <Text
                  style={[styles.patientName, {color: theme.colors.onSurface}]}>
                  Anna Bianchi
                </Text>
                <Text
                  style={[
                    styles.appointmentNote,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  Note: Prima visita - valutazione posturale
                </Text>
              </View>
            </View>

            <View style={styles.appointmentActions}>
              <Button
                mode="outlined"
                style={styles.actionButton}
                labelStyle={{color: theme.colors.secondary}}
                icon="phone">
                Chiama
              </Button>
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
});

export default TherapistDashboardScreen;
