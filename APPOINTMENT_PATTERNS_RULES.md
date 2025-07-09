# Regole Cursor per Gestione AppointmentPattern - TherapyCRM

## Panoramica Sistema

Il sistema di gestione appuntamenti utilizza il pattern `AppointmentPattern` per creare serie ricorrenti di appuntamenti. Ogni pattern genera automaticamente i singoli appuntamenti (`Appointment`) per l'intero periodo del piano terapeutico.

## Struttura Database

### Tabelle Principali
- `therapeutic_plans`: Piano terapeutico del paziente
- `plan_therapies`: Singole terapie all'interno del piano
- `appointment_patterns`: Pattern ricorrenti per generare appuntamenti
- `appointments`: Singoli appuntamenti generati dai pattern

### Relazioni Chiave
```
TherapeuticPlan 1 → * PlanTherapy 1 → * AppointmentPattern 1 → * Appointment
```

## Regole di Implementazione

### 1. Creazione Pattern

**SEMPRE:**
- Creare un pattern per ogni combinazione unica di: `planTherapyId + dayOfWeek + time`
- Validare che `start_date` e `end_date` siano coerenti con il piano terapeutico
- Impostare `is_active = true` per pattern attivi
- Utilizzare transazioni database per pattern + generazione appuntamenti

**MAI:**
- Creare pattern senza generare immediatamente gli appuntamenti
- Permettere pattern con `days_of_week` vuoto
- Creare pattern con `duration_minutes < 15` o `> 180`

### 2. Generazione Appuntamenti

**Logica di Generazione:**
```php
// Per ogni pattern attivo
foreach ($patterns as $pattern) {
    $currentDate = new DateTime($pattern->start_date);
    $endDate = new DateTime($pattern->end_date);
    
    while ($currentDate <= $endDate) {
        if ($currentDate->format('N') == $pattern->days_of_week) {
            // Genera appuntamento se non ci sono conflitti
            $this->createAppointmentFromPattern($pattern, $currentDate);
        }
        $currentDate->add(new DateInterval('P1D'));
    }
}
```

**Validazioni Obbligatorie:**
- Controllo conflitti terapista: `hasTherapistConflict()`
- Verifica disponibilità paziente
- Controllo orari lavorativi
- Esclusione giorni festivi (se configurato)

### 3. Modifiche Massive

**Scenario: Cambio Orario**
```php
// 1. Modifica il pattern
$pattern->start_time = $newTime;
$pattern->save();

// 2. Aggiorna appuntamenti futuri
Appointment::updateAll(
    ['appointment_datetime' => new Expression("CONCAT(DATE(appointment_datetime), ' $newTime:00')")],
    ['and', 
        ['pattern_id' => $pattern->id],
        ['>=', 'appointment_datetime', date('Y-m-d H:i:s')],
        ['status' => Appointment::STATUS_SCHEDULED]
    ]
);
```

**Scenario: Cancellazione Temporanea**
```php
// NON eliminare il pattern, disattivarlo
$pattern->is_active = false;
$pattern->save();

// Cancellare solo appuntamenti futuri
Appointment::updateAll(
    ['status' => Appointment::STATUS_CANCELLED],
    ['and',
        ['pattern_id' => $pattern->id],
        ['>=', 'appointment_datetime', date('Y-m-d H:i:s')]
    ]
);
```

### 4. Gestione Eccezioni

**Modifica Singolo Appuntamento:**
```php
// L'appuntamento mantiene il pattern_id ma può essere modificato
$appointment = Appointment::findOne($id);
$appointment->appointment_datetime = $newDateTime;
$appointment->therapist_id = $newTherapistId;
$appointment->original_therapist_id = $originalTherapistId; // Se sostituzione
$appointment->save();

// Il pattern rimane invariato
```

**Sostituzione Terapista:**
```php
// Per sostituzioni temporanee
$appointment->therapist_id = $substituteTherapistId;
$appointment->original_therapist_id = $originalTherapistId;
$appointment->notes = "Sostituzione per assenza terapista";
$appointment->save();
```

### 5. Validazioni Pattern

**Regole di Validazione:**
```php
public function rules()
{
    return [
        [['plan_therapy_id', 'frequency_type', 'start_time', 'duration_minutes', 'days_of_week', 'start_date'], 'required'],
        [['frequency_type'], 'in', 'range' => ['weekly', 'biweekly', 'monthly']],
        [['days_of_week'], 'match', 'pattern' => '/^[1-7](,[1-7])*$/'],
        [['duration_minutes'], 'integer', 'min' => 15, 'max' => 180],
        [['start_time'], 'time', 'format' => 'php:H:i'],
        [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
        [['end_date'], 'compare', 'compareAttribute' => 'start_date', 'operator' => '>='],
    ];
}
```

### 6. Query Ottimizzate

**Trovare Pattern Attivi:**
```php
$activePatterns = AppointmentPattern::find()
    ->where(['is_active' => true])
    ->andWhere(['<=', 'start_date', date('Y-m-d')])
    ->andWhere(['>=', 'end_date', date('Y-m-d')])
    ->with(['planTherapy.therapeuticPlan'])
    ->all();
```

**Statistiche Pattern:**
```php
// Conteggio pattern per tipo terapia
$stats = AppointmentPattern::find()
    ->select(['pt.treatment_type_id', 'COUNT(*) as pattern_count'])
    ->leftJoin('plan_therapies pt', 'pt.id = appointment_patterns.plan_therapy_id')
    ->where(['appointment_patterns.is_active' => true])
    ->groupBy('pt.treatment_type_id')
    ->asArray()
    ->all();
```

