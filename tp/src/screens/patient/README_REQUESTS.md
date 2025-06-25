# Sistema di Richieste Pazienti

## Panoramica

Il sistema di richieste permette ai pazienti di creare richieste per documenti e certificati direttamente dall'app mobile. Il sistema è stato progettato per essere flessibile e facilmente estendibile.

## Struttura dei File

### API Layer

- **`src/api/requests.js`**: Servizio API per gestire tutte le operazioni relative alle richieste
  - Simulazione dati per sviluppo (da sostituire con endpoint reali)
  - Funzioni per CRUD completo delle richieste
  - Utilities per formattazione stati e colori

### Screen Components

- **`PatientRequestsScreen.js`**: Schermata principale con lista richieste e statistiche
- **`CreateRequestScreen.js`**: Form per creare nuove richieste con validazione dinamica
- **`RequestDetailsScreen.js`**: Vista dettagliata di una richiesta specifica

### Navigation

- **`PatientNavigator.js`**: Aggiornato per includere la tab "Richieste"

## Funzionalità Implementate

### 1. Gestione Tipologie Richieste

- Caricamento dinamico delle tipologie disponibili
- Ogni tipologia ha configurazioni specifiche:
  - `requires_reason`: Se richiede un motivo obbligatorio
  - `requires_date_range`: Se richiede un periodo di riferimento
  - `estimated_days`: Giorni stimati per completamento
  - `category`: Categoria per colore e icona

### 2. Form Dinamico per Creazione

- Validazione in tempo reale
- Campi condizionali basati sulla tipologia selezionata
- Riepilogo prima dell'invio

### 3. Stati delle Richieste

- **pending**: In attesa di elaborazione
- **in_progress**: In elaborazione
- **completed**: Completata e scaricabile
- **rejected**: Rifiutata
- **cancelled**: Annullata

### 4. UI/UX Features

- Filtri per stato delle richieste
- Statistiche visuali con contatori
- Pull-to-refresh
- Loading states e error handling
- Timeline di avanzamento
- Azioni contestuali (scarica, annulla, dettagli)

## Simulazione Dati

### Tipologie Richieste Simulate

1. **Certificato Medico** - Richiede motivo e date
2. **Relazione Terapeutica** - Richiede motivo
3. **Copia Cartella Clinica** - Richiede motivo
4. **Certificato di Idoneità** - Richiede motivo
5. **Referto Esami** - Richiede date
6. **Cambio Appuntamento** - Richiede motivo

### Richieste Esempio

- Una richiesta in elaborazione (Certificato Medico)
- Una richiesta completata (Relazione Terapeutica)

## TODO per Implementazione Backend

### 1. Endpoint da Implementare

```php
// Backend endpoints necessari
GET    /api/requests/types           // Tipologie disponibili
POST   /api/requests                 // Crea richiesta
GET    /api/requests                 // Lista richieste utente
GET    /api/requests/{id}            // Dettagli richiesta
POST   /api/requests/{id}/cancel     // Annulla richiesta
GET    /api/requests/{id}/download   // Scarica documento
```

### 2. Database Schema

```sql
-- Tabelle necessarie
CREATE TABLE request_types (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    icon VARCHAR(50),
    category VARCHAR(50),
    estimated_days INT,
    requires_reason BOOLEAN,
    requires_date_range BOOLEAN
);

CREATE TABLE patient_requests (
    id INT PRIMARY KEY,
    patient_id INT,
    request_type_id INT,
    status ENUM('pending', 'in_progress', 'completed', 'rejected', 'cancelled'),
    reason TEXT,
    notes TEXT,
    date_from DATE,
    date_to DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    completed_at TIMESTAMP,
    document_path VARCHAR(255)
);
```

### 3. Sostituzioni nel Codice

Nel file `src/api/requests.js`, sostituire i commenti `// TODO:` con chiamate reali:

```javascript
// Esempio per getRequestTypes
export const getRequestTypes = async () => {
  try {
    const response = await apiClient.get('/requests/types');
    return response.data;
  } catch (error) {
    throw error;
  }
};
```

## Estensioni Future

### 1. Date Picker Nativo

Installare e configurare `react-native-date-picker`:

```bash
npm install react-native-date-picker
cd ios && pod install  # Solo per iOS
```

### 2. Download Documenti

Implementare download reale con:

- `react-native-fs` per salvataggio file
- `react-native-share` per condivisione
- Gestione permessi per storage

### 3. Notifiche Push

Integrare con OneSignal per notificare:

- Cambio stato richiesta
- Documento pronto per download
- Promemoria per richieste scadenti

### 4. Filtri Avanzati

- Filtro per data
- Filtro per tipologia
- Ricerca testuale
- Ordinamento personalizzabile

### 5. Statistiche Avanzate

- Grafici di utilizzo
- Tempi medi di elaborazione
- Report mensili/annuali

## Note Tecniche

### Dipendenze Utilizzate

- `react-native-paper`: Componenti UI Material Design
- `@react-navigation`: Navigazione tra schermate
- `react-redux`: Gestione stato globale
- `axios`: Client HTTP per API

### Compatibilità

- ✅ Android
- ✅ iOS
- ✅ React Native >= 0.70
- ✅ React Native Paper >= 5.0

### Performance

- Lazy loading delle immagini
- Paginazione per liste lunghe
- Cache delle tipologie richieste
- Debouncing per ricerca

## Supporto

Per domande o problemi con l'implementazione:

1. Controllare i log console per errori API
2. Verificare che gli endpoint backend siano configurati
3. Testare prima con dati simulati
4. Controllare permessi OneSignal per notifiche
