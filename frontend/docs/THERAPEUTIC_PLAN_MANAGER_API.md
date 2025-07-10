# TherapeuticPlanManagerController API Documentation

## Panoramica

Il `TherapeuticPlanManagerController` gestisce la creazione e gestione di pattern di appuntamenti e singoli appuntamenti per i piani terapeutici. Tutti gli endpoint restituiscono JSON e richiedono il permesso `manage_appointments`.

## Endpoints Disponibili

### 1. Creazione Pattern di Appuntamenti

**Endpoint**: `POST /therapeutic-plan-manager/create-pattern`

**Descrizione**: Crea un pattern ricorrente di appuntamenti e genera automaticamente tutti gli appuntamenti individuali per il periodo specificato.

#### Parametri di Input

```json
{
  "planTherapyId": 100,        // ID del piano terapia (required)
  "therapistId": 5,            // ID del terapista (required)
  "dayOfWeek": 1,              // Giorno settimana: 1=Lunedì, 7=Domenica (required)
  "startTime": "10:00",        // Ora inizio formato HH:mm (required)
  "durationMinutes": 60,       // Durata in minuti (15-180) (required)
  "validFrom": "2024-01-15",   // Data inizio validità YYYY-MM-DD (required)
  "validTo": "2024-06-15"      // Data fine validità YYYY-MM-DD (required)
}
```

#### Esempio di Richiesta

```javascript
// Utilizzo con fetch API
const createPattern = async () => {
    try {
        const response = await fetch('/therapeutic-plan-manager/create-pattern', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            },
            body: JSON.stringify({
                planTherapyId: 100,
                therapistId: 5,
                dayOfWeek: 1,
                startTime: "10:00",
                durationMinutes: 60,
                validFrom: "2024-01-15",
                validTo: "2024-06-15"
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`Pattern creato! ${data.appointmentsCreated} appuntamenti generati`);
            
            // Gestisci conflitti se presenti
            if (data.conflicts.length > 0) {
                handleConflicts(data.conflicts);
            }
            
            // Gestisci superamenti limiti settimanali
            if (data.weeklyLimitExceeded.length > 0) {
                handleWeeklyLimits(data.weeklyLimitExceeded);
            }
        } else {
            console.error('Errore:', data.error);
        }
        
    } catch (error) {
        console.error('Errore nella richiesta:', error);
    }
};

// Utilizzo con jQuery/AJAX
$.ajax({
    url: '/therapeutic-plan-manager/create-pattern',
    type: 'POST',
    dataType: 'json',
    data: {
        planTherapyId: 100,
        therapistId: 5,
        dayOfWeek: 1,
        startTime: "10:00",
        durationMinutes: 60,
        validFrom: "2024-01-15",
        validTo: "2024-06-15",
        _csrf: $('meta[name=csrf-token]').attr('content')
    },
    success: function(data) {
        if (data.success) {
            showSuccessMessage(`Pattern creato! ${data.appointmentsCreated} appuntamenti generati`);
            
            if (data.conflicts.length > 0) {
                showConflictsWarning(data.conflicts);
            }
            
            if (data.weeklyLimitExceeded.length > 0) {
                showWeeklyLimitWarning(data.weeklyLimitExceeded);
            }
        } else {
            showErrorMessage(data.error);
        }
    },
    error: function(xhr, status, error) {
        showErrorMessage('Errore nella comunicazione con il server');
    }
});
```

#### Esempio di Risposta Successo

```json
{
    "success": true,
    "appointmentsCreated": 12,
    "conflicts": [
        {
            "date": "2024-02-15",
            "time": "10:00",
            "therapistId": 5,
            "existingAppointmentId": 123,
            "existingAppointmentInfo": {
                "patientName": "Mario Rossi",
                "startTime": "10:00",
                "endTime": "11:00"
            }
        }
    ],
    "weeklyLimitExceeded": [
        {
            "weekStartDate": "2024-02-12",
            "currentHours": 38,
            "limitHours": 35,
            "newTotal": 39
        }
    ],
    "data": {
        "patternId": 45
    },
    "message": "Pattern creato con successo"
}
```

