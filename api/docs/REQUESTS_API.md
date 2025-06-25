# API Richieste Pazienti

Documentazione completa per gli endpoint API relativi alle richieste dei pazienti nel sistema TherapyCRM.

## Autenticazione

Tutti gli endpoint richiedono autenticazione Bearer Token JWT ottenuto tramite login:

```http
Authorization: Bearer {jwt_token}
```

Per ottenere un token JWT valido, effettuare prima il login:

```http
POST /api/auth/login
Content-Type: application/x-www-form-urlencoded

email=paziente@example.com&password=password123
```

## Endpoint: GET /requests/types

### Descrizione
Recupera l'elenco delle tipologie di richieste attive dal database per i pazienti autenticati.

### URL
```
GET /api/requests/types
```

### Headers Richiesti
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Parametri
Nessun parametro richiesto.

### Risposta di Successo (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Certificato Medico",
      "description": "Richiesta certificato medico per assenza lavorativa",
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
      "category": "therapy",
      "estimated_days": 5,
      "requires_reason": true,
      "requires_date_range": false,
      "is_active": true
    }
  ],
  "meta": {
    "total": 8,
    "categories": ["appointment", "fitness", "medical", "therapy"]
  }
}
```

### Campi di Risposta

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID univoco della tipologia |
| `name` | string | Nome della tipologia di richiesta |
| `description` | string | Descrizione dettagliata |
| `category` | string | Categoria per organizzazione UI (`medical`, `therapy`, `fitness`, `appointment`) |
| `estimated_days` | integer | Giorni lavorativi stimati per completamento |
| `requires_reason` | boolean | Se true, il motivo è obbligatorio nel form |
| `requires_date_range` | boolean | Se true, date inizio/fine sono obbligatorie |
| `is_active` | boolean | Se la tipologia è attualmente disponibile |

### Metadati Risposta

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `meta.total` | integer | Numero totale di tipologie attive |
| `meta.categories` | array | Lista delle categorie disponibili |

### Categorie Disponibili

- **medical**: Certificati medici, cartelle cliniche, valutazioni
- **therapy**: Relazioni terapeutiche, programmi riabilitativi, esercizi
- **fitness**: Certificati idoneità fisica, valutazioni sportive
- **appointment**: Richieste appuntamenti urgenti, modifiche programmazione

### Risposte di Errore

#### 401 Unauthorized
```json
{
  "success": false,
  "message": "Il token di autenticazione non è stato fornito.",
  "error_code": "UNAUTHORIZED"
}
```

#### 403 Access Denied
```json
{
  "success": false,
  "message": "Accesso negato per questo tipo di utente",
  "error_code": "ACCESS_DENIED"
}
```

#### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Errore interno del server",
  "error_code": "INTERNAL_ERROR"
}
```

## Esempi di Utilizzo

### cURL
```bash
# 1. Login per ottenere token
curl -X POST "http://localhost/TherapyCRM/api/auth/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=paziente1@example.com&password=password123"

# 2. Chiamata endpoint con token
curl -X GET "http://localhost/TherapyCRM/api/requests/types" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

### JavaScript (Fetch API)
```javascript
// Assume di avere già un token JWT valido
const token = 'your_jwt_token_here';

fetch('/api/requests/types', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  }
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Tipologie disponibili:', data.data);
    console.log('Totale:', data.meta.total);
    console.log('Categorie:', data.meta.categories);
  } else {
    console.error('Errore:', data.message);
  }
});
```

### React Native (con axios)
```javascript
import axios from 'axios';

const getRequestTypes = async (token) => {
  try {
    const response = await axios.get('/api/requests/types', {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });
    
    if (response.data.success) {
      return response.data.data; // Array delle tipologie
    } else {
      throw new Error(response.data.message);
    }
  } catch (error) {
    console.error('Errore nel recupero tipologie:', error);
    throw error;
  }
};
```

## Note per lo Sviluppo

### Stato Attuale
- ✅ Endpoint implementato con dati dal database (tabella `request_types`)
- ✅ Autenticazione JWT Bearer Token
- ✅ Gestione errori e logging
- ✅ Fallback per database vuoto con array vuoto
- ✅ Documentazione OpenAPI/Swagger
- ✅ Categorizzazione per UI
- ✅ Metadati per organizzazione frontend

## Endpoint: POST /requests

### Descrizione
Crea una nuova richiesta di documento per il paziente autenticato. La validazione è dinamica basata sui requisiti della tipologia selezionata.

### URL
```
POST /api/requests
```

### Headers Richiesti
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Request Body
```json
{
  "type_id": 1,
  "reason": "Certificato per assenza lavorativa dal 15/01 al 20/01",
  "notes": "Note aggiuntive opzionali",
  "date_from": "2025-01-15",
  "date_to": "2025-01-20"
}
```

### Parametri

| Campo | Tipo | Obbligatorio | Descrizione |
|-------|------|-------------|-------------|
| `type_id` | integer | ✅ Sempre | ID della tipologia di richiesta |
| `reason` | string (max 1000) | ⚠️ Condizionale | Motivo della richiesta (obbligatorio se `requires_reason = true`) |
| `notes` | string (max 2000) | ❌ Opzionale | Note aggiuntive |
| `date_from` | string (YYYY-MM-DD) | ⚠️ Condizionale | Data di inizio (obbligatoria se `requires_date_range = true`) |
| `date_to` | string (YYYY-MM-DD) | ⚠️ Condizionale | Data di fine (obbligatoria se `requires_date_range = true`) |

### Response Successo (201)
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
    "date_to": "2025-01-20",
    "created_by": {
      "id": 789,
      "user_id": 456,
      "first_name": "Mario",
      "last_name": "Rossi",
      "relationship_type": "parent"
    }
  },
  "message": "Richiesta creata con successo! Riceverai una notifica quando sarà pronta."
}
```

