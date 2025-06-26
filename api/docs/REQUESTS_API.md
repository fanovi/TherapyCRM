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

## Gestione Timezone UTC

⚠️ **IMPORTANTE:** Tutti i timestamp nell'API sono gestiti in UTC per garantire consistenza globale.

### Configurazione
- **Timezone server:** UTC (configurato in `api/config/bootstrap.php`)
- **Formato timestamp:** ISO8601 con suffisso 'Z' (es: `2025-01-25T14:30:00Z`)
- **Validazione date:** Tutte le date sono validate in UTC
- **Confronti date:** Tutti i confronti sono timezone-safe

### Esempi Timestamp
```json
{
  "created_at": "2025-01-25T14:30:00Z",
  "estimated_completion": "2025-01-28T18:00:00Z"
}
```

### Conversione Timezone Client
Se la tua app ha bisogno di mostrare date nel timezone locale:

**JavaScript:**
```javascript
const utcDate = "2025-01-25T14:30:00Z";
const localDate = new Date(utcDate).toLocaleString();
```

**React Native:**
```javascript
import { format } from 'date-fns';
const utcDate = "2025-01-25T14:30:00Z";
const localDate = format(new Date(utcDate), 'dd/MM/yyyy HH:mm');
```

## Endpoint: GET /requests/{id}

### Descrizione
Recupera i dettagli completi di una singola richiesta specifica. L'utente deve avere accesso al paziente associato alla richiesta.

### URL
```
GET /api/requests/{id}
```

### Headers Richiesti
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Parametri Path

| Parametro | Tipo | Richiesto | Descrizione |
|-----------|------|-----------|-------------|
| `id` | integer | **Sì** | ID della richiesta da recuperare |

### Risposta di Successo (200)

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
    "type_info": {
      "id": 1,
      "name": "Certificato Medico",
      "category": "medical",
      "estimated_days": 3
    },
    "created_by": {
      "id": 1,
      "user_id": 4,
      "first_name": "Anna",
      "last_name": "Bianchi",
      "relationship_type": "parent"
    }
  }
}
```

### Campi Risposta Richiesta

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID univoco della richiesta |
| `request_type` | string | Nome della tipologia di richiesta |
| `status` | string | Stato attuale (pending, accepted, processing, ready, delivered, cancelled, rejected) |
| `created_at` | string | Data creazione in formato UTC ISO8601 |
| `updated_at` | string | Data ultimo aggiornamento in formato UTC ISO8601 |
| `estimated_completion` | string | Data stimata completamento in formato UTC ISO8601 |
| `completed_at` | string\|null | Data completamento effettivo (null se non completata) |
| `reason` | string\|null | Motivo della richiesta |
| `notes` | string\|null | Note aggiuntive |
| `date_from` | string\|null | Data inizio periodo (formato YYYY-MM-DD) |
| `date_to` | string\|null | Data fine periodo (formato YYYY-MM-DD) |
| `type_info` | object | Informazioni dettagliate sul tipo di richiesta |
| `created_by` | object\|null | Informazioni su chi ha creato la richiesta |

### Campi type_info

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID del tipo di richiesta |
| `name` | string | Nome del tipo di richiesta |
| `category` | string | Categoria (medical, therapy, fitness, appointment) |
| `estimated_days` | integer | Giorni lavorativi stimati per completamento |

### Campi created_by

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID dell'AccountPatient |
| `user_id` | integer | ID dell'utente che ha fatto la richiesta |
| `first_name` | string | Nome del richiedente |
| `last_name` | string | Cognome del richiedente |
| `relationship_type` | string | Tipo di relazione (self, parent, guardian, caregiver) |

### Errori Specifici

#### 404 Not Found - Richiesta non trovata
```json
{
  "success": false,
  "error": "Richiesta non trovata",
  "code": "NOT_FOUND"
}
```

#### 403 Forbidden - Accesso negato
```json
{
  "success": false,
  "error": "Non hai i permessi per accedere a questa richiesta. Non hai i permessi per accedere alle richieste del paziente ID: 2. Pazienti accessibili: ID 1: Giulia Bianchi",
  "code": "ACCESS_DENIED"
}
```

#### 400 Bad Request - ID non valido
```json
{
  "success": false,
  "error": "ID richiesta non valido",
  "code": "MISSING_REQUIRED_FIELD",
  "details": {
    "id": "L'ID deve essere un numero intero positivo"
  }
}
```

## Endpoint: GET /requests

### Descrizione
Recupera l'elenco delle richieste di un paziente specifico con paginazione e filtri opzionali.

### URL
```
GET /api/requests
```

### Headers Richiesti
```http
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Parametri Query

