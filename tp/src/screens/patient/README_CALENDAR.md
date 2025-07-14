# Sistema Calendario Pazienti

## Panoramica

Il sistema calendario permette ai pazienti di visualizzare i propri appuntamenti attraverso un'interfaccia calendario intuitiva. Il sistema è stato progettato per funzionare con endpoint API POST e include tutte le funzionalità necessarie per la gestione degli appuntamenti.

## Struttura dei File

### API Layer

- **`src/api/calendar.js`**: Servizio API per gestire tutte le operazioni relative al calendario
  - `getPatientAppointments(patientId, date)`: Recupera appuntamenti per una data specifica
  - `getPatientMarkedDates(patientId, month)`: Recupera giorni con appuntamenti per evidenziarli
  - Utilities per formattazione stati, colori e validazioni

### Screen Components

- **`PatientCalendarScreen.js`**: Schermata principale con calendario e lista appuntamenti
  - Calendario interattivo con `react-native-calendars`
  - Lista appuntamenti con `FlatList` per performance ottimali
  - Pull-to-refresh e gestione stati di caricamento
  - Dialog per conferma cancellazione appuntamenti

### Backend API

- **`api/controllers/CalendarController.php`**: Controller con endpoint dedicati ai pazienti
  - `POST /api/calendar/patient-appointments`: Appuntamenti per data specifica
  - `POST /api/calendar/patient-marked-dates`: Date con appuntamenti per evidenziazione

## Funzionalità Implementate

### 1. Visualizzazione Calendario

- Calendario mensile con evidenziazione giorni con appuntamenti
- Navigazione tra mesi con caricamento automatico delle date marcate
- Selezione data per visualizzare appuntamenti specifici
- Tema personalizzabile e responsive

### 2. Lista Appuntamenti

- **FlatList** per performance ottimali con molti appuntamenti
- Visualizzazione dettagliata per ogni appuntamento:
  - Orario e durata
  - Stato con colori distintivi
  - Tipo di terapia
  - Informazioni terapista (opzionali)
  - Note e ubicazione
- Azioni disponibili: chiamata, cancellazione (se permessa)

### 3. Stati degli Appuntamenti

- **confermato**: Appuntamento programmato (blu)
- **completato**: Appuntamento concluso (verde)
- **annullato**: Appuntamento cancellato (rosso)
- **assente_giustificato**: Assenza giustificata (arancione)
- **assente_non_giustificato**: Assenza non giustificata (rosso)

### 4. Gestione Errori e Stati

- Loading states durante le chiamate API
- Gestione errori con messaggi utente-friendly
- Pull-to-refresh per aggiornamento manuale
- Stato vuoto quando non ci sono appuntamenti
- Validazione presenza paziente selezionato

## Struttura Dati

### Appuntamento (dal backend)

```javascript
{
  id: 123,
  date: "2024-01-15",
  time: "10:30",
  datetime: "2024-01-15 10:30:00",
  duration_minutes: 60,
  status: "confermato",
  type: "Fisioterapia",
  appointment_type: "terapia",
  treatment_code: "FISIO",
  notes: "Note opzionali",
  location: "Centro Terapeutico",
  therapist: {
    id: 456,
    name: "Dr. Mario Rossi",
    first_name: "Mario",
    last_name: "Rossi",
    specialization: "Fisioterapista",
    avatar: "https://..."
  },
  patient: {
    name: "Luca Bianchi",
    first_name: "Luca",
    last_name: "Bianchi"
  }
}
```

### Date Marcate (per calendario)

```javascript
{
  "2024-01-15": {
    marked: true,
    dotColor: "#007AFF",
    activeOpacity: 0.5,
    appointment_count: 2,
    customStyles: {
      container: {
        backgroundColor: "#E3F2FD",
        borderRadius: 8
      },
      text: {
        color: "#1976D2",
        fontWeight: "bold"
      }
    }
  }
}
```

## Endpoint API

### 1. Recupero Appuntamenti

```http
POST /api/calendar/patient-appointments
Content-Type: application/json
Authorization: Bearer {token}

{
  "patient_id": 123,
  "date": "2024-01-15"
}
```

