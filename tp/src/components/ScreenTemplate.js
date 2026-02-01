import React from 'react';
import {View, StyleSheet, ScrollView, StatusBar, TouchableOpacity} from 'react-native';
import {Text, IconButton, useTheme} from 'react-native-paper';
import {useSelector} from 'react-redux';
import {useNavigation} from '@react-navigation/native';
import NotificationBadge from './NotificationBadge';
import FloatingPatientSelector from './FloatingPatientSelector';

const ScreenTemplate = ({
  children,
  title,
  subtitle,
  headerRight,
  scrollable = true,
  showHeader = true,
  useBackgroundColor = true,
  useHeaderBackground = true,
  showNotifications = true,
  showBackButton = false,
  onBackPress,
  message,
}) => {
  const theme = useTheme();
  const navigation = useNavigation();
  const {user} = useSelector(state => state.auth);
  const {patients, currentPatient} = useSelector(state => state.patient);
  const ContentComponent = scrollable ? ScrollView : View;

  const handleBackPress = () => {
    if (onBackPress) {
      onBackPress();
    } else {
      navigation.goBack();
    }
  };

  const backgroundColor = useBackgroundColor
    ? theme.colors.background
    : 'transparent';
  const headerBackgroundColor = useHeaderBackground
    ? theme.colors.surface
    : 'transparent';

  // Determina se mostrare info paziente (solo per utenti pazienti con pazienti associati)
  const isPatientUser =
    user?.user_type === 'paziente' || user?.role === 'patient';
  const hasPatients = patients && patients.length > 0;
  const showPatientInfo = isPatientUser && hasPatients;

  return (
    <View style={[styles.container, {backgroundColor}]}>
      <StatusBar
        barStyle="dark-content"
        backgroundColor={backgroundColor}
        translucent={false}
      />

      {showHeader && (
        <View
          style={[
            styles.header,
            {
              backgroundColor: headerBackgroundColor,
              borderBottomColor: theme.colors.outline,
            },
          ]}>
          <View style={styles.headerContent}>
            {showBackButton && (
              <TouchableOpacity
                onPress={handleBackPress}
                style={styles.backButton}>
                <IconButton
                  icon="arrow-left"
                  size={24}
                  iconColor={theme.colors.onSurface}
                />
              </TouchableOpacity>
            )}
            <View style={[styles.headerLeft, showBackButton && styles.headerLeftWithBack]}>
              {/* Saluto utente connesso - nascosto se c'è il pulsante indietro */}
              {!showBackButton && (
                <Text
                  style={[styles.welcomeText, {color: theme.colors.onSurface}]}>
                  Ciao, {user?.firstName || user?.name || 'Utente'}
                </Text>
              )}

              {/* Titoli originali */}
              {title && (
                <Text style={[styles.title, {color: theme.colors.onSurface}]}>
                  {title}
                </Text>
              )}
              {subtitle && (
                <Text
                  style={[
                    styles.subtitle,
                    {color: theme.colors.onSurfaceVariant},
                  ]}>
                  {subtitle}
                </Text>
              )}
            </View>
            <View style={styles.headerRight}>
              {showNotifications && (
                <NotificationBadge style={styles.notificationBadge} />
              )}
              {headerRight}
            </View>
          </View>
        </View>
      )}

      {/* Barra informazioni paziente - sotto l'header */}
      {showPatientInfo && currentPatient && (
        <View
          style={[
            styles.patientInfoBar,
            {backgroundColor: theme.colors.primaryContainer},
          ]}>
          <Text
            style={[
              styles.patientInfoText,
              {color: theme.colors.onPrimaryContainer},
            ]}>
            {message ? (
              message
            ) : (
              <>
                <Text>👤 Stai visualizzando l'app per il paziente: </Text>
                <Text style={styles.patientName}>
                  {currentPatient.patient_name}
                </Text>
              </>
            )}
          </Text>
        </View>
      )}

      <ContentComponent
        style={styles.content}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={scrollable ? styles.scrollContent : undefined}>
        {children}
      </ContentComponent>

      {/* Floating Patient Selector - solo se ci sono più pazienti */}
      {isPatientUser && patients && patients.length > 1 && (
        <FloatingPatientSelector />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerLeft: {
    flex: 1,
  },
  headerLeftWithBack: {
    marginLeft: -8,
  },
  backButton: {
    marginLeft: -12,
    marginRight: 4,
  },
  headerRight: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: 16,
  },
  notificationBadge: {
    marginRight: 8,
  },
  welcomeText: {
    fontSize: 16,
    fontWeight: '500',
    marginBottom: 8,
  },
  title: {
    fontSize: 24,
    fontWeight: '600',
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 14,
    fontWeight: '400',
  },
  patientInfoBar: {
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(0,0,0,0.1)',
  },
  patientInfoText: {
    fontSize: 14,
    fontWeight: '400',
    textAlign: 'center',
  },
  patientName: {
    fontWeight: '600',
  },
  content: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingBottom: 20,
  },
});

export default ScreenTemplate;
