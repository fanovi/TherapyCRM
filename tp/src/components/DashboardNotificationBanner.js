import React from 'react';
import {View, StyleSheet} from 'react-native';
import NotificationPermissionRequest from './NotificationPermissionRequest';

const DashboardNotificationBanner = () => {
  return (
    <View style={styles.container}>
      <NotificationPermissionRequest />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    marginBottom: 16,
  },
});

export default DashboardNotificationBanner;