### Campi di Risposta

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID univoco della richiesta |
| `request_type` | string | Nome della tipologia di richiesta |
| `status` | string | Stato della richiesta (`pending`, `processing`, `completed`) |
| `created_at` | string (ISO 8601) | Data e ora di creazione |
| `estimated_completion` | string (ISO 8601) | Data stimata di completamento |
| `reason` | string\|null | Motivo della richiesta |
| `notes` | string\|null | Note aggiuntive |
| `date_from` | string\|null | Data di inizio (formato YYYY-MM-DD) |
| `date_to` | string\|null | Data di fine (formato YYYY-MM-DD) |
| `created_by` | object | Dati dell'account che ha creato la richiesta |

### Oggetto created_by

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID dell'AccountPatient |
| `user_id` | integer | ID dell'utente che ha fatto la richiesta |
| `first_name` | string | Nome dell'utente |
| `last_name` | string | Cognome dell'utente |
| `relationship_type` | string | Tipo di relazione con il paziente (`self`, `parent`, `tutor`, `other`) |

### Response Errore (400)
```json
{
  "success": false,
  "message": "Requisiti della tipologia non soddisfatti",
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "reason": ["Il motivo è obbligatorio per la tipologia 'Certificato Medico'"],
    "date_from": ["La data di inizio è obbligatoria per la tipologia 'Certificato Medico'"]
  }
}
```

### Response Errore (404)
```json
{
  "success": false,
  "message": "Tipologia di richiesta non trovata o non attiva",
  "error_code": "TYPE_NOT_FOUND"
}
```

### Esempi Pratici

#### Certificato Medico (completo)
```bash
curl -X POST "http://localhost/TherapyCRM/api/requests" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type_id": 1,
    "reason": "Certificato per assenza lavorativa",
    "date_from": "2025-01-15",
    "date_to": "2025-01-20",
    "notes": "Richiesta urgente"
  }'
```

#### Certificato Idoneità Fisica (minimale)
```bash
curl -X POST "http://localhost/TherapyCRM/api/requests" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type_id": 4,
    "notes": "Per iscrizione palestra"
  }'
```

### Validazione Dinamica

La validazione dei campi dipende dalla tipologia selezionata:

```javascript
// Esempi di validazione basata su tipologia
if (requestType.requires_reason) {
    // Campo 'reason' è obbligatorio
}

if (requestType.requires_date_range) {
    // Campi 'date_from' e 'date_to' sono obbligatori
    // date_to >= date_from
}
```

### Test Completo
Per testare l'endpoint con tutti gli scenari:

```bash
php api/test/test_create_request_endpoint.php
```

### Sviluppi Futuri
- 🔄 Implementazione modello database `DocumentRequest`
- ✅ Implementazione modello database `RequestType` (COMPLETATO)
- ✅ Query dinamiche al database (COMPLETATO)
- 🔄 Filtri per categoria e stato
- 🔄 Sistema di upload allegati per richieste
- 🔄 Permessi RBAC specifici per tipo utente
- 🔄 Localizzazione nomi e descrizioni
- 🔄 Configurazione tempi stimati personalizzabili

### Integrazione con App Mobile
L'endpoint è progettato per integrarsi perfettamente con l'app React Native esistente:

1. **Categorizzazione**: Le categorie permettono di organizzare le richieste in sezioni UI
2. **Metadati di Form**: I campi `requires_reason` e `requires_date_range` guidano la costruzione dinamica dei form
3. **Tempi Stimati**: `estimated_days` può essere mostrato all'utente per gestire le aspettative
4. **Filtraggio**: Solo le tipologie attive (`is_active: true`) vengono restituite

### Test
Per testare l'endpoint, utilizzare lo script fornito:

```bash
php api/test/test_requests_endpoint.php
```

Lo script effettua automaticamente:
1. Login con credenziali di test
2. Chiamata all'endpoint con token valido
3. Visualizzazione formattata dei risultati

## Sicurezza

- ✅ Autenticazione JWT obbligatoria
- ✅ Validazione token con database
- ✅ Logging degli accessi per audit
- ✅ Gestione CORS per app mobile
- ✅ Sanitizzazione delle risposte
- ✅ Rate limiting (gestito a livello infrastruttura)

L'endpoint è pronto per l'uso in produzione con dati dinamici dal database. Il modello `RequestType` è implementato e funzionante. 