### 7. Gestione Errori

**Errori Comuni da Gestire:**
- Conflitti terapista durante generazione
- Pattern con date invalide
- Terapie inesistenti
- Limiti orari superati

**Esempio Gestione Errore:**
```php
try {
    $transaction = Yii::$app->db->beginTransaction();
    
    // Salva pattern
    if (!$pattern->save()) {
        throw new Exception('Errore salvataggio pattern: ' . json_encode($pattern->errors));
    }
    
    // Genera appuntamenti
    $generated = $this->generateAppointmentsFromPattern($pattern);
    if ($generated === 0) {
        throw new Exception('Nessun appuntamento generato - verificare disponibilità');
    }
    
    $transaction->commit();
} catch (Exception $e) {
    $transaction->rollBack();
    return ['success' => false, 'error' => $e->getMessage()];
}
```

### 8. Performance

**Ottimizzazioni Obbligatorie:**
- Usare `with()` per caricare relazioni
- Batch insert per appuntamenti multipli
- Indici su: `pattern_id`, `plan_therapy_id`, `appointment_datetime`, `therapist_id`
- Limitare query di conflitto a finestre temporali specifiche

**Batch Insert Esempio:**
```php
$appointments = [];
foreach ($dates as $date) {
    $appointments[] = [
        'pattern_id' => $pattern->id,
        'plan_therapy_id' => $pattern->plan_therapy_id,
        'therapist_id' => $therapistId,
        'patient_id' => $patientId,
        'appointment_datetime' => $date . ' ' . $pattern->start_time,
        'duration_minutes' => $pattern->duration_minutes,
        'status' => Appointment::STATUS_SCHEDULED,
        'created_by' => Yii::$app->user->id,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
}

Yii::$app->db->createCommand()->batchInsert(
    Appointment::tableName(),
    array_keys($appointments[0]),
    $appointments
)->execute();
```

### 9. API Response Format

**Successo:**
```json
{
    "success": true,
    "message": "Piano schedulato con successo",
    "data": {
        "appointmentsCreated": 39,
        "patternsCreated": 3,
        "patterns": [
            {
                "id": 300,
                "therapy": "Logopedia",
                "schedule": "Lunedì 10:00",
                "appointments": 13
            }
        ]
    }
}
```

**Errore:**
```json
{
    "success": false,
    "error": "Conflitto terapista rilevato",
    "details": {
        "pattern_id": 300,
        "conflicting_datetime": "2024-01-15 10:00:00",
        "conflicting_appointment_id": 1025
    }
}
```

### 10. Testing

**Test Obbligatori:**
- Generazione pattern con diverse frequenze
- Controllo conflitti terapista
- Modifiche massive
- Gestione eccezioni singole
- Validazione date e orari
- Performance con grandi volumi

**Esempio Test:**
```php
public function testPatternGeneration()
{
    $pattern = new AppointmentPattern([
        'plan_therapy_id' => 200,
        'frequency_type' => 'weekly',
        'start_time' => '10:00',
        'duration_minutes' => 60,
        'days_of_week' => '1,3,5',
        'start_date' => '2024-01-01',
        'end_date' => '2024-03-31'
    ]);
    
    $this->assertTrue($pattern->save());
    
    $generated = $this->generateAppointmentsFromPattern($pattern);
    $this->assertEquals(39, $generated); // 13 settimane * 3 giorni
}
```

## Note Implementative

1. **Sempre usare transazioni** per operazioni pattern + appuntamenti
2. **Validare conflitti** prima di salvare qualsiasi appuntamento
3. **Mantenere pattern attivi** anche dopo modifiche per tracciabilità
4. **Loggare tutte le modifiche massive** per audit
5. **Testare performance** con piani di lunga durata (>6 mesi)

## Esempi di Utilizzo

### Creazione Piano Completo
```php
// 1. Piano terapeutico già esistente
$plan = TherapeuticPlan::findOne(100);

// 2. Terapie del piano
$logopedia = PlanTherapy::findOne(200);
$psicoterapia = PlanTherapy::findOne(201);

// 3. Pattern per logopedia (2 volte/settimana)
$pattern1 = new AppointmentPattern([
    'plan_therapy_id' => 200,
    'frequency_type' => 'weekly',
    'days_of_week' => '1', // Lunedì
    'start_time' => '10:00',
    'duration_minutes' => 60,
    'start_date' => $plan->start_date,
    'end_date' => $plan->end_date
]);

$pattern2 = new AppointmentPattern([
    'plan_therapy_id' => 200,
    'frequency_type' => 'weekly',
    'days_of_week' => '3', // Mercoledì
    'start_time' => '13:00',
    'duration_minutes' => 60,
    'start_date' => $plan->start_date,
    'end_date' => $plan->end_date
]);

// 4. Pattern per psicoterapia (1 volta/settimana)
$pattern3 = new AppointmentPattern([
    'plan_therapy_id' => 201,
    'frequency_type' => 'weekly',
    'days_of_week' => '5', // Venerdì
    'start_time' => '15:00',
    'duration_minutes' => 60,
    'start_date' => $plan->start_date,
    'end_date' => $plan->end_date
]);

// 5. Salvataggio e generazione
foreach ([$pattern1, $pattern2, $pattern3] as $pattern) {
    if ($pattern->save()) {
        $this->generateAppointmentsFromPattern($pattern);
    }
}
```

Questo file deve essere seguito rigorosamente per garantire coerenza e performance ottimali nel sistema di gestione appuntamenti. 