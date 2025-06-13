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
import ScreenTemplate from '../../components/ScreenTemplate';
import {loginService} from '../../services/loginService';

const PatientHomeScreen = () => {
  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const theme = useTheme();

  const handleLogout = async () => {
    await loginService.logout(dispatch);
  };

  return (
    <ScreenTemplate
      title={`Benvenuto, ${user?.firstName}`}
      subtitle="Gestisci i tuoi appuntamenti e monitora la tua salute"
      headerRight={
        <IconButton
          icon="logout"
          iconColor="#757575"
          size={24}
          onPress={handleLogout}
        />
      }>
      {/* Quick Actions */}
      <View style={styles.section}>
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
      </View>

      {/* Prossimi Appuntamenti */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Prossimi Appuntamenti</Text>

        <Card style={styles.appointmentCard}>
          <Card.Content>
            <View style={styles.appointmentHeader}>
              <View>
                <Text style={styles.appointmentDate}>Lunedì 15 Gennaio</Text>
                <Text style={styles.appointmentTime}>10:30 - 11:30</Text>
              </View>
              <Chip icon="doctor" style={styles.statusChip}>
                Confermato
              </Chip>
            </View>

            <View style={styles.appointmentDetails}>
              <Avatar.Icon
                size={40}
                icon="account-tie"
                style={styles.doctorAvatar}
              />
              <View style={styles.doctorInfo}>
                <Text style={styles.doctorName}>Dr. Giulia Verdi</Text>
                <Text style={styles.appointmentType}>Fisioterapia</Text>
              </View>
            </View>

            <Button
              mode="outlined"
              style={styles.appointmentButton}
              icon="map-marker">
              Visualizza Dettagli
            </Button>
          </Card.Content>
        </Card>
      </View>

      {/* Stato Salute */}
      <View style={styles.section}>
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
});

export default PatientHomeScreen;
