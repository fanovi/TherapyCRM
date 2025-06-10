import React from "react";
import { View, StyleSheet, ScrollView, StatusBar } from "react-native";
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  IconButton,
} from "react-native-paper";
import { useSelector, useDispatch } from "react-redux";
import LinearGradient from "react-native-linear-gradient";
import { logoutUser } from "../../store/authSlice";

const TherapistHomeScreen = () => {
  const dispatch = useDispatch();
  const { user } = useSelector((state) => state.auth);

  const handleLogout = () => {
    dispatch(logoutUser());
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#4CAF50" />

      {/* Header */}
      <LinearGradient colors={["#4CAF50", "#388E3C"]} style={styles.header}>
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

      <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
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
                <Text style={styles.statNumber}>24</Text>
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
                <Text style={styles.statNumber}>8</Text>
                <Text style={styles.statLabel}>Oggi</Text>
              </Card.Content>
            </Card>
          </View>

          <View style={styles.statsRow}>
            <Card style={styles.statCard}>
              <Card.Content style={styles.statContent}>
                <Avatar.Icon size={32} icon="clock" style={styles.statIcon} />
                <Text style={styles.statNumber}>32</Text>
                <Text style={styles.statLabel}>Ore Settimana</Text>
              </Card.Content>
            </Card>

            <Card style={styles.statCard}>
              <Card.Content style={styles.statContent}>
                <Avatar.Icon
                  size={32}
                  icon="chart-line"
                  style={styles.statIcon}
                />
                <Text style={styles.statNumber}>96%</Text>
                <Text style={styles.statLabel}>Soddisfazione</Text>
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
                  <Text style={styles.appointmentTime}>10:30 - 11:30</Text>
                  <Text style={styles.appointmentType}>Fisioterapia</Text>
                </View>
                <Chip icon="account" style={styles.patientChip}>
                  Mario Rossi
                </Chip>
              </View>

              <View style={styles.appointmentNotes}>
                <Text style={styles.notesLabel}>Note:</Text>
                <Text style={styles.notesText}>
                  Controllo recupero post-operatorio ginocchio
                </Text>
              </View>

              <View style={styles.appointmentActions}>
                <Button
                  mode="outlined"
                  style={styles.actionButton}
                  icon="phone"
                  compact
                >
                  Chiama
                </Button>
                <Button
                  mode="contained"
                  style={styles.actionButton}
                  icon="file-document"
                  compact
                >
                  Cartella
                </Button>
              </View>
            </Card.Content>
          </Card>

          <Card style={styles.appointmentCard}>
            <Card.Content>
              <View style={styles.appointmentHeader}>
                <View>
                  <Text style={styles.appointmentTime}>14:00 - 15:00</Text>
                  <Text style={styles.appointmentType}>Consulenza</Text>
                </View>
                <Chip icon="account" style={styles.patientChip}>
                  Anna Bianchi
                </Chip>
              </View>

              <View style={styles.appointmentNotes}>
                <Text style={styles.notesLabel}>Note:</Text>
                <Text style={styles.notesText}>
                  Prima visita - valutazione posturale
                </Text>
              </View>

              <View style={styles.appointmentActions}>
                <Button
                  mode="outlined"
                  style={styles.actionButton}
                  icon="phone"
                  compact
                >
                  Chiama
                </Button>
                <Button
                  mode="contained"
                  style={styles.actionButton}
                  icon="file-document"
                  compact
                >
                  Cartella
                </Button>
              </View>
            </Card.Content>
          </Card>
        </View>

        {/* Azioni Rapide */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Azioni Rapide</Text>

          <View style={styles.quickActions}>
            <Button
              mode="contained"
              icon="account-plus"
              style={styles.quickButton}
              contentStyle={styles.quickButtonContent}
            >
              Nuovo Paziente
            </Button>

            <Button
              mode="outlined"
              icon="calendar-plus"
              style={styles.quickButton}
              contentStyle={styles.quickButtonContent}
            >
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
    backgroundColor: "#F8F9FA",
  },
  header: {
    paddingTop: 20,
    paddingBottom: 20,
  },
  headerContent: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
  },
  userInfo: {
    flexDirection: "row",
    alignItems: "center",
  },
  avatar: {
    backgroundColor: "rgba(255, 255, 255, 0.2)",
  },
  userDetails: {
    marginLeft: 16,
  },
  greeting: {
    color: "rgba(255, 255, 255, 0.8)",
    fontSize: 14,
  },
  userName: {
    color: "white",
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 4,
  },
  roleChip: {
    backgroundColor: "rgba(255, 255, 255, 0.2)",
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
    fontWeight: "bold",
    color: "#333",
    marginBottom: 16,
  },
  statsRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 12,
  },
  statCard: {
    flex: 0.48,
    borderRadius: 12,
    elevation: 2,
  },
  statContent: {
    alignItems: "center",
    paddingVertical: 16,
  },
  statIcon: {
    backgroundColor: "#E8F5E8",
    marginBottom: 8,
  },
  statNumber: {
    fontSize: 20,
    fontWeight: "bold",
    color: "#333",
    marginBottom: 4,
  },
  statLabel: {
    fontSize: 12,
    color: "#666",
    textAlign: "center",
  },
  appointmentCard: {
    borderRadius: 12,
    elevation: 2,
    marginBottom: 12,
  },
  appointmentHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  appointmentTime: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#333",
  },
  appointmentType: {
    fontSize: 14,
    color: "#666",
  },
  patientChip: {
    backgroundColor: "#E3F2FD",
  },
  appointmentNotes: {
    marginBottom: 16,
  },
  notesLabel: {
    fontSize: 12,
    color: "#666",
    marginBottom: 4,
  },
  notesText: {
    fontSize: 14,
    color: "#333",
  },
  appointmentActions: {
    flexDirection: "row",
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
});

export default TherapistHomeScreen;
