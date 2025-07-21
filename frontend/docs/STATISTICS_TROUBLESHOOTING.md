# Risoluzione Problemi - Modulo Statistiche

## ✅ Problemi Risolti

### 1. Errore BootstrapAsset
**Problema**: `Failed to instantiate component or class "yii\bootstrap4\BootstrapAsset"`

**Causa**: Il progetto non aveva l'asset Bootstrap4 installato

**Soluzione**: 
- Rimosso `yii\bootstrap4\BootstrapAsset` dalle dipendenze
- Incluso Bootstrap 5 tramite CDN nell'asset bundle
- Aggiornato StatisticsAsset per usare dipendenze corrette

### 2. Dipendenza Circolare Asset
**Problema**: `A circular dependency is detected for bundle 'frontend\assets\StatisticsAsset'`

**Causa**: Riferimento a asset bundle Kartik non disponibili

**Soluzione**:
- Rimossi riferimenti a `kartik\select2\Select2Asset` e `kartik\daterange\DateRangePickerAsset`
- Semplificato le dipendenze dell'asset bundle
- Usato solo `yii\web\YiiAsset` e `frontend\assets\AppAsset`

### 3. Widget Kartik Non Disponibili
**Problema**: Uso di widget Kartik (Select2, DatePicker) non installati

**Soluzione**:
- Sostituiti tutti i widget Select2 con `dropDownList()` standard
- Sostituiti tutti i DatePicker con `input('date')` HTML5
- Aggiornato StatisticsFilter per non dipendere da estensioni esterne

### 4. Conflitto Metodo render()
**Problema**: Conflitto nel widget StatisticsFilter con metodo parent

**Soluzione**:
- Rinominato metodo `render()` in `renderTemplate()`
- Aggiornata chiamata nel metodo `run()`

## 🔧 Modifiche Apportate

### File Modificati
1. **frontend/assets/StatisticsAsset.php**
   - Rimosso dipendenze Bootstrap4 e Kartik
   - Aggiunto Bootstrap 5 e Chart.js via CDN

2. **frontend/widgets/StatisticsFilter.php**
   - Sostituiti widget Kartik con HTML standard
   - Risolto conflitto metodo render()

3. **frontend/views/statistics/treatments.php**
   - Sostituito Select2 e DatePicker con componenti standard

4. **frontend/controllers/StatisticsController.php**
   - Migliorata gestione errori con dati fallback

## ✅ Test di Verifica

Il modulo è stato testato e verificato:
- ✅ Database connesso
- ✅ Viste materializzate funzionanti (8 pazienti, 11 trattamenti)
- ✅ Tutti i services caricano correttamente
- ✅ Search models validano correttamente
- ✅ Nessun errore nei log

## 🌐 Accesso al Modulo

**URL**: `http://localhost/TherapyCRM/statistics`

**Requisiti**:
- Utente autenticato
- Permesso RBAC: `view_statistics`

## 📊 Funzionalità Disponibili

### Dashboard Principale
- Cards riassuntive (pazienti, assenze, trattamenti, piani)
- Grafici crescita pazienti
- Top 5 trattamenti
- Link rapidi alle analisi

### Sezioni Disponibili
1. **Assenze** (`/statistics/absences`)
   - Heatmap orari x giorni
   - Filtri avanzati
   - Statistiche per motivo

2. **Pazienti** (`/statistics/patients`)
   - Demografia (età, genere)
   - Trattamenti multipli
   - Distribuzione per regime

3. **Trattamenti** (`/statistics/treatments`)
   - Ranking per popolarità
   - Ricerca combinazioni
   - Analisi ore settimanali

4. **Piani** (`/statistics/plans`)
   - Stati e durate
   - Piani in scadenza
   - Tassi completamento

## 🔍 Diagnostica

### Verifica Viste Database
```sql
SELECT COUNT(*) FROM statistics_patients_mv;
SELECT COUNT(*) FROM statistics_absences_mv;
SELECT COUNT(*) FROM statistics_treatments_mv;
```

### Verifica Permessi
```php
Yii::$app->user->can('view_statistics')
```

### Log Errori
```bash
tail -f frontend/runtime/logs/app.log
```

## 🚨 Problemi Comuni

### 1. Pagina Non Carica
- Verificare permessi utente
- Controllare database connesso
- Verificare viste materializzate esistono

### 2. Grafici Non Appaiono
- Verificare Chart.js caricato via CDN
- Controllare console JavaScript per errori
- Verificare endpoints AJAX funzionanti

### 3. Filtri Non Funzionano
- Verificare JavaScript caricato
- Controllare form submission
- Verificare parametri URL

### 4. Export Fallisce
- Verificare PhpSpreadsheet installato
- Controllare permessi directory runtime
- Verificare memoria PHP sufficiente

## 📈 Performance

### Cache
- Dashboard: 1 ora TTL
- Dettagli: 15 minuti TTL
- Invalidazione automatica con tag

### Ottimizzazioni Database
- Viste materializzate per query complesse
- Indici su campi filtrabili
- Query ottimizzate con join efficaci

### Frontend
- Bootstrap 5 responsive
- Chart.js per grafici performanti
- CSS/JS minificati in produzione 