**Risposta:**

```json
{
  "success": true,
  "data": [...appuntamenti...],
  "meta": {
    "patient_id": 123,
    "date": "2024-01-15",
    "count": 2
  }
}
```

### 2. Date Marcate

```http
POST /api/calendar/patient-marked-dates
Content-Type: application/json
Authorization: Bearer {token}

{
  "patient_id": 123,
  "month": "2024-01"
}
```

**Risposta:**

```json
{
  "success": true,
  "data": {...date_marcate...},
  "meta": {
    "patient_id": 123,
    "month": "2024-01",
    "total_days_with_appointments": 8
  }
}
```

## Autenticazione e Sicurezza

### JWT Authentication

- Tutti gli endpoint richiedono token JWT valido
- Il token viene passato nell'header `Authorization: Bearer {token}`
- Autenticazione gestita automaticamente da `apiClient`

### Validazione Parametri

- **patient_id**: Obbligatorio, deve essere numerico
- **date**: Formato YYYY-MM-DD, validato lato server
- **month**: Formato YYYY-MM, validato lato server

### Gestione Errori

- Errori di rete gestiti con retry automatico
- Errori di validazione mostrati all'utente
- Logging dettagliato per debugging

## Performance e Ottimizzazioni

### 1. FlatList

- Sostituisce il ciclo `map()` per performance migliori
- Rendering lazy degli elementi
- Supporto pull-to-refresh nativo

### 2. Caching Intelligente

- Le date marcate vengono caricate solo quando necessario
- Riutilizzo dati quando possibile
- Gestione stati di caricamento ottimizzata

### 3. Gestione Memoria

- Componenti ottimizzati per evitare memory leaks
- Cleanup automatico degli event listeners
- Gestione corretta degli stati asincroni

## Personalizzazione Tema

### Colori Stati

```javascript
const statusColors = {
  confermato: '#007AFF', // Blu iOS
  completato: '#34C759', // Verde iOS
  annullato: '#FF3B30', // Rosso iOS
  assente_giustificato: '#FF9500', // Arancione iOS
  assente_non_giustificato: '#FF3B30', // Rosso iOS
};
```

### Tema Calendario

- Colori adattivi al tema dell'app
- Supporto dark/light mode
- Personalizzazione font e dimensioni

## Estensioni Future

### 1. Notifiche Push

- Promemoria appuntamenti
- Conferme e cancellazioni
- Aggiornamenti in tempo reale

### 2. Sincronizzazione Calendario

- Esportazione in calendario nativo
- Sincronizzazione bidirezionale
- Supporto calendar provider esterni

### 3. Funzionalità Avanzate

- Riprogrammazione appuntamenti
- Chat con terapista
- Documenti allegati
- Valutazioni post-appuntamento

## Debugging e Logging

### Console Logging

Tutti i servizi API includono logging dettagliato:

```javascript
console.log('📅 Patient ID:', patientId);
console.log('📅 Date:', date);
console.log('📥 Response:', response.data);
```

### Error Handling

```javascript
try {
  const response = await getPatientAppointments(patientId, date);
  // Handle success
} catch (error) {
  console.error('❌ Errore:', error);
  Alert.alert('Errore', 'Impossibile caricare gli appuntamenti');
}
```

## Testing

### Unit Tests

- Test delle utility functions
- Validazione formattazione date
- Test logica cancellazione appuntamenti

### Integration Tests

- Test chiamate API
- Test navigazione calendario
- Test gestione errori

### E2E Tests

- Flusso completo visualizzazione appuntamenti
- Test interazione calendario
- Test pull-to-refresh

## Checklist Implementazione

- [x] Endpoint API backend
- [x] Servizio API frontend
- [x] Schermata calendario con FlatList
- [x] Gestione stati di caricamento
- [x] Pull-to-refresh
- [x] Gestione errori
- [x] Validazione parametri
- [x] Documentazione completa
- [ ] Unit tests
- [ ] Integration tests
- [ ] Ottimizzazioni performance avanzate
