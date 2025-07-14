# Sistema di Cancellazione Appuntamenti - Documentazione

## Panoramica

Il sistema di cancellazione appuntamenti consente ai pazienti di cancellare i propri appuntamenti confermati attraverso un'interfaccia user-friendly con motivi predefiniti e note personalizzabili.

## Funzionalità

### 1. **Modale di Cancellazione**

La modale presenta:

- **Informazioni appuntamento**: Data, ora e dettagli dell'appuntamento da cancellare
- **Selezione motivo**: Lista di motivi predefiniti con radio buttons
- **Motivo personalizzato**: Campo di testo per "Altro" motivo
- **Note aggiuntive**: Campo opzionale per dettagli aggiuntivi
- **Validazione**: Controlli per garantire che sia selezionato un motivo

### 2. **Motivi di Cancellazione Predefiniti**

```javascript
const reasons = [
  'Malattia',
  'Impegno lavorativo',
  'Impegno familiare',
  'Problemi di trasporto',
  'Condizioni meteorologiche',
  'Emergenza personale',
  'Altro',
];
```

### 3. **Regole di Cancellazione**

- ✅ Solo appuntamenti con status `'confermato'` possono essere cancellati
- ✅ Solo appuntamenti futuri possono essere cancellati
- ✅ Motivo obbligatorio
- ✅ Note opzionali
- ✅ Conferma richiesta prima della cancellazione

## Implementazione Backend

### Endpoint API

**POST** `/api/calendar/cancel-appointment`

**Body:**

```json
{
  "appointment_id": 123,
  "reason": "Malattia",
  "notes": "Febbre alta"
}
```

**Response Success:**

```json
{
  "success": true,
  "message": "Appuntamento cancellato con successo",
  "data": {
    "appointment_id": 123,
    "new_status": "assente_giustificato",
    "reason": "Malattia",
    "notes": "Febbre alta",
    "cancelled_at": "2024-01-15 10:30:00"
  }
}
```

### Logica Backend

1. **Validazione Parametri**

   - `appointment_id` obbligatorio
   - `reason` obbligatorio
   - `notes` opzionale

2. **Controlli di Sicurezza**

   - Appuntamento deve esistere
   - Status deve essere `'scheduled'`
   - Data deve essere futura

3. **Aggiornamento Database**

   - Status → `'absent_justified'`
   - Aggiunta note di cancellazione
   - Nessun record di assenza separato - il cambio di stato è sufficiente

4. **Notifiche Automatiche**

   - **Notifica al Manager**: Informa della cancellazione con dettagli completi
   - **Notifica al Terapista**: Avvisa il terapista dell'appuntamento cancellato
   - Include: paziente, data/ora, motivo, note aggiuntive

5. **Transazione Database**
   - Rollback automatico in caso di errore
   - Logging completo delle operazioni
   - Notifiche inviate dopo salvataggio riuscito

## Implementazione Frontend

### Componenti Utilizzati

```javascript
// React Native Paper
import {
  Dialog,
  RadioButton,
  TextInput,
  Button,
  Divider,
  ActivityIndicator,
} from 'react-native-paper';
```

### Stato del Componente

```javascript
const [cancelDialog, setCancelDialog] = useState({
  visible: false,
  appointment: null,
});

const [cancellationForm, setCancellationForm] = useState({
  reason: '',
  customReason: '',
  notes: '',
});

const [isSubmittingCancellation, setIsSubmittingCancellation] = useState(false);
```

### Flusso di Cancellazione

1. **Apertura Modale**

   ```javascript
   const handleCancelAppointment = appointment => {
     setCancelDialog({visible: true, appointment});
     // Reset form
     setCancellationForm({reason: '', customReason: '', notes: ''});
   };
   ```

2. **Validazione Form**

   ```javascript
   // Motivo obbligatorio
   if (!cancellationForm.reason) {
     Alert.alert('Errore', 'Seleziona un motivo per la cancellazione');
     return;
   }

   // Motivo personalizzato se "Altro"
   if (
     cancellationForm.reason === 'Altro' &&
     !cancellationForm.customReason.trim()
   ) {
     Alert.alert('Errore', 'Specifica il motivo personalizzato');
     return;
   }
   ```

3. **Chiamata API**

   ```javascript
   const response = await cancelPatientAppointment(
     appointment.id,
     finalReason,
     cancellationForm.notes.trim(),
   );
   ```

4. **Gestione Risposta**
   - Success: Alert di conferma + reload dati
   - Error: Alert di errore specifico

## Gestione Errori

### Tipi di Errore

- **AUTH_ERROR**: Errore di autenticazione → Logout automatico
- **PERMISSION_ERROR**: Errore di permessi → Alert senza logout
- **CANCELLATION_ERROR**: Errore di cancellazione → Alert specifico
- **NETWORK_ERROR**: Errore di rete → Alert di connessione

### Error Handling

