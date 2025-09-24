import React, {useState, useEffect, useCallback} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  RefreshControl,
  Linking,
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
import ScreenTemplate from '../../components/ScreenTemplate';
import {useCurrentPatient} from '../../hooks/useCurrentPatient';
import {
  getPatientRequests,
  getStatusColor,
  getStatusIcon,
  getStatusLabel,
  mapBackendStatusToFrontend,
  mapFrontendFilterToBackend,
  canCancelRequest,
  hasDownloadableDocument,
} from '../../api/requests';

// Costanti
const DELEGA_RITIRO_BAMBINI_PDF_URL =
  'https://app-cgm.badil.it/api/download/delega-ritiro-bimbi.pdf';

const PatientRequestsScreen = ({navigation}) => {
  const {currentPatient, patientId} = useCurrentPatient();
  const hasSelectedPatient = !!currentPatient;
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

  const loadRequests = useCallback(async () => {
    if (!patientId) {
      console.warn('Nessun paziente selezionato');
      return;
    }

    try {
      setLoading(true);

      // Mappa il filtro frontend agli stati backend
      const statusFilter = mapFrontendFilterToBackend(selectedFilter);

      const response = await getPatientRequests(patientId, statusFilter);

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
  }, [patientId, selectedFilter, calculateStats]); // Dipendenze specifiche

  useEffect(() => {
    if (hasSelectedPatient && patientId) {
      loadRequests();
    } else {
      setLoading(false);
    }
  }, [hasSelectedPatient, patientId, loadRequests]); // Ora loadRequests è memoizzato

  const handleRefresh = async () => {
    setRefreshing(true);
    await loadRequests();
    setRefreshing(false);
  };

  const calculateStats = useCallback(requests => {
    const stats = {
      total: requests.length,
      pending: 0,
      in_progress: 0,
      completed: 0,
    };

    requests.forEach(request => {
      const frontendStatus = mapBackendStatusToFrontend(request.status);
      if (frontendStatus === 'pending') {
        stats.pending++;
      } else if (frontendStatus === 'in_progress') {
        stats.in_progress++;
      } else if (frontendStatus === 'completed') {
        stats.completed++;
      }
    });

    setStats(stats);
  }, []); // Nessuna dipendenza esterna

  const formatDate = dateString => {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  const formatDateTime = dateString => {
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
    const colors = {
      all: '#2196F3',
      pending: '#FF9800',
      in_progress: '#2196F3',
      completed: '#4CAF50',
    };
    return colors[filter] || '#2196F3';
  };

  const handleDownloadDelega = async () => {
    try {
      const supported = await Linking.canOpenURL(DELEGA_RITIRO_BAMBINI_PDF_URL);
      if (supported) {
        await Linking.openURL(DELEGA_RITIRO_BAMBINI_PDF_URL);
      } else {
        Alert.alert(
          'Errore',
          'Impossibile aprire il link. Verifica la connessione internet.',
        );
      }
    } catch (error) {
      console.error('Errore apertura PDF delega:', error);
      Alert.alert(
        'Errore',
        "Si è verificato un errore durante l'apertura del documento.",
      );
    }
  };

  // Mostra messaggio se nessun paziente è selezionato
  if (!hasSelectedPatient) {
    return (
      <ScreenTemplate title="Le Mie Richieste">
        <View style={styles.noPatientContainer}>
          <Avatar.Icon
            size={80}
            icon="account-alert"
            style={styles.noPatientIcon}
          />
          <Text style={styles.noPatientTitle}>Nessun Paziente Selezionato</Text>
          <Text style={styles.noPatientSubtitle}>
            Seleziona un paziente per visualizzare le richieste
          </Text>
          <Button
            mode="contained"
            onPress={() => navigation.navigate('PatientSelection')}
            style={styles.selectPatientButton}>
            Seleziona Paziente
          </Button>
        </View>
      </ScreenTemplate>
    );
  }

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

        {request.notes && (
          <Text style={styles.requestReason} numberOfLines={2}>
            {request.notes}
          </Text>
        )}

        {request.estimated_completion && request.status !== 'consegnato' && (
          <View style={styles.estimatedContainer}>
            <Avatar.Icon
              size={20}
              icon="clock-outline"
              style={styles.estimatedIcon}
            />
            <Text style={styles.estimatedText}>
              Consegna prevista: {formatDateTime(request.estimated_completion)}
            </Text>
          </View>
        )}

        {request.status === 'consegnato' && request.completed_at && (
          <View style={styles.completedContainer}>
            <Avatar.Icon
              size={20}
              icon="check-circle"
              style={styles.completedIcon}
            />
            <Text style={styles.completedText}>
              Completata il: {formatDateTime(request.completed_at)}
            </Text>
          </View>
        )}

        <View style={styles.requestActions}>
          <Button
            mode="outlined"
            onPress={() =>
              navigation.navigate('RequestDetails', {requestId: request.id})
            }
            style={styles.actionButton}>
            Dettagli
          </Button>

          {hasDownloadableDocument(request.status) && (
            <Button
              mode="contained"
              icon="download"
              onPress={() => {
                // TODO: Implementare download
                Alert.alert(
                  'Download',
                  'Funzionalità in via di implementazione',
                );
              }}
              style={[styles.actionButton, styles.downloadButton]}>
              Scarica
            </Button>
          )}
        </View>
      </Card.Content>
    </Card>
  );

  return (
    <ScreenTemplate
      title="Le Mie Richieste"
      subtitle={
        currentPatient
          ? `${currentPatient.first_name} ${currentPatient.last_name}`
          : ''
      }>
      <ScrollView
        style={styles.container}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
        }>
        {/* Statistiche */}
        <Card style={styles.statsCard}>
          <Card.Content>
            <Text style={styles.statsTitle}>Riepilogo Richieste</Text>
            <View style={styles.statsContainer}>
              <View style={styles.statItem}>
                <Text style={styles.statNumber}>{stats.total}</Text>
                <Text style={styles.statLabel}>Totale</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={[styles.statNumber, {color: '#FF9800'}]}>
                  {stats.pending}
                </Text>
                <Text style={styles.statLabel}>In Attesa</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={[styles.statNumber, {color: '#2196F3'}]}>
                  {stats.in_progress}
                </Text>
                <Text style={styles.statLabel}>In Corso</Text>
              </View>
              <View style={styles.statItem}>
                <Text style={[styles.statNumber, {color: '#4CAF50'}]}>
                  {stats.completed}
                </Text>
                <Text style={styles.statLabel}>Completate</Text>
              </View>
            </View>
          </Card.Content>
        </Card>

        {/* Pulsante Delega Ritiro Bambini */}
        <Card style={styles.delegaCard}>
          <Card.Content>
            <View style={styles.delegaContainer}>
              <View style={styles.delegaInfo}>
                <Avatar.Icon
                  size={40}
                  icon="file-pdf-box"
                  style={styles.delegaIcon}
                />
                <View style={styles.delegaTextContainer}>
                  <Text style={styles.delegaTitle}>Delega Ritiro Bambini</Text>
                  <Text style={styles.delegaSubtitle}>
                    Scarica il modulo per delegare il ritiro
                  </Text>
                </View>
              </View>
              <Button
                mode="contained"
                icon="download"
                onPress={handleDownloadDelega}
                style={styles.delegaButton}>
                Scarica PDF
              </Button>
            </View>
          </Card.Content>
        </Card>

        {/* Filtri */}
        <Card style={styles.filtersCard}>
          <Card.Content>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              style={styles.filtersContainer}>
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
                    selectedFilter === filter.key && {
                      backgroundColor: getFilterColor(filter.key) + '20',
                    },
                  ]}
                  textStyle={
                    selectedFilter === filter.key && {
                      color: getFilterColor(filter.key),
                    }
                  }>
                  {filter.label}
                </Chip>
              ))}
            </ScrollView>
          </Card.Content>
        </Card>

        {/* Lista Richieste */}
        {loading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" />
            <Text style={styles.loadingText}>Caricamento richieste...</Text>
          </View>
        ) : requests.length === 0 ? (
          <Card style={styles.emptyCard}>
            <Card.Content style={styles.emptyContent}>
              <Avatar.Icon
                size={80}
                icon="file-document-outline"
                style={styles.emptyIcon}
              />
              <Text style={styles.emptyTitle}>Nessuna Richiesta</Text>
              <Text style={styles.emptySubtitle}>
                {selectedFilter === 'all'
                  ? 'Non hai ancora fatto richieste'
                  : `Nessuna richiesta con filtro "${
                      selectedFilter === 'pending'
                        ? 'In Attesa'
                        : selectedFilter === 'in_progress'
                        ? 'In Corso'
                        : 'Completate'
                    }"`}
              </Text>
            </Card.Content>
          </Card>
        ) : (
          <>
            {requests.map(renderRequestCard)}
            <View style={styles.bottomSpacing} />
          </>
        )}
      </ScrollView>

      {/* FAB per creare nuova richiesta */}
      {!loading && (
        <Button
          mode="contained"
          icon="plus"
          onPress={() => navigation.navigate('CreateRequest')}
          style={styles.fab}>
          Nuova Richiesta
        </Button>
      )}
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
  },
  noPatientContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  noPatientIcon: {
    backgroundColor: '#FF9800',
    marginBottom: 24,
  },
  noPatientTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 12,
    textAlign: 'center',
  },
  noPatientSubtitle: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 32,
  },
  selectPatientButton: {
    paddingHorizontal: 24,
  },
  statsCard: {
    marginBottom: 16,
  },
  statsTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  statsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  statItem: {
    alignItems: 'center',
  },
  statNumber: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#2196F3',
  },
  statLabel: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  delegaCard: {
    marginBottom: 16,
  },
  delegaContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  delegaInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  delegaIcon: {
    backgroundColor: '#FF5722',
    marginRight: 12,
  },
  delegaTextContainer: {
    flex: 1,
  },
  delegaTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  delegaSubtitle: {
    fontSize: 12,
    color: '#666',
  },
  delegaButton: {
    backgroundColor: '#FF5722',
    marginLeft: 12,
  },
  filtersCard: {
    marginBottom: 16,
  },
  filtersContainer: {
    flexDirection: 'row',
  },
  filterChip: {
    marginRight: 8,
  },
  requestCard: {
    marginBottom: 12,
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
    fontWeight: 'bold',
    marginBottom: 4,
  },
  requestDate: {
    fontSize: 12,
    color: '#666',
  },
  statusChip: {
    alignSelf: 'flex-start',
  },
  requestReason: {
    fontSize: 14,
    color: '#666',
    marginBottom: 12,
    fontStyle: 'italic',
  },
  estimatedContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  estimatedIcon: {
    backgroundColor: '#FF9800',
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
    backgroundColor: '#4CAF50',
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
    gap: 8,
  },
  actionButton: {
    minWidth: 80,
  },
  downloadButton: {
    backgroundColor: '#4CAF50',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: '#666',
  },
  emptyCard: {
    marginTop: 32,
  },
  emptyContent: {
    alignItems: 'center',
    padding: 32,
  },
  emptyIcon: {
    backgroundColor: '#E0E0E0',
    marginBottom: 24,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 12,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
    marginBottom: 24,
  },
  createButton: {
    paddingHorizontal: 24,
  },
  fab: {
    position: 'absolute',
    bottom: 16,
    right: 16,
    paddingHorizontal: 16,
  },
  bottomSpacing: {
    height: 80,
  },
});

export default PatientRequestsScreen;
