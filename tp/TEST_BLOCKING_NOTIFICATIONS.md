# 🧪 Guida Test Notifiche Bloccanti

## 📋 Setup Iniziale

### 1. Applicare le Migrations

```bash
# Naviga nella root del progetto
cd /Applications/XAMPP/xamppfiles/htdocs/TherapyCRM

# Applica la migration per il campo viewed_at
./yii migrate
```

### 2. Installare Dipendenze App Mobile

```bash
cd tp/
npm install @react-native-community/blur

# Se iOS
cd ios/ && pod install && cd ..
```

### 3. Avviare il Sistema

```bash
# Avvia XAMPP (Apache + MySQL)
# Avvia l'app React Native
npx react-native run-ios
# o
npx react-native run-android
```

## 🎯 Test Scenario 1: Creazione e Test Notifica Bloccante

### Step 1: Accedi all'App

- Fai login nell'app mobile
- Verifica che la navigazione sia normale

### Step 2: Crea Notifica Bloccante via API

```bash
# Ottieni il token di autenticazione dall'app (dai log o AsyncStorage)
# Sostituisci YOUR_TOKEN con il token reale

curl -X POST "http://localhost/TherapyCRM/api/notifications/create-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "type": "blocking",
    "title": "🚨 Lettura Obbligatoria Test",
    "message": "Questa è una notifica di test che deve essere confermata per sbloccare l'\''app. Contiene informazioni importanti che richiedono la tua attenzione immediata."
  }'
```

**Risultato atteso:**

```json
{
  "success": true,
  "message": "Notifica di test blocking creata con successo",
  "data": {
    "id": 123,
    "type": "blocking",
    "title": "🚨 Lettura Obbligatoria Test",
    "requires_read_confirmation": true,
    "recipient_user_id": 456,
    "created_at": "2024-01-01 12:00:00"
  }
}
```

### Step 3: Verifica Blocco App

**Entro 30 secondi** (polling automatico), l'app dovrebbe:

- ✅ Mostrare l'overlay bloccante
- ✅ Mostrare la notifica con contenuto blurrato
- ✅ Impedire navigazione normale
- ✅ Mostrare conteggio notifiche (badge rosso)

### Step 4: Test Funzionalità Overlay

1. **Test Logout**: Prova il tasto "Esci dall'App" → dovrebbe funzionare
2. **Test Click Notifica**: Clicca sulla notifica blurrata

**Risultato atteso:**

- ✅ API call a `/notifications/{id}/mark-viewed`
- ✅ Apertura modal con contenuto completo
- ✅ Log nel backend: "Notifica {id} visualizzata dall'utente..."

### Step 5: Test Modal di Lettura

1. **Leggi il contenuto** completo nella modal
2. **Test "Chiudi senza Confermare"** → torna all'overlay (app ancora bloccata)
3. **Riapri la notifica** e clicca **"Conferma Lettura"**

**Risultato atteso:**

- ✅ API call a `/notifications/{id}/confirm-read`
- ✅ Modal si chiude
- ✅ Overlay scompare
- ✅ App sbloccata e navigabile
- ✅ Alert "Lettura Confermata"

## 🔄 Test Scenario 2: Multiple Notifiche Bloccanti

### Step 1: Crea 3 Notifiche Bloccanti

```bash
# Notifica 1
curl -X POST "http://localhost/TherapyCRM/api/notifications/create-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"type": "blocking", "title": "Notifica 1/3", "message": "Prima notifica importante"}'

# Notifica 2
curl -X POST "http://localhost/TherapyCRM/api/notifications/create-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"type": "blocking", "title": "Notifica 2/3", "message": "Seconda notifica importante"}'

# Notifica 3
curl -X POST "http://localhost/TherapyCRM/api/notifications/create-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"type": "blocking", "title": "Notifica 3/3", "message": "Terza notifica importante"}'
```

### Step 2: Verifica Comportamento

- ✅ Badge mostra "3"
- ✅ Overlay mostra tutte e 3 le notifiche
- ✅ Ogni notifica è cliccabile individualmente

### Step 3: Conferma Progressiva

