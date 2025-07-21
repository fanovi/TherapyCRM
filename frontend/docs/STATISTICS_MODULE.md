# Modulo Statistiche TherapyCRM

## Panoramica

Il modulo Statistiche fornisce analisi complete e visualizzazioni interattive per tutti i dati del centro terapeutico. Include dashboard, grafici dinamici, filtri avanzati e funzionalità di export.

## Struttura del Modulo

### Controller
- **`frontend/controllers/StatisticsController.php`** - Controller principale con tutte le action

### Search Models  
- **`frontend/models/AbsenceStatisticsSearch.php`** - Filtri per statistiche assenze
- **`frontend/models/PatientStatisticsSearch.php`** - Filtri per statistiche pazienti  
- **`frontend/models/TreatmentStatisticsSearch.php`** - Filtri per statistiche trattamenti

### Services
- **`common/services/statistics/StatisticsService.php`** - Service principale e dashboard
- **`common/services/statistics/AbsenceStatisticsService.php`** - Logica assenze
- **`common/services/statistics/PatientStatisticsService.php`** - Logica pazienti
- **`common/services/statistics/TreatmentStatisticsService.php`** - Logica trattamenti

### Widgets
- **`frontend/widgets/StatsCard.php`** - Card riassuntive con icone
- **`frontend/widgets/ChartWidget.php`** - Grafici con Chart.js 
- **`frontend/widgets/StatisticsFilter.php`** - Filtri avanzati

### Views
- **`frontend/views/statistics/index.php`** - Dashboard principale
- **`frontend/views/statistics/absences.php`** - Analisi assenze
- **`frontend/views/statistics/patients.php`** - Analisi pazienti
- **`frontend/views/statistics/treatments.php`** - Analisi trattamenti
- **`frontend/views/statistics/plans.php`** - Analisi piani terapeutici

### Assets
- **`frontend/assets/StatisticsAsset.php`** - Bundle CSS/JS
- **`frontend/web/css/statistics.css`** - Stili personalizzati
- **`frontend/web/js/statistics.js`** - JavaScript per grafici e interazioni

## Funzionalità Principali

### Dashboard
- **Cards riassuntive** con metriche chiave
- **Grafici dinamici** per trend e distribuzioni
- **Links rapidi** alle analisi dettagliate
- **Auto-refresh** ogni 5 minuti

### Analisi Assenze
- **Heatmap interattiva** (orari x giorni settimana)
- **Filtri avanzati** per periodo, terapista, trattamento
- **Statistiche per motivo** e chi genera l'assenza
- **Trend temporali** e tassi mensili
- **Tabelle dettagliate** con export Excel

### Analisi Pazienti
- **Demografia completa** (età, genere, distribuzione)
- **Trattamenti multipli** con analisi combinazioni
- **Filtri per regime** sanitario e distretto
- **Crescita nel tempo** e statistiche comparative

### Analisi Trattamenti
- **Ranking per popolarità** e numero pazienti
- **Ricerca combinazioni** con modalità ANY/ALL/EXACT
- **Distribuzione ore settimanali** e tipologie
- **Combinazioni più frequenti** automatiche
- **Analisi efficacia** con tassi completamento

### Analisi Piani Terapeutici
- **Stati e durate** con statistiche aggregate
- **Piani in scadenza** con alert automatici
- **Tassi di completamento** per paziente
- **Trend creazione mensile** 

## Tecnologie Utilizzate

### Frontend
- **Chart.js 3.9.1** - Grafici interattivi
- **Select2** - Filtri multipli avanzati
- **DateRangePicker** - Selezione periodi
- **Bootstrap 4** - Layout responsive
- **FontAwesome** - Icone

### Backend
- **Yii2 Query Builder** - Query ottimizzate
- **Caching** con TagDependency per performance
- **PhpSpreadsheet** - Export Excel
- **Viste materializzate** del database

### Database
- **Viste ottimizzate** (`statistics_*_mv`)
- **Indici** per performance query
- **Aggregazioni** pre-calcolate

## Routes

