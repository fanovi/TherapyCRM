# Sistema di Notifiche ai Pazienti

## Panoramica

Il sistema di notifiche ai pazienti permette di inviare notifiche di tipo info a tutti gli account collegati ai pazienti selezionati nella vista `/patient/index`.

## Funzionalità

### 1. Selezione Pazienti
- **Checkbox individuali**: Ogni paziente ha una checkbox per la selezione
- **Seleziona tutto**: Checkbox master per selezionare/deselezionare tutti i pazienti
- **Contatore dinamico**: Mostra il numero di pazienti selezionati
- **Barra azioni**: Appare quando almeno un paziente è selezionato

### 2. Invio Notifiche
- **Modal interattivo**: Interfaccia pulita per comporre la notifica
- **Validazione**: Controlli client-side e server-side
- **Loading states**: Feedback visivo durante l'invio
- **Gestione errori**: Messaggi di errore chiari e informativi

### 3. Logica di Business
- Le notifiche vengono inviate a **tutti gli account collegati** ai pazienti selezionati
- Vengono considerati solo gli account attivi (`status = 'active'`)
- Ogni notifica è di tipo `info` per default
- Il mittente è l'utente correntemente autenticato

## Struttura File

### Backend (PHP)
```
frontend/controllers/PatientController.php
├── actionSendNotification()        # Action per l'invio notifiche
└── getUsersLinkedToPatients()      # Helper per ottenere utenti collegati

common/models/Patient.php
├── getLinkedUsers()                # Relazione con gli utenti
├── getLinkedUserIds()              # IDs degli utenti collegati
└── getLinkedUsersForPatients()     # Utenti di più pazienti (statico)

common/components/NotificationService.php
└── sendNotification()              # Servizio per l'invio notifiche
```

### Frontend (JavaScript/CSS)
```
frontend/web/js/patient-notifications.js  # Logica JavaScript
frontend/views/patient/index.php          # Vista con interfaccia
```

## API Endpoint

### POST `/patient/send-notification`

**Parametri:**
- `patient_ids[]`: Array di ID pazienti
- `title`: Titolo della notifica (max 100 caratteri)
- `message`: Messaggio della notifica (max 500 caratteri)
- `_csrf`: Token CSRF

**Risposta Successo:**
```json
{
    "success": true,
    "message": "Notifica inviata con successo a X account collegati ai pazienti selezionati.",
    "details": {
        "patients_count": 3,
        "accounts_notified": 5,
        "errors": []
    }
}
```

**Risposta Errore:**
```json
{
    "success": false,
    "error": "Messaggio di errore descrittivo"
}
```

## Permessi Richiesti

- `create_patient`: Permesso necessario per accedere alla funzionalità di notifiche

## Utilizzo

### 1. Accesso alla Funzionalità
- Navigare alla pagina `/patient/index`
- Verificare di avere i permessi necessari
- Selezionare uno o più pazienti tramite le checkbox

### 2. Invio Notifica
- Cliccare sul pulsante "Invia Notifica" nella barra delle azioni
- Compilare il form nel modal:
  - **Titolo**: Breve descrizione della notifica
  - **Messaggio**: Contenuto dettagliato della notifica
- Cliccare "Invia Notifica" per confermare

### 3. Verifica Risultato
- Messaggio di successo: La notifica è stata inviata correttamente
- Messaggio di errore: Verificare i dati inseriti e riprovare

## Tecnologie Utilizzate

### Backend
- **Yii2 Framework**: Struttura MVC e gestione componenti
- **NotificationService**: Servizio dedicato per l'invio notifiche
- **OneSignal**: Sistema di push notification (se configurato)
- **MySQL**: Database per memorizzazione notifiche

### Frontend
- **Alpine.js**: Reattività e gestione stato del modal
- **Tailwind CSS**: Styling e componenti UI
- **jQuery**: Manipolazione DOM e chiamate AJAX
- **Vanilla JavaScript**: Logica di business lato client

## Flusso di Lavoro

1. **Selezione**: L'utente seleziona i pazienti nella tabella
2. **Validazione Client**: JavaScript verifica che ci siano pazienti selezionati
3. **Modal**: Viene mostrato il form per comporre la notifica
4. **Validazione**: Controlli client-side sui campi obbligatori
5. **Invio AJAX**: Chiamata POST all'endpoint del server
6. **Elaborazione Server**: 
   - Validazione parametri
   - Ricerca utenti collegati ai pazienti
   - Invio tramite NotificationService
   - Response JSON con risultato
7. **Feedback**: Messaggio di successo/errore nel modal
8. **Reset**: Pulizia selezioni e chiusura modal

## Possibili Miglioramenti

### Funzionalità Aggiuntive
- **Template predefiniti**: Notifiche con contenuto standardizzato
- **Programmazione**: Invio notifiche a data/ora specifica
- **Filtri avanzati**: Selezione pazienti per criteri specifici
- **Anteprima**: Preview della notifica prima dell'invio
- **Statistiche**: Report su notifiche inviate

### Ottimizzazioni Tecniche
- **Cache**: Memorizzazione temporanea utenti collegati
- **Batch processing**: Gestione di grandi volumi di notifiche
- **Queue system**: Invio asincrono per performance migliori
- **Retry logic**: Nuovo tentativo in caso di errori temporanei

## Troubleshooting

### Problemi Comuni

**Errore: "Nessun account collegato ai pazienti selezionati"**
- Verificare che i pazienti abbiano account attivi collegati
- Controllare la tabella `account_patients` nel database

**Errore: "Errore di comunicazione con il server"**
- Verificare la connessione internet
- Controllare i log del server in `frontend/runtime/logs/app.log`
- Verificare che il token CSRF sia valido

**Modal non si apre**
- Verificare che Alpine.js sia caricato correttamente
- Controllare la console browser per errori JavaScript
- Verificare che il file `patient-notifications.js` sia incluso

### Log e Debug

**Log Server:**
```bash
tail -f frontend/runtime/logs/app.log
```

**Debug JavaScript:**
```javascript
// Aprire la console browser e verificare
console.log(window.patientNotifications);
```

**Debug Database:**
```sql
-- Verificare account collegati a un paziente
SELECT ap.*, u.username, u.status 
FROM account_patients ap 
JOIN users u ON u.id = ap.user_id 
WHERE ap.patient_id = [PATIENT_ID];
```

## Sicurezza

### Controlli Implementati
- **Autenticazione**: Solo utenti autenticati possono accedere
- **Autorizzazione**: Controllo permessi RBAC
- **CSRF Protection**: Token anti-forgery nelle richieste POST
- **Input Validation**: Sanitizzazione e validazione parametri
- **SQL Injection**: Uso di prepared statements
- **XSS Protection**: Escape di output HTML

### Best Practices
- Non esporre informazioni sensibili nei log
- Validare sempre i dati sia client-side che server-side
- Limitare la lunghezza dei messaggi per prevenire spam
- Monitorare i tentativi di accesso non autorizzati 