1. **Conferma notifica 1** → Badge diventa "2", overlay rimane
2. **Conferma notifica 2** → Badge diventa "1", overlay rimane
3. **Conferma notifica 3** → Badge scompare, app sbloccata

## 🔍 Test Scenario 3: Verifica Tracking

### Controlla Log Backend

```bash
tail -f frontend/runtime/logs/app.log | grep -i notification
```

**Log attesi:**

```
[info] Notifica 123 visualizzata dall'utente 456 alle 2024-01-01 12:05:30
[info] Notifica bloccante 123 confermata come letta dall'utente 456
[info] Utente 456 ha 0 notifiche bloccanti
```

### Verifica Database

```sql
SELECT
    id,
    title,
    requires_read_confirmation,
    viewed_at,
    read_at,
    created_at
FROM notifications
WHERE recipient_user_id = YOUR_USER_ID
ORDER BY created_at DESC
LIMIT 5;
```

## 🕒 Test Scenario 4: Polling e Foreground

### Step 1: Test Polling

1. Crea notifica bloccante via API
2. **Aspetta massimo 30 secondi** → app dovrebbe bloccarsi automaticamente

### Step 2: Test App in Background

1. Con notifica bloccante attiva, metti l'app in background
2. Conferma la notifica via web/altro dispositivo:

```bash
curl -X POST "http://localhost/TherapyCRM/api/notifications/{ID}/confirm-read" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

3. **Riporta l'app in foreground** → dovrebbe sbloccarsi automaticamente

## 🔧 Comandi Utility per Testing

### Verifica Stato Notifiche

```bash
# Controlla se ci sono notifiche bloccanti
curl -X GET "http://localhost/TherapyCRM/api/notifications/has-blocking" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Lista Notifiche Bloccanti

```bash
curl -X GET "http://localhost/TherapyCRM/api/notifications/blocking" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Crea Notifica Normale (non bloccante)

```bash
curl -X POST "http://localhost/TherapyCRM/api/notifications/create-test" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"type": "normal", "title": "Test Normale", "message": "Questa non blocca l'\''app"}'
```

## 🐛 Troubleshooting

### App Non Si Blocca

1. **Controlla console React Native** per errori API
2. **Verifica token di autenticazione** non scaduto
3. **Controlla polling** sia attivo: cerca log "🔄 Avvio polling notifiche bloccanti"
4. **Verifica endpoint** `/notifications/blocking` funzioni

### Overlay Non Appare

1. **Controlla Redux DevTools** per stato `ui.isAppBlocked`
2. **Verifica import** di `BlockingNotificationOverlay` in `AppNavigator`
3. **Controlla z-index** dell'overlay (dovrebbe essere 9999)

### API Non Funziona

1. **Verifica server** Yii2 attivo
2. **Controlla route** API in `api/config/main.php`
3. **Verifica log backend** per errori PHP

### Database Issues

```bash
# Verifica tabella esista
mysql -u root -p -e "DESCRIBE therapycrm.notifications;"

# Verifica campo viewed_at
mysql -u root -p -e "SHOW COLUMNS FROM therapycrm.notifications LIKE 'viewed_at';"
```

## ✅ Checklist Test Completi

- [ ] Migration applicata con successo
- [ ] Dipendenze React Native installate
- [ ] Notifica bloccante singola funziona
- [ ] Multiple notifiche bloccanti funzionano
- [ ] Tracking viewed_at funziona
- [ ] Polling automatico funziona
- [ ] App in background/foreground funziona
- [ ] Logout funziona con notifiche attive
- [ ] Log backend registrano correttamente
- [ ] Performance accettabile su device

## 📱 Test su Device Reali

Dopo test su simulatore, ripeti su:

- [ ] iPhone fisico
- [ ] Android fisico
- [ ] Connessione lenta/instabile
- [ ] App backgrounded per lunghi periodi

## 🚀 Test in Produzione

Prima del deploy:

- [ ] Disabilita endpoint `/create-test` in produzione
- [ ] Testa con notifiche reali dal sistema
- [ ] Verifica performance polling in produzione
- [ ] Testa con multiple utenti simultanei
