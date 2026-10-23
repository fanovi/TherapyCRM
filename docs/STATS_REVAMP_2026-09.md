# Revamp statistiche (mail cliente, settembre 2026)

Branch: `stats_calendario`

## Settings

- [x] Classificazione Interno/Esterno (`setting.location_type`, esterni: Domiciliare e Scuola), migration `m260924_120000_add_location_type_to_setting`
- [x] Nuovo setting "Regime misto" (interno) legato al regime ABA (id ricavato per nome), migration `m260925_100000_add_regime_misti_setting` (da eseguire dopo quella di `location_type`)
- [ ] Rimuovere ABA SP (id 7), ABA PT (id 8), ABA RBT (id 9) — **in attesa**: in prod sono usati (642 terapie di cui 356 su piani attivi, 144 pattern, 52 appuntamenti futuri); il cliente li corregge a mano, poi si procede
  - [ ] Avvisare il cliente su cosa correggere
  - [ ] Ricontrollare in prod che non siano più usati su piani attivi, pattern e appuntamenti futuri
  - [ ] Decidere come trattare lo storico (disattivazione vs cancellazione) e gestire le select di form piano e modale modifica appuntamento

## Calendario

- [x] Cache `getSettings()` in `api.ts` separata per regime (prima la prima lista caricata valeva per tutte le modali)
- [x] Test in browser del fix cache settings

## Piani terapeutici

- [x] Tipologia nuovo/rinnovo (`plan_type`) e piano rinnovato (`renewal_of_id`), migration `m260924_150000_add_plan_type_and_renewal_of_to_therapeutic_plans` con recupero dei piani esistenti
- [x] Form: radio tipologia + select piani del paziente (AJAX `get-patient-plans`)
- [x] View: link "rinnovo di" / "rinnovato da"; index: colonna e filtro tipologia
- [x] Aggiornati `renew()` e TestDataController
- [x] Test in browser di form, view e index
- [x] Migration eseguite (codice e migration piani vanno rilasciati insieme)

## Dashboard principale (dati odierni)

- [x] Scheda "Nuovi piani" mensile al posto di "Nuovi pazienti": piani `new` con start_date nel mese corrente (anche futura), bozze escluse, stato attuale ignorato; riga "· N rinnovi"; variazione % sugli stessi giorni (1..oggi) del mese precedente. Conteggio in `StatisticsService::countPlansByType()` (scope `ofType()`/`notDraft()`)
  - [x] Stessa definizione in `/statistics`: `getPlansSummary()['new_this_month']` e trend "Nuovi piani" di `/statistics/plans` (prima per `created_at`, tutte le tipologie)
  - [x] Comando dati di prova `yii test-data/generate-plan-stats yes` / `clear-plan-stats yes` (piani marcati `[TEST_STATS_NUOVI_PIANI]`)
  - [x] Test in browser (09-25, dati di prova): dashboard 4 nuovi / +2 rinnovi (non inclusi) / +50%; trend `/statistics/plans` con "Tutti gli stati" = query attesa su 12 mesi; `/statistics` e `/statistics/absences` senza errori
  - [x] Trend "Nuovi piani" di `/statistics/plans`: ignora il filtro Stato (gli altri filtri restano), titolo e nota sotto il grafico spiegano cosa conta; testato in browser con il filtro di default
