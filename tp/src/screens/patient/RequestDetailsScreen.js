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
  canCancelRequest,
  hasDownloadableDocument,
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
      'Sei sicuro di voler annullare questa richiesta? Questa azione non può essere annullata.',
      [
        {text: 'No', style: 'cancel'},
        {
          text: 'Sì, Annulla',
          style: 'destructive',
          onPress: async () => {
            try {
              setActionLoading(true);
              await cancelRequest(requestId, "Annullata dall'utente");

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
            } catch (error) {
              console.error('Errore annullamento:', error);
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
      await downloadRequestDocument(requestId);

      // TODO: Implementare download reale
      Alert.alert('Download', 'Funzionalità in via di implementazione');
    } catch (error) {
      console.error('Errore download:', error);
      Alert.alert('Errore', 'Impossibile scaricare il documento');
    } finally {
      setActionLoading(false);
    }
  };

  const formatDate = dateString => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  const formatDateTime = dateString => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusMessage = status => {
    const messages = {
      inviata: 'La tua richiesta è stata ricevuta e sarà elaborata a breve.',
      presa_in_carico:
        'La richiesta è attualmente in elaborazione da parte del nostro staff.',
      stampato:
        'Il documento è stato stampato e sarà presto disponibile per il ritiro.',
      consegnato:
        'La richiesta è stata completata. Il documento è disponibile.',
      rifiutata:
        'La richiesta è stata rifiutata. Contatta il nostro staff per maggiori informazioni.',
      annullata: 'La richiesta è stata annullata.',
    };
    return messages[status] || 'Stato della richiesta non disponibile.';
  };

  const getRelationshipLabel = relationshipType => {
    const labels = {
      self: 'Se stesso',
      parent: 'Genitore',
      tutor: 'Tutore',
      other: 'Altro',
    };
    return labels[relationshipType] || relationshipType;
  };

  const renderTimeline = () => {
    const timelineItems = [];

    // Richiesta creata
    timelineItems.push({
      status: 'inviata',
      label: 'Richiesta Inviata',
      date: request.created_at,
      description: 'La richiesta è stata inviata con successo',
      completed: true,
    });

    // Presa in carico
    if (
      request.status === 'presa_in_carico' ||
      request.status === 'stampato' ||
      request.status === 'consegnato'
    ) {
      timelineItems.push({
        status: 'presa_in_carico',
        label: 'Presa in Carico',
        date: request.updated_at, // Approssimazione
        description: 'La richiesta è stata presa in carico dal nostro staff',
        completed: true,
      });
    }

    // Stampato
    if (request.status === 'stampato' || request.status === 'consegnato') {
      timelineItems.push({
        status: 'stampato',
        label: 'Documento Stampato',
        date: request.updated_at, // Approssimazione
        description: 'Il documento è stato stampato e preparato',
        completed: true,
      });
    }

    // Consegnato
    if (request.status === 'consegnato') {
      timelineItems.push({
        status: 'consegnato',
        label: 'Documento Consegnato',
        date: request.completed_at || request.updated_at,
        description: 'Il documento è stato consegnato',
        completed: true,
      });
    }

    // Rifiutata
    if (request.status === 'rifiutata') {
      timelineItems.push({
        status: 'rifiutata',
        label: 'Richiesta Rifiutata',
        date: request.rejected_at || request.updated_at,
        description:
          request.rejection_reason || 'La richiesta è stata rifiutata',
        completed: true,
      });
    }

    // Annullata
    if (request.status === 'annullata') {
      timelineItems.push({
        status: 'annullata',
        label: 'Richiesta Annullata',
        date: request.cancelled_at || request.updated_at,
        description:
          request.cancellation_reason || 'La richiesta è stata annullata',
        completed: true,
      });
    }

    return (
      <Card style={styles.timelineCard}>
        <Card.Content>
          <Text style={styles.sectionTitle}>Cronologia</Text>
          {timelineItems.map((item, index) => (
            <View key={index} style={styles.timelineItem}>
              <View style={styles.timelineIndicator}>
                <Avatar.Icon
                  size={32}
                  icon={getStatusIcon(item.status)}
                  style={[
                    styles.timelineIcon,
                    {backgroundColor: getStatusColor(item.status) + '20'},
                  ]}
                />
                {index < timelineItems.length - 1 && (
                  <View style={styles.timelineLine} />
                )}
              </View>
              <View style={styles.timelineContent}>
                <Text style={styles.timelineLabel}>{item.label}</Text>
                <Text style={styles.timelineDate}>
                  {formatDateTime(item.date)}
                </Text>
                <Text style={styles.timelineDescription}>
                  {item.description}
                </Text>
              </View>
            </View>
          ))}
        </Card.Content>
      </Card>
    );
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

        {/* Informazioni Richiesta */}
        <Card style={styles.infoCard}>
          <Card.Content>
            <Text style={styles.sectionTitle}>Informazioni Richiesta</Text>

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Tipologia:</Text>
              <Text style={styles.infoValue}>{request.request_type}</Text>
            </View>

            {request.type_info && (
              <>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Categoria:</Text>
                  <Text style={styles.infoValue}>
                    {request.type_info.category}
                  </Text>
                </View>

                {request.type_info.estimated_days && (
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Tempo stimato:</Text>
                    <Text style={styles.infoValue}>
                      ~{request.type_info.estimated_days} giorni
                    </Text>
                  </View>
                )}
              </>
            )}

            <View style={styles.infoRow}>
              <Text style={styles.infoLabel}>Data richiesta:</Text>
              <Text style={styles.infoValue}>
                {formatDateTime(request.created_at)}
              </Text>
            </View>

            {request.estimated_completion && (
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Consegna prevista:</Text>
                <Text style={styles.infoValue}>
                  {formatDateTime(request.estimated_completion)}
                </Text>
              </View>
            )}

            {request.completed_at && (
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Completata il:</Text>
                <Text style={styles.infoValue}>
                  {formatDateTime(request.completed_at)}
                </Text>
              </View>
            )}
          </Card.Content>
        </Card>

        {/* Dettagli Terapia - Solo per "Relazione terapista" */}
        {request.therapy_details &&
          request.request_type === 'Relazione terapista' && (
            <Card style={styles.therapyCard}>
              <Card.Content>
                <Text style={styles.sectionTitle}>Dettagli Terapia</Text>

                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Tipologia:</Text>
                  <Text style={styles.infoValue}>
                    {request.therapy_details.treatment_type_name}
                  </Text>
                </View>

                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Modalità:</Text>
                  <Text style={styles.infoValue}>
                    {request.therapy_details.group_type}
                  </Text>
                </View>

                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Ore settimanali:</Text>
                  <Text style={styles.infoValue}>
                    {request.therapy_details.weekly_hours}h
                  </Text>
                </View>

                <Surface style={styles.therapyNotice}>
                  <Text style={styles.therapyNoticeText}>
                    💡 Questa richiesta di relazione si riferisce specificamente
                    alla terapia "{request.therapy_details.treatment_type_name}"
                  </Text>
                </Surface>
              </Card.Content>
            </Card>
          )}

        {/* Note */}
        {request.notes && (
          <Card style={styles.notesCard}>
            <Card.Content>
              <Text style={styles.sectionTitle}>Note</Text>
              <Text style={styles.notesText}>{request.notes}</Text>
            </Card.Content>
          </Card>
        )}

        {/* Creata da */}
        {request.created_by && (
          <Card style={styles.createdByCard}>
            <Card.Content>
              <Text style={styles.sectionTitle}>Richiesta creata da</Text>
              <View style={styles.createdByInfo}>
                <Avatar.Icon
                  size={40}
                  icon="account"
                  style={styles.createdByAvatar}
                />
                <View style={styles.createdByDetails}>
                  <Text style={styles.createdByName}>
                    {request.created_by.first_name}{' '}
                    {request.created_by.last_name}
                  </Text>
                  <Text style={styles.createdByRelation}>
                    {getRelationshipLabel(request.created_by.relationship_type)}
                  </Text>
                </View>
              </View>
            </Card.Content>
          </Card>
        )}

        {/* Timeline */}
        {renderTimeline()}

        {/* Azioni */}
        <Card style={styles.actionsCard}>
          <Card.Content>
            <Text style={styles.sectionTitle}>Azioni</Text>
            <View style={styles.actionsContainer}>
              {hasDownloadableDocument(request.status) && (
                <Button
                  mode="contained"
                  icon="download"
                  onPress={handleDownload}
                  loading={actionLoading}
                  disabled={actionLoading}
                  style={styles.downloadButton}>
                  Scarica Documento
                </Button>
              )}

              {canCancelRequest(request.status) && (
                <Button
                  mode="outlined"
                  icon="cancel"
                  onPress={handleCancel}
                  loading={actionLoading}
                  disabled={actionLoading}
                  style={styles.cancelButton}
                  textColor="#F44336">
                  Annulla Richiesta
                </Button>
              )}
            </View>
          </Card.Content>
        </Card>

        <View style={styles.bottomSpacing} />
      </ScrollView>
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  errorIcon: {
    backgroundColor: '#F44336',
    marginBottom: 24,
  },
  errorTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 12,
    textAlign: 'center',
  },
  errorSubtitle: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 32,
  },
  backButton: {
    paddingHorizontal: 24,
  },
  statusCard: {
    marginBottom: 16,
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
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  infoRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  infoLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#666',
    width: 120,
  },
  infoValue: {
    fontSize: 14,
    flex: 1,
    color: '#333',
  },
  therapyCard: {
    marginBottom: 16,
  },
  therapyNotice: {
    backgroundColor: '#E3F2FD',
    padding: 12,
    borderRadius: 8,
    marginTop: 12,
  },
  therapyNoticeText: {
    fontSize: 13,
    color: '#1565C0',
    lineHeight: 18,
    fontStyle: 'italic',
  },
  notesCard: {
    marginBottom: 16,
  },
  notesText: {
    fontSize: 14,
    color: '#333',
    lineHeight: 20,
    fontStyle: 'italic',
  },
  createdByCard: {
    marginBottom: 16,
  },
  createdByInfo: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  createdByAvatar: {
    backgroundColor: '#E3F2FD',
    marginRight: 12,
  },
  createdByDetails: {
    flex: 1,
  },
  createdByName: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  createdByRelation: {
    fontSize: 14,
    color: '#666',
  },
  timelineCard: {
    marginBottom: 16,
  },
  timelineItem: {
    flexDirection: 'row',
    marginBottom: 16,
  },
  timelineIndicator: {
    alignItems: 'center',
    marginRight: 16,
  },
  timelineIcon: {
    marginBottom: 8,
  },
  timelineLine: {
    width: 2,
    flex: 1,
    backgroundColor: '#E0E0E0',
  },
  timelineContent: {
    flex: 1,
  },
  timelineLabel: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  timelineDate: {
    fontSize: 12,
    color: '#666',
    marginBottom: 4,
  },
  timelineDescription: {
    fontSize: 14,
    color: '#333',
    lineHeight: 18,
  },
  actionsCard: {
    marginBottom: 16,
  },
  actionsContainer: {
    gap: 12,
  },
  downloadButton: {
    backgroundColor: '#4CAF50',
  },
  cancelButton: {
    borderColor: '#F44336',
  },
  bottomSpacing: {
    height: 32,
  },
});

export default RequestDetailsScreen;