| Parametro | Tipo | Richiesto | Default | Descrizione |
|-----------|------|-----------|---------|-------------|
| `patient_id` | integer | **Sì** | - | ID del paziente per cui recuperare le richieste |
| `page` | integer | No | 1 | Numero pagina (minimo 1) |
| `limit` | integer | No | 20 | Elementi per pagina (massimo 100) |
| `status` | string | No | - | Filtro per status (pending, accepted, processing, ready, delivered, cancelled, rejected) |

### Risposta di Successo (200)

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
      "download_url": null,
      "created_by": {
        "id": 1,
        "user_id": 4,
        "first_name": "Anna",
        "last_name": "Bianchi",
        "relationship_type": "parent"
      }
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
      "download_url": "https://api.example.com/documents/124/download",
      "created_by": {
        "id": 1,
        "user_id": 4,
        "first_name": "Anna",
        "last_name": "Bianchi",
        "relationship_type": "parent"
      }
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 15,
    "total_pages": 1,
    "has_next_page": false,
    "has_prev_page": false,
    "status_filter": null,
    "patient_id": 1
  }
}
```

### Campi Risposta Richiesta

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID univoco della richiesta |
| `request_type` | string | Nome della tipologia di richiesta |
| `status` | string | Stato attuale (pending, accepted, processing, ready, delivered, cancelled, rejected) |
| `created_at` | string | Data creazione in formato UTC ISO8601 |
| `updated_at` | string | Data ultimo aggiornamento in formato UTC ISO8601 |
| `estimated_completion` | string | Data stimata completamento in formato UTC ISO8601 |
| `completed_at` | string\|null | Data completamento effettivo (null se non completata) |
| `reason` | string\|null | Motivo della richiesta |
| `notes` | string\|null | Note aggiuntive |
| `date_from` | string\|null | Data inizio periodo (formato YYYY-MM-DD) |
| `date_to` | string\|null | Data fine periodo (formato YYYY-MM-DD) |
| `download_url` | string\|null | URL per download documento (null se non pronto) |
| `created_by` | object\|null | Informazioni su chi ha creato la richiesta |

### Campi created_by

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | integer | ID dell'AccountPatient |
| `user_id` | integer | ID dell'utente che ha fatto la richiesta |
| `first_name` | string | Nome del richiedente |
| `last_name` | string | Cognome del richiedente |
| `relationship_type` | string | Tipo di relazione (self, parent, guardian, caregiver) |

### Metadati Paginazione

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `page` | integer | Pagina corrente |
| `limit` | integer | Elementi per pagina |
| `total` | integer | Numero totale di richieste |
| `total_pages` | integer | Numero totale di pagine |
| `has_next_page` | boolean | Se esiste una pagina successiva |
| `has_prev_page` | boolean | Se esiste una pagina precedente |
| `status_filter` | string\|null | Filtro status applicato |
| `patient_id` | integer | ID del paziente |

### Stati Richiesta

- **pending**: Richiesta inviata, in attesa di presa in carico
- **accepted**: Richiesta accettata, in lavorazione
- **processing**: Documento in preparazione
- **ready**: Documento pronto per il ritiro/download
- **delivered**: Documento consegnato al paziente
- **cancelled**: Richiesta annullata dal paziente
- **rejected**: Richiesta rifiutata dall'amministrazione

### Errori Specifici

#### 400 Bad Request - Parametri non validi
```json
{
  "success": false,
  "error": "Parametri di richiesta non validi",
  "code": "MISSING_REQUIRED_FIELD",
  "details": {
    "patient_id": "Il parametro patient_id è obbligatorio"
  }
}
```

#### 403 Forbidden - Accesso negato al paziente
```json
{
  "success": false,
  "error": "Non hai i permessi per accedere alle richieste del paziente ID: 999. Pazienti accessibili: ID 1: Giulia Bianchi",
  "code": "ACCESS_DENIED"
}
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

