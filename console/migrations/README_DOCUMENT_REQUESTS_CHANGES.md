# Modifiche alle Tabelle per Document Requests

## Migration: `m250630_143000_create_request_statuses_table.php`

### Nuova Tabella `request_statuses`

Tabella creata per gestire gli stati delle richieste di documenti, sostituendo l'ENUM nella tabella `document_requests`.

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | PRIMARY KEY | Chiave primaria |
| `name` | VARCHAR(100) | Nome dello stato |
| `display_order` | INT | Ordine di visualizzazione |
| `created_at` | TIMESTAMP | Data creazione |
| `updated_at` | TIMESTAMP | Data ultimo aggiornamento |

### Stati Predefiniti

| ID | Nome | Ordine |
|----|------|--------|
| 1 | Inviata | 1 |
| 2 | Presa in carico | 2 |
| 3 | Stampato | 3 |
| 4 | Consegnato | 4 |

### Indici Creati

- `idx_request_statuses_display_order` su `display_order`
- `idx_request_statuses_name` su `name` (UNIQUE)

---

## Migration: `m250630_143100_modify_document_requests_table.php`

### Modifiche alla Tabella `document_requests`

#### Campi Aggiunti

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `therapeutic_plan_id` | INT NULL | FK a `therapeutic_plans` (nullable) |
| `therapy_id` | INT NULL | FK a `plan_therapies` (nullable) |

#### Campi Modificati

| Campo | Prima | Dopo | Descrizione |
|-------|-------|------|-------------|
| `status` | ENUM(...) | INT NOT NULL | FK a `request_statuses` |
| `notes` | TEXT | VARCHAR(2000) | Note con limite di caratteri |

### Migrazione Dati Status

La migration include la migrazione automatica dei dati esistenti dal vecchio sistema ENUM al nuovo sistema FK:

```sql
-- Mapping degli stati esistenti
'pending' → 1 (Inviata)
'accepted' → 2 (Presa in carico)
'processing' → 2 (Presa in carico)
'ready' → 3 (Stampato)
'delivered' → 4 (Consegnato)
Altri stati → 1 (Inviata - default)
```

### Foreign Keys Aggiunte

| Nome FK | Tabella Source | Campo Source | Tabella Target | Campo Target | On Delete |
|---------|---------------|--------------|---------------|--------------|-----------|
| `fk-document_requests-therapeutic_plan_id` | document_requests | therapeutic_plan_id | therapeutic_plans | id | SET NULL |
| `fk-document_requests-therapy_id` | document_requests | therapy_id | plan_therapies | id | SET NULL |
| `fk-document_requests-status` | document_requests | status | request_statuses | id | RESTRICT |

### Indici Aggiunti per Performance

#### Indici Query Principali
- `idx_document_requests_account_patient` su `requested_by_account_patient_id`
- `idx_document_requests_patient_status` su `[patient_id, status]`
- `idx_document_requests_type_status` su `[request_type_id, status]`

#### Indici Aggiuntivi
- `idx_document_requests_status_created` su `[status, created_at]`
- `idx_document_requests_therapeutic_plan` su `therapeutic_plan_id`
- `idx_document_requests_therapy` su `therapy_id`
- `idx_document_requests_estimated_completion` su `estimated_completion`
- `idx_document_requests_patient_type_status` su `[patient_id, request_type_id, status]`

### Query Ottimizzate

Gli indici supportano ottimalmente queste query frequenti:

```sql
-- Query per account paziente (app mobile)
SELECT * FROM document_requests WHERE requested_by_account_patient_id = ?

-- Query per paziente con filtro stato
SELECT * FROM document_requests WHERE patient_id = ? AND status = ?

-- Query per tipo di richiesta con stato
SELECT * FROM document_requests WHERE request_type_id = ? AND status = ?

-- Dashboard con filtri multipli
SELECT * FROM document_requests 
WHERE patient_id = ? AND request_type_id = ? AND status = ?
```

## Struttura Finale `document_requests`

| Campo | Tipo | Nullable | FK | Descrizione |
|-------|------|----------|-----|-------------|
| `id` | INT | NO | - | Chiave primaria |
| `patient_id` | INT | NO | patients.id | Paziente della richiesta |
| `therapeutic_plan_id` | INT | YES | therapeutic_plans.id | Piano terapeutico associato |
| `therapy_id` | INT | YES | plan_therapies.id | Terapia specifica |
| `request_type_id` | INT | NO | request_types.id | Tipo di richiesta |
| `requested_by_account_patient_id` | INT | NO | account_patients.id | Account richiedente |
| `status` | INT | NO | request_statuses.id | Stato attuale |
| `reason` | TEXT | YES | - | Motivo della richiesta |
| `notes` | VARCHAR(2000) | YES | - | Note aggiuntive |
| `date_from` | DATE | YES | - | Data inizio periodo |
| `date_to` | DATE | YES | - | Data fine periodo |
| `estimated_completion` | DATETIME | NO | - | Completamento stimato |
| `completed_at` | DATETIME | YES | - | Data completamento |
| `delivered_at` | DATETIME | YES | - | Data consegna |
| `rejected_at` | DATETIME | YES | - | Data rifiuto |
| `rejection_reason` | TEXT | YES | - | Motivo rifiuto |
| `cancelled_at` | DATETIME | YES | - | Data cancellazione |
| `cancellation_reason` | TEXT | YES | - | Motivo cancellazione |
| `created_at` | TIMESTAMP | NO | - | Data creazione |
| `updated_at` | TIMESTAMP | NO | - | Data aggiornamento |

## Impatto su Modelli

### RequestStatus (Nuovo Modello)

Necessario creare un nuovo modello `RequestStatus` per gestire la tabella stati:

```php
class RequestStatus extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%request_statuses}}';
    }
    
    public function getDocumentRequests()
    {
        return $this->hasMany(DocumentRequest::class, ['status' => 'id']);
    }
}
```

### DocumentRequest (Modifiche)

Il modello `DocumentRequest` deve essere aggiornato per:

1. Aggiungere relazioni ai nuovi campi FK
2. Modificare il campo `status` da ENUM a FK
3. Aggiornare metodi di controllo stato

```php
// Nuove relazioni
public function getTherapeuticPlan()
{
    return $this->hasOne(TherapeuticPlan::class, ['id' => 'therapeutic_plan_id']);
}

public function getTherapy()
{
    return $this->hasOne(PlanTherapy::class, ['id' => 'therapy_id']);
}

public function getStatusRecord()
{
    return $this->hasOne(RequestStatus::class, ['id' => 'status']);
}
```

## Comandi per Applicare le Migration

```bash
# Applica le migration in ordine
./yii migrate

# In caso di rollback (ATTENZIONE: perderai i dati degli stati)
./yii migrate/down 2
```

## Testing

Dopo aver applicato le migration, testare:

1. **Migrazione dati**: Verificare che gli stati esistenti siano stati migrati correttamente
2. **Foreign keys**: Testare che le relazioni funzionino
3. **Performance**: Verificare che le query principali usino gli indici corretti
4. **API**: Aggiornare e testare gli endpoint che usano questi dati

```sql
-- Test migrazione stati
SELECT 
    dr.id,
    dr.status as status_id,
    rs.name as status_name,
    rs.display_order
FROM document_requests dr
JOIN request_statuses rs ON dr.status = rs.id
LIMIT 10;

-- Test performance query
EXPLAIN SELECT * FROM document_requests 
WHERE patient_id = 1 AND status = 2;
``` 