```javascript
try {
  const response = await cancelPatientAppointment(id, reason, notes);
  // Handle success
} catch (error) {
  if (error.type === 'AUTH_ERROR') {
    // Interceptor handles logout
  } else if (error.type === 'CANCELLATION_ERROR') {
    Alert.alert('Errore Cancellazione', error.message);
  } else {
    Alert.alert('Errore', "Impossibile cancellare l'appuntamento");
  }
}
```

## Sistema di Notifiche

### Notifiche Automatiche

Quando un paziente cancella un appuntamento, il sistema invia automaticamente:

#### Notifica al Manager

```
Titolo: "Appuntamento Cancellato dal Paziente"
Messaggio: "Il paziente [Nome Paziente] ha cancellato l'appuntamento del [Data] alle [Ora] con il terapista [Nome Terapista].

Motivo: [Motivo selezionato]
Note: [Note aggiuntive se presenti]"

Dati aggiuntivi:
- appointment_id, patient_id, therapist_id
- cancellation_reason, appointment_date, appointment_time
- type: 'appointment_cancellation'
```

#### Notifica al Terapista

```
Titolo: "Appuntamento Cancellato"
Messaggio: "Il tuo appuntamento con [Nome Paziente] del [Data] alle [Ora] è stato cancellato dal paziente.

Motivo: [Motivo selezionato]
Note: [Note aggiuntive se presenti]"

Dati aggiuntivi:
- appointment_id, patient_id, patient_name
- cancellation_reason, appointment_date, appointment_time
- type: 'appointment_cancellation'
```

### Gestione Errori Notifiche

- Le notifiche sono inviate **dopo** il salvataggio riuscito dell'appuntamento
- Eventuali errori nell'invio notifiche **non bloccano** l'operazione di cancellazione
- Errori notifiche vengono loggati ma non mostrati all'utente
- Garantisce che la cancellazione avvenga sempre, anche se le notifiche falliscono

## Sicurezza

### Validazioni Backend

- ✅ Autenticazione JWT obbligatoria
- ✅ Verifica esistenza appuntamento
- ✅ Controllo status appuntamento
- ✅ Verifica data futura
- ✅ Sanitizzazione input

### Validazioni Frontend

- ✅ Motivo obbligatorio
- ✅ Motivo personalizzato se "Altro"
- ✅ Controllo data futura (UI)
- ✅ Disabilitazione pulsanti durante invio

## Database

### Tabella Appointments

```sql
UPDATE appointments SET
  status = 'absent_justified',
  notes = CONCAT(IFNULL(notes, ''), '\n\nCANCELLATO DAL PAZIENTE - Motivo: {reason}')
WHERE id = {appointment_id};
```

### Tabella Absences (opzionale)

```sql
INSERT INTO absences (
  appointment_id,
  patient_id,
  absence_date,
  reason,
  notes,
  is_justified,
  is_communicated,
  communicated_by,
  communicated_at,
  absence_type
) VALUES (
  {appointment_id},
  {patient_id},
  {appointment_datetime},
  {reason},
  {notes},
  1,
  1,
  {user_id},
  NOW(),
  'patient_cancellation'
);
```

## Testing

### Test Cases

1. **Cancellazione Normale**

   - Appuntamento futuro confermato
   - Motivo selezionato
   - Note opzionali

2. **Validazione Form**

   - Motivo non selezionato
   - "Altro" senza motivo personalizzato
   - Campi vuoti

3. **Controlli Backend**

   - Appuntamento inesistente
   - Appuntamento già cancellato
   - Appuntamento passato

4. **Gestione Errori**
   - Errore di rete
   - Errore di autenticazione
   - Errore del server

### Test Manuale

```bash
# Test endpoint
curl -X POST http://localhost/TherapyCRM/api/calendar/cancel-appointment \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"appointment_id": 123, "reason": "Malattia", "notes": "Test"}'
```

## Logging

### Backend Logging

```php
// Success
Yii::info("Appuntamento {$appointmentId} cancellato dal paziente. Motivo: {$reason}", __METHOD__);

// Error
Yii::error('Errore cancellazione appuntamento: ' . $e->getMessage(), __METHOD__);
```

### Frontend Logging

```javascript
// Error
console.error('Errore cancellazione appuntamento:', error);

// Success
console.log('Appuntamento cancellato con successo');
```

## Miglioramenti Futuri

### Possibili Estensioni

1. **Notifiche Push**: Notifica al terapista della cancellazione
2. **Riprogrammazione**: Suggerimento slot alternativi
3. **Storico Cancellazioni**: Tracciamento pattern di cancellazione
4. **Motivi Personalizzati**: Gestione motivi specifici per struttura
5. **Tempo Limite**: Limite di tempo per cancellazioni (es. 24h prima)

### Ottimizzazioni

1. **Caching**: Cache dei motivi di cancellazione
2. **Offline Support**: Supporto per cancellazioni offline
3. **Batch Operations**: Cancellazione multipla
4. **Analytics**: Statistiche sui motivi di cancellazione

## Conclusione

Il sistema di cancellazione appuntamenti fornisce un'interfaccia completa e sicura per la gestione delle cancellazioni da parte dei pazienti, con validazioni robuste sia lato client che server, gestione errori completa e logging dettagliato per il monitoraggio e debug.