### Gestione Errori Standard

Tutti gli errori seguono il formato standardizzato:

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

#### Codici Errore Comuni:

- `INVALID_REQUEST_TYPE`: Tipologia richiesta non valida
- `MISSING_REQUIRED_FIELD`: Campo obbligatorio mancante
- `REQUEST_NOT_FOUND`: Richiesta non trovata
- `REQUEST_NOT_CANCELLABLE`: Richiesta non annullabile
- `DOCUMENT_NOT_READY`: Documento non pronto per download
- `RATE_LIMIT_EXCEEDED`: Limite richieste superato
- `UNAUTHORIZED`: Token mancante o non valido
- `ACCESS_DENIED`: Accesso negato
- `INTERNAL_ERROR`: Errore interno del server

#### 401 Unauthorized
```json
{
  "success": false,
  "error": "Il token di autenticazione non è stato fornito.",
  "code": "UNAUTHORIZED"
}
```

#### 403 Access Denied
```json
{
  "success": false,
  "error": "Accesso negato per questo tipo di utente",
  "code": "ACCESS_DENIED"
}
```

#### 500 Internal Server Error
```json
{
  "success": false,
  "error": "Errore interno del server",
  "code": "INTERNAL_ERROR"
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

### Response Errore (400) - Validazione
```json
{
  "success": false,
  "error": "Errori di validazione dei campi obbligatori",
  "code": "MISSING_REQUIRED_FIELD",
  "details": {
    "reason": "Il motivo è obbligatorio per la tipologia 'Certificato Medico'",
    "date_from": "La data di inizio è obbligatoria per la tipologia 'Certificato Medico'"
  }
}
```

### Response Errore (404) - Tipologia Non Trovata
```json
{
  "success": false,
  "error": "Tipologia di richiesta non valida o non attiva",
  "code": "INVALID_REQUEST_TYPE",
  "details": {
    "type_id": "Tipologia con ID 999 non trovata"
  }
}
```

### Response Errore (401) - Non Autorizzato
```json
{
  "success": false,
  "error": "Il token di autenticazione non è stato fornito.",
  "code": "UNAUTHORIZED"
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

### Esempi di Utilizzo per GET /requests/{id}

#### cURL - Recupera singola richiesta
```bash
# 1. Login per ottenere token
curl -X POST "http://localhost/TherapyCRM/api/auth/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=paziente@test.it&password=12345678"

# 2. Recupera richiesta specifica ID 123
curl -X GET "http://localhost/TherapyCRM/api/requests/123" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

#### JavaScript/Fetch API
```javascript
const fetchRequestDetail = async (requestId) => {
  try {
    const response = await fetch(`/api/requests/${requestId}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Richiesta:', data.data);
      console.log('Tipo:', data.data.type_info);
      console.log('Creata da:', data.data.created_by);
      return data.data;
    } else {
      throw new Error(data.error);
    }
  } catch (error) {
    console.error('Error fetching request:', error);
    throw error;
  }
};

// Utilizzo
fetchRequestDetail(123).then(request => {
  console.log(`Richiesta ${request.id}: ${request.request_type} - ${request.status}`);
});
```

#### React Native
```javascript
import { API_BASE_URL } from '../config/api';

const RequestDetailService = {
  async getRequestDetail(requestId) {
    try {
      const response = await fetch(`${API_BASE_URL}/requests/${requestId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (data.success) {
        return data.data;
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Error fetching request detail:', error);
      throw error;
    }
  }
};

// Utilizzo nel componente
const RequestDetailScreen = ({ route }) => {
  const { requestId } = route.params;
  const [request, setRequest] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const loadRequestDetail = async () => {
      try {
        setLoading(true);
        const requestData = await RequestDetailService.getRequestDetail(requestId);
        setRequest(requestData);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    loadRequestDetail();
  }, [requestId]);

  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorMessage message={error} />;

  return (
    <View style={styles.container}>
      <Text style={styles.title}>{request.request_type}</Text>
      <Text style={styles.status}>Status: {request.status}</Text>
      <Text style={styles.reason}>{request.reason}</Text>
      
      {request.type_info && (
        <View style={styles.typeInfo}>
          <Text>Categoria: {request.type_info.category}</Text>
          <Text>Giorni stimati: {request.type_info.estimated_days}</Text>
        </View>
      )}
      
      {request.created_by && (
        <View style={styles.createdBy}>
          <Text>Richiesta da: {request.created_by.first_name} {request.created_by.last_name}</Text>
          <Text>Relazione: {request.created_by.relationship_type}</Text>
        </View>
      )}
    </View>
  );
};
```

### Esempi di Utilizzo per GET /requests

#### cURL - Recupera tutte le richieste del paziente
```bash
# 1. Login per ottenere token
curl -X POST "http://localhost/TherapyCRM/api/auth/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=paziente@test.it&password=12345678"

# 2. Recupera richieste paziente ID 1
curl -X GET "http://localhost/TherapyCRM/api/requests?patient_id=1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

#### cURL - Con paginazione
```bash
curl -X GET "http://localhost/TherapyCRM/api/requests?patient_id=1&page=2&limit=10" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

#### cURL - Con filtro status
```bash
curl -X GET "http://localhost/TherapyCRM/api/requests?patient_id=1&status=pending" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

#### JavaScript/Fetch API
```javascript
const response = await fetch('/api/requests?patient_id=1&page=1&limit=20', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
if (data.success) {
  console.log('Richieste:', data.data);
  console.log('Totale:', data.meta.total);
  console.log('Pagine:', data.meta.total_pages);
}
```

#### React Native
```javascript
import { API_BASE_URL } from '../config/api';

const fetchPatientRequests = async (patientId, page = 1, status = null) => {
  try {
    let url = `${API_BASE_URL}/requests?patient_id=${patientId}&page=${page}`;
    if (status) {
      url += `&status=${status}`;
    }
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      return {
        requests: data.data,
        pagination: data.meta
      };
    } else {
      throw new Error(data.error);
    }
  } catch (error) {
    console.error('Error fetching requests:', error);
    throw error;
  }
};

// Utilizzo nel componente
const [requests, setRequests] = useState([]);
const [loading, setLoading] = useState(false);

useEffect(() => {
  const loadRequests = async () => {
    setLoading(true);
    try {
      const { requests, pagination } = await fetchPatientRequests(patientId);
      setRequests(requests);
      setPagination(pagination);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };
  
  loadRequests();
}, [patientId]);
```

### Test
Per testare gli endpoint, utilizzare gli script forniti:

```bash
# Test endpoint GET /requests/types
php api/test/test_requests_endpoint.php

# Test endpoint POST /requests
php api/test/test_create_request_endpoint.php

# Test endpoint GET /requests (lista paginata)
php api/test/test_get_patient_requests.php

# Test endpoint GET /requests/{id} (singola richiesta)
php api/test/test_get_single_request.php
```

Gli script effettuano automaticamente:
1. Login con credenziali di test
2. Chiamate agli endpoint con token valido
3. Test di tutti i parametri e scenari di errore
4. Visualizzazione formattata dei risultati

## Sicurezza

- ✅ Autenticazione JWT obbligatoria
- ✅ Validazione token con database
- ✅ Logging degli accessi per audit
- ✅ Gestione CORS per app mobile
- ✅ Sanitizzazione delle risposte
- ✅ Rate limiting (gestito a livello infrastruttura)

L'endpoint è pronto per l'uso in produzione con dati dinamici dal database. Il modello `RequestType` è implementato e funzionante. 