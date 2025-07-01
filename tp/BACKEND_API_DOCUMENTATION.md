# API Documentation - Sistema Richieste Pazienti

## Panoramica

Documentazione tecnica per l'implementazione degli endpoint backend necessari per il sistema di richieste documenti dei pazienti nell'app mobile TherapyCRM.

## Base URL

```
https://your-domain.com/api
```

## Autenticazione

Tutti gli endpoint richiedono autenticazione Bearer Token:

```
Authorization: Bearer {jwt_token}
```

---

## 📋 Endpoint Richiesti

### 1. GET /requests/types

**Descrizione:** Recupera le tipologie di richieste disponibili per i pazienti

**Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Response JSON:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Certificato Medico",
      "description": "Richiesta certificato medico per assenza lavorativa",
      "icon": "file-document-outline",
      "category": "medical",
      "estimated_days": 3,
      "requires_reason": true,
      "requires_date_range": true,
      "is_active": true
    },
    {
      "id": 2,
      "name": "Relazione Terapeutica",
      "description": "Richiesta relazione dettagliata sui progressi terapeutici",
      "icon": "chart-line",
      "category": "therapy",
      "estimated_days": 5,
      "requires_reason": true,
      "requires_date_range": false,
      "is_active": true
    }
  ]
}
```

**Campi Spiegati:**

- `icon`: Nome icona Material Design (per UI)
- `category`: Categoria per colori UI (medical, therapy, fitness, appointment)
- `estimated_days`: Giorni lavorativi stimati per completamento
- `requires_reason`: Se true, il motivo è obbligatorio nel form
- `requires_date_range`: Se true, date inizio/fine sono obbligatorie

---

### 2. POST /requests

**Descrizione:** Crea una nuova richiesta documento

**Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
  "request_type_id": 1,
  "patient_id": 1,
  "reason": "Certificato per assenza lavorativa dal 15/01 al 20/01",
  "notes": "Note aggiuntive opzionali",
  "date_from": "2025-01-15",
  "date_to": "2025-01-20"
}
```

**Campi Obbligatori:**
- `request_type_id`: ID della tipologia di richiesta
- `patient_id`: ID del paziente per cui fare la richiesta

**Campi Opzionali:**
- `notes`: Sempre opzionale
- `date_from`, `date_to`: Obbligatori solo se `requires_date_range = true`
- `reason`: Obbligatorio solo se `requires_reason = true`

**Response JSON (Successo):**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "request_type": "Certificato Medico",
    "status": "pending",
    "created_at": "2025-01-25T10:30:00Z",
    "estimated_completion": "2025-01-28T18:00:00Z",
    "reason": "Certificato per assenza lavorativa dal 15/01 al 20/01",
    "notes": "Note aggiuntive opzionali",
    "date_from": "2025-01-15",
    "date_to": "2025-01-20"
  },
  "message": "Richiesta creata con successo! Riceverai una notifica quando sarà pronta."
}
```

---

### 3. GET /requests

**Descrizione:** Recupera le richieste dell'utente autenticato

**Headers:**

```
Authorization: Bearer {token}
```

**Query Parameters (Opzionali):**

```
?status=pending          // Filtra per stato
&page=1                  // Paginazione
&limit=20                // Elementi per pagina
```

**Stati Possibili:**

- `pending`: In attesa di elaborazione
- `in_progress`: In elaborazione
- `completed`: Completata
- `rejected`: Rifiutata
- `cancelled`: Annullata

**Response JSON:**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "request_type": "Certificato Medico",
      "status": "in_progress",
      "created_at": "2025-01-20T10:30:00Z",
      "updated_at": "2025-01-22T14:20:00Z",
      "estimated_completion": "2025-01-25T18:00:00Z",
      "completed_at": null,
      "reason": "Certificato per assenza lavorativa",
      "notes": "Richiesta urgente",
      "date_from": "2025-01-15",
      "date_to": "2025-01-20",
      "download_url": null
    },
    {
      "id": 124,
      "request_type": "Relazione Terapeutica",
      "status": "completed",
      "created_at": "2025-01-15T14:20:00Z",
      "updated_at": "2025-01-18T16:45:00Z",
      "estimated_completion": "2025-01-20T18:00:00Z",
      "completed_at": "2025-01-18T16:45:00Z",
      "reason": "Relazione per visita specialistica",
      "notes": null,
      "date_from": null,
      "date_to": null,
      "download_url": "https://your-domain.com/api/requests/124/download"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "limit": 20,
    "total_pages": 1
  }
}
```

---

### 4. GET /requests/{id}

**Descrizione:** Recupera dettagli di una richiesta specifica

**Headers:**

```
Authorization: Bearer {token}
```

