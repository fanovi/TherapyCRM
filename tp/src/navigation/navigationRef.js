import {createNavigationContainerRef} from '@react-navigation/native';

/**
 * Ref globale alla NavigationContainer.
 * Usata per navigare da contesti non-React (es. listener OneSignal in services/).
 */
export const navigationRef = createNavigationContainerRef();

/**
 * Naviga alla schermata di dettaglio di una notifica, gestendo il ruolo
 * dell'utente loggato (paziente vs terapista) e la struttura nidificata
 * dei navigator (Tab -> Stack 'Notifications' -> 'NotificationDetail').
 *
 * @param {number|string} notificationId
 */
export function navigateToNotificationDetail(notificationId) {
  if (!navigationRef.isReady() || !notificationId) {
    return;
  }
  // Lazy import per evitare cicli tra services e store.
  // eslint-disable-next-line global-require
  const {store} = require('../store');
  const state = store.getState();
  const user = state?.auth?.user;
  if (!user) {
    return;
  }
  const isPatient =
    user.user_type === 'paziente' || user.role === 'patient';
  const rootScreen = isPatient ? 'Patient' : 'Therapist';

  navigationRef.navigate(rootScreen, {
    screen: 'Notifications',
    params: {
      screen: 'NotificationDetail',
      params: {notificationId: notificationId},
    },
  });
}
