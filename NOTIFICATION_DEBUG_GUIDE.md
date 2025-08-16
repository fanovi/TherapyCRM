# 🔍 Guida Debug Sistema Notifiche - TherapyCRM

## 🚨 Problema Identificato

Il sistema delle notifiche non mostra la lista all'utente perché **mancavano le route nell'API**.

## ✅ Soluzioni Implementate

### 1. **Route API Mancanti** ✅ RISOLTO

**Problema**: Le route per `/notifications` e `/notifications/unread` non erano configurate in `api/config/main.php`

**Soluzione**: Aggiunte tutte le route mancanti:

```php
'rules' => [
    'GET notifications' => 'notification/index',
    'GET notifications/unread' => 'notification/unread',
    'GET notifications/has-blocking' => 'notification/has-blocking',
    'GET notifications/blocking' => 'notification/blocking',
    'POST notifications/<id:\d+>/mark-read' => 'notification/mark-read',
    'POST notifications/<id:\d+>/confirm-read' => 'notification/confirm-read',
    'POST notifications/<id:\d+>/mark-viewed' => 'notification/mark-viewed',
    'POST notifications/send' => 'notification/send',
    'POST notifications/send-template' => 'notification/send-template',
    'POST notifications/broadcast' => 'notification/broadcast',
    'POST notifications/create-test' => 'notification/create-test',
],
```

### 2. **Logging Migliorato** ✅ IMPLEMENTATO

**Problema**: Gli errori venivano catturati ma non loggati correttamente

**Soluzione**: Aggiunto logging dettagliato in:

- `tp/src/components/NotificationBadge.js`
- `tp/src/screens/patient/PatientNotificationsScreen.js`

## 🧪 Test del Sistema

### Test 1: Verifica Route API

```bash
# Test senza autenticazione (deve restituire 401)
curl -X GET "https://heavily-wealthy-buzzard.ngrok-free.app/TherapyCRM/api/notifications/unread" \
     -H "Content-Type: application/json" \
     -H "ngrok-skip-browser-warning: true"

# Risposta attesa: {"name":"Unauthorized","message":"Il token di autenticazione non è stato fornito.","code":0,"status":401,"type":"yii\\web\\UnauthorizedHttpException"}
```

### Test 2: Verifica Database

```sql
-- Controlla notifiche esistenti
USE therapist_cgm;
SELECT id, recipient_user_id, title, notification_type, read_at, created_at
FROM notifications
ORDER BY created_at DESC
LIMIT 10;

-- Conta notifiche per utente
SELECT recipient_user_id, COUNT(*) as total,
       SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread
FROM notifications
GROUP BY recipient_user_id;
```

### Test 3: Verifica App Mobile

Con il logging migliorato, ora puoi vedere negli log dell'app:

```
🔔 NotificationBadge: Recupero conteggio notifiche...
🔔 NotificationBadge: Risposta ricevuta: {success: true, data: {...}}
🔔 NotificationBadge: Impostando conteggio a: 2

📱 PatientNotifications: Recupero notifiche pagina 1, refresh: true
📱 PatientNotifications: Risposta ricevuta: {success: true, data: {...}}
📱 PatientNotifications: 5 notifiche ricevute
📱 PatientNotifications: 5 notifiche dopo filtro 'all'
```

## 📊 Stato Database Attuale

Dalla verifica del database:

- **6 notifiche totali** nel sistema
- **Utente 23**: 4 notifiche (1 non letta)
- **Utente 24**: 1 notifica (1 non letta)

## 🔧 Prossimi Passi

1. **Testa l'app mobile** dopo le modifiche
2. **Verifica i log** per confermare che le API vengono chiamate correttamente
3. **Controlla l'autenticazione** se ancora non funziona

## 🚀 Comandi Utili

```bash
# Monitora i log dell'API
tail -f api/runtime/logs/app.log | grep -i notification

# Controlla le route dell'API
./yii help

# Pulisci cache se necessario
./yii cache/flush-all
```

## 📋 Checklist Debug

- [x] Route API configurate
- [x] Controller NotificationController esistente e funzionante
- [x] Modello Notification corretto
- [x] NotificationService implementato
- [x] Logging migliorato nell'app mobile
- [ ] Test con app mobile reale
- [ ] Verifica autenticazione JWT
- [ ] Test completo del flusso

## 🎯 Risultato Atteso

Dopo queste modifiche, l'app mobile dovrebbe:

1. **Mostrare il badge** con il numero corretto di notifiche non lette
2. **Mostrare la lista** delle notifiche quando si clicca sul badge
3. **Loggare dettagliatamente** eventuali errori per debug futuro

Se il problema persiste, controlla i log dell'app mobile per vedere dove si interrompe il flusso.
