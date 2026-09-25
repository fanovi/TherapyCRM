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

- [ ] Scheda "Nuovi piani" mensile (piani `new` con start_date nel mese corrente)
- [ ] Pazienti in carico con drill-down Regime → Settings → Trattamenti
- [ ] Appuntamenti di oggi con drill-down Regime → Settings → Trattamenti
- [ ] Conteggio appuntamenti interni/esterni

## Dashboard statistiche (mese corrente)

- [ ] Pazienti attivi con drill-down
- [ ] Scheda "Trattamenti attivi" sostituita da "Assenze/Presenze" con drill-down
- [ ] Click sulla scheda Piani → pagina analisi piani

## Analisi assenze

- [ ] Dettaglio singola assenza (chi, quando, motivo) per pazienti e terapisti
- [ ] Checkbox "solo piani in corso" (default on; off = split piani vecchi/in corso)
- [ ] Appuntamenti previsti per regime
- [ ] Assenze terapisti per coordinamento (gruppo) con filtro

## Decisioni aperte

- [ ] Recupero piani esistenti separato per regime (cambio regime = piano nuovo): da confermare
