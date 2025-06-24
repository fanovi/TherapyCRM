import React, {useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
  Dimensions,
} from 'react-native';
import {
  Modal,
  Portal,
  Card,
  Button,
  IconButton,
  ActivityIndicator,
  Divider,
} from 'react-native-paper';
import {useSelector, useDispatch} from 'react-redux';
import {closeNotificationModal} from '../slices/uiSlice';
import blockingNotificationService from '../services/blockingNotificationService';

const {height} = Dimensions.get('window');

const NotificationModal = () => {
  const dispatch = useDispatch();
  const {selectedNotification, isNotificationModalOpen} = useSelector(
    state => state.ui,
  );
  const [isConfirming, setIsConfirming] = useState(false);

  const handleClose = () => {
    if (isConfirming) return; // Impedisci la chiusura durante la conferma
    dispatch(closeNotificationModal());
  };

  const handleConfirmRead = async () => {
    if (!selectedNotification) return;

    setIsConfirming(true);

    try {
      console.log(
        `✅ Confermando lettura notifica ${selectedNotification.id}...`,
      );

      const success = await blockingNotificationService.confirmRead(
        selectedNotification.id,
      );

      if (success) {
        // Chiudi la modal dopo la conferma
        dispatch(closeNotificationModal());

        // Mostra un feedback di successo
        Alert.alert(
          'Lettura Confermata',
          'La notifica è stata confermata come letta.',
          [{text: 'OK'}],
        );
      } else {
        Alert.alert(
          'Errore',
          'Si è verificato un errore durante la conferma della lettura. Riprova.',
          [{text: 'OK'}],
        );
      }
    } catch (error) {
      console.error('❌ Errore confermando lettura:', error);
      Alert.alert(
        'Errore',
        'Si è verificato un errore durante la conferma della lettura. Riprova.',
        [{text: 'OK'}],
      );
    } finally {
      setIsConfirming(false);
    }
  };

  const getNotificationTypeIcon = type => {
    switch (type) {
      case 'mandatory_read':
        return 'alert-circle';
      case 'deadline':
        return 'clock-alert';
      case 'reminder':
        return 'bell-alert';
      default:
        return 'information';
    }
  };

  const getNotificationTypeColor = type => {
    switch (type) {
      case 'mandatory_read':
        return '#F44336'; // Rosso
      case 'deadline':
        return '#FF9800'; // Arancione
      case 'reminder':
        return '#2196F3'; // Blu
      default:
        return '#9E9E9E'; // Grigio
    }
  };

  const getNotificationTypeLabel = type => {
    switch (type) {
      case 'mandatory_read':
        return 'Lettura Obbligatoria';
      case 'deadline':
        return 'Scadenza';
      case 'reminder':
        return 'Promemoria';
      case 'info':
        return 'Informazione';
      default:
        return 'Notifica';
    }
  };

  if (!selectedNotification) {
    return null;
  }

  return (
    <Portal>
      <Modal
        visible={isNotificationModalOpen}
        onDismiss={handleClose}
        contentContainerStyle={styles.modalContainer}>
        <Card style={styles.card}>
          {/* Header */}
          <View style={styles.header}>
            <View style={styles.headerLeft}>
              <IconButton
                icon={getNotificationTypeIcon(
                  selectedNotification.notification_type,
                )}
                iconColor={getNotificationTypeColor(
                  selectedNotification.notification_type,
                )}
                size={24}
              />
              <View style={styles.headerText}>
                <Text style={styles.typeLabel}>
                  {getNotificationTypeLabel(
                    selectedNotification.notification_type,
                  )}
                </Text>
                <Text style={styles.date}>
                  {new Date(selectedNotification.created_at).toLocaleDateString(
                    'it-IT',
                    {
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    },
                  )}
                </Text>
              </View>
            </View>
            <IconButton
              icon="close"
              iconColor="#666"
              size={20}
              onPress={handleClose}
              disabled={isConfirming}
            />
          </View>

          <Divider />

          {/* Content */}
          <ScrollView
            style={styles.content}
            showsVerticalScrollIndicator={true}>
            {/* Title */}
            <Text style={styles.title}>{selectedNotification.title}</Text>

            {/* Message */}
            {selectedNotification.message && (
              <View style={styles.messageContainer}>
                <Text style={styles.messageLabel}>Messaggio:</Text>
                <Text style={styles.message}>
                  {selectedNotification.message}
                </Text>
              </View>
            )}

            {/* Sender info if available */}
            {selectedNotification.sender_user_id && (
              <View style={styles.senderContainer}>
                <Text style={styles.senderLabel}>Da:</Text>
                <Text style={styles.sender}>
                  {selectedNotification.senderUser?.nome || 'Sistema'}
                </Text>
              </View>
            )}

            {/* Important notice */}
            <View style={styles.importantNotice}>
              <IconButton icon="alert-circle" iconColor="#F44336" size={20} />
              <Text style={styles.importantNoticeText}>
                Questa notifica richiede la tua conferma di lettura per
                continuare ad utilizzare l'app.
              </Text>
            </View>
          </ScrollView>

          {/* Footer Actions */}
          <View style={styles.footer}>
            <Button
              mode="outlined"
              onPress={handleClose}
              disabled={isConfirming}
              style={styles.cancelButton}>
              Chiudi senza Confermare
            </Button>

            <Button
              mode="contained"
              onPress={handleConfirmRead}
              disabled={isConfirming}
              style={styles.confirmButton}
              icon={isConfirming ? undefined : 'check-circle'}>
              {isConfirming ? (
                <ActivityIndicator color="white" size="small" />
              ) : (
                'Conferma Lettura'
              )}
            </Button>
          </View>
        </Card>
      </Modal>
    </Portal>
  );
};

const styles = StyleSheet.create({
  modalContainer: {
    margin: 20,
    maxHeight: height * 0.85,
  },
  card: {
    backgroundColor: 'white',
    borderRadius: 12,
    overflow: 'hidden',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    backgroundColor: '#F8F9FA',
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  headerText: {
    marginLeft: 8,
  },
  typeLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  date: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  content: {
    maxHeight: height * 0.5,
    padding: 20,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 16,
    lineHeight: 28,
  },
  messageContainer: {
    marginBottom: 16,
  },
  messageLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#666',
    marginBottom: 8,
  },
  message: {
    fontSize: 16,
    color: '#333',
    lineHeight: 24,
  },
  senderContainer: {
    marginBottom: 16,
  },
  senderLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#666',
    marginBottom: 4,
  },
  sender: {
    fontSize: 14,
    color: '#333',
  },
  importantNotice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#FFF3E0',
    padding: 12,
    borderRadius: 8,
    marginTop: 16,
    borderLeftWidth: 4,
    borderLeftColor: '#F44336',
  },
  importantNoticeText: {
    flex: 1,
    fontSize: 14,
    color: '#E65100',
    lineHeight: 20,
    marginLeft: 8,
    marginTop: 2,
  },
  footer: {
    flexDirection: 'row',
    padding: 20,
    gap: 12,
    backgroundColor: '#F8F9FA',
    borderTopWidth: 1,
    borderTopColor: '#E0E0E0',
  },
  cancelButton: {
    flex: 1,
    borderColor: '#666',
  },
  confirmButton: {
    flex: 1,
    backgroundColor: '#4CAF50',
  },
});

export default NotificationModal;