### 2. Creazione Singolo Appuntamento

**Endpoint**: `POST /therapeutic-plan-manager/create-appointment`

**Descrizione**: Crea un singolo appuntamento senza pattern ricorrente.

#### Parametri di Input

```json
{
  "planTherapyId": 100,                    // ID del piano terapia (required)
  "therapistId": 5,                        // ID del terapista (required)
  "appointmentDateTime": "2024-02-15 10:00:00", // Data e ora completa (required)
  "durationMinutes": 60,                   // Durata in minuti (15-180) (required)
  "notes": "Sessione di recupero"          // Note aggiuntive (optional)
}
```

#### Esempio di Richiesta

```javascript
const createSingleAppointment = async (appointmentData) => {
    try {
        const response = await fetch('/therapeutic-plan-manager/create-appointment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            },
            body: JSON.stringify(appointmentData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Appuntamento creato:', data.data.appointmentId);
            
            if (data.data.weeklyLimitExceeded.length > 0) {
                showWeeklyLimitWarning(data.data.weeklyLimitExceeded);
            }
            
            return data.data.appointmentId;
        } else {
            if (data.conflict) {
                showConflictError(data.conflict);
            } else {
                showErrorMessage(data.error);
            }
            return null;
        }
        
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return null;
    }
};
```

#### Esempio di Risposta Successo

```json
{
    "success": true,
    "message": "Appuntamento creato con successo",
    "data": {
        "appointmentId": 456,
        "weeklyLimitExceeded": []
    }
}
```

#### Esempio di Risposta Conflitto

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

### 3. Lista Terapisti

**Endpoint**: `GET /therapeutic-plan-manager/get-therapists`

**Descrizione**: Ottiene la lista di tutti i terapisti attivi con le loro informazioni.

#### Esempio di Richiesta

```javascript
const getTherapists = async () => {
    try {
        const response = await fetch('/therapeutic-plan-manager/get-therapists');
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            console.error('Errore nel caricamento terapisti:', data.error);
            return [];
        }
        
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return [];
    }
};

// Utilizzo per popolare una select
const populateTherapistSelect = async () => {
    const therapists = await getTherapists();
    const select = document.getElementById('therapist-select');
    
    select.innerHTML = '<option value="">Seleziona un terapista...</option>';
    
    therapists.forEach(therapist => {
        const option = document.createElement('option');
        option.value = therapist.id;
        option.textContent = `${therapist.name} - ${therapist.specialization}`;
        option.dataset.weeklyHours = therapist.weeklyHours;
        select.appendChild(option);
    });
};
```

#### Esempio di Risposta

```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "Dr. Mario Rossi",
            "email": "mario.rossi@therapycrm.com",
            "specialization": "Logopedia",
            "weeklyHours": 35
        },
        {
            "id": 8,
            "name": "Dr.ssa Anna Verdi",
            "email": "anna.verdi@therapycrm.com",
            "specialization": "Psicoterapia",
            "weeklyHours": 30
        }
    ]
}
```

### 4. Lista Terapisti per Tipo Trattamento

**Endpoint**: `GET /therapeutic-plan-manager/get-therapists-by-treatment`

**Descrizione**: Ottiene la lista dei terapisti attivi filtrati per tipo di trattamento.

#### Parametri Query String

- `treatmentTypeId`: ID del tipo di trattamento (required)

#### Esempio di Richiesta

