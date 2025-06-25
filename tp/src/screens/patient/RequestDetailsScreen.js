import React, {useState, useEffect} from 'react';
import {View, StyleSheet, ScrollView, Alert, Linking} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Chip,
  Divider,
  ActivityIndicator,
  Surface,
} from 'react-native-paper';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getRequestDetails,
  cancelRequest,
  downloadRequestDocument,
  getStatusColor,
  getStatusIcon,
  getStatusLabel,
} from '../../api/requests';

const RequestDetailsScreen = ({navigation, route}) => {
  const {requestId} = route.params;

  const [request, setRequest] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    loadRequestDetails();
  }, [requestId]);

  const loadRequestDetails = async () => {
    try {
      setLoading(true);
      const response = await getRequestDetails(requestId);

      if (response.success) {
        setRequest(response.data);
      }
    } catch (error) {
      console.error('Errore caricamento dettagli:', error);
      Alert.alert('Errore', 'Impossibile caricare i dettagli della richiesta');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    Alert.alert(
      'Annulla Richiesta',
      "Sei sicuro di voler annullare questa richiesta? L'azione non può essere annullata.",
      [
        {text: 'No', style: 'cancel'},
        {
          text: 'Sì, Annulla',
          style: 'destructive',
          onPress: async () => {
            try {
              setActionLoading(true);
              const response = await cancelRequest(
                requestId,
                "Annullata dall'utente",
              );

              if (response.success) {
                Alert.alert(
                  'Richiesta Annullata',
                  'La richiesta è stata annullata con successo',
                  [
                    {
                      text: 'OK',
                      onPress: () => navigation.goBack(),
                    },
                  ],
                );
              }
            } catch (error) {
              Alert.alert('Errore', 'Impossibile annullare la richiesta');
            } finally {
              setActionLoading(false);
            }
          },
        },
      ],
    );
  };

  const handleDownload = async () => {
    try {
      setActionLoading(true);

      // TODO: Implementare download reale
      Alert.alert(
        'Download',
        'Funzionalità in via di implementazione. Il documento sarà disponibile per il download a breve.',
      );

      // In una implementazione reale:
      // const response = await downloadRequestDocument(requestId);
      // if (response.success) {
      //   // Aprire il file o salvarlo
      // }
    } catch (error) {
      Alert.alert('Errore', 'Impossibile scaricare il documento');
    } finally {
      setActionLoading(false);
    }
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

  const formatDateOnly = dateString => {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT');
  };

  const getStatusMessage = status => {
    const messages = {
      pending: 'La tua richiesta è stata ricevuta e sarà elaborata a breve.',
      in_progress:
        'La richiesta è attualmente in elaborazione da parte del nostro staff.',
      completed:
        'La richiesta è stata completata. Il documento è disponibile per il download.',
      rejected:
        'La richiesta è stata rifiutata. Contatta il nostro staff per maggiori informazioni.',
      cancelled: 'La richiesta è stata annullata.',
    };
    return messages[status] || 'Stato della richiesta non disponibile.';
  };

  if (loading) {
    return (
      <ScreenTemplate title="Dettagli Richiesta">
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" />
          <Text style={styles.loadingText}>Caricamento dettagli...</Text>
        </View>
      </ScreenTemplate>
    );
  }

  if (!request) {
    return (
      <ScreenTemplate title="Dettagli Richiesta">
        <View style={styles.errorContainer}>
          <Avatar.Icon size={80} icon="alert-circle" style={styles.errorIcon} />
          <Text style={styles.errorTitle}>Richiesta non trovata</Text>
          <Text style={styles.errorSubtitle}>
            La richiesta che stai cercando non esiste o è stata rimossa.
          </Text>
          <Button
            mode="contained"
            onPress={() => navigation.goBack()}
            style={styles.backButton}>
            Torna Indietro
          </Button>
        </View>
      </ScreenTemplate>
    );
  }

  return (
    <ScreenTemplate
      title="Dettagli Richiesta"
      subtitle={`#${request.id} - ${request.request_type}`}>
      <ScrollView style={styles.container}>
        {/* Stato Richiesta */}
        <Card style={styles.statusCard}>
          <Card.Content>
            <View style={styles.statusHeader}>
              <Avatar.Icon
                size={48}
                icon={getStatusIcon(request.status)}
                style={[
                  styles.statusAvatar,
                  {backgroundColor: getStatusColor(request.status) + '20'},
                ]}
              />

              <View style={styles.statusInfo}>
                <Chip
                  icon={getStatusIcon(request.status)}
                  style={[
                    styles.statusChip,
                    {backgroundColor: getStatusColor(request.status) + '20'},
                  ]}
                  textStyle={{color: getStatusColor(request.status)}}>
                  {getStatusLabel(request.status)}
                </Chip>

                <Text style={styles.statusMessage}>
                  {getStatusMessage(request.status)}
                </Text>
              </View>
            </View>
          </Card.Content>
        </Card>

        {/* Informazioni Principali */}
        <Card style={styles.infoCard}>
          <Card.Content>
            <Text style={styles.cardTitle}>Informazioni Richiesta</Text>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Documento richiesto:</Text>
              <Text style={styles.infoValue}>{request.request_type}</Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Data richiesta:</Text>
              <Text style={styles.infoValue}>
                {formatDate(request.created_at)}
              </Text>
            </View>

            {request.estimated_completion && request.status !== 'completed' && (
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Consegna prevista:</Text>
                <Text style={[styles.infoValue, styles.estimatedText]}>
                  {formatDate(request.estimated_completion)}
                </Text>
              </View>
            )}

            {request.completed_at && (
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Completata il:</Text>
                <Text style={[styles.infoValue, styles.completedText]}>
                  {formatDate(request.completed_at)}
                </Text>
              </View>
            )}
          </Card.Content>
        </Card>

        {/* Dettagli della Richiesta */}
        {(request.reason ||
          request.date_from ||
          request.date_to ||
          request.notes) && (
          <Card style={styles.detailsCard}>
            <Card.Content>
              <Text style={styles.cardTitle}>Dettagli</Text>

              {request.reason && (
                <View style={styles.detailSection}>
                  <Text style={styles.detailLabel}>Motivo:</Text>
                  <Surface style={styles.detailContent}>
                    <Text style={styles.detailText}>{request.reason}</Text>
                  </Surface>
                </View>
              )}

              {(request.date_from || request.date_to) && (
                <View style={styles.detailSection}>
                  <Text style={styles.detailLabel}>
                    Periodo di riferimento:
                  </Text>
                  <Surface style={styles.detailContent}>
                    <Text style={styles.detailText}>
                      {request.date_from &&
                        `Dal ${formatDateOnly(request.date_from)}`}
                      {request.date_to &&
                        ` al ${formatDateOnly(request.date_to)}`}
                    </Text>
                  </Surface>
                </View>
              )}

              {request.notes && (
                <View style={styles.detailSection}>
                  <Text style={styles.detailLabel}>Note aggiuntive:</Text>
                  <Surface style={styles.detailContent}>
                    <Text style={styles.detailText}>{request.notes}</Text>
                  </Surface>
                </View>
              )}
            </Card.Content>
          </Card>
        )}

        {/* Timeline/Progress */}
        <Card style={styles.timelineCard}>
          <Card.Content>
            <Text style={styles.cardTitle}>Stato di Avanzamento</Text>

            <View style={styles.timeline}>
              <View style={styles.timelineItem}>
                <View
                  style={[styles.timelineIcon, styles.timelineIconCompleted]}>
                  <Avatar.Icon size={24} icon="plus" />
                </View>
                <View style={styles.timelineContent}>
                  <Text style={styles.timelineTitle}>Richiesta Creata</Text>
                  <Text style={styles.timelineDate}>
                    {formatDate(request.created_at)}
                  </Text>
                </View>
              </View>

              <View style={styles.timelineItem}>
                <View
                  style={[
                    styles.timelineIcon,
                    ['in_progress', 'completed', 'rejected'].includes(
                      request.status,
                    )
                      ? styles.timelineIconCompleted
                      : styles.timelineIconPending,
                  ]}>
                  <Avatar.Icon size={24} icon="progress-clock" />
                </View>
                <View style={styles.timelineContent}>
                  <Text style={styles.timelineTitle}>In Elaborazione</Text>
                  <Text style={styles.timelineDate}>
                    {['in_progress', 'completed', 'rejected'].includes(
                      request.status,
                    )
                      ? 'In corso...'
                      : 'In attesa di elaborazione'}
                  </Text>
                </View>
              </View>

              <View style={styles.timelineItem}>
                <View
                  style={[
                    styles.timelineIcon,
                    request.status === 'completed'
                      ? styles.timelineIconCompleted
                      : styles.timelineIconPending,
                  ]}>
                  <Avatar.Icon size={24} icon="check" />
                </View>
                <View style={styles.timelineContent}>
                  <Text style={styles.timelineTitle}>Completata</Text>
                  <Text style={styles.timelineDate}>
                    {request.completed_at
                      ? formatDate(request.completed_at)
                      : request.estimated_completion
                      ? `Prevista per il ${formatDate(
                          request.estimated_completion,
                        )}`
                      : 'In attesa...'}
                  </Text>
                </View>
              </View>
            </View>
          </Card.Content>
        </Card>
      </ScrollView>

      {/* Azioni */}
      <View style={styles.actionsContainer}>
        {request.status === 'pending' && (
          <Button
            mode="outlined"
            icon="cancel"
            onPress={handleCancel}
            style={styles.cancelButton}
            textColor="#F44336"
            loading={actionLoading}
            disabled={actionLoading}>
            Annulla Richiesta
          </Button>
        )}

        {request.status === 'completed' && request.download_url && (
          <Button
            mode="contained"
            icon="download"
            onPress={handleDownload}
            style={styles.downloadButton}
            loading={actionLoading}
            disabled={actionLoading}>
            Scarica Documento
          </Button>
        )}

        <Button
          mode="outlined"
          icon="arrow-left"
          onPress={() => navigation.goBack()}
          style={styles.backButton}
          disabled={actionLoading}>
          Torna Indietro
        </Button>
      </View>
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  errorIcon: {
    backgroundColor: '#FFEBEE',
    marginBottom: 16,
  },
  errorTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
    marginBottom: 8,
  },
  errorSubtitle: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
    marginBottom: 24,
  },
  statusCard: {
    margin: 20,
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  statusHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  statusAvatar: {
    marginRight: 16,
  },
  statusInfo: {
    flex: 1,
  },
  statusChip: {
    alignSelf: 'flex-start',
    marginBottom: 8,
  },
  statusMessage: {
    fontSize: 14,
    color: '#666',
    lineHeight: 20,
  },
  infoCard: {
    marginHorizontal: 20,
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  detailsCard: {
    marginHorizontal: 20,
    marginBottom: 16,
    borderRadius: 12,
    elevation: 2,
  },
  timelineCard: {
    marginHorizontal: 20,
    marginBottom: 20,
    borderRadius: 12,
    elevation: 2,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
    alignItems: 'flex-start',
  },
  infoLabel: {
    fontSize: 14,
    color: '#666',
    flex: 0.4,
  },
  infoValue: {
    fontSize: 14,
    color: '#333',
    fontWeight: '500',
    flex: 0.6,
    textAlign: 'right',
  },
  estimatedText: {
    color: '#FF9800',
  },
  completedText: {
    color: '#4CAF50',
  },
  detailSection: {
    marginBottom: 16,
  },
  detailLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
    marginBottom: 8,
  },
  detailContent: {
    padding: 12,
    borderRadius: 8,
    backgroundColor: '#F5F5F5',
  },
  detailText: {
    fontSize: 14,
    color: '#333',
    lineHeight: 20,
  },
  timeline: {
    paddingLeft: 8,
  },
  timelineItem: {
    flexDirection: 'row',
    marginBottom: 16,
    alignItems: 'center',
  },
  timelineIcon: {
    marginRight: 16,
    borderRadius: 20,
  },
  timelineIconCompleted: {
    backgroundColor: '#E8F5E8',
  },
  timelineIconPending: {
    backgroundColor: '#F5F5F5',
  },
  timelineContent: {
    flex: 1,
  },
  timelineTitle: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
  },
  timelineDate: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  actionsContainer: {
    padding: 20,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#E0E0E0',
    gap: 12,
  },
  cancelButton: {
    borderColor: '#F44336',
  },
  downloadButton: {
    backgroundColor: '#4CAF50',
  },
  backButton: {
    // Stile di default
  },
});

export default RequestDetailsScreen;
