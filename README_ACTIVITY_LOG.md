# Sistema Activity Logging per TherapyCRM

Sistema completo di logging delle attività CRUD con notifiche, widget, esportazione e gestione avanzata.

## 🏗️ Componenti del Sistema

### 1. Database
- **Migration**: `console/migrations/m241220_120000_create_activity_log_table.php`
- **Tabella**: `activity_log` con tutti i campi necessari e indici ottimizzati

### 2. Model
- **ActivityLog**: `common/models/ActivityLog.php`
- Metodi helper per JSON, descrizioni, scopes e statistiche

### 3. Behavior 
- **ActivityLogBehavior**: `common/behaviors/ActivityLogBehavior.php`
- Logging automatico di INSERT, UPDATE, DELETE
- Sistema di notifiche email per azioni critiche
- Configurabile con attributi da escludere

### 4. Controller Backend
- **ActivityLogController**: `backend/controllers/ActivityLogController.php`
- Visualizzazione, ricerca, filtri, statistiche
- Pulizia e esportazione log

### 5. Widget
- **ActivityLogWidget**: `common/widgets/ActivityLogWidget.php`
- Mostra cronologia attività per entità specifica
- Completamente personalizzabile

### 6. Console Controller
- **ActivityLogController**: `console/controllers/ActivityLogController.php`
- Comandi per pulizia automatica, statistiche, esportazione, verifica

### 7. Helper
- **ActivityLogHelper**: `common/helpers/ActivityLogHelper.php`
- Formattazione dati, export Excel, gestione etichette

## 🚀 Installazione

### 1. Esegui la Migration
```bash
./yii migrate
```

### 2. Aggiungi il Behavior ai tuoi Model
```php
// In common/models/YourModel.php
public function behaviors()
{
    return [
        [
            'class' => ActivityLogBehavior::class,
            'excludedAttributes' => ['created_at', 'updated_at', 'password_hash'],
            'logUnchangedAttributes' => false,
        ],
    ];
}
```

### 3. Configura le Notifiche
Le configurazioni sono in `common/config/params.php` nella sezione `activityLog`.

## 📖 Utilizzo

### Widget nelle View
```php
// In una view di dettaglio
echo ActivityLogWidget::widget([
    'entityName' => 'Patient',
    'entityId' => $model->id,
    'limit' => 5,
    'showUser' => true,
    'showChanges' => true,
]);
```

### Comandi Console
```bash
# Pulizia log più vecchi di 6 mesi
./yii activity-log/cleanup 6

# Statistiche ultimi 30 giorni  
./yii activity-log/stats 30

# Esportazione in CSV
./yii activity-log/export log_export.csv 30

# Verifica integrità
./yii activity-log/verify
```

### Backend Interface
Vai a `/backend/activity-log` per:
- Visualizzare tutti i log con filtri avanzati
- Vedere statistiche dettagliate
- Esportare in Excel
- Gestire la pulizia

## ⚙️ Configurazione

### Parametri Principali
```php
// In common/config/params.php
'activityLog' => [
    'criticalEntities' => ['User', 'Patient', 'Therapist'],
    'criticalActions' => ['delete'],
    'notificationEmails' => ['admin@therapycrm.com'],
    'cleanup' => [
        'enabled' => true,
        'retentionMonths' => 12,
    ],
]
```

### Behavior Options
```php
[
    'class' => ActivityLogBehavior::class,
    'excludedAttributes' => ['password_hash', 'auth_key'],
    'logUnchangedAttributes' => false,
    'entityNameCallback' => function($model) {
        return 'CustomEntityName';
    },
]
```

## 🔍 Features Avanzate

### 1. Notifiche Email Automatiche
- Inviate per azioni critiche configurabili
- Template HTML personalizzabile
- Supporto per code background (opzionale)

### 2. Export Multi-formato
- Excel con PhpSpreadsheet
- CSV per console
- Filtri personalizzabili

### 3. Widget Personalizzabile
- Mostra cronologia per entità
- Configurazioni multiple
- Styling responsive

### 4. Sistema di Pulizia
- Comando console per automazione
- Configurabile tramite cron
- Sicurezza con conferme

### 5. Statistiche Avanzate
- Per utente, entità, azione
- Grafici temporali
- Top performers

## 🛡️ Sicurezza

### Campi Sensibili
I campi sensibili vengono automaticamente esclusi:
- password_hash
- auth_key
- access_token
- verification_token

### Controllo Accessi
- Solo admin possono vedere i log
- Filtraggio per ruoli
- Audit trail completo

### Privacy
- IP address opzionale
- User agent tracking configurabile
- Possibilità di anonimizzazione

## 📊 Esempi di Output

### Log di Creazione
```
Mario Rossi ha creato Patient #142
Dettagli: name: "Luca Bianchi", email: "luca@example.com"
```

### Log di Modifica  
```
Anna Verdi ha modificato Appointment #58
Modifiche:
- status: "scheduled" → "completed"
- notes: "" → "Sessione completata con successo"
```

### Log di Eliminazione
```
Admin ha eliminato User #23  
Dati eliminati: username: "old_user", email: "old@example.com"
```

## 🧪 Testing

### Test Manuale
1. Crea, modifica, elimina record con behavior abilitato
2. Verifica log in backend interface
3. Testa widget in view di dettaglio
4. Esegui comandi console

### Verifica Integrità
```bash
./yii activity-log/verify
```

## 🔧 Troubleshooting

### Log Non Vengono Creati
1. Verifica che il behavior sia configurato
2. Controlla che l'utente sia autenticato
3. Verifica permessi database

### Notifiche Non Funzionano
1. Controlla configurazione mailer
2. Verifica parametri activityLog
3. Controlla log errori

### Performance Issues
1. Aggiungi indici su campi filtrati frequentemente
2. Configura pulizia automatica
3. Considera l'uso di code background

## 📈 Monitoraggio

### Metriche Importanti
- Numero log per giorno
- Utenti più attivi  
- Entità più modificate
- Errori di logging

### Dashboard Suggerimenti
- Grafici attività temporali
- Top utenti per modifiche
- Heatmap delle azioni
- Alert per azioni critiche

## 🔄 Estensioni Future

### Possibili Miglioramenti
- Compressione log vecchi
- Geo-localizzazione IP
- Crittografia dati sensibili
- Plugin per analisi avanzate
- API REST per log
- Dashboard real-time

### Integrazione
- Sistema di backup
- SIEM integration
- Compliance reporting
- Audit trails export

## 📞 Supporto

Per domande o problemi:
1. Controlla questa documentazione
2. Verifica log errori in `frontend/runtime/logs/`
3. Usa comando `verify` per diagnostica
4. Contatta il team di sviluppo

---

**Sistema Activity Logging v1.0 - TherapyCRM** 