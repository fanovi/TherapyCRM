# Regole di Validazione Appuntamenti - TherapyCRM

## Panoramica

Il sistema TherapyCRM implementa un sistema completo di validazione per gli appuntamenti che previene tutti i tipi di conflitti e garantisce l'integrità dei dati. Questo documento descrive tutte le regole di validazione implementate.

## Tipi di Validazione

### 1. Conflitti Terapista (Therapist Conflicts)

**Scopo**: Impedire che lo stesso terapista abbia appuntamenti sovrapposti temporalmente.

**Metodo**: `checkTherapistConflict($therapistId, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null)`

**Logica**:

- Verifica sovrapposizioni orarie del terapista
- Controlla sia l'inizio che la fine dell'appuntamento
- Esclude appuntamenti cancellati (`STATUS_CANCELLED`)
- Permette di escludere un appuntamento specifico (per aggiornamenti)

**Condizioni SQL**:

```sql
WHERE therapist_id = $therapistId
AND status != 'cancelled'
AND (
    (appointment_datetime <= $start AND DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE) > $start)
    OR
    (appointment_datetime < $end AND appointment_datetime >= $start)
)
```

**Messaggio di Errore**: "Conflitto terapista rilevato"

### 2. Conflitti Slot Temporale Paziente (Patient Time Slot Conflicts)

**Scopo**: Impedire che lo stesso paziente abbia appuntamenti sovrapposti temporalmente.

**Metodo**: `checkPatientTimeSlotConflict($patientId, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null)`

**Logica**:

- Verifica che lo stesso paziente non abbia appuntamenti che si sovrappongono temporalmente
- Utilizza JOIN tra tabelle per identificare il paziente
- Controlla sovrapposizioni orarie complete
- Esclude appuntamenti cancellati

**Condizioni SQL**:

```sql
INNER JOIN plan_therapies pt ON pt.id = a.plan_therapy_id
INNER JOIN therapeutic_plans tp ON tp.id = pt.therapeutic_plan_id
WHERE tp.patient_id = $patientId
AND a.status != 'cancelled'
AND (sovrapposizione oraria)
```

**Messaggio di Errore**: "Slot paziente già occupato"

### 3. Conflitti Tipologia Trattamento (Treatment Type Conflicts)

**Scopo**: Impedire che lo stesso paziente abbia più appuntamenti della stessa tipologia di trattamento nello stesso giorno.

**Metodo**: `checkSameTreatmentTypeConflict($planTherapyId, $appointmentDateTime, $excludeAppointmentId = null)`

**Logica**:

- Verifica duplicati dello stesso piano terapia (`plan_therapy_id`) nello stesso giorno
- Controllo granulare a livello di terapia specifica
- Esclude appuntamenti cancellati
- Verifica solo nello stesso giorno (00:00:00 - 23:59:59)

**Condizioni SQL**:

```sql
WHERE a.plan_therapy_id = $planTherapyId
AND a.status != 'cancelled'
AND a.appointment_datetime BETWEEN '$dateStart' AND '$dateEnd'
```

**Messaggio di Errore**: "Conflitto tipologia trattamento rilevato"

### 4. Limite Settimanale Terapista (Weekly Limit Check)

**Scopo**: Verificare che il terapista non superi le ore contrattuali settimanali.

**Metodo**: `checkWeeklyLimit($therapist, $appointmentDateTime, $durationMinutes)`

**Logica**:

- Calcola le ore della settimana corrente del terapista
- Confronta con le ore contrattuali (`weekly_hours_contract`)
- Considera la settimana da lunedì a domenica
- Include appuntamenti programmati e completati

**Messaggio**: Warning informativo, non blocca la creazione

## Integrazione nei Metodi

### Metodi che Implementano Tutte le Validazioni

1. **`actionCreateAppointment()`** - Creazione singoli appuntamenti
2. **`actionUpdateAppointment()`** - Aggiornamento appuntamenti esistenti
3. **`generateAppointments()`** - Generazione pattern ricorrenti
4. **`actionUpdatePatternAppointments()`** - Aggiornamento pattern futuri

### Ordine di Esecuzione delle Validazioni

1. **Validazione campi obbligatori**
2. **Conflitti terapista**
3. **Conflitti slot temporale paziente** ⭐ _NUOVO_
4. **Conflitti tipologia trattamento**
5. **Limite settimanale** (warning)

## Messaggi di Errore Dettagliati

### Conflitto Terapista

```json
{
  "success": false,
  "error": "Conflitto terapista rilevato",
  "conflict": {
    "existingAppointmentId": 123,
    "existingAppointmentInfo": {
      "patientName": "Mario Rossi",
      "startTime": "10:00",
      "endTime": "11:00"
    }
  }
}
```

