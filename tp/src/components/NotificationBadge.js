import React, {useState, useEffect, useCallback} from 'react';
import {TouchableOpacity, StyleSheet} from 'react-native';
import {Badge, useTheme} from 'react-native-paper';
import {useSelector} from 'react-redux';
import {useNavigation, useFocusEffect} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import {getUnreadNotifications} from '../api/notifications';

/**
 * Badge delle notifiche per l'header - naviga alla pagina dedicata
 */
const NotificationBadge = ({style}) => {
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);

  const theme = useTheme();
  const navigation = useNavigation();
  const {isAuthenticated, user} = useSelector(state => state.auth);

  useEffect(() => {
    if (isAuthenticated && user) {
      fetchUnreadCount();

      // Aggiorna il conteggio ogni 30 secondi
      const interval = setInterval(fetchUnreadCount, 30000);
      return () => clearInterval(interval);
    } else {
      setUnreadCount(0);
    }
  }, [isAuthenticated, user]);

  // Aggiorna il conteggio quando la schermata torna in focus
  // (per esempio quando l'utente torna dalla pagina delle notifiche)
  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated && user) {
        fetchUnreadCount();
      }
    }, [isAuthenticated, user]),
  );

  const fetchUnreadCount = async () => {
    if (loading) return;

    try {
      setLoading(true);
      console.log('🔔 NotificationBadge: Recupero conteggio notifiche...');

      const response = await getUnreadNotifications(1);
      console.log('🔔 NotificationBadge: Risposta ricevuta:', response);

      if (response.success && response.data) {
        const count = response.data.unread_count || 0;
        console.log('🔔 NotificationBadge: Impostando conteggio a:', count);
        setUnreadCount(count);
      } else {
        console.warn('🔔 NotificationBadge: Risposta non valida:', response);
        setUnreadCount(0);
      }
    } catch (error) {
      console.error(
        '❌ NotificationBadge: Errore recupero conteggio notifiche:',
        error,
      );
      console.error('❌ NotificationBadge: Tipo errore:', error.type);
      console.error('❌ NotificationBadge: Messaggio errore:', error.message);
      console.error(
        '❌ NotificationBadge: Errore completo:',
        JSON.stringify(error, null, 2),
      );

      // In caso di errore, mantieni il conteggio precedente
      // setUnreadCount(0);
    } finally {
      setLoading(false);
    }
  };

  const handlePress = () => {
    // Naviga alla schermata delle notifiche
    navigation.navigate('Notifications');
  };

  if (!isAuthenticated) {
    return null;
  }

  return (
    <TouchableOpacity onPress={handlePress} style={[styles.container, style]}>
      <Icon name="notifications" size={24} color={theme.colors.onSurface} />
      {unreadCount > 0 && (
        <Badge style={styles.badge} size={18}>
          {unreadCount > 99 ? '99+' : unreadCount}
        </Badge>
      )}
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  container: {
    position: 'relative',
    padding: 8,
  },
  badge: {
    position: 'absolute',
    top: 2,
    right: 2,
    backgroundColor: '#F44336',
    color: 'white',
    minWidth: 18,
    height: 18,
  },
});

export default NotificationBadge;
