# Revamp statistiche (mail cliente, settembre 2026)

Branch: `stats_calendario`

## Settings

- [x] Classificazione Interno/Esterno (`setting.location_type`, esterni: Domiciliare e Scuola), migration `m260924_120000_add_location_type_to_setting`
- [ ] Nuovo setting "Regime misti" (interno di default)
- [ ] Rimuovere ABA SP, ABA PT, ABA RBT

## Piani terapeutici

- [x] Tipologia nuovo/rinnovo (`plan_type`) e piano rinnovato (`renewal_of_id`), migration `m260924_150000_add_plan_type_and_renewal_of_to_therapeutic_plans` con recupero dei piani esistenti
- [x] Form: radio tipologia + select piani del paziente (AJAX `get-patient-plans`)
- [x] View: link "rinnovo di" / "rinnovato da"; index: colonna e filtro tipologia
- [x] Aggiornati `renew()` e TestDataController
- [ ] Test in browser di form, view e index
- [ ] Migration eseguite (codice e migration piani vanno rilasciati insieme)

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
