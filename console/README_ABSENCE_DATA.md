# Generatore Dati Assenze - TherapyCRM

## Descrizione

Lo script `AbsenceDataController` è un controller console che permette di generare dati di assenza realistici per il sistema TherapyCRM. È stato progettato per popolare il database con assenze di esempio sia per terapisti che per pazienti, utile per testing e demo delle funzionalità statistiche.

## Caratteristiche

### Assenze Terapisti

- **Tipi supportati**: Ferie, Malattia, Personale, Formazione, Altro
- **Distribuzione realistica**:
  - Ferie (40%): media 7 giorni
  - Malattia (30%): media 3 giorni
  - Personale (15%): media 1 giorno
  - Formazione (10%): media 2 giorni
  - Altro (5%): media 1 giorno
- **Motivi specifici** per ogni tipo di assenza
- **Auto-approvazione** per dati di esempio

### Assenze Pazienti

- **Modifica status appuntamenti** esistenti
- **Distribuzione**: 70% giustificate, 30% non giustificate
- **Tasso di assenza**: ~5% degli appuntamenti totali
- **Motivi realistici** per assenze giustificate

## Utilizzo

### Comandi Principali

```bash
# Genera assenze per tutto l'anno corrente
./yii absence-data/generate-year

# Genera assenze per un mese specifico (1-12)
./yii absence-data/generate-month 3

# Genera assenze per un periodo personalizzato
./yii absence-data/generate-period 2024-06-01 2024-08-31

# Mostra anteprima di cosa verrà generato
./yii absence-data/preview

# Mostra statistiche delle assenze correnti
./yii absence-data/stats
```

### Comandi di Pulizia

```bash
# Rimuove TUTTE le assenze dal sistema
./yii absence-data/clear-all

# Rimuove assenze in un periodo specifico
./yii absence-data/clear-period 2024-01-01 2024-12-31

# Mostra help completo
./yii absence-data/help
```

## Esempi di Output

### Generazione Annuale

```
🚀 Generazione assenze per l'anno 2024...

1️⃣ Generazione assenze terapisti...
   ✅ Generate 180 assenze per 12 terapisti

2️⃣ Generazione assenze pazienti...
   ✅ Modificati 45 appuntamenti per 32 pazienti

🎉 GENERAZIONE COMPLETATA
========================
📊 Periodo: 2024-01-01 - 2024-12-31
👨‍⚕️ Assenze terapisti: 180
🧑‍⚕️ Assenze pazienti: 45
📈 Totale: 225
```

### Statistiche

```
📊 STATISTICHE ASSENZE CORRENTI
===============================

👨‍⚕️ ASSENZE TERAPISTI (2024)
   Vacation: 72 (504 giorni totali)
   Sick leave: 54 (162 giorni totali)
   Personal: 27 (27 giorni totali)
   Training: 18 (36 giorni totali)
   Other: 9 (9 giorni totali)
   TOTALE: 180 assenze, 738 giorni

🧑‍⚕️ ASSENZE PAZIENTI (2024)
   Giustificate: 32
   Non giustificate: 13
   TOTALE: 45

📈 Tasso assenze pazienti: 4.85%
```

## Logica di Generazione

### Terapisti

1. **Selezione attiva**: Solo terapisti con `is_active = 1`
2. **Distribuzione annuale**: 8-25 assenze per terapista
3. **Proporzione periodo**: Scalata in base alla durata del periodo
4. **Tipi pesati**: Selezione casuale basata sui pesi configurati
5. **Date realistiche**: Distribuzione uniforme nel periodo
6. **Durata variabile**: ±2 giorni dalla media per tipo

### Pazienti

1. **Appuntamenti eligibili**: Solo `scheduled` e `completed`
2. **Tasso target**: 5% degli appuntamenti totali
3. **Selezione casuale**: Mischia appuntamenti per distribuzione uniforme
4. **Status update**: Modifica in `absent_justified` o `absent_not_justified`
5. **Note automatiche**: Aggiunge motivo dell'assenza

## Sicurezza

### Controlli Preventivi

- **Verifica dati esistenti**: Avvisa se ci sono già assenze nel periodo
- **Conferma utente**: Richiede conferma per operazioni di massa
- **Transazioni**: Rollback automatico in caso di errore
- **Validazione date**: Controllo formato e coerenza

### Operazioni Reversibili

- **Clear commands**: Possibilità di rimuovere dati generati
- **Backup consigliato**: Sempre fare backup prima di operazioni massive
- **Log dettagliati**: Output completo delle operazioni

## Configurazione

### Personalizzazione Pesi

Modifica l'array `$therapistAbsenceTypes` in `AbsenceDataController.php`:

```php
private $therapistAbsenceTypes = [
    Absence::TYPE_VACATION => [
        'weight' => 40,     // Percentuale sul totale
        'avg_days' => 7,    // Durata media
        'reasons' => ['...'] // Motivi possibili
    ],
    // ...
];
```

### Motivi Assenze Pazienti

Modifica l'array `$patientAbsenceReasons`:

```php
private $patientAbsenceReasons = [
    'justified' => ['Malattia', 'Visita medica', '...'],
    'not_justified' => ['', '', ''] // Vuoti per non giustificate
];
```

## Requisiti Sistema

- **Yii2**: Framework installato e configurato
- **Database**: MySQL con tabelle `absences`, `appointments`, `therapists`, `patients`
- **Permessi**: Accesso console con permessi di scrittura database
- **Dati esistenti**: Terapisti attivi e appuntamenti nel periodo target

## Best Practices

### Testing

1. **Ambiente sviluppo**: Utilizzare solo in dev/staging
2. **Backup database**: Prima di generazioni massive
3. **Test graduali**: Iniziare con periodi piccoli
4. **Verifica risultati**: Controllare statistiche dopo generazione

### Performance

1. **Periodi limitati**: Per grandi dataset, generare per mesi
2. **Monitoraggio memoria**: Per dataset molto grandi
3. **Batch processing**: Lo script gestisce automaticamente transazioni

### Manutenzione

1. **Pulizia periodica**: Rimuovere dati di test obsoleti
2. **Aggiornamento pesi**: Adattare alle statistiche reali
3. **Nuovi motivi**: Aggiungere motivi specifici del dominio

## Troubleshooting

### Errori Comuni

**"Nessun terapista attivo trovato"**

- Verificare che esistano terapisti con `is_active = 1`
- Controllare dati in tabella `therapists`

**"Nessun appuntamento nel periodo"**

- Verificare esistenza appuntamenti nel range di date
- Controllare status appuntamenti (non cancellati)

**"Errore validazione date"**

- Utilizzare formato `YYYY-MM-DD`
- Verificare che data inizio < data fine

**"Rollback transazione"**

- Controllare log errori Yii
- Verificare permessi database
- Controllare integrità referenziale

### Debug

```bash
# Abilitare debug Yii per dettagli errori
export YII_DEBUG=1

# Controllare log applicazione
tail -f frontend/runtime/logs/app.log
```

## Integrazione Dashboard

I dati generati sono immediatamente visibili in:

- **Statistiche Assenze** (`/statistics/absences`)
- **Dashboard principale** (metriche assenze)
- **Calendari terapisti** (periodi di assenza)
- **Report export** (Excel con dati completi)

## Changelog

- **v1.0**: Versione iniziale con generazione base
- **v1.1**: Aggiunta distribuzione realistica e motivi specifici
- **v1.2**: Supporto periodi personalizzati e statistiche dettagliate
