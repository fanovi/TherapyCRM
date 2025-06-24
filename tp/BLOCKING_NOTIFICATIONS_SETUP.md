# Setup Notifiche Bloccanti - TherapyCRM

## 📋 Panoramica

Sistema di notifiche bloccanti implementato nell'app mobile React Native. Quando un utente riceve notifiche con `requires_read_confirmation = true`, l'app viene bloccata fino alla conferma di lettura di tutte le notifiche.

## 🏗️ Architettura Implementata

### Backend (API)

- **Endpoint aggiunti al `NotificationController.php`:**
  - `GET /api/notifications/blocking` - Recupera notifiche bloccanti
  - `POST /api/notifications/{id}/confirm-read` - Conferma lettura
  - `POST /api/notifications/{id}/mark-viewed` - Segna come visualizzata

### Frontend (React Native)

- **Redux Store esteso** (`uiSlice.js`) per gestire stato notifiche bloccanti
- **Servizio dedicato** (`blockingNotificationService.js`) per logica business
- **Hook personalizzato** (`useBlockingNotifications.js`) per integrazione
- **Componenti UI:**
  - `BlockingNotificationOverlay` - Overlay che blocca l'app
  - `NotificationModal` - Modal per lettura e conferma

## 🎯 Funzionalità

### Comportamento App Bloccata

- ✅ Overlay full-screen che copre l'intera app
- ✅ Lista notifiche con contenuto blurrato
- ✅ Solo 2 azioni possibili: leggere notifica o logout
- ✅ Indicatore conteggio notifiche da leggere

### Lettura Notifiche

- ✅ Click su notifica → segna come "visualizzata" (tracking)
- ✅ Modal con contenuto completo e chiaro
- ✅ Tasto "Conferma Lettura" → API conferma + rimozione da lista
- ✅ Feedback visivo di successo

### Sistema Automatico

- ✅ Controllo automatico al login
- ✅ Polling ogni 30 secondi quando autenticati
- ✅ Controllo quando app torna in foreground
- ✅ Cleanup automatico al logout

## 🛠️ Setup Richiesto

### 1. Installazione Dipendenze React Native

```bash
cd tp/
npm install @react-native-community/blur
```

### 2. Setup iOS (se necessario)

```bash
cd ios/
pod install
```

### 3. Setup Android (se necessario)

Aggiungere al `android/app/src/main/java/.../MainApplication.java`:

```java
import com.cmcewen.blurview.BlurViewPackage;

@Override
protected List<ReactPackage> getPackages() {
  return Arrays.<ReactPackage>asList(
    new BlurViewPackage()  // Aggiungere questa riga
  );
}
```

### 4. Test Backend

Verificare che gli endpoint funzionino:

```bash
# Test recupero notifiche bloccanti
curl -X GET "http://localhost/api/notifications/blocking" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test conferma lettura
curl -X POST "http://localhost/api/notifications/1/confirm-read" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🔧 Configurazione

### Personalizzazione Polling

Modificare in `AppNavigator.js`:

```javascript
useBlockingNotifications({
  autoCheck: true,
  enablePolling: true,
  pollingInterval: 15000, // 15 secondi invece di 30
  checkOnForeground: true,
});
```

### Personalizzazione UI

Modificare gli stili in:

- `BlockingNotificationOverlay.js` - Overlay principale
- `NotificationModal.js` - Modal lettura

## 📝 Utilizzo

### Creazione Notifica Bloccante (Backend)

```php
$notification = new Notification();
$notification->recipient_user_id = $userId;
$notification->title = "Lettura Obbligatoria";
$notification->message = "Questo messaggio richiede conferma";
$notification->requires_read_confirmation = true; // ← Importante!
$notification->notification_type = Notification::TYPE_MANDATORY_READ;
$notification->save();
```

### Controllo Manuale (App)

```javascript
import useBlockingNotifications from '../hooks/useBlockingNotifications';

const {checkNotifications, isAppBlocked, notificationCount} =
  useBlockingNotifications();

// Controllo manuale
await checkNotifications();

// Stato attuale
console.log('App bloccata:', isAppBlocked);
console.log('Notifiche da leggere:', notificationCount);
```

## 🐛 Debugging

### Log da Monitorare

- **App:** Console del simulatore/device per log Redux e API
- **Backend:** `frontend/runtime/logs/app.log` per log endpoint notifiche

### Debug Commands

```bash
# Monitoraggio log backend
tail -f frontend/runtime/logs/app.log | grep -i notification

# Debug React Native
npx react-native log-ios    # iOS
npx react-native log-android # Android
```

## 🚀 Deployment

1. **Testare in sviluppo** con notifiche di prova
2. **Verificare performance** del polling su device reali
3. **Configurare intervalli** appropriati per produzione
4. **Testare scenari edge case:**
   - Perdita connessione durante conferma
   - App in background per lungo tempo
   - Multiple notifiche simultanee

## 💡 Miglioramenti Futuri

- [ ] Campo `viewed_at` nel database per tracking visualizzazioni
- [ ] Notifiche push per notifiche bloccanti urgenti
- [ ] Possibilità di rimandare lettura (con limite temporale)
- [ ] Analytics su tempo di lettura e interazione
- [ ] Supporto per attachment/media nelle notifiche

## 🔗 File Modificati/Creati

**Backend:**

- `api/controllers/NotificationController.php` (esteso)

**Frontend:**

- `tp/src/slices/uiSlice.js` (esteso)
- `tp/src/api/notifications.js` (esteso)
- `tp/src/services/blockingNotificationService.js` (nuovo)
- `tp/src/components/BlockingNotificationOverlay.js` (nuovo)
- `tp/src/components/NotificationModal.js` (nuovo)
- `tp/src/hooks/useBlockingNotifications.js` (nuovo)
- `tp/src/navigation/AppNavigator.js` (modificato)
- `tp/App.tsx` (modificato)

## ✅ Status

- [x] Backend API endpoints
- [x] Redux state management
- [x] Servizi e logica business
- [x] Componenti UI
- [x] Integrazione nell'app
- [x] Migration per campo `viewed_at`
- [x] Tracking visualizzazione notifiche
- [x] Endpoint verifica stato bloccante
- [x] Script di test automatizzato
- [x] Documentazione completa
- [ ] Test su device reali
- [ ] Installazione dipendenze native
