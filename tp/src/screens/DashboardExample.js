import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import DashboardNotificationBanner from '../components/DashboardNotificationBanner';

const DashboardExample = () => {
  return (
    <ScrollView style={styles.container}>
      {/* Banner per i permessi delle notifiche - si mostra solo quando necessario */}
      <DashboardNotificationBanner />

      {/* Contenuto della dashboard */}
      <View style={styles.content}>
        <Text style={styles.title}>Dashboard</Text>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Appuntamenti Oggi</Text>
          <Text style={styles.cardContent}>3 appuntamenti programmati</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Messaggi Non Letti</Text>
          <Text style={styles.cardContent}>5 nuovi messaggi</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Attività Recenti</Text>
          <Text style={styles.cardContent}>Ultimo aggiornamento: 2 ore fa</Text>
        </View>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  content: {
    padding: 16,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 20,
    color: '#333',
  },
  card: {
    backgroundColor: 'white',
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 3.84,
    elevation: 5,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 8,
    color: '#333',
  },
  cardContent: {
    fontSize: 14,
    color: '#666',
  },
});

export default DashboardExample;