- [x] Pazienti in carico con drill-down Regime → Settings → Trattamenti (09-25)
  - [x] Clic sulla scheda: pannello sotto il Riepilogo, all'apertura solo i regimi; pazienti distinti a ogni livello (nota nel pannello); ordine per numero decrescente; niente barre, solo il numero
  - [x] Setting con etichetta Interno/Esterno; riga "Senza terapie" sotto il regime per i piani attivi senza terapie
  - [x] Clic sul trattamento: elenco pazienti (Paziente con link se `view_patient`, Piano, Scadenza, Ore/sett.)
  - [x] `StatisticsService::patientsInChargePlansQuery()` = unica definizione di "in carico", usata anche dal totale della scheda
  - [x] Azioni JSON `site/patients-in-charge-breakdown` e `site/patients-in-charge-list` (403 senza `view_statistics`)
  - [x] JS generico `frontend/web/js/drilldown-panel.js`, da riusare per Appuntamenti di oggi
  - [x] `statistics.css` e `drilldown-panel.js` caricati con `appendTimestamp` (prima il browser teneva il CSS vecchio in cache)
  - [x] Test in browser (09-25, DB locale): totale 18 = L11 10 + FKT 377 8; per ogni trattamento l'elenco ha tante righe quanto il conteggio; Espandi/Comprimi ok; nessun errore in console
  - [x] Da provare con un utente senza `view_patient` (nomi senza link) e senza `view_statistics` (403) — fatto 10-23 (vedi Correzioni collaterali)
- [x] Appuntamenti di oggi con drill-down Regime → Settings → Trattamenti (10-23)
  - [x] Conta gli appuntamenti previsti (programmati + completati, il denominatore della scheda): `StatisticsService::expectedAppointmentsQuery()`; una seduta di gruppo = una riga per paziente
  - [x] Setting e trattamento dell'appuntamento, regime dal piano; primo livello anche "Cicli privati" e "Senza piano" se presenti
  - [x] Clic sul trattamento: elenco appuntamenti (Ora, Paziente con link se `view_patient` e "(gruppo)", Terapista, Stato)
  - [x] Azioni JSON `site/today-appointments-breakdown` e `site/today-appointments-list` (403 senza `view_statistics`); `drilldown-panel.js` con opzione `emptyListText`
  - [x] Test in browser (10-23, dati di prova `[TEST_STATS_APPUNTAMENTI_OGGI]`): scheda 1/5, 3 interni · 2 esterni; FKT 377 2 (gruppo), L11 2 (Ambulatoriale + Domiciliare), Cicli privati 1 (Scuola); elenchi coerenti coi conteggi; nessun errore in console
  - [x] Da provare con un utente senza `view_patient` e senza `view_statistics` (insieme a Pazienti in carico) — fatto 10-23 (vedi Correzioni collaterali)
- [x] Conteggio appuntamenti interni/esterni: riga "N interni · M esterni" nella scheda Appuntamenti di oggi, sui previsti (`countExpectedAppointmentsByLocation()`)

## Dashboard statistiche (mese corrente)

- [x] Pazienti attivi con drill-down (10-23)
  - [x] Mese corrente: pazienti con un piano il cui periodo tocca il mese, qualunque stato tranne bozza e in attesa (`StatisticsService::activePatientsPlansQuery()`/`countActivePatients()`); prima era "piano attivo oggi", uguale a Pazienti in carico
  - [x] Stesso pannello di Pazienti in carico: dettaglio ed elenco resi parametrici sulla query dei piani (`buildPatientsBreakdown()`/`buildPatientsList()`); azioni `statistics/active-patients-breakdown` e `-list`
- [x] Scheda "Trattamenti attivi" sostituita da "Presenze / Assenze" con drill-down (10-23)
  - [x] Mese corrente fino a oggi: presenze = appuntamenti completati, assenze = assenze del paziente (giustificate e non); assenze terapisti escluse; percentuale di presenza e numero di giustificate
  - [x] Pannello con due colonne Presenze/Assenze (`counts` nei nodi, classe `has-split-counts` in `drilldown-panel.js`/`statistics.css`); elenco con Data, Ora, Paziente, Terapista, Stato; azioni `statistics/attendance-breakdown` e `-list`
  - [x] Tolta la scheda "Assenze Questo Mese" (doppione con numeri diversi); rimossi `getAbsencesSummary()` e `getTreatmentsSummary()`, non più usati; riepilogo su 3 schede