### Conflitto Slot Paziente

```json
{
  "success": false,
  "error": "Slot paziente già occupato",
  "conflict": {
    "type": "patient_time_slot_conflict",
    "existingAppointmentId": 456,
    "patientName": "Mario Rossi",
    "treatmentType": "Fisioterapia",
    "existingAppointmentDate": "2024-01-15",
    "existingAppointmentTime": "10:00",
    "existingAppointmentEndTime": "11:00",
    "existingTherapistName": "Dr. Bianchi",
    "message": "Il paziente Mario Rossi ha già un appuntamento di Fisioterapia in data 15/01/2024 dalle ore 10:00 alle ore 11:00 con Dr. Bianchi"
  }
}
```

### Conflitto Tipologia Trattamento

```json
{
  "success": false,
  "error": "Conflitto tipologia trattamento rilevato",
  "conflict": {
    "type": "same_plan_therapy",
    "existingAppointmentId": 789,
    "planTherapyId": 45,
    "treatmentType": "Fisioterapia",
    "patientName": "Mario Rossi",
    "existingAppointmentDate": "2024-01-15",
    "existingAppointmentTime": "14:00",
    "existingTherapistName": "Dr. Verdi",
    "message": "Esiste già un appuntamento di Fisioterapia per Mario Rossi in data 15/01/2024 alle ore 14:00 con Dr. Verdi"
  }
}
```

## Casi d'Uso Speciali

### Aggiornamento Appuntamenti

- **Cambio terapista**: Ricalcola `plan_therapy_id` e esegue tutti i controlli
- **Cambio orario**: Verifica tutti i conflitti con nuovo orario
- **Stesso terapista, stesso orario**: Salta i controlli non necessari
- **Appuntamenti completati**: Non possono essere modificati

### Pattern Ricorrenti

- **Creazione pattern**: Verifica conflitti per ogni data generata
- **Aggiornamento pattern**: Verifica conflitti per tutti gli appuntamenti futuri
- **Conflitti parziali**: Crea solo gli appuntamenti senza conflitti

### Esclusioni

- **Appuntamenti cancellati**: Esclusi da tutti i controlli
- **Appuntamento corrente**: Escluso durante aggiornamenti
- **Date passate**: Incluse nei controlli per mantenere integrità storica

## Best Practices

### Per Sviluppatori

1. **Sempre verificare tutti i tipi di conflitto** prima di salvare
2. **Usare transazioni** per operazioni multiple
3. **Loggare i conflitti** per debugging
4. **Fornire messaggi di errore dettagliati** all'utente
5. **Mantenere coerenza** tra tutti i metodi di gestione appuntamenti

### Per Utenti

1. **Controllare disponibilità** prima di creare appuntamenti
2. **Verificare conflitti** quando si spostano appuntamenti
3. **Considerare limiti settimanali** dei terapisti
4. **Pianificare trattamenti** evitando sovrapposizioni dello stesso paziente

## Logging e Debugging

### Log di Conflitti

```php
// Esempio di log per conflitto slot temporale paziente
Yii::info("Conflitto slot temporale paziente rilevato: Paziente {$patient->getFullName()}, Terapia {$treatmentType->name}, Terapista {$therapistName}, DateTime {$result->appointment_datetime}", __METHOD__);
```

### Log di Creazione Appuntamenti

```php
// Esempio di log per creazione riuscita
Yii::info("Appuntamento creato con successo: ID {$appointment->id}, DateTime: {$appointmentDateTime}", __METHOD__);
```

## Struttura Database Coinvolta

### Tabelle Principali

- `appointments` - Appuntamenti
- `plan_therapies` - Piani terapia specifici
- `therapeutic_plans` - Piani terapeutici generali
- `therapists` - Terapisti
- `patients` - Pazienti
- `treatment_types` - Tipologie di trattamento

### Relazioni Chiave

```
appointments -> plan_therapies -> therapeutic_plans -> patients
appointments -> therapists
plan_therapies -> treatment_types
```

## Changelog

### Versione 2.0 (Corrente)

- ✅ **Aggiunto controllo conflitti slot temporale paziente**
- ✅ **Migliorato controllo conflitti tipologia trattamento**
- ✅ **Integrato in tutti i metodi di gestione appuntamenti**
- ✅ **Messaggi di errore dettagliati e standardizzati**

### Versione 1.0 (Precedente)

- ✅ Controllo conflitti terapista
- ✅ Controllo base duplicati tipologia trattamento
- ✅ Limite settimanale terapista
- ❌ Mancava controllo slot temporale paziente

---

**Autore**: Sistema TherapyCRM  
**Data**: 2024  
**Versione**: 2.0
