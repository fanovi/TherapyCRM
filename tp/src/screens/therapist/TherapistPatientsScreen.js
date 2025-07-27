import React, {useState, useEffect} from 'react';
import {View, StyleSheet, FlatList, RefreshControl} from 'react-native';
import {
  Text,
  Button,
  useTheme,
  Card,
  Avatar,
  Chip,
  ActivityIndicator,
  IconButton,
} from 'react-native-paper';
import {therapistService} from '../../services/therapistService';
import ScreenTemplate from '../../components/ScreenTemplate';

const TherapistPatientsScreen = () => {
  const theme = useTheme();
  const [patients, setPatients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const loadPatients = async () => {
    try {
      setError(null);
      const response = await therapistService.getPatients();

      if (response.success) {
        setPatients(response.data.patients);
      } else {
        setError('Errore nel caricamento dei pazienti');
      }
    } catch (err) {
      setError('Errore di connessione');
      console.error('Errore caricamento pazienti:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadPatients();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    loadPatients();
  };

  const formatDate = dateString => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  const formatDateTime = dateString => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const renderPatient = ({item}) => (
    <Card style={styles.patientCard}>
      <Card.Content>
        <View style={styles.patientHeader}>
          <Avatar.Image
            size={50}
            source={{uri: item.avatar}}
            style={styles.avatar}
          />
          <View style={styles.patientInfo}>
            <Text style={[styles.patientName, {color: theme.colors.onSurface}]}>
              {item.fullName}
            </Text>
            <Text
              style={[
                styles.patientDetail,
                {color: theme.colors.onSurfaceVariant},
              ]}>
              {item.phone || 'Telefono non disponibile'}
            </Text>
            {item.email && (
              <Text
                style={[
                  styles.patientDetail,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                {item.email}
              </Text>
            )}
          </View>
          <IconButton
            icon="phone"
            size={20}
            iconColor={theme.colors.primary}
            onPress={() => {
              // TODO: Implementare chiamata
              console.log('Chiama paziente:', item.phone);
            }}
          />
        </View>

        <View style={styles.patientStats}>
          <View style={styles.statItem}>
            <Text
              style={[
                styles.statLabel,
                {color: theme.colors.onSurfaceVariant},
              ]}>
              Appuntamenti
            </Text>
            <Chip
              style={[
                styles.statChip,
                {backgroundColor: theme.colors.primaryContainer},
              ]}>
              {item.totalAppointments}
            </Chip>
          </View>

          <View style={styles.statItem}>
            <Text
              style={[
                styles.statLabel,
                {color: theme.colors.onSurfaceVariant},
              ]}>
              Ultimo
            </Text>
            <Text style={[styles.statValue, {color: theme.colors.onSurface}]}>
              {formatDateTime(item.lastAppointment)}
            </Text>
          </View>

          <View style={styles.statItem}>
            <Text
              style={[
                styles.statLabel,
                {color: theme.colors.onSurfaceVariant},
              ]}>
              Prossimo
            </Text>
            <Text style={[styles.statValue, {color: theme.colors.onSurface}]}>
              {formatDateTime(item.nextAppointment)}
            </Text>
          </View>
        </View>

        {item.dateOfBirth && (
          <Text
            style={[styles.birthDate, {color: theme.colors.onSurfaceVariant}]}>
            Nato il: {formatDate(item.dateOfBirth)}
          </Text>
        )}
      </Card.Content>
    </Card>
  );

  if (loading) {
    return (
      <ScreenTemplate title="Pazienti" subtitle="I tuoi pazienti attivi">
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={theme.colors.primary} />
          <Text style={[styles.loadingText, {color: theme.colors.onSurface}]}>
            Caricamento pazienti...
          </Text>
        </View>
      </ScreenTemplate>
    );
  }

  return (
    <ScreenTemplate
      title="Pazienti"
      subtitle={`${patients.length} pazienti attivi`}>
      {error && (
        <View
          style={[
            styles.errorContainer,
            {backgroundColor: theme.colors.errorContainer},
          ]}>
          <Text style={[styles.errorText, {color: theme.colors.error}]}>
            {error}
          </Text>
          <Button mode="outlined" onPress={loadPatients}>
            Riprova
          </Button>
        </View>
      )}

      {patients.length === 0 && !error ? (
        <View style={styles.emptyContainer}>
          <Text
            style={[styles.emptyText, {color: theme.colors.onSurfaceVariant}]}>
            Nessun paziente attivo al momento
          </Text>
          <Button mode="contained" icon="account-plus" style={styles.addButton}>
            Aggiungi Nuovo Paziente
          </Button>
        </View>
      ) : (
        <FlatList
          data={patients}
          renderItem={renderPatient}
          keyExtractor={item => item.id.toString()}
          contentContainerStyle={styles.listContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
          }
          showsVerticalScrollIndicator={false}
        />
      )}
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
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
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  emptyText: {
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 20,
  },
  addButton: {
    borderRadius: 8,
  },
  listContainer: {
    padding: 16,
  },
  patientCard: {
    marginBottom: 12,
    borderRadius: 12,
    elevation: 2,
  },
  patientHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  avatar: {
    marginRight: 12,
  },
  patientInfo: {
    flex: 1,
  },
  patientName: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  patientDetail: {
    fontSize: 14,
    marginBottom: 2,
  },
  patientStats: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  statItem: {
    alignItems: 'center',
    flex: 1,
  },
  statLabel: {
    fontSize: 12,
    marginBottom: 4,
  },
  statChip: {
    borderRadius: 12,
  },
  statValue: {
    fontSize: 12,
    textAlign: 'center',
  },
  birthDate: {
    fontSize: 12,
    textAlign: 'center',
    fontStyle: 'italic',
  },
});

export default TherapistPatientsScreen;
