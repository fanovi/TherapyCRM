# 📱 Configurazione OneSignal per TherapyCRM

## 🚀 Setup Completato

OneSignal è stato integrato nell'app React Native con la chiave: `517b6b4d-1c8f-40cf-a814-34830eb24aca`

### ✅ Cosa è stato configurato:

1. **Installazione pacchetti**:

   - `react-native-onesignal` installato
   - Pod iOS installati e configurati
   - Auto-linking attivato per Android

2. **Servizi creati**:

   - `src/services/oneSignalService.js` - Gestione principale OneSignal
   - `src/api/notifications.js` - API per comunicare con il backend
   - `src/hooks/useOneSignal.js` - Hook React per integrare con Redux

3. **Componenti UI**:
   - `src/components/NotificationBadge.js` - Badge per notifiche non lette

## 🏷️ Sistema di Tag

**Tag principale**: `user_id` - Identifica ogni utente nell'app

### Come funziona:

- Quando l'utente fa **login** → OneSignal imposta `user_id = {user.id}`
- Quando l'utente fa **logout** → OneSignal imposta `user_id = -1`

### Tag aggiuntivi configurati:

- `email` - Email dell'utente
- `user_role` - Ruolo dell'utente (paziente, terapista, coordinatore, admin)
- `username` - Nome utente

### Vantaggi del tag -1 per logout:

- **Tracciamento dispositivi**: Mantiene traccia dei dispositivi anche dopo logout
- **Notifiche marketing**: Permette di inviare notifiche generiche agli utenti disconnessi
- **Analytics migliori**: Distingue tra "mai loggato" e "logout esplicito"
- **Re-engagement**: Facilita campagne per riportare utenti nell'app

## 🔔 Gestione Notifiche

### Invio notifiche dal backend:

```php
// Invio a un singolo utente
NotificationHelper::sendToUsers(123, 'Titolo', 'Messaggio');

// Invio a più utenti
NotificationHelper::sendToUsers([1,2,3], 'Titolo', 'Messaggio');

// Invio per ruolo
NotificationHelper::sendToTherapists('Titolo', 'Messaggio');

// Invio a utenti in logout (marketing/re-engagement)
Yii::$app->oneSignal->sendToLoggedOutUsers('Bentornato!', 'Accedi per vedere le tue notifiche');
```

### Gestione nell'app:

- Le notifiche vengono automaticamente gestite da `oneSignalService`
- Quando l'utente tocca una notifica, vengono marcate come lette nell'API
- Il badge mostra il conteggio delle notifiche non lette

## 📋 API Endpoints Disponibili

### Backend (PHP/Yii2):

- `POST /api/notifications/send` - Invia notifica
- `POST /api/notifications/send-template` - Invia con template
- `POST /api/notifications/broadcast` - Invia a tutti
- `GET /api/notifications/unread` - Notifiche non lette
- `POST /api/notifications/{id}/mark-read` - Segna come letta

### App (React Native):

```javascript
import {
  getUnreadNotifications,
  markNotificationAsRead,
} from '../api/notifications';

// Recupera notifiche non lette
const response = await getUnreadNotifications();

// Segna come letta
await markNotificationAsRead(notificationId);
```

## 🎯 Uso nell'App

### Hook OneSignal:

```javascript
import useOneSignal from '../hooks/useOneSignal';

const MyComponent = () => {
  const { sendTag, requestPermissions, getDeviceId } = useOneSignal();

  // Il tag user_id viene gestito automaticamente al login/logout
  // Ma puoi inviare tag personalizzati:
  await sendTag('custom_tag', 'value');
};
```

### Badge Notifiche:

```javascript
import NotificationBadge from '../components/NotificationBadge';

const Header = () => {
  return (
    <NotificationBadge onPress={() => navigation.navigate('Notifications')} />
  );
};
```

## 🛠️ Comandi Console (Backend)

```bash
# Elabora notifiche programmate (cron job)
./yii notification/process-scheduled

# Test notifica a un utente
./yii notification/test 123 "Test" "Messaggio di prova"

# Promemoria scadenze piani terapeutici
./yii notification/plan-expiration-reminders 7

# Statistiche notifiche
./yii notification/stats
```

## 🔧 Configurazione Cron

Aggiungi al crontab del server:

```bash
# Elabora notifiche ogni 5 minuti
*/5 * * * * /path/to/yii notification/process-scheduled

# Promemoria scadenze ogni giorno alle 9:00
0 9 * * * /path/to/yii notification/plan-expiration-reminders

# Controllo soglie assenze ogni lunedì alle 10:00
0 10 * * 1 /path/to/yii notification/absence-threshold
```

## 🎨 Personalizzazione Notifiche

### Dati personalizzati:

```javascript
// Nel backend
$data = [
  'screen' => 'PatientDetails',
  'patient_id' => 123,
  'custom_action' => 'view_appointments'
];

NotificationHelper::sendToUsers($userIds, $title, $message, 'info', $data);
```

### Navigazione personalizzata:

Nel file `oneSignalService.js`, modifica `handleNotificationClick()`:

```javascript
handleNotificationClick(notification) {
  const additionalData = notification.additionalData;

  if (additionalData?.screen) {
    // Usa React Navigation per navigare
    navigationRef.navigate(additionalData.screen, {
      patientId: additionalData.patient_id
    });
  }
}
```

## 🔍 Debug

### Logs nell'app (solo sviluppo):

- Console logs prefissati con emoji (🔔 📱 🏷️ ✅ ❌)
- Log dettagliati delle operazioni OneSignal

### Test in sviluppo:

```javascript
// Verifica tag impostati
const deviceId = await oneSignalService.getDeviceId();
console.log('Device ID:', deviceId);

// Test invio tag
await oneSignalService.sendTag('test', 'value');
```

## ⚠️ Note Importanti

1. **iOS**: Richiede certificati push configurati su OneSignal
2. **Android**: Richiede Firebase/FCM configurato
3. **Permessi**: L'app richiede automaticamente i permessi per le notifiche
4. **Testing**: Usa device fisici per testare le notifiche push

## 🚀 Prossimi Passi

1. Configurare certificati push per iOS
2. Configurare Firebase per Android
3. Testare le notifiche su dispositivi fisici
4. Implementare navigazione personalizzata per diversi tipi di notifiche
5. Creare template avanzati per notifiche ricorrenti

---

## 📞 Supporto

Per problemi con OneSignal, controlla:

- [Documentazione OneSignal](https://documentation.onesignal.com/)
- [React Native OneSignal SDK](https://github.com/OneSignal/react-native-onesignal)
- Console OneSignal per statistiche e debug
