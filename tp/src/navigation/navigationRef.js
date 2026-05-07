import {createNavigationContainerRef} from '@react-navigation/native';

/**
 * Ref globale alla NavigationContainer.
 * Usata per navigare da contesti non-React (es. listener OneSignal in services/).
 */
export const navigationRef = createNavigationContainerRef();

let pendingNotificationId = null;
let pendingTimer = null;

function attemptNavigate(notificationId) {
  if (!navigationRef.isReady()) {
    return false;
  }
  // Lazy import per evitare cicli tra services e store.
  // eslint-disable-next-line global-require
  const {store} = require('../store');
  const state = store.getState();
  const user = state?.auth?.user;
  const isAuthenticated = state?.auth?.isAuthenticated;
  if (!isAuthenticated || !user) {
    // Utente non ancora loggato: rimanda al ready successivo dopo login.
    return false;
  }
  const isPatient =
    user.user_type === 'paziente' || user.role === 'patient';
  const rootScreen = isPatient ? 'Patient' : 'Therapist';

  console.log(
    '[navigationRef] navigating to NotificationDetail',
    {rootScreen, notificationId},
  );

  if (notificationId) {
    navigationRef.navigate(rootScreen, {
      screen: 'Notifications',
      params: {
        screen: 'NotificationDetail',
        initial: false,
        params: {notificationId: notificationId},
      },
    });
  } else {
    navigationRef.navigate(rootScreen, {
      screen: 'Notifications',
      params: {screen: 'NotificationsList'},
    });
  }
  return true;
}

/**
 * Naviga al dettaglio notifica. Se la navigation non e' ancora pronta o
 * l'utente non e' loggato, mette l'id in coda e ripete il tentativo
 * periodicamente per ~10s (gestisce cold start dal push).
 *
 * @param {number|string|null} notificationId
 */
export function navigateToNotificationDetail(notificationId) {
  pendingNotificationId = notificationId || null;
  if (attemptNavigate(pendingNotificationId)) {
    pendingNotificationId = null;
    if (pendingTimer) {
      clearInterval(pendingTimer);
      pendingTimer = null;
    }
    return;
  }
  if (pendingTimer) {
    return;
  }
  let tries = 0;
  pendingTimer = setInterval(() => {
    tries += 1;
    if (attemptNavigate(pendingNotificationId)) {
      pendingNotificationId = null;
      clearInterval(pendingTimer);
      pendingTimer = null;
    } else if (tries >= 20) {
      // ~10s totali, smettiamo per non lasciare timer attivo.
      clearInterval(pendingTimer);
      pendingTimer = null;
    }
  }, 500);
}

/**
 * Hook esterno: chiamare quando lo stato auth cambia (es. dopo login)
 * cosi' una notifica clickata prima dell'autenticazione viene processata.
 */
export function flushPendingNotificationNavigation() {
  if (pendingNotificationId !== null) {
    if (attemptNavigate(pendingNotificationId)) {
      pendingNotificationId = null;
      if (pendingTimer) {
        clearInterval(pendingTimer);
        pendingTimer = null;
      }
    }
  }
}
