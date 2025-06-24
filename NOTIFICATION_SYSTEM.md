# Sistema di Notifiche ai Pazienti

## Panoramica

Il sistema di notifiche ai pazienti permette di inviare notifiche di tipo info a tutti gli account collegati ai pazienti selezionati nella vista `/patient/index`.

## Funzionalità Implementate

### 1. Selezione Pazienti
- **Checkbox individuali**: Ogni paziente ha una checkbox per la selezione
- **Seleziona tutto**: Checkbox master per selezionare/deselezionare tutti i pazienti
- **Contatore dinamico**: Mostra il numero di pazienti selezionati
- **Barra azioni**: Appare quando almeno un paziente è selezionato

### 2. Invio Notifiche
- **Modal interattivo**: Interfaccia pulita per comporre la notifica (Alpine.js)
- **Validazione**: Controlli client-side e server-side
- **Loading states**: Feedback visivo durante l'invio
- **Gestione errori**: Messaggi di errore chiari e informativi

### 3. Logica di Business
- Le notifiche vengono inviate a **tutti gli account collegati** ai pazienti selezionati
- Vengono considerati solo gli account attivi (`status = 'active'`)
- Ogni notifica è di tipo `info` per default
- Il mittente è l'utente correntemente autenticato

## File Modificati/Creati

### Backend
1. **`frontend/controllers/PatientController.php`**
   - Aggiunta action `actionSendNotification()`
   - Aggiunto helper `getUsersLinkedToPatients()`

2. **`common/models/Patient.php`**
   - Aggiunto metodo `getLinkedUsers()`
   - Aggiunto metodo `getLinkedUserIds()`
   - Aggiunto metodo statico `getLinkedUsersForPatients()`

### Frontend
3. **`frontend/views/patient/index.php`**
   - Aggiunta colonna checkbox per selezione pazienti
   - Aggiunta barra azioni con contatore e pulsante invio
   - Aggiunto modal Alpine.js per comporre notifiche
   - Registrazione del file JavaScript

4. **`frontend/web/js/patient-notifications.js`** (NUOVO)
   - Classe JavaScript per gestire la logica delle notifiche
   - Integrazione con Alpine.js per il modal
   - Gestione eventi, validazione e chiamate AJAX

## API Endpoint

### POST `/patient/send-notification`

**Parametri richiesti:**
- `patient_ids[]`: Array di ID pazienti
- `title`: Titolo della notifica (max 100 caratteri)
- `message`: Messaggio della notifica (max 500 caratteri)

**Risposta Successo:**
```json
{
    "success": true,
    "message": "Notifica inviata con successo a X account.",
    "details": {
        "patients_count": 3,
        "accounts_notified": 5,
        "errors": []
    }
}
```

## Utilizzo

1. **Accedere alla pagina `/patient/index`**
2. **Selezionare uno o più pazienti** tramite le checkbox
3. **Cliccare "Invia Notifica"** nella barra delle azioni che appare
4. **Compilare il form** nel modal con titolo e messaggio
5. **Confermare l'invio** - le notifiche verranno inviate a tutti gli account collegati

## Permessi Richiesti

- `create_patient`: Necessario per accedere alla funzionalità

## Tecnologie Utilizzate

- **Backend**: Yii2, NotificationService, MySQL
- **Frontend**: Alpine.js, Tailwind CSS, jQuery, JavaScript ES6+
- **Notifiche**: Sistema esistente con OneSignal integration

## Note di Sicurezza

- Controllo permessi RBAC
- Protezione CSRF
- Validazione input client e server
- Solo account attivi ricevono notifiche
- Logging delle operazioni per audit 