- [x] Click sulla scheda Piani → pagina analisi piani: "Piani in Scadenza" è un link a `/statistics/plans`
- [x] Righe degli elenchi in `common\helpers\DrilldownHelper` (usato anche da SiteController)
- [x] Test in browser (10-23, DB locale): Pazienti attivi 19 = 17 attivi + 1 sospeso + 1 interrotto, elenchi coerenti; Presenze/Assenze 1/1 con dettaglio ed elenco; link piani ok; pannelli della dashboard principale invariati; nessun errore in console
- [x] Da provare con un utente senza `view_patient` / `view_statistics` (insieme ai pannelli della dashboard principale) — fatto 10-23 (vedi Correzioni collaterali)

## Analisi assenze

- [x] Dettaglio singola assenza (chi, quando, motivo) per pazienti e terapisti (10-23)
  - [x] Nuova sezione "Elenco assenze" (prima la pagina non aveva righe singole): 25 per pagina, filtri della pagina, una riga per evento come la scheda e l'export (seduta di gruppo = una riga con tutti i pazienti, `getAbsenceEventsQuery()`); clic/Invio su una riga apre il dettaglio (`statistics/absence-detail`)
  - [x] Terapisti: da `absences` (periodo, tipo, motivo, note, inserita da/il, approvata da/il); sostituzione: a chi è passato l'appuntamento
  - [x] Pazienti: chi/quando dall'ultimo `activity_log` che porta lo stato in assenza (in prod dal 23/02/2026, 6191 righe), motivo e note dalla nota sull'appuntamento (formati app e gestionale); prima di febbraio solo il motivo
- [x] Checkbox "solo piani in corso" (default on; off = split piani vecchi/in corso) (10-23)
  - [x] In corso = piano attivo oggi (come Pazienti in carico); cicli privati sempre inclusi; colonna `plan_in_progress` nell'unione di `getBaseAbsencesQuery()`
  - [x] Senza spunta: "N su piani in corso · M su piani non in corso (· K senza piano)" nella scheda Eventi di assenza ("non in corso" = conclusi, interrotti, sospesi; prima diceva "conclusi")
- [x] Appuntamenti previsti per regime (10-23): la scheda "Slot previsti" diventa "Appuntamenti previsti" (gruppo = un appuntamento per paziente) con pannello Regime → Setting → Trattamento; il tasso di assenza resta sugli slot (indicati nella scheda)
  - [x] `AbsenceStatisticsService::plannedAppointmentsQuery()` unica per scheda, ore previste e pannello; `StatisticsService::buildAppointmentsBreakdown()`/`buildAppointmentsList()` pubblici
- [x] Assenze terapisti per coordinamento (gruppo) con filtro (10-23)
  - [x] Filtro "Coordinamento" su tutta la pagina: assenze dei terapisti del gruppo e assenze dei pazienti seguiti da loro; appartenenza alla data dell'assenza (`group_therapists.assigned_from/to`)
  - [x] Tabella "Assenze terapisti per coordinamento" (eventi, terapisti assenti, ore perse) + riga "Senza coordinamento"
- [x] Export Excel allineato all'elenco (10-23): stessi eventi e ordine, motivo reale (nota per i pazienti, assenza per i terapisti), nuove colonne Setting, Piano (in corso/concluso/ciclo privato), Inserita da/il (lette in blocco), data gg/mm/aaaa e "Cognome Nome"; verificato rileggendo il file generato (98 righe = scheda)
- [x] Test in browser (10-23, dati di prova `[TEST_STATS_ANALISI_ASSENZE]`): dettaglio paziente e terapista, divisione 28 in corso / 70 conclusi, paginazione che mantiene i filtri, filtro Gruppo 3 con scheda e pannello coerenti (58), nessun errore in console

## Correzioni collaterali