**Response JSON:**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "request_type": "Certificato Medico",
    "status": "completed",
    "created_at": "2025-01-20T10:30:00Z",
    "updated_at": "2025-01-23T16:45:00Z",
    "estimated_completion": "2025-01-25T18:00:00Z",
    "completed_at": "2025-01-23T16:45:00Z",
    "reason": "Certificato per assenza lavorativa dal 15/01 al 20/01",
    "notes": "Richiesta urgente per datore di lavoro",
    "date_from": "2025-01-15",
    "date_to": "2025-01-20",
    "download_url": "https://your-domain.com/api/requests/123/download",
    "type_info": {
      "id": 1,
      "name": "Certificato Medico",
      "category": "medical",
      "estimated_days": 3
    }
  }
}
```

---

### 5. POST /requests/{id}/cancel

**Descrizione:** Annulla una richiesta (solo se status = "pending")

**Headers:**

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**

```json
{
  "reason": "Non più necessario"
}
```

**Response JSON (Successo):**

```json
{
  "success": true,
  "message": "Richiesta annullata con successo",
  "data": {
    "id": 123,
    "status": "cancelled",
    "cancelled_at": "2025-01-25T10:30:00Z",
    "cancellation_reason": "Non più necessario"
  }
}
```

**Response JSON (Errore - Non Annullabile):**

```json
{
  "success": false,
  "error": "Impossibile annullare una richiesta in stato 'in_progress'",
  "code": "REQUEST_NOT_CANCELLABLE"
}
```

---

### 6. GET /requests/{id}/download

**Descrizione:** Scarica il documento completato (solo se status = "completed")

**Headers:**

```
Authorization: Bearer {token}
```

**Response:** File PDF/DOC come stream binario

**Headers Response:**

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="certificato_medico_123.pdf"
Content-Length: 245760
```

**Response JSON (Errore - Non Disponibile):**

```json
{
  "success": false,
  "error": "Documento non ancora disponibile per il download",
  "code": "DOCUMENT_NOT_READY"
}
```

---

## 🗄️ Schema Database Suggerito

### Tabella: request_types

```sql
CREATE TABLE request_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    category VARCHAR(50),
    estimated_days INT DEFAULT 3,
    requires_reason BOOLEAN DEFAULT FALSE,
    requires_date_range BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabella: patient_requests

```sql
CREATE TABLE patient_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    request_type_id INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'rejected', 'cancelled') DEFAULT 'pending',
    reason TEXT,
    notes TEXT,
    date_from DATE NULL,
    date_to DATE NULL,
    document_path VARCHAR(500),
    estimated_completion DATETIME,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (request_type_id) REFERENCES request_types(id),

    INDEX idx_patient_status (patient_id, status),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
);
```

---

## 🔒 Controlli di Sicurezza

### Validazioni Richieste:

1. **Autenticazione:** Verificare JWT token valido
2. **Autorizzazione:** Utente può accedere solo alle proprie richieste
3. **Validazione Dati:**
   - `request_type_id` deve esistere e essere attivo
   - `patient_id` deve esistere
   - `reason` obbligatorio se `requires_reason = true`
   - `date_from/date_to` obbligatori se `requires_date_range = true`
   - `date_to` deve essere >= `date_from`

### Rate Limiting:

- Max 10 richieste create per paziente al giorno
- Max 100 chiamate API per paziente all'ora

---

## 📤 Gestione Errori Standard

### Formato Errore:

```json
{
  "success": false,
  "error": "Messaggio errore leggibile",
  "code": "ERROR_CODE",
  "details": {
    "field": "Dettaglio specifico errore"
  }
}
```

### Codici Errore Comuni:

- `INVALID_REQUEST_TYPE`: Tipologia richiesta non valida
- `MISSING_REQUIRED_FIELD`: Campo obbligatorio mancante
- `REQUEST_NOT_FOUND`: Richiesta non trovata
- `REQUEST_NOT_CANCELLABLE`: Richiesta non annullabile
- `DOCUMENT_NOT_READY`: Documento non pronto per download
- `RATE_LIMIT_EXCEEDED`: Limite richieste superato

---

## 🔔 Notifiche (Opzionale)

Se integrato con OneSignal, inviare notifiche push quando:

- Richiesta cambia stato (pending → in_progress → completed)
- Documento pronto per download
- Richiesta rifiutata (con motivo)

**Payload Notifica:**

```json
{
  "type": "request_status_change",
  "request_id": 123,
  "new_status": "completed",
  "title": "Documento Pronto",
  "message": "Il tuo Certificato Medico è pronto per il download"
}
```

---

## 🧪 Dati di Test

### Tipologie da Inserire:

```sql
INSERT INTO request_types (name, description, icon, category, estimated_days, requires_reason, requires_date_range) VALUES
('Certificato Medico', 'Richiesta certificato medico per assenza lavorativa', 'file-document-outline', 'medical', 3, true, true),
('Relazione Terapeutica', 'Richiesta relazione dettagliata sui progressi terapeutici', 'chart-line', 'therapy', 5, true, false),
('Copia Cartella Clinica', 'Richiesta copia della cartella clinica completa', 'folder-account', 'medical', 7, true, false),
('Certificato di Idoneità', 'Certificato di idoneità per attività sportiva/lavorativa', 'medal', 'fitness', 2, true, false),
('Referto Esami', 'Richiesta copia referto di esami specifici', 'test-tube', 'medical', 1, false, true),
('Cambio Appuntamento', 'Richiesta modifica o spostamento appuntamento esistente', 'calendar-edit', 'appointment', 1, true, false);
```

---

## 📞 Supporto Tecnico

Per domande sull'implementazione:

1. Verificare formato JSON esatto negli esempi
2. Testare con Postman/curl prima dell'integrazione
3. Implementare logging dettagliato per debug
4. Gestire timezone UTC per date/timestamp