```javascript
const getTherapistsByTreatment = async (treatmentTypeId) => {
    try {
        const response = await fetch(`/therapeutic-plan-manager/get-therapists-by-treatment?treatmentTypeId=${treatmentTypeId}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            console.error('Errore:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return [];
    }
};
```

#### Esempio di Risposta

```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "Mario Rossi",
            "email": "mario.rossi@example.com"
        },
        {
            "id": 8,
            "name": "Laura Bianchi",
            "email": "laura.bianchi@example.com"
        }
    ]
}
```

### 5. Dettagli Paziente

**Endpoint**: `GET /therapeutic-plan-manager/get-patient`

**Descrizione**: Ottiene i dati anagrafici di base di un paziente.

#### Parametri Query String

- `id`: ID del paziente (required)

#### Esempio di Richiesta

```javascript
const getPatientDetails = async (patientId) => {
    try {
        const response = await fetch(`/therapeutic-plan-manager/get-patient?id=${patientId}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            console.error('Errore:', data.error);
            return null;
        }
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return null;
    }
};
```

#### Esempio di Risposta

```json
{
    "success": true,
    "data": {
        "id": 100,
        "name": "Giuseppe Verdi",
        "birthDate": "1990-05-15",
        "fiscalCode": "VRDGPP90E15H501X",
        "email": "giuseppe.verdi@example.com"
    }
}
```

### 6. Appuntamenti Terapista

**Endpoint**: `GET /therapeutic-plan-manager/get-therapist-appointments`

**Descrizione**: Ottiene tutti gli appuntamenti di un terapista per un mese specifico.

#### Parametri Query String

- `therapistId`: ID del terapista (required)
- `month`: Numero del mese (1-12) (required)
- `year`: Anno in formato YYYY (required)

#### Esempio di Richiesta

```javascript
const getTherapistAppointments = async (therapistId, month, year) => {
    try {
        const response = await fetch(`/therapeutic-plan-manager/get-therapist-appointments?therapistId=${therapistId}&month=${month}&year=${year}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            console.error('Errore:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return [];
    }
};
```

#### Esempio di Risposta

```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "datetime": "2024-02-15 10:00:00",
            "duration": 60,
            "status": "scheduled",
            "patient": {
                "id": 100,
                "name": "Giuseppe Verdi"
            }
        }
    ]
}
```

### 7. Appuntamenti Paziente

**Endpoint**: `GET /therapeutic-plan-manager/get-patient-appointments`

**Descrizione**: Ottiene tutti gli appuntamenti di un paziente per un mese specifico.

#### Parametri Query String

- `patientId`: ID del paziente (required)
- `month`: Numero del mese (1-12) (required)
- `year`: Anno in formato YYYY (required)

#### Esempio di Richiesta

```javascript
const getPatientAppointments = async (patientId, month, year) => {
    try {
        const response = await fetch(`/therapeutic-plan-manager/get-patient-appointments?patientId=${patientId}&month=${month}&year=${year}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            console.error('Errore:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return [];
    }
};
```

#### Esempio di Risposta

```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "datetime": "2024-02-15 10:00:00",
            "duration": 60,
            "status": "scheduled",
            "treatmentType": "Fisioterapia",
            "therapist": {
                "id": 5,
                "name": "Mario Rossi"
            }
        }
    ]
}
```

### 8. Aggiornamento Appuntamento

**Endpoint**: `POST /therapeutic-plan-manager/update-appointment`

**Descrizione**: Aggiorna un singolo appuntamento esistente.

#### Parametri di Input

```json
{
    "appointmentId": 123,                   // ID dell'appuntamento (required)
    "therapistId": 5,                       // Nuovo ID terapista (required)
    "appointmentDateTime": "2024-02-15 10:00:00", // Nuova data e ora (required)
    "durationMinutes": 60,                  // Nuova durata in minuti (required)
    "notes": "Cambio orario richiesto"      // Note aggiuntive (optional)
}
```

#### Esempio di Richiesta

```javascript
const updateAppointment = async (appointmentData) => {
    try {
        const response = await fetch('/therapeutic-plan-manager/update-appointment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            },
            body: JSON.stringify(appointmentData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Appuntamento aggiornato:', data.data.appointmentId);
            return true;
        } else {
            if (data.conflict) {
                showConflictError(data.conflict);
            } else {
                showErrorMessage(data.error);
            }
            return false;
        }
        
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return false;
    }
};
```

#### Esempio di Risposta Successo

```json
{
    "success": true,
    "message": "Appuntamento aggiornato con successo",
    "data": {
        "appointmentId": 123
    }
}
```

### 9. Aggiornamento Appuntamenti Pattern

**Endpoint**: `POST /therapeutic-plan-manager/update-pattern-appointments`

**Descrizione**: Aggiorna tutti gli appuntamenti futuri di un pattern specifico.

#### Parametri di Input

```json
{
    "patternId": 45,           // ID del pattern (required)
    "therapistId": 5,          // Nuovo ID terapista (required)
    "startTime": "11:00",      // Nuovo orario inizio (required)
    "durationMinutes": 60,     // Nuova durata in minuti (required)
    "fromDate": "2024-02-15"   // Data da cui applicare le modifiche (required)
}
```

#### Esempio di Richiesta

```javascript
const updatePatternAppointments = async (updateData) => {
    try {
        const response = await fetch('/therapeutic-plan-manager/update-pattern-appointments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            },
            body: JSON.stringify(updateData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`Aggiornati ${data.data.updatedCount} appuntamenti`);
            
            if (data.data.conflicts.length > 0) {
                showConflictsWarning(data.data.conflicts);
            }
            
            return true;
        } else {
            showErrorMessage(data.error);
            return false;
        }
        
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return false;
    }
};
```

#### Esempio di Risposta Successo

```json
{
    "success": true,
    "message": "Appuntamenti aggiornati con successo",
    "data": {
        "updatedCount": 10,
        "conflicts": [
            {
                "date": "2024-03-15",
                "time": "11:00",
                "therapistId": 5,
                "existingAppointmentId": 456,
                "existingAppointmentInfo": {
                    "patientName": "Luigi Bianchi",
                    "startTime": "11:00",
                    "endTime": "12:00"
                }
            }
        ]
    }
}
```

### 10. Cancellazione Appuntamento

**Endpoint**: `POST /therapeutic-plan-manager/delete-appointment`

**Descrizione**: Effettua la cancellazione logica di un appuntamento non completato.

#### Parametri di Input

```json
{
    "appointmentId": 123       // ID dell'appuntamento (required)
}
```

#### Esempio di Richiesta

```javascript
const deleteAppointment = async (appointmentId) => {
    try {
        const response = await fetch('/therapeutic-plan-manager/delete-appointment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            },
            body: JSON.stringify({ appointmentId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Appuntamento cancellato con successo');
            return true;
        } else {
            showErrorMessage(data.error);
            return false;
        }
        
    } catch (error) {
        console.error('Errore nella richiesta:', error);
        return false;
    }
};
```

#### Esempio di Risposta Successo

```json
{
    "success": true,
    "message": "Appuntamento cancellato con successo"
}
```

## Gestione Errori

### Tipi di Errori Comuni

#### 1. Errori di Validazione

```json
{
    "success": false,
    "error": "Campo obbligatorio mancante: planTherapyId",
    "code": "GENERIC_ERROR"
}
```

#### 2. Errori di Autorizzazione

```json
{
    "success": false,
    "error": "Non hai i permessi per accedere a questa risorsa",
    "code": "FORBIDDEN"
}
```

#### 3. Errori di Entità Non Trovate

```json
{
    "success": false,
    "error": "Terapista non trovato o non attivo",
    "code": "GENERIC_ERROR"
}
```

#### 4. Errori di Validazione Business Logic

```json
{
    "success": false,
    "error": "Le date del pattern devono essere comprese nel periodo del piano terapeutico",
    "code": "GENERIC_ERROR"
}
```

## Esempi di Utilizzo Frontend

### 1. Form di Creazione Pattern

```html
<form id="pattern-form">
    <div class="form-group">
        <label for="plan-therapy-id">Piano Terapia</label>
        <select id="plan-therapy-id" name="planTherapyId" required>
            <option value="">Seleziona piano terapia...</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="therapist-id">Terapista</label>
        <select id="therapist-id" name="therapistId" required>
            <option value="">Seleziona terapista...</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="day-of-week">Giorno della Settimana</label>
        <select id="day-of-week" name="dayOfWeek" required>
            <option value="1">Lunedì</option>
            <option value="2">Martedì</option>
            <option value="3">Mercoledì</option>
            <option value="4">Giovedì</option>
            <option value="5">Venerdì</option>
            <option value="6">Sabato</option>
            <option value="7">Domenica</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="start-time">Ora Inizio</label>
        <input type="time" id="start-time" name="startTime" required>
    </div>
    
    <div class="form-group">
        <label for="duration">Durata (minuti)</label>
        <select id="duration" name="durationMinutes" required>
            <option value="30">30 minuti</option>
            <option value="45">45 minuti</option>
            <option value="60">60 minuti</option>
            <option value="90">90 minuti</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="valid-from">Data Inizio</label>
        <input type="date" id="valid-from" name="validFrom" required>
    </div>
    
    <div class="form-group">
        <label for="valid-to">Data Fine</label>
        <input type="date" id="valid-to" name="validTo" required>
    </div>
    
    <button type="submit">Crea Pattern</button>
</form>
```

### 2. JavaScript per Gestione Form

```javascript
class PatternManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.loadTherapists();
        this.bindEvents();
    }
    
    async loadTherapists() {
        const therapists = await getTherapists();
        this.populateTherapistSelect(therapists);
    }
    
    populateTherapistSelect(therapists) {
        const select = document.getElementById('therapist-id');
        select.innerHTML = '<option value="">Seleziona terapista...</option>';
        
        therapists.forEach(therapist => {
            const option = document.createElement('option');
            option.value = therapist.id;
            option.textContent = `${therapist.name} - ${therapist.specialization}`;
            option.dataset.weeklyHours = therapist.weeklyHours;
            select.appendChild(option);
        });
    }
    
    bindEvents() {
        const form = document.getElementById('pattern-form');
        form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        // Converti i valori numerici
        data.planTherapyId = parseInt(data.planTherapyId);
        data.therapistId = parseInt(data.therapistId);
        data.dayOfWeek = parseInt(data.dayOfWeek);
        data.durationMinutes = parseInt(data.durationMinutes);
        
        try {
            const result = await this.createPattern(data);
            
            if (result) {
                this.showSuccessMessage(
                    `Pattern creato con successo! ${result.appointmentsCreated} appuntamenti generati.`
                );
                
                if (result.conflicts.length > 0) {
                    this.showConflictsModal(result.conflicts);
                }
                
                if (result.weeklyLimitExceeded.length > 0) {
                    this.showWeeklyLimitModal(result.weeklyLimitExceeded);
                }
                
                form.reset();
            }
            
        } catch (error) {
            this.showErrorMessage('Errore nella creazione del pattern');
        }
    }
    
    async createPattern(data) {
        const response = await fetch('/therapeutic-plan-manager/create-pattern', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            return result;
        } else {
            throw new Error(result.error);
        }
    }
    
    showSuccessMessage(message) {
        // Implementa la visualizzazione del messaggio di successo
        // Puoi usare toast, modal, o sistema di notifiche esistente
        console.log('Success:', message);
    }
    
    showErrorMessage(message) {
        // Implementa la visualizzazione del messaggio di errore
        console.error('Error:', message);
    }
    
    showConflictsModal(conflicts) {
        // Implementa modal per mostrare i conflitti
        console.log('Conflicts:', conflicts);
    }
    
    showWeeklyLimitModal(limits) {
        // Implementa modal per mostrare i superamenti dei limiti
        console.log('Weekly limits exceeded:', limits);
    }
}

// Inizializza quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    new PatternManager();
});
```

### 3. Gestione Conflitti e Avvisi

```javascript
class ConflictManager {
    showConflictModal(conflicts) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Conflitti Rilevati</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-warning">
                            Alcuni appuntamenti non sono stati creati a causa di conflitti:
                        </p>
                        <ul class="list-unstyled">
                            ${conflicts.map(conflict => `
                                <li class="mb-2">
                                    <strong>${conflict.date} alle ${conflict.time}</strong><br>
                                    <small class="text-muted">
                                        Conflitto con appuntamento di ${conflict.existingAppointmentInfo.patientName}
                                        (${conflict.existingAppointmentInfo.startTime} - ${conflict.existingAppointmentInfo.endTime})
                                    </small>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        $(modal).modal('show');
        
        // Rimuovi modal dopo la chiusura
        $(modal).on('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
    
    showWeeklyLimitModal(limits) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Limite Settimanale Superato</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-info">
                            Attenzione: Il limite settimanale del terapista è stato superato:
                        </p>
                        <ul class="list-unstyled">
                            ${limits.map(limit => `
                                <li class="mb-2">
                                    <strong>Settimana del ${limit.weekStartDate}</strong><br>
                                    <small class="text-muted">
                                        Ore attuali: ${limit.currentHours} / ${limit.limitHours}
                                        (Nuovo totale: ${limit.newTotal})
                                    </small>
                                </li>
                            `).join('')}
                        </ul>
                        <p class="text-muted small">
                            Gli appuntamenti sono stati comunque creati per permettere una gestione flessibile.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">
                            Ho capito
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        $(modal).modal('show');
        
        // Rimuovi modal dopo la chiusura
        $(modal).on('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
}
```

## Best Practices

### 1. Gestione CSRF Token

Assicurati sempre di includere il CSRF token nelle richieste POST:

```javascript
// Includi sempre il token CSRF
const csrfToken = $('meta[name=csrf-token]').attr('content');

// Nelle richieste fetch
headers: {
    'X-CSRF-Token': csrfToken
}

// Nelle richieste jQuery
data: {
    _csrf: csrfToken
}
```

### 2. Validazione Lato Client

Implementa sempre validazione lato client prima di inviare i dati:

```javascript
function validatePatternData(data) {
    const errors = [];
    
    if (!data.planTherapyId) {
        errors.push('Piano terapia obbligatorio');
    }
    
    if (!data.therapistId) {
        errors.push('Terapista obbligatorio');
    }
    
    if (!data.startTime || !/^\d{2}:\d{2}$/.test(data.startTime)) {
        errors.push('Ora inizio deve essere nel formato HH:mm');
    }
    
    if (data.durationMinutes < 15 || data.durationMinutes > 180) {
        errors.push('Durata deve essere tra 15 e 180 minuti');
    }
    
    if (new Date(data.validFrom) > new Date(data.validTo)) {
        errors.push('Data inizio non può essere successiva alla data fine');
    }
    
    return errors;
}
```

### 3. Gestione Loading States

Mostra sempre feedback visivo durante le operazioni:

```javascript
async function createPatternWithLoading(data) {
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.textContent;
    
    // Mostra loading
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creazione in corso...';
    
    try {
        const result = await createPattern(data);
        return result;
    } finally {
        // Ripristina stato originale
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}
```

### 4. Debouncing per Ricerche

Per ricerche in tempo reale, usa debouncing:

```javascript
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const debouncedSearch = debounce(async (query) => {
    // Esegui ricerca
}, 300);
```

## Codici di Errore

| Codice | Descrizione | Azione Consigliata |
|--------|-------------|-------------------|
| `GENERIC_ERROR` | Errore generico | Mostra messaggio utente |
| `VALIDATION_ERROR` | Errore validazione | Evidenzia campi non validi |
| `PERMISSION_DENIED` | Permessi insufficienti | Redirect a login |
| `THERAPIST_NOT_FOUND` | Terapista non trovato | Ricarica lista terapisti |
| `PLAN_THERAPY_NOT_FOUND` | Piano terapia non trovato | Verifica selezione |
| `CONFLICT_ERROR` | Conflitto appuntamento | Mostra alternative |

## Supporto

Per problemi o domande sull'utilizzo dell'API:

1. Controlla i log dell'applicazione in `frontend/runtime/logs/app.log`
2. Verifica che l'utente abbia il permesso `manage_appointments`
3. Controlla che i dati inviati rispettino i formati richiesti
4. Verifica la presenza del CSRF token nelle richieste POST

---

*Documentazione aggiornata al: 2024-01-15* 