- [x] Calcolo "mese scorso"/"-N mesi" sbagliato dal 29 al 31 del mese (`strtotime('-1 month')`): corretto con `first day of` in SiteController, StatisticsService, AbsenceStatisticsService, StatisticsController
- [ ] Stesso bug in `frontend/views/complaint/index.php:48` (fuori dalle statistiche, non toccato)
- [x] Rimosso `TherapeuticPlanQuery::renewed()` (usava `STATUS_RENEWED` inesistente, nessun chiamante)
- [x] `TestDataController::generateTherapeuticPlans()` imposta `plan_type`
- [x] Manuale (`manuale-gestionale.php`, `docs/MANUALE_UTENTE.md`) (10-23): riscritte §2 Dashboard (schede attuali + uso del pannello di dettaglio), §5 Piani (tipologia nuovo/rinnovo, setting Interno/Esterno e Regime misto senza elenco fisso, rinnovo), §10 Statistiche (dashboard del mese, Analisi assenze completa con export, trend Nuovi piani); tolte voci superate (Terapisti attivi, Ore settimanali, Piccolo Gruppo); ABA SP/PT/RBT non citati; testo senza accenti come il resto
  - [ ] Screenshot `img-03`, `img-18`, `img-19` (cartella `img/manuale/gestionale` assente: oggi solo segnaposto, descrizioni aggiornate)

- [x] Test permessi (10-23, via `curl` sul DB locale): utente di prova con solo `platform_login` + `view_statistics` → tutti gli endpoint dei pannelli e `absence-detail` 200 con `url` paziente null, export 403 e pulsante assente (manca `export_data`); `coordinator1` (senza `view_statistics`) → dashboard di benvenuto, tutti gli endpoint e le pagine statistiche 403. Utente di prova cancellato. Non fatto il controllo visivo in browser (la password locale di Admin1 non è quella del seed)

## Revisione del codice (10-23, `/code-review high` sul diff non committato)

- [x] Pazienti attivi: per i piani interrotti la fine è `termination_date` (prima contavano fino alla `end_date` prevista): `plansInPeriodQuery()` usa `COALESCE(termination_date, end_date)`; in locale 19 → 18
- [x] "Inserita da/il" delle assenze paziente: `activity_log` cercato con `entity_name` sia `Appuntamento` (behavior) sia `Appointment` (riga esplicita del gestionale, `AbsenceController.php:1225`), costante `APPOINTMENT_LOG_ENTITY_NAMES`
- [x] Interni/esterni della scheda Appuntamenti di oggi: LEFT JOIN su setting e voce "senza setting" se > 0, così la somma torna col totale
- [x] Motivo nell'export = motivo nel dettaglio (`reasonFromNote()`: motivo, altrimenti nota intera, altrimenti "Non indicato"; motivo vuoto → "Non indicato")
- [x] Tolto `orderBy` morto in `actionPlannedAppointmentsList` (sovrascritto da `buildAppointmentsList()`)
- [x] Etichetta "Concluso" → "Non in corso" (export, scheda, hint, manuali)
- [x] Duplicazioni: unica mappatura filtri `AbsenceStatisticsSearch::toFilters()` (tolto `extractFilters` del service, quello del controller delega); nomi utente in un solo punto (`userNames()` su `UserProfile::getFullName()`), `therapistName()` via `Therapist`; opzioni coordinamento via `CoordinatorGroup`
- [ ] Rimandato: checkbox "Solo piani in corso" riferita a oggi anche su periodi passati (conforme alla mail, ma un'analisi di un anno fa con la spunta attiva mostra quasi zero assenze) — da segnalare al cliente o nota in pagina
- [ ] Rimandato: prestazioni Analisi assenze (query base a tre rami eseguita ~9 volte per caricamento, +3 con elenco e coordinamento) — misurare sul server di test con dati reali
- [x] Riverificato dopo le correzioni: CLI (conteggi, motivo, log `Appointment`, toFilters, export 98 righe con "Non in corso") e browser (dashboard, `/statistics`, `/statistics/absences` con filtri), nessun errore in console

## Decisioni aperte

- [ ] Recupero piani esistenti separato per regime (cambio regime = piano nuovo): da confermare
