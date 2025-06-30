# Modifiche alla Tabella `request_types`

## Migration: `m250630_202122_modify_request_types_table.php`

### Nuova Struttura Tabella

La tabella `request_types` è stata completamente ridisegnata con la seguente struttura:

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | PRIMARY KEY | Chiave primaria |
| `name` | VARCHAR(255) | Nome del tipo di richiesta |
| `therapeutic_plan_rule` | INT | Regola per associazione piano terapeutico |
| `allow_multiple_requests` | TINYINT(1) | Permette richieste multiple simultanee |
| `require_therapy_assignment` | TINYINT(1) | Richiede assegnazione terapia |
| `require_notes` | TINYINT(1) | Richiede inserimento note |
| `is_active` | TINYINT(1) | Stato attivo del tipo (0=inattivo, 1=attivo) |
| `created_at` | TIMESTAMP | Data creazione |
| `updated_at` | TIMESTAMP | Data ultimo aggiornamento |

### Valori Campo `therapeutic_plan_rule`

- **1** (PLAN_OPTIONAL): Si può associare il piano terapeutico, ma non è obbligatorio
- **2** (PLAN_NOT_ALLOWED): Non si può associare il piano terapeutico  
- **3** (PLAN_REQUIRED): Si deve associare obbligatoriamente il piano terapeutico

### Valori Campi Boolean (TINYINT)

- **0**: False/No
- **1**: True/Sì

## Dati Iniziali Inseriti

| Nome | Piano Terap. | Multiple | Terapia | Note | Attivo |
|------|-------------|----------|---------|------|--------|
| Copia Piano Terapeutico | 3 (Obbligatorio) | 0 (No) | 0 (No) | 0 (No) | 1 (Sì) |
| Relazione terapista | 3 (Obbligatorio) | 0 (No) | 1 (Sì) | 0 (No) | 1 (Sì) |
| Relazione visita specialistica | 2 (Non associabile) | 0 (No) | 0 (No) | 0 (No) | 1 (Sì) |
| Attestato frequenza | 1 (Opzionale) | 0 (No) | 0 (No) | 0 (No) | 1 (Sì) |
| Altro | 2 (Non associabile) | 1 (Sì) | 0 (No) | 1 (Sì) | 1 (Sì) |

## Modifiche ai Modelli

### `RequestType.php`
- Completamente riscritto per la nuova struttura
- Aggiunge costanti per i valori enum
- Nuovi metodi helper per controlli boolean
- Metodo `getForApi()` aggiornato

### `RequestTypeQuery.php`  
- Rimossi metodi obsoleti (category, requiresReason, requiresDateRange, ecc.)
- Aggiunti nuovi metodi per filtraggi:
  - `active()` - filtra solo tipi attivi
  - `requiresTherapeuticPlan()`
  - `optionalTherapeuticPlan()`
  - `noTherapeuticPlan()`
  - `allowsMultiple()`
  - `requiresNotes()`
  - `requiresTherapy()`

## Metodi Principali RequestType

### Controlli Piano Terapeutico
```php
$type->isTherapeuticPlanRequired()    // È obbligatorio?
$type->isTherapeuticPlanOptional()    // È opzionale?
$type->isTherapeuticPlanNotAllowed()  // Non è associabile?
```

### Altri Controlli
```php
$type->allowsMultipleRequests()       // Permette richieste multiple?
$type->requiresTherapyAssignment()    // Richiede assegnazione terapia?
$type->requiresNotes()                // Richiede note?
$type->isActive()                     // È attivo?
```

### Utility
```php
RequestType::getForApi()              // Dati formattati per API
RequestType::getDropdownOptions()     // Opzioni per dropdown
RequestType::findById($id)            // Trova per ID
RequestType::findActive()             // Trova solo tipi attivi
RequestType::findActiveById($id)      // Trova tipo attivo per ID
```

## Test

Eseguire il test con:
```bash
php api/test/test_request_types_migration.php
```

Il test verifica:
1. ✅ Struttura tabella corretta (incluso campo is_active)
2. ✅ Dati inseriti correttamente (tutti attivi per default)
3. ✅ Metodi del modello funzionanti (incluso isActive())
4. ✅ Response API formattata (incluso campo is_active)
5. ✅ Query builder personalizzato (incluso active())
6. ✅ Opzioni dropdown

## Compatibilità

⚠️ **BREAKING CHANGES**: Questa migration elimina completamente la struttura precedente della tabella. 

I campi eliminati erano:
- `description`
- `category` 
- `estimated_days`
- `requires_reason`
- `requires_date_range`
- `is_active`

Verificare che non ci siano riferimenti a questi campi nel codice esistente prima di applicare la migration.

## Rollback

Per annullare la migration:
```bash
./yii migrate/down 1
```

Questo eliminerà completamente la tabella `request_types`. 