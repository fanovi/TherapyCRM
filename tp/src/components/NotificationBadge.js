import React, {useState, useEffect} from 'react';
import {View, Text, TouchableOpacity, StyleSheet} from 'react-native';
import {Badge} from 'react-native-paper';
import {useSelector} from 'react-redux';
import {getUnreadNotifications} from '../api/notifications';

/**
 * Componente badge per mostrare il numero di notifiche non lette
 */
const NotificationBadge = ({onPress, style}) => {
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
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

  const fetchUnreadCount = async () => {
    if (loading) return;

    try {
      setLoading(true);
      const response = await getUnreadNotifications(1); // Solo per il conteggio

      if (response.success && response.data) {
        setUnreadCount(response.data.unread_count || 0);
      }
    } catch (error) {
      console.error('Errore recupero notifiche non lette:', error);
      // Non mostrare errori all'utente per questo controllo automatico
    } finally {
      setLoading(false);
    }
  };

  const handlePress = () => {
    if (onPress) {
      onPress();
    }
    // Dopo aver aperto le notifiche, aggiorna il conteggio
    setTimeout(fetchUnreadCount, 1000);
  };

  if (!isAuthenticated || unreadCount === 0) {
    return (
      <TouchableOpacity onPress={handlePress} style={[styles.container, style]}>
        <Text style={styles.bellIcon}>🔔</Text>
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity onPress={handlePress} style={[styles.container, style]}>
      <Text style={styles.bellIcon}>🔔</Text>
      <Badge style={styles.badge} size={20}>
        {unreadCount > 99 ? '99+' : unreadCount}
      </Badge>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  container: {
    position: 'relative',
    padding: 8,
  },
  bellIcon: {
    fontSize: 24,
  },
  badge: {
    position: 'absolute',
    top: 2,
    right: 2,
    backgroundColor: '#ff4444',
    color: 'white',
    minWidth: 18,
    height: 18,
  },
});

export default NotificationBadge;
