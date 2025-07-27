import React, {useState, useEffect, useCallback} from 'react';
import {
  View,
  StyleSheet,
  FlatList,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
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
import {useSelector} from 'react-redux';
import {useFocusEffect, useNavigation} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getNotifications,
  markNotificationAsRead,
  confirmNotificationRead,
  createTestNotification,
} from '../../api/notifications';

const PatientNotificationsScreen = () => {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [filter, setFilter] = useState('all'); // all, unread, read

  const theme = useTheme();
  const navigation = useNavigation();
  const {user} = useSelector(state => state.auth);

  useFocusEffect(
    useCallback(() => {
      fetchNotifications(1, true);
    }, [filter]),
  );

  const fetchNotifications = async (pageNum = 1, refresh = false) => {
    try {
      console.log(
        `📱 PatientNotifications: Recupero notifiche pagina ${pageNum}, refresh: ${refresh}`,
      );

      if (refresh) {
        setRefreshing(true);
        setPage(1);
        setHasMore(true);
      } else {
        setLoadingMore(true);
      }

      const response = await getNotifications(pageNum, 10); // 10 per pagina
      console.log('📱 PatientNotifications: Risposta ricevuta:', response);

      if (response.success && response.data) {
        const newNotifications = response.data.notifications || [];
        console.log(
          `📱 PatientNotifications: ${newNotifications.length} notifiche ricevute`,
        );

        // Applica il filtro
        let filteredNotifications = newNotifications;
        if (filter === 'unread') {
          filteredNotifications = newNotifications.filter(n => !n.read_at);
        } else if (filter === 'read') {
          filteredNotifications = newNotifications.filter(n => n.read_at);
        }
        console.log(
          `📱 PatientNotifications: ${filteredNotifications.length} notifiche dopo filtro '${filter}'`,
        );

        if (refresh || pageNum === 1) {
          setNotifications(filteredNotifications);
        } else {
          setNotifications(prev => [...prev, ...filteredNotifications]);
        }

        // Verifica se ci sono altre pagine usando has_next
        const {pagination} = response.data;
        setHasMore(pagination && pagination.has_next);
        setPage(pageNum);
        console.log('📱 PatientNotifications: Paginazione:', pagination);
      } else {
        console.warn('📱 PatientNotifications: Risposta non valida:', response);
        setNotifications([]);
      }
    } catch (error) {
      console.error(
        '❌ PatientNotifications: Errore recupero notifiche:',
        error,
      );
      console.error('❌ PatientNotifications: Tipo errore:', error.type);
      console.error(
        '❌ PatientNotifications: Messaggio errore:',
        error.message,
      );
      console.error(
        '❌ PatientNotifications: Errore completo:',
        JSON.stringify(error, null, 2),
      );

      // In caso di errore, mostra lista vuota
      setNotifications([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  };

  const handleNotificationPress = notification => {
    // Naviga al dettaglio della notifica
    navigation.navigate('NotificationDetail', {
      notificationId: notification.id,
    });
  };

  const handleLoadMore = () => {
    if (hasMore && !loadingMore) {
      fetchNotifications(page + 1, false);
    }
  };

  const handleCreateTestNotification = async (type = 'normal') => {
    try {
      await createTestNotification(type);
      // Ricarica le notifiche per vedere quella nuova
      setTimeout(() => fetchNotifications(1, true), 1000);
    } catch (error) {
      console.error('Errore creazione notifica di test:', error);
    }
  };

  const getNotificationIcon = type => {
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

  const getNotificationColor = (type, isRead) => {
    if (isRead) {
      return theme.colors.onSurfaceVariant;
    }

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
    switch (type) {
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
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const renderNotificationItem = ({item: notification}) => (
    <TouchableOpacity
      onPress={() => handleNotificationPress(notification)}
      style={styles.notificationTouchable}>
      <Card
        style={[
          styles.notificationCard,
          {
            backgroundColor: notification.read_at
              ? theme.colors.surface
              : theme.colors.primaryContainer + '30',
          },
        ]}>
        <Card.Content style={styles.cardContent}>
          <View style={styles.notificationHeader}>
            <View style={styles.iconAndType}>
              <Icon
                name={getNotificationIcon(notification.notification_type)}
                size={24}
                color={getNotificationColor(
                  notification.notification_type,
                  !!notification.read_at,
                )}
                style={styles.notificationIcon}
              />
            </View>

            {!notification.read_at && (
              <View
                style={[
                  styles.unreadIndicator,
                  {
                    backgroundColor: getNotificationColor(
                      notification.notification_type,
                      false,
                    ),
                  },
                ]}
              />
            )}
          </View>

          <Text
            style={[
              styles.notificationTitle,
              {
                color: theme.colors.onSurface,
                fontWeight: notification.read_at ? '400' : '600',
              },
            ]}>
            {notification.title}
          </Text>

          {notification.message_preview && (
            <Text
              style={[
                styles.notificationMessage,
                {color: theme.colors.onSurfaceVariant},
              ]}
              numberOfLines={2}>
              {notification.message_preview}
            </Text>
          )}

          <View style={styles.notificationFooter}>
            <View style={styles.footerLeft}>
              <Text
                style={[
                  styles.notificationDate,
                  {color: theme.colors.onSurfaceVariant},
                ]}>
                {formatDate(notification.created_at)}
              </Text>
              {notification.sender_name && (
                <Text
                  style={[
                    styles.senderName,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  • {notification.sender_name}
                </Text>
              )}
            </View>

            <View style={styles.footerRight}>
              {notification.read_at ? (
                <Icon
                  name="check-circle"
                  size={16}
                  color={theme.colors.primary}
                />
              ) : (
                <Icon
                  name="chevron-right"
                  size={20}
                  color={theme.colors.onSurfaceVariant}
                />
              )}
            </View>
          </View>
        </Card.Content>
      </Card>
    </TouchableOpacity>
  );

  const renderListFooter = () => {
    if (!hasMore) return null;

    return (
      <View style={styles.loadMoreContainer}>
        {loadingMore ? (
          <ActivityIndicator color={theme.colors.primary} />
        ) : (
          <Button mode="outlined" onPress={handleLoadMore}>
            Carica altre notifiche
          </Button>
        )}
      </View>
    );
  };

  const renderEmptyList = () => (
    <View style={styles.emptyContainer}>
      <Icon
        name="notifications-none"
        size={64}
        color={theme.colors.onSurfaceVariant}
      />
      <Text style={[styles.emptyTitle, {color: theme.colors.onSurface}]}>
        {filter === 'unread'
          ? 'Nessuna notifica non letta'
          : filter === 'read'
          ? 'Nessuna notifica letta'
          : 'Nessuna notifica'}
      </Text>
      <Text
        style={[styles.emptySubtitle, {color: theme.colors.onSurfaceVariant}]}>
        {filter === 'all' && 'Le tue notifiche appariranno qui'}
      </Text>

      {/* Pulsanti per test in sviluppo */}
      {__DEV__ && (
        <View style={styles.devButtons}>
          <Button
            mode="outlined"
            onPress={() => handleCreateTestNotification('normal')}
            style={styles.devButton}>
            Test Normale
          </Button>
          <Button
            mode="outlined"
            onPress={() => handleCreateTestNotification('blocking')}
            style={styles.devButton}>
            Test Bloccante
          </Button>
        </View>
      )}
    </View>
  );

  return (
    <ScreenTemplate
      message="👤 Stai visualizzando le notifiche per tutti i pazienti a te associati"
      title="Notifiche"
      subtitle={`${notifications.length} notifiche`}
      showNotifications={false} // Non mostrare il dropdown qui
      headerRight={
        <IconButton
          icon="refresh"
          size={24}
          onPress={() => fetchNotifications(1, true)}
          iconColor={theme.colors.secondary}
        />
      }>
      {/* Filtri */}
      <View style={styles.filtersContainer}>
        <View style={styles.filters}>
          <Chip
            selected={filter === 'all'}
            onPress={() => setFilter('all')}
            style={styles.filterChip}>
            Tutte
          </Chip>
          <Chip
            selected={filter === 'unread'}
            onPress={() => setFilter('unread')}
            style={styles.filterChip}>
            Non lette
          </Chip>
          <Chip
            selected={filter === 'read'}
            onPress={() => setFilter('read')}
            style={styles.filterChip}>
            Lette
          </Chip>
        </View>
      </View>

      <Divider />

      {/* Lista Notifiche */}
      <FlatList
        data={notifications}
        keyExtractor={item => item.id.toString()}
        renderItem={renderNotificationItem}
        ListEmptyComponent={!loading ? renderEmptyList : null}
        ListFooterComponent={renderListFooter}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => fetchNotifications(1, true)}
            colors={[theme.colors.primary]}
          />
        }
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.1}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={
          notifications.length === 0 ? styles.emptyListContainer : null
        }
      />
    </ScreenTemplate>
  );
};

const styles = StyleSheet.create({
  filtersContainer: {
    paddingHorizontal: 20,
    paddingVertical: 16,
  },
  filters: {
    flexDirection: 'row',
    gap: 8,
  },
  filterChip: {
    marginRight: 8,
  },
  notificationTouchable: {
    marginHorizontal: 20,
    marginVertical: 6,
  },
  notificationCard: {
    borderRadius: 12,
    elevation: 1,
  },
  cardContent: {
    padding: 16,
  },
  notificationHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  iconAndType: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  notificationIcon: {
    marginRight: 8,
  },
  typeChip: {
    height: 24,
  },
  unreadIndicator: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginLeft: 8,
  },
  notificationTitle: {
    fontSize: 16,
    lineHeight: 22,
    marginBottom: 8,
  },
  notificationMessage: {
    fontSize: 14,
    lineHeight: 20,
    marginBottom: 12,
  },
  notificationFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
  },
  footerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  footerRight: {
    marginLeft: 8,
  },
  notificationDate: {
    fontSize: 12,
  },
  senderName: {
    fontSize: 12,
    marginLeft: 4,
  },
  loadMoreContainer: {
    padding: 20,
    alignItems: 'center',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  emptyListContainer: {
    flexGrow: 1,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    marginTop: 16,
    marginBottom: 8,
    textAlign: 'center',
  },
  emptySubtitle: {
    fontSize: 14,
    textAlign: 'center',
    marginBottom: 24,
  },
  devButtons: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 16,
  },
  devButton: {
    minWidth: 120,
  },
});

export default PatientNotificationsScreen;
