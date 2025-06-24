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
  FAB,
} from 'react-native-paper';
import {useSelector} from 'react-redux';
import {useFocusEffect} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import ScreenTemplate from '../../components/ScreenTemplate';
import {
  getNotifications,
  markNotificationAsRead,
  confirmNotificationRead,
  createTestNotification,
} from '../../api/notifications';

const TherapistNotificationsScreen = () => {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [filter, setFilter] = useState('all'); // all, unread, read, type
  const [typeFilter, setTypeFilter] = useState('all'); // all, info, reminder, deadline, mandatory_read

  const theme = useTheme();
  const {user} = useSelector(state => state.auth);

  useFocusEffect(
    useCallback(() => {
      fetchNotifications(1, true);
    }, [filter, typeFilter]),
  );

  const fetchNotifications = async (pageNum = 1, refresh = false) => {
    try {
      if (refresh) {
        setRefreshing(true);
        setPage(1);
        setHasMore(true);
      } else {
        setLoadingMore(true);
      }

      const response = await getNotifications(pageNum, 10); // 10 per pagina

      if (response.success && response.data) {
        let newNotifications = response.data.notifications || [];

        // Applica i filtri
        if (filter === 'unread') {
          newNotifications = newNotifications.filter(n => !n.read_at);
        } else if (filter === 'read') {
          newNotifications = newNotifications.filter(n => n.read_at);
        }

        if (typeFilter !== 'all') {
          newNotifications = newNotifications.filter(
            n => n.notification_type === typeFilter,
          );
        }

        if (refresh || pageNum === 1) {
          setNotifications(newNotifications);
        } else {
          setNotifications(prev => [...prev, ...newNotifications]);
        }

        // Verifica se ci sono altre pagine usando has_next
        const {pagination} = response.data;
        setHasMore(pagination && pagination.has_next);
        setPage(pageNum);
      }
    } catch (error) {
      console.error('Errore recupero notifiche:', error);
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

  const getPriorityIcon = type => {
    switch (type) {
      case 'mandatory_read':
        return 'flag';
      case 'deadline':
        return 'error';
      case 'reminder':
        return 'access-time';
      default:
        return null;
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

  const getUnreadCount = () => notifications.filter(n => !n.read_at).length;
  const getTypeCount = type =>
    notifications.filter(n => n.notification_type === type).length;

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
            borderLeftWidth: notification.requires_read_confirmation ? 4 : 0,
            borderLeftColor: getNotificationColor(
              notification.notification_type,
              false,
            ),
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
              <Chip
                mode="outlined"
                compact
                style={[
                  styles.typeChip,
                  {
                    borderColor: getNotificationColor(
                      notification.notification_type,
                      !!notification.read_at,
                    ),
                  },
                ]}>
                {getTypeLabel(notification.notification_type)}
              </Chip>

              {/* Indicatore priorità */}
              {getPriorityIcon(notification.notification_type) && (
                <Icon
                  name={getPriorityIcon(notification.notification_type)}
                  size={16}
                  color={getNotificationColor(
                    notification.notification_type,
                    !!notification.read_at,
                  )}
                  style={styles.priorityIcon}
                />
              )}
            </View>

            <View style={styles.headerRight}>
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
                    styles.senderInfo,
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
        {filter === 'all' &&
          typeFilter === 'all' &&
          'Le notifiche appariranno qui'}
      </Text>

      {/* Pulsanti per test in sviluppo */}
      {__DEV__ && filter === 'all' && typeFilter === 'all' && (
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
      title="Notifiche"
      subtitle={`${notifications.length} notifiche${
        getUnreadCount() > 0 ? ` • ${getUnreadCount()} non lette` : ''
      }`}
      showNotifications={false} // Non mostrare il dropdown qui
      headerRight={
        <View style={styles.headerActions}>
          <IconButton
            icon="refresh"
            size={24}
            onPress={() => fetchNotifications(1, true)}
            iconColor={theme.colors.secondary}
          />
        </View>
      }>
      {/* Statistiche veloce */}
      <View style={styles.statsContainer}>
        <View style={styles.statItem}>
          <Text style={[styles.statNumber, {color: theme.colors.primary}]}>
            {notifications.length}
          </Text>
          <Text
            style={[styles.statLabel, {color: theme.colors.onSurfaceVariant}]}>
            Totali
          </Text>
        </View>
        <View style={styles.statItem}>
          <Text style={[styles.statNumber, {color: '#F44336'}]}>
            {getUnreadCount()}
          </Text>
          <Text
            style={[styles.statLabel, {color: theme.colors.onSurfaceVariant}]}>
            Non lette
          </Text>
        </View>
        <View style={styles.statItem}>
          <Text style={[styles.statNumber, {color: '#FF9800'}]}>
            {getTypeCount('deadline')}
          </Text>
          <Text
            style={[styles.statLabel, {color: theme.colors.onSurfaceVariant}]}>
            Scadenze
          </Text>
        </View>
        <View style={styles.statItem}>
          <Text style={[styles.statNumber, {color: theme.colors.secondary}]}>
            {getTypeCount('reminder')}
          </Text>
          <Text
            style={[styles.statLabel, {color: theme.colors.onSurfaceVariant}]}>
            Promemoria
          </Text>
        </View>
      </View>

      <Divider />

      {/* Filtri Stato */}
      <View style={styles.filtersContainer}>
        <Text style={[styles.filterTitle, {color: theme.colors.onSurface}]}>
          Stato
        </Text>
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
            Non lette ({getUnreadCount()})
          </Chip>
          <Chip
            selected={filter === 'read'}
            onPress={() => setFilter('read')}
            style={styles.filterChip}>
            Lette
          </Chip>
        </View>
      </View>

      {/* Filtri Tipo */}
      <View style={styles.filtersContainer}>
        <Text style={[styles.filterTitle, {color: theme.colors.onSurface}]}>
          Tipo
        </Text>
        <View style={styles.filters}>
          <Chip
            selected={typeFilter === 'all'}
            onPress={() => setTypeFilter('all')}
            style={styles.filterChip}>
            Tutti
          </Chip>
          <Chip
            selected={typeFilter === 'info'}
            onPress={() => setTypeFilter('info')}
            style={styles.filterChip}>
            Info
          </Chip>
          <Chip
            selected={typeFilter === 'reminder'}
            onPress={() => setTypeFilter('reminder')}
            style={styles.filterChip}>
            Promemoria
          </Chip>
          <Chip
            selected={typeFilter === 'deadline'}
            onPress={() => setTypeFilter('deadline')}
            style={styles.filterChip}>
            Scadenze
          </Chip>
          <Chip
            selected={typeFilter === 'mandatory_read'}
            onPress={() => setTypeFilter('mandatory_read')}
            style={styles.filterChip}>
            Obbligatorie
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
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  statsContainer: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    paddingVertical: 16,
    justifyContent: 'space-around',
  },
  statItem: {
    alignItems: 'center',
  },
  statNumber: {
    fontSize: 24,
    fontWeight: '700',
  },
  statLabel: {
    fontSize: 12,
    marginTop: 2,
  },
  filtersContainer: {
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  filterTitle: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 8,
  },
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  filterChip: {
    marginRight: 8,
    marginBottom: 4,
  },
  notificationTouchable: {
    marginHorizontal: 20,
    marginVertical: 6,
  },
  notificationCard: {
    borderRadius: 12,
    elevation: 2,
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
  priorityIcon: {
    marginLeft: 8,
  },
  headerRight: {
    alignItems: 'center',
  },
  unreadIndicator: {
    width: 10,
    height: 10,
    borderRadius: 5,
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
    alignItems: 'flex-end',
  },
  footerLeft: {
    flex: 1,
  },
  footerRight: {
    marginLeft: 8,
  },
  notificationDate: {
    fontSize: 12,
    marginBottom: 2,
  },
  senderInfo: {
    fontSize: 11,
    fontStyle: 'italic',
  },
  readStatus: {
    fontSize: 11,
    fontWeight: '500',
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

export default TherapistNotificationsScreen;
