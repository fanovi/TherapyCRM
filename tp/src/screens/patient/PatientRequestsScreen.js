import React, {useState, useEffect} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  RefreshControl,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  IconButton,
  Divider,
  ActivityIndicator,
} from 'react-native-paper';
import {useSelector} from 'react-redux';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getUserRequests,
  getStatusColor,
  getStatusIcon,
  getStatusLabel,
} from '../../api/requests';

const PatientRequestsScreen = ({navigation}) => {
  const {currentPatient} = useSelector(state => state.patient);
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedFilter, setSelectedFilter] = useState('all');

  // Contatori per statistiche
  const [stats, setStats] = useState({
    total: 0,
    pending: 0,
    in_progress: 0,
    completed: 0,
  });

  useEffect(() => {
    loadRequests();
  }, [selectedFilter]);

  const loadRequests = async () => {
    try {
      setLoading(true);

      const filterParam = selectedFilter === 'all' ? null : selectedFilter;
      const response = await getUserRequests(filterParam);

      if (response.success) {
        setRequests(response.data);
        calculateStats(response.data);
      }
    } catch (error) {
      console.error('Errore caricamento richieste:', error);
      Alert.alert('Errore', 'Impossibile caricare le richieste');
    } finally {
      setLoading(false);
    }
  };

  const handleRefresh = async () => {
    setRefreshing(true);
    await loadRequests();
    setRefreshing(false);
  };

  const calculateStats = requestsData => {
    const newStats = {
      total: requestsData.length,
      pending: requestsData.filter(r => r.status === 'pending').length,
      in_progress: requestsData.filter(r => r.status === 'in_progress').length,
      completed: requestsData.filter(r => r.status === 'completed').length,
    };
    setStats(newStats);
  };

  const formatDate = dateString => {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getFilterColor = filter => {
    if (filter === selectedFilter) {
      switch (filter) {
        case 'all':
          return '#2196F3';
        case 'pending':
          return '#FF9800';
        case 'in_progress':
          return '#2196F3';
        case 'completed':
          return '#4CAF50';
        default:
          return '#9E9E9E';
      }
    }
    return '#E0E0E0';
  };

  const renderRequestCard = request => (
    <Card key={request.id} style={styles.requestCard}>
      <Card.Content>
        <View style={styles.requestHeader}>
          <View style={styles.requestTitleContainer}>
            <Text style={styles.requestTitle}>{request.request_type}</Text>
            <Text style={styles.requestDate}>
              {formatDate(request.created_at)}
            </Text>
          </View>

          <Chip
            icon={getStatusIcon(request.status)}
            style={[
              styles.statusChip,
              {backgroundColor: getStatusColor(request.status) + '20'},
            ]}
            textStyle={{color: getStatusColor(request.status)}}>
            {getStatusLabel(request.status)}
          </Chip>
        </View>

        {request.reason && (
          <Text style={styles.requestReason} numberOfLines={2}>
            {request.reason}
          </Text>
        )}

        {request.estimated_completion && request.status !== 'completed' && (
          <View style={styles.estimatedContainer}>
            <Avatar.Icon
              size={20}
              icon="clock-outline"
              style={styles.estimatedIcon}
            />
            <Text style={styles.estimatedText}>
              Consegna prevista: {formatDate(request.estimated_completion)}
            </Text>
          </View>
        )}

        {request.status === 'completed' && request.completed_at && (
          <View style={styles.completedContainer}>
            <Avatar.Icon
              size={20}
              icon="check-circle"
              style={styles.completedIcon}
            />
            <Text style={styles.completedText}>
              Completata il: {formatDate(request.completed_at)}
            </Text>
          </View>
        )}

        <View style={styles.requestActions}>
          <Button
            mode="outlined"
            icon="eye"
            onPress={() =>
              navigation.navigate('RequestDetails', {requestId: request.id})
            }
            style={styles.actionButton}>
            Dettagli
          </Button>

          {request.status === 'completed' && request.download_url && (
            <Button
              mode="contained"
              icon="download"
              onPress={() => handleDownload(request.id)}
              style={styles.downloadButton}>
              Scarica
            </Button>
          )}

          {request.status === 'pending' && (
            <Button
              mode="text"
              icon="cancel"
              textColor="#F44336"
              onPress={() => handleCancelRequest(request.id)}>
              Annulla
            </Button>
          )}
        </View>
      </Card.Content>
    </Card>
  );

  const handleDownload = async requestId => {
    try {
      // TODO: Implementare download reale
      Alert.alert('Download', 'Funzionalità in via di implementazione');
    } catch (error) {
      Alert.alert('Errore', 'Impossibile scaricare il documento');
    }
  };

  const handleCancelRequest = requestId => {
    Alert.alert(
      'Annulla Richiesta',
      'Sei sicuro di voler annullare questa richiesta?',
      [
        {text: 'No', style: 'cancel'},
        {
          text: 'Sì, Annulla',
          style: 'destructive',
          onPress: async () => {
            try {
              // TODO: Implementare annullamento
              Alert.alert('Successo', 'Richiesta annullata');
              loadRequests();
            } catch (error) {
              Alert.alert('Errore', 'Impossibile annullare la richiesta');
            }
          },
        },
      ],
    );
  };

  if (loading && !refreshing) {
    return (
      <ScreenTemplate title="Le Mie Richieste">
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" />
          <Text style={styles.loadingText}>Caricamento richieste...</Text>
        </View>
      </ScreenTemplate>
    );
  }

  return (
    <ScreenTemplate
      title="Le Mie Richieste"
      subtitle="Gestisci documenti e certificati"
      headerRight={
        <IconButton
          icon="plus"
          iconColor="#2196F3"
          size={24}
          onPress={() => navigation.navigate('CreateRequest')}
        />
      }>
      <ScrollView
        style={styles.container}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
        }>
        {/* Statistiche */}
        <View style={styles.statsContainer}>
          <View style={styles.statsRow}>
            <View style={styles.statCard}>
              <Text style={styles.statNumber}>{stats.total}</Text>
              <Text style={styles.statLabel}>Totali</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={[styles.statNumber, {color: '#FF9800'}]}>
                {stats.pending}
              </Text>
              <Text style={styles.statLabel}>In Attesa</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={[styles.statNumber, {color: '#2196F3'}]}>
                {stats.in_progress}
              </Text>
              <Text style={styles.statLabel}>In Corso</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={[styles.statNumber, {color: '#4CAF50'}]}>
                {stats.completed}
              </Text>
              <Text style={styles.statLabel}>Completate</Text>
            </View>
          </View>
        </View>

        {/* Filtri */}
        <View style={styles.filtersContainer}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {[
              {key: 'all', label: 'Tutte'},
              {key: 'pending', label: 'In Attesa'},
              {key: 'in_progress', label: 'In Corso'},
              {key: 'completed', label: 'Completate'},
            ].map(filter => (
              <Chip
                key={filter.key}
                selected={selectedFilter === filter.key}
                onPress={() => setSelectedFilter(filter.key)}
                style={[
                  styles.filterChip,
                  {
                    backgroundColor:
                      selectedFilter === filter.key
                        ? getFilterColor(filter.key)
                        : '#F5F5F5',
                  },
                ]}
                textStyle={{
                  color: selectedFilter === filter.key ? '#FFFFFF' : '#666666',
                }}>
                {filter.label}
              </Chip>
            ))}
          </ScrollView>
        </View>

        {/* Pulsante Crea Nuova Richiesta */}
        <Card style={styles.createCard}>
          <Card.Content style={styles.createContent}>
            <Avatar.Icon
              size={48}
              icon="plus-circle"
              style={styles.createIcon}
            />
            <View style={styles.createTextContainer}>
              <Text style={styles.createTitle}>Nuova Richiesta</Text>
              <Text style={styles.createSubtitle}>
                Richiedi certificati, referti e documenti
              </Text>
            </View>
            <IconButton
              icon="chevron-right"
              iconColor="#2196F3"
              onPress={() => navigation.navigate('CreateRequest')}
            />
          </Card.Content>
        </Card>

        {/* Lista Richieste */}
        {requests.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Avatar.Icon
              size={80}
              icon="file-document-outline"
              style={styles.emptyIcon}
            />
            <Text style={styles.emptyTitle}>Nessuna richiesta</Text>
            <Text style={styles.emptySubtitle}>
              Non hai ancora effettuato richieste.{'\n'}
              Tocca il pulsante + per iniziare.
            </Text>
          </View>
        ) : (
          <View style={styles.requestsList}>
            {requests.map(renderRequestCard)}
          </View>
        )}
      </ScrollView>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8F9FA',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#666',
  },
  statsContainer: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statCard: {
    alignItems: 'center',
    flex: 1,
  },
  statNumber: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#333',
  },
  statLabel: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  filtersContainer: {
    paddingHorizontal: 20,
    paddingBottom: 16,
  },
  filterChip: {
    marginRight: 8,
  },
  createCard: {
    marginHorizontal: 20,
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  createContent: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
  },
  createIcon: {
    backgroundColor: '#E3F2FD',
  },
  createTextContainer: {
    flex: 1,
    marginLeft: 16,
  },
  createTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  createSubtitle: {
    fontSize: 14,
    color: '#666',
    marginTop: 2,
  },
  requestsList: {
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  requestCard: {
    marginBottom: 12,
    borderRadius: 12,
    elevation: 2,
  },
  requestHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  requestTitleContainer: {
    flex: 1,
    marginRight: 12,
  },
  requestTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  requestDate: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  statusChip: {
    height: 28,
  },
  requestReason: {
    fontSize: 14,
    color: '#666',
    marginBottom: 12,
    lineHeight: 20,
  },
  estimatedContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  estimatedIcon: {
    backgroundColor: '#FFF3E0',
    marginRight: 8,
  },
  estimatedText: {
    fontSize: 12,
    color: '#FF9800',
    fontWeight: '500',
  },
  completedContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  completedIcon: {
    backgroundColor: '#E8F5E8',
    marginRight: 8,
  },
  completedText: {
    fontSize: 12,
    color: '#4CAF50',
    fontWeight: '500',
  },
  requestActions: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    alignItems: 'center',
    marginTop: 8,
  },
  actionButton: {
    marginRight: 8,
  },
  downloadButton: {
    marginRight: 8,
  },
  emptyContainer: {
    alignItems: 'center',
    paddingVertical: 40,
    paddingHorizontal: 20,
  },
  emptyIcon: {
    backgroundColor: '#F5F5F5',
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
    marginBottom: 8,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
    lineHeight: 20,
  },
});

export default PatientRequestsScreen;