```php
'statistics' => 'statistics/index',
'statistics/dashboard' => 'statistics/index',
'statistics/absences' => 'statistics/absences',
'statistics/patients' => 'statistics/patients', 
'statistics/treatments' => 'statistics/treatments',
'statistics/plans' => 'statistics/plans',
'statistics/chart-data/<type>' => 'statistics/chart-data',
'statistics/export/<type>' => 'statistics/export',
```

## Permessi RBAC

Il modulo richiede il permesso **`view_statistics`** per accedere a tutte le funzionalità.

## Performance e Caching

### Cache Strategy
- **Dashboard**: 1 ora (dati generali)
- **Dettagli**: 15 minuti (dati filtrati)
- **Charts AJAX**: 15 minuti
- **Tag invalidation** automatica

### Ottimizzazioni
- **Viste materializzate** per query complesse
- **Indici** su campi filtrabili
- **Lazy loading** per grafici AJAX
- **Paginazione** per tabelle grandi

## API Endpoints

### Chart Data (AJAX)
- `GET /statistics/chart-data/absence-heatmap` - Heatmap assenze
- `GET /statistics/chart-data/absence-trend` - Trend assenze
- `GET /statistics/chart-data/patient-age-groups` - Gruppi età
- `GET /statistics/chart-data/treatment-ranking` - Ranking trattamenti

### Export Data
- `POST /statistics/export/absences` - Export assenze Excel
- `POST /statistics/export/patients` - Export pazienti Excel  
- `POST /statistics/export/treatments` - Export trattamenti Excel
- `POST /statistics/export/plans` - Export piani Excel

## Utilizzo dei Widget

### StatsCard
```php
echo StatsCard::widget([
    'title' => 'Pazienti Attivi',
    'value' => 125,
    'icon' => 'fas fa-users',
    'color' => 'primary',
    'footer' => 'Ultimo aggiornamento: oggi',
    'url' => Url::to(['patients']),
    'valueFormat' => 'number'
]);
```

### ChartWidget  
```php
echo ChartWidget::widget([
    'title' => 'Trend Assenze',
    'type' => 'line',
    'ajaxUrl' => Url::to(['chart-data', 'type' => 'absence-trend']),
    'height' => 300
]);
```

### StatisticsFilter
```php
echo StatisticsFilter::widget([
    'model' => $searchModel,
    'form' => $form,
    'fields' => ['dateFrom', 'dateTo', 'therapistId'],
    'options' => ['therapists' => $therapistOptions],
    'collapsible' => true
]);
```

## Esempi d'Uso

### Analisi Assenze Personalizzata
1. Accedi a `/statistics/absences`
2. Imposta filtri (periodo, terapista, trattamento)
3. Visualizza heatmap e grafici aggiornati
4. Esporta dati in Excel per analisi offline

### Ricerca Combinazioni Trattamenti
1. Accedi a `/statistics/treatments`
2. Seleziona trattamenti da analizzare
3. Scegli modalità (ANY/ALL/EXACT)
4. Visualizza pazienti con combinazione richiesta

### Monitoraggio Piani in Scadenza
1. Accedi a `/statistics/plans`
2. Controlla sezione "Piani in Scadenza"
3. Identifica piani con urgenza critica (≤7 giorni)
4. Pianifica rinnovi o conclusioni

## Risoluzione Problemi

### Performance Lente
- Verifica cache attiva: `Yii::$app->cache->flush()`
- Controlla indici database su campi filtrabili
- Riduci periodo di analisi per query complesse

### Grafici Non Caricano
- Verifica JavaScript console per errori
- Controlla connessione AJAX endpoints
- Conferma permessi `view_statistics`

### Export Fallisce
- Verifica spazio disco e permessi directory `/runtime`
- Controlla memoria PHP per dataset grandi
- Usa filtri per ridurre dimensione export

## Estensioni Future

### Possibili Miglioramenti
- **Dashboard personalizzabili** per ruolo utente
- **Alert automatici** via email per soglie
- **Report schedulati** con invio automatico
- **Analisi predittive** con machine learning
- **Integrazione API** per sistemi esterni
- **Mobile app** per statistiche in tempo reale

### Integrazioni
- **Sistema CRM** per lead tracking
- **Fatturazione** per analisi revenue
- **Inventory management** per risorse
- **HR sistema** per performance terapisti 