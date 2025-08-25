import React, {useState, useEffect} from 'react';
import {View, StyleSheet, ScrollView, Alert} from 'react-native';
import {
  Text,
  Card,
  IconButton,
  useTheme,
  Button,
  Chip,
  ActivityIndicator,
  Divider,
} from 'react-native-paper';
import {useRoute, useNavigation} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getNotificationDetail,
  markNotificationAsRead,
  confirmNotificationRead,
} from '../../api/notifications';

const NotificationDetailScreen = () => {
  const [notification, setNotification] = useState(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  const route = useRoute();
  const navigation = useNavigation();
  const theme = useTheme();
  const {notificationId} = route.params;

  useEffect(() => {
    fetchNotificationDetail();
  }, [notificationId]);

  // Segna automaticamente come letta quando la notifica viene caricata
  useEffect(() => {
    if (notification && !notification?.read_at) {
      handleMarkAsRead();
    }
  }, [notification]);

  const fetchNotificationDetail = async () => {
    try {
      setLoading(true);
      console.log('📱 NotificationDetail: Recupero dettaglio notifica...');

      const response = await getNotificationDetail(notificationId);
      console.log('📱 NotificationDetail: Risposta ricevuta:', response);

      if (response.success && response.data) {
        // Debug dettagliato dei dati ricevuti
        console.log(
          '📱 NotificationDetail: Notifica recuperata:',
          response.data,
        );
        console.log(
          '📱 NotificationDetail: Tipo notification_type:',
          typeof response.data.notification_type,
          response.data.notification_type,
        );
        console.log(
          '📱 NotificationDetail: Tipo title:',
          typeof response.data.title,
          response.data.title,
        );
        console.log(
          '📱 NotificationDetail: Tipo message:',
          typeof response.data.message,
          response.data.message,
        );
        console.log(
          '📱 NotificationDetail: Sender object:',
          response.data.sender,
        );

        setNotification(response.data);
        setLoading(false);
      } else {
        console.warn('📱 NotificationDetail: Risposta non valida:', response);
        Alert.alert(
          'Errore',
          'Impossibile recuperare il dettaglio della notifica',
        );
        navigation.goBack();
      }
    } catch (error) {
      console.error('❌ NotificationDetail: Errore recupero dettaglio:', error);
      Alert.alert('Errore', 'Errore di connessione');
      navigation.goBack();
    }
  };

  const handleMarkAsRead = async () => {
    if (!notification || notification?.read_at) return;

    try {
      setActionLoading(true);

      if (notification?.requires_read_confirmation) {
        await confirmNotificationRead(notification.id);
        console.log('📱 NotificationDetail: Lettura confermata');
      } else {
        await markNotificationAsRead(notification.id);
        console.log('📱 NotificationDetail: Notifica segnata come letta');
      }

      // Aggiorna lo stato locale
      setNotification(prev => ({
        ...prev,
        read_at: new Date().toISOString(),
      }));
    } catch (error) {
      console.error(
        '❌ NotificationDetail: Errore segnando notifica come letta:',
        error,
      );
      // Non mostrare alert per lettura automatica
    } finally {
      setActionLoading(false);
    }
  };

  const getNotificationIcon = type => {
    if (!type) return 'info';
    switch (type) {
      case 'reminder':
        return 'schedule';
      case 'deadline':
        return 'warning';
      case 'mandatory_read':
        return 'priority-high';
      default:
        return 'info';
    }
  };

  const getNotificationColor = type => {
    if (!type) return theme.colors.primary;
    switch (type) {
      case 'reminder':
        return theme.colors.secondary;
      case 'deadline':
        return '#FF9800';
      case 'mandatory_read':
        return '#F44336';
      default:
        return theme.colors.primary;
    }
  };

  const getTypeLabel = type => {
    if (!type) return 'Informazione';
    const typeStr = String(type); // Forza conversione a stringa
    switch (typeStr) {
      case 'reminder':
        return 'Promemoria';
      case 'deadline':
        return 'Scadenza';
      case 'mandatory_read':
        return 'Lettura Obbligatoria';
      case 'info':
      default:
        return 'Informazione';
    }
  };

  const formatDate = dateString => {
    if (!dateString) return 'N/A';
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return 'N/A';
      return date.toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch (error) {
      console.warn('Errore formattazione data:', error);
      return 'N/A';
    }
  };

  if (loading) {
    return (
      <ScreenTemplate title="Dettaglio Notifica" showBackButton>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" />
          <Text style={styles.loadingText}>Caricamento...</Text>
        </View>
      </ScreenTemplate>
    );
  }

  if (!notification || !notification.id) {
    return (
      <ScreenTemplate title="Dettaglio Notifica" showBackButton>
        <View style={styles.errorContainer}>
          <Icon name="error" size={64} color={theme.colors.error} />
          <Text style={styles.errorText}>Notifica non trovata</Text>
        </View>
      </ScreenTemplate>
    );
  }

  // Verifica che tutti i dati critici siano presenti e del tipo corretto
  const safeNotification = {
    ...notification,
    title: String(notification?.title || 'Titolo non disponibile'),
    message: String(notification?.message || 'Messaggio non disponibile'),
    notification_type: String(notification?.notification_type || 'info'),
    created_at: notification?.created_at || null,
    read_at: notification?.read_at || null,
    viewed_at: notification?.viewed_at || null,
    requires_read_confirmation: Boolean(
      notification?.requires_read_confirmation,
    ),
    sender: notification?.sender || null,
  };

  return (
    <ScreenTemplate
      title="Dettaglio Notifica"
      message="👤 Stai visualizzando le notifiche per tutti i pazienti a te associati"
      showBackButton>
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <Card style={styles.notificationCard}>
          <Card.Content>
            <View style={styles.header}>
              <View style={styles.typeContainer}>
                <Icon
                  name={getNotificationIcon(safeNotification.notification_type)}
                  size={28}
                  color={getNotificationColor(
                    safeNotification.notification_type,
                  )}
                  style={styles.typeIcon}
                />
                <Chip
                  mode="outlined"
                  style={[
                    styles.typeChip,
                    {
                      borderColor: getNotificationColor(
                        safeNotification.notification_type,
                      ),
                    },
                  ]}>
                  <Text>
                    {getTypeLabel(safeNotification.notification_type)}
                  </Text>
                </Chip>
              </View>

              {!safeNotification.read_at && (
                <View
                  style={[
                    styles.unreadIndicator,
                    {
                      backgroundColor: getNotificationColor(
                        safeNotification.notification_type,
                      ),
                    },
                  ]}
                />
              )}
            </View>

            <Divider style={styles.divider} />

            <Text variant="headlineSmall" style={styles.title}>
              {safeNotification.title}
            </Text>

            <View style={styles.metadataContainer}>
              <View style={styles.metadataRow}>
                <Icon
                  name="schedule"
                  size={16}
                  color={theme.colors.onSurfaceVariant}
                />
                <Text variant="bodySmall" style={styles.metadataText}>
                  Creata: {String(formatDate(safeNotification.created_at))}
                </Text>
              </View>

              {safeNotification.sender?.username && (
                <View style={styles.metadataRow}>
                  <Icon
                    name="person"
                    size={16}
                    color={theme.colors.onSurfaceVariant}
                  />
                  <Text variant="bodySmall" style={styles.metadataText}>
                    Da: {String(safeNotification.sender.username)}
                  </Text>
                </View>
              )}

              {safeNotification.read_at && (
                <View style={styles.metadataRow}>
                  <Icon
                    name="check-circle"
                    size={16}
                    color={theme.colors.primary}
                  />
                  <Text
                    variant="bodySmall"
                    style={[
                      styles.metadataText,
                      {color: theme.colors.primary},
                    ]}>
                    Letta: {String(formatDate(safeNotification.read_at))}
                  </Text>
                </View>
              )}

              {safeNotification.viewed_at && (
                <View style={styles.metadataRow}>
                  <Icon
                    name="visibility"
                    size={16}
                    color={theme.colors.onSurfaceVariant}
                  />
                  <Text variant="bodySmall" style={styles.metadataText}>
                    Visualizzata:{' '}
                    {String(formatDate(safeNotification.viewed_at))}
                  </Text>
                </View>
              )}
            </View>

            <Divider style={styles.divider} />

            <Text variant="bodyLarge" style={styles.message}>
              {safeNotification.message}
            </Text>
          </Card.Content>
        </Card>

        {!safeNotification.read_at &&
          safeNotification.requires_read_confirmation && (
            <Card style={styles.actionsCard}>
              <Card.Content>
                <Text variant="titleMedium" style={styles.actionsTitle}>
                  Azioni
                </Text>
                <Button
                  mode="contained"
                  onPress={handleMarkAsRead}
                  loading={actionLoading}
                  disabled={actionLoading}
                  icon="check-circle"
                  style={styles.actionButton}>
                  Conferma Lettura
                </Button>
              </Card.Content>
            </Card>
          )}

        {safeNotification.read_at && (
          <Card style={styles.readCard}>
            <Card.Content>
              <View style={styles.readContainer}>
                <Icon
                  name="check-circle"
                  size={24}
                  color={theme.colors.primary}
                />
                <Text variant="bodyMedium" style={styles.readText}>
                  Notifica già letta
                </Text>
              </View>
            </Card.Content>
          </Card>
        )}
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
  },
  loadingText: {
    marginTop: 16,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  errorText: {
    marginTop: 16,
    textAlign: 'center',
  },
  notificationCard: {
    marginBottom: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  typeContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  typeIcon: {
    marginRight: 8,
  },
  typeChip: {
    height: 28,
  },
  unreadIndicator: {
    width: 12,
    height: 12,
    borderRadius: 6,
  },
  divider: {
    marginVertical: 16,
  },
  title: {
    fontWeight: 'bold',
    marginBottom: 16,
  },
  metadataContainer: {
    marginBottom: 8,
  },
  metadataRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 4,
  },
  metadataText: {
    marginLeft: 8,
  },
  message: {
    lineHeight: 24,
  },
  actionsCard: {
    marginBottom: 16,
  },
  actionsTitle: {
    marginBottom: 12,
  },
  actionButton: {
    marginTop: 8,
  },
  readCard: {
    marginBottom: 16,
  },
  readContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  readText: {
    marginLeft: 8,
  },
});

export default NotificationDetailScreen;
