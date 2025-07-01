# 🎯 PROMPT: Implementazione Sistema Storico Stati Richieste Documenti

## 📋 CONTESTO COMPLETATO

Ho già applicato le seguenti migrations per TherapyCRM:
1. ✅ `m250630_213136_create_request_statuses_table.php` - Tabella stati: "Inviata", "Presa in carico", "Stampato", "Consegnato"
2. ✅ `m250630_213220_modify_document_requests_table.php` - Tabella semplificata con 8 attributi: id, account_patient_id, patient_id, therapeutic_plan_id, therapy_id, notes, status, created_at
3. ✅ `m250630_214500_create_document_request_status_history_table.php` - Tabella storico: id, document_request_id, from_status_id, to_status_id, changed_by_user_id, created_at

## 🎯 OBIETTIVO

Implementa il sistema completo di gestione storico stati per le richieste documenti seguendo i pattern Yii2 Advanced del progetto TherapyCRM.

## 📊 STRUTTURE DATABASE ESISTENTI

```sql
-- request_statuses: 4 record predefiniti
1: "Inviata" 
2: "Presa in carico"
3: "Stampato" 
4: "Consegnato"

-- document_requests: 8 campi
id, account_patient_id, patient_id, therapeutic_plan_id, therapy_id, notes, status, created_at

-- document_request_status_history: 6 campi
id, document_request_id, from_status_id, to_status_id, changed_by_user_id, created_at
```

## 🚀 TASKS DA IMPLEMENTARE

### TASK 1: Creare Modello DocumentRequestStatusHistory

**File:** `common/models/DocumentRequestStatusHistory.php`

**Implementa:**
- Extends ActiveRecord con tableName() corretto
- TimestampBehavior SOLO per created_at (readonly table)
- Relazioni: getDocumentRequest(), getFromStatus(), getToStatus(), getChangedByUser()
- Rules di validazione appropriate
- Metodi statici per analytics: getAverageCompletionTime(), getStuckRequests($days)
- Attributi labels in italiano
- Pattern timezone UTC per created_at

### TASK 2: Aggiornare Modello DocumentRequest

**File:** `common/models/DocumentRequest.php`

**Aggiungi:**
- Metodo changeStatus($newStatusId, $userId = null) con gestione automatica storico
- Relazione getStatusHistory() ordinata per created_at ASC
- Metodo getCurrentStatusDuration() per calcolare tempo nello stato attuale
- Metodo getStatusFlow() per visualizzare flow completo stati
- Validazione business per transizioni stati valide (Inviata → Presa in carico → Stampato → Consegnato)

### TASK 3: Creare RequestStatus Model

**File:** `common/models/RequestStatus.php`

**Implementa:**
- ActiveRecord con scope findActive()
- Costanti per stati: STATUS_SUBMITTED = 1, STATUS_TAKEN = 2, STATUS_PRINTED = 3, STATUS_DELIVERED = 4
- Metodo getForSelect() per dropdown
- Metodo isValidTransition($fromStatusId, $toStatusId) per business rules
- Relazione getDocumentRequests()

### TASK 4: API Endpoint per Gestione Stati

**File:** `api/controllers/RequestStatusController.php`

**Implementa:**
- JwtAuthBehavior con autenticazione obbligatoria
- actionUpdateStatus($requestId) per cambio stato con storico automatico
- actionGetHistory($requestId) per recuperare storico cambi
- actionGetAnalytics() per statistiche workflow
- Formato response JSON standardizzato con gestione errori
- Validazione permessi utente per cambio stati
- Annotazioni OpenAPI per Swagger
- Logging audit per cambi stato

### TASK 5: Frontend Interface (Backend)

**File:** `frontend/views/document-request/`

**Crea:**
- `_status_history.php` - Widget per visualizzare storico stati
- `_status_change_form.php` - Form per cambio stato con dropdown
- JavaScript per gestione AJAX cambio stato
- CSS per timeline visuale dello storico
- Integrazione con sistema notifiche esistente

