import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
  Dimensions,
} from 'react-native';
import {BlurView} from '@react-native-community/blur';
import {useSelector, useDispatch} from 'react-redux';
import {Card, Button, Badge, IconButton} from 'react-native-paper';
import {openNotificationModal} from '../slices/uiSlice';
import {logoutUser} from '../slices/authSlice';
import blockingNotificationService from '../services/blockingNotificationService';

const {height, width} = Dimensions.get('window');

const BlockingNotificationOverlay = () => {
  const dispatch = useDispatch();
  const {blockingNotifications, isAppBlocked} = useSelector(state => state.ui);

  if (!isAppBlocked || blockingNotifications.length === 0) {
    return null;
  }

  const handleNotificationPress = async notification => {
    // Segna come visualizzata quando l'utente clicca
    await blockingNotificationService.markAsViewed(notification.id);

    // Apri la modal con il contenuto completo
    dispatch(openNotificationModal(notification));
  };

  const handleLogout = () => {
    Alert.alert(
      'Conferma Logout',
      'Sei sicuro di voler uscire? Le notifiche non lette rimarranno da confermare al prossimo accesso.',
      [
        {
          text: 'Annulla',
          style: 'cancel',
        },
        {
          text: 'Esci',
          style: 'destructive',
          onPress: () => {
            blockingNotificationService.clearAll();
            dispatch(logoutUser());
          },
        },
      ],
    );
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

  return (
    <View style={styles.overlay}>
      {/* Background blur */}
      <BlurView style={styles.blurView} blurType="dark" blurAmount={10} />

      {/* Content */}
      <View style={styles.content}>
        {/* Header */}
        <View style={styles.header}>
          <View style={styles.headerText}>
            <Text style={styles.title}>Notifiche Importanti</Text>
            <Text style={styles.subtitle}>
              Devi leggere e confermare queste notifiche per continuare
            </Text>
          </View>
          <Badge style={styles.badge} size={24}>
            {blockingNotifications.length}
          </Badge>
        </View>

        {/* Notifications List */}
        <ScrollView
          style={styles.scrollView}
          showsVerticalScrollIndicator={true}>
          {blockingNotifications.map(notification => (
            <TouchableOpacity
              key={notification.id}
              onPress={() => handleNotificationPress(notification)}
              activeOpacity={0.7}>
              <Card style={styles.notificationCard} mode="elevated">
                <Card.Content>
                  <View style={styles.notificationHeader}>
                    <View style={styles.notificationIcon}>
                      <IconButton
                        icon={getNotificationTypeIcon(
                          notification.notification_type,
                        )}
                        iconColor={getNotificationTypeColor(
                          notification.notification_type,
                        )}
                        size={20}
                      />
                    </View>
                    <View style={styles.notificationTextContainer}>
                      <Text style={styles.notificationTitle}>
                        {notification.title}
                      </Text>
                      <Text style={styles.notificationDate}>
                        {new Date(notification.created_at).toLocaleDateString(
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

                  {/* Blurred message preview */}
                  <View style={styles.messagePreview}>
                    <BlurView
                      style={styles.messageBlur}
                      blurType="light"
                      blurAmount={5}>
                      <Text style={styles.blurredText}>
                        {notification.message?.substring(0, 100) ||
                          'Tocca per leggere il contenuto...'}
                      </Text>
                    </BlurView>
                  </View>

                  <View style={styles.actionHint}>
                    <Text style={styles.actionHintText}>
                      👆 Tocca per leggere e confermare
                    </Text>
                  </View>
                </Card.Content>
              </Card>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {/* Footer Actions */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            L'app è bloccata fino alla conferma di tutte le notifiche
          </Text>
          <Button
            mode="outlined"
            onPress={handleLogout}
            icon="logout"
            style={styles.logoutButton}
            textColor="#F44336">
            Esci dall'App
          </Button>
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    zIndex: 9999,
    backgroundColor: 'rgba(0, 0, 0, 0.3)',
  },
  blurView: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
  },
  content: {
    flex: 1,
    margin: 20,
    marginTop: 60,
    backgroundColor: 'white',
    borderRadius: 16,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.25,
    shadowRadius: 12,
    elevation: 8,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#E0E0E0',
  },
  headerText: {
    flex: 1,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 14,
    color: '#666',
    lineHeight: 20,
  },
  badge: {
    backgroundColor: '#F44336',
    color: 'white',
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  notificationCard: {
    marginBottom: 12,
    backgroundColor: 'white',
  },
  notificationHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  notificationIcon: {
    marginRight: 8,
  },
  notificationTextContainer: {
    flex: 1,
  },
  notificationTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
    marginBottom: 2,
  },
  notificationDate: {
    fontSize: 12,
    color: '#999',
  },
  messagePreview: {
    position: 'relative',
    marginBottom: 12,
  },
  messageBlur: {
    padding: 12,
    borderRadius: 8,
    backgroundColor: 'rgba(240, 240, 240, 0.8)',
  },
  blurredText: {
    fontSize: 14,
    color: '#666',
    lineHeight: 20,
  },
  actionHint: {
    alignItems: 'center',
    paddingTop: 8,
  },
  actionHintText: {
    fontSize: 12,
    color: '#2196F3',
    fontStyle: 'italic',
  },
  footer: {
    padding: 20,
    borderTopWidth: 1,
    borderTopColor: '#E0E0E0',
    alignItems: 'center',
  },
  footerText: {
    fontSize: 12,
    color: '#666',
    textAlign: 'center',
    marginBottom: 12,
    lineHeight: 16,
  },
  logoutButton: {
    borderColor: '#F44336',
  },
});

export default BlockingNotificationOverlay;