### TASK 6: Componenti Helper

**File:** `common/components/DocumentRequestStatusHelper.php`

**Implementa:**
- getStatusBadgeHtml($statusId) per visualizzazione badge colorati
- getWorkflowProgressHtml($documentRequest) per barra progresso
- validateStatusTransition($fromStatus, $toStatus, $userRole) 
- getStatusTransitionOptions($currentStatus, $userRole)
- calculateSLA($documentRequest) per calcolo tempi SLA

### TASK 7: Test Scripts

**File:** `api/test/test_document_request_status_workflow.php`

**Testa:**
- Login JWT + creazione richiesta documento
- Cambio stati in sequenza corretta (1→2→3→4)
- Verifica storico completo per ogni cambio
- Test transizioni non valide (es. 1→4 diretto)
- Calcolo analytics e tempi medi
- Gestione errori e validazioni

### TASK 8: Migration per Dati Storici (se necessario)

**File:** `console/migrations/m250630_220000_populate_initial_status_history.php`

**Se esistono dati:**
- Crea record storico iniziale per document_requests esistenti
- Imposta from_status_id = NULL e to_status_id = status attuale
- changed_by_user_id = 1 (admin) per dati legacy
- created_at = document_requests.created_at

## 📋 PATTERN DA SEGUIRE

### Timezone UTC
```php
// SEMPRE usare timezone UTC
$history->created_at = new DateTime('now', new DateTimeZone('UTC'));
```

### Gestione Errori Standardizzata
```php
// Formato response API standard
return [
    'success' => false,
    'error' => 'Messaggio errore',
    'code' => 'ERROR_CODE',
    'details' => []
];
```

### Logging Audit
```php
// SEMPRE loggare cambi stato critici
Yii::info("Status changed for request {$requestId}: {$oldStatus} → {$newStatus} by user {$userId}", __METHOD__);
```

### Validazione Business Rules
```php
// Transizioni valide: 1→2→3→4 (no salti)
if (!RequestStatus::isValidTransition($currentStatus, $newStatus)) {
    throw new BadRequestHttpException('Transizione stato non valida');
}
```

## 🧪 CHECKLIST TESTING

- [ ] Creazione modelli con relazioni corrette
- [ ] Cambio stato con storico automatico
- [ ] Validazione transizioni stati
- [ ] API endpoint funzionanti con JWT
- [ ] Swagger documentazione completa
- [ ] Frontend interfaccia cambio stato
- [ ] Analytics e calcolo tempi
- [ ] Gestione errori e edge cases
- [ ] Performance query con indici
- [ ] Timezone UTC consistente

## 📚 DOCUMENTAZIONE DA AGGIORNARE

- [ ] REQUESTS_API.md - Nuovi endpoint stati
- [ ] README.md di progetto
- [ ] Annotazioni OpenAPI complete
- [ ] Comments nel codice per maintenance

## 🎯 RISULTATO ATTESO

Sistema completo di gestione stati richieste documenti con:
- ✅ Tracciamento automatico cambi stato
- ✅ Storico completo e timeline visuale
- ✅ API per app mobile
- ✅ Analytics e KPI workflow
- ✅ Validazione business rules
- ✅ Interface user-friendly
- ✅ Audit trail completo
- ✅ Performance ottimizzata

## 🚨 NOTE CRITICHE

1. **NON modificare mai direttamente document_requests.status** - usare sempre changeStatus()
2. **Timezone UTC** obbligatorio per consistency
3. **Validare transizioni** prima di ogni cambio stato
4. **Logging audit** per compliance sanitaria
5. **Testing completo** prima di deploy produzione

---

**Questo prompt contiene tutto il necessario per completare l'implementazione del sistema di storico stati. Seguire l'ordine dei TASK per massima efficienza.** 