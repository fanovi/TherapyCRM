# Comunicazione tra EA360 e APP — flussi delle chiamate

> **Documento di proposta.** Descrive esclusivamente i **flussi di comunicazione** tra EA360 e APP: chi chiama chi, con quale metodo e quando. Campi, payload, codici di errore e strategia di riconciliazione sono nell'analisi tecnica allegata e non vengono ripetuti qui.

**Versione:** 2.0 · **Data:** 2026-09-04 · **Stato:** da validare con EA360

Sostituisce la proposta v1.0 del 2026-07-14 ("piattaforma esterna B"): stessi contenuti, nome reale della piattaforma e taglio limitato ai soli flussi. Lo schema grafico degli stessi flussi è allegato alla pagina.

---

## 1. Ruoli

| Attore | Ruolo |
| --- | --- |
| **APP** | Espone le API: Authorization Server (emette i token) e Resource Server (serve i dati). Invia il webhook di chiusura appuntamento. |
| **EA360** | Client: chiama le API di APP e riceve il webhook su un proprio endpoint. |

## 2. Legenda

| Simbolo | Significato |
| --- | --- |
| **→** | Chiamata API (EA360 → APP) |
| **⇠** | Risposta API (APP → EA360) |
| **⇢** | Webhook (APP → EA360) |

## 3. Convenzioni comuni

Base path `/v1`, formato JSON in richiesta e risposta, token in header `Authorization: Bearer`, date ISO-8601 con offset (`Europe/Rome`), letture paginate (`page`, `per_page`, massimo 200 elementi).

---

## Flusso 0 — Autenticazione

| Dir. | Chiamata |
| --- | --- |
| → | `POST /oauth/token` — grant `client_credentials`, credenziali in `Authorization: Basic` |
| ⇠ | `access_token` (JWT RS256), `expires_in: 3600`, `scope` |

Il token accompagna ogni chiamata dei flussi successivi. Non esistono refresh token: alla scadenza EA360 ne richiede un altro. Gli scope sono separati per ambito (`patients:write`, `therapists:write`, `plans:write`, `appointments:read`, `reference:read`).

---

## Flusso 1 — Sincronizzazione iniziale di pazienti e terapisti · **EA360 → APP**

| Dir. | Chiamata | Cosa fa |
| --- | --- | --- |
| → | `POST /v1/patients/batch` · `POST /v1/therapists/batch` | Carico massivo delle anagrafiche, lotti fino a 200 record |
| ⇠ | | Esito **record per record**: un errore singolo non blocca il lotto |

Il carico iniziale può essere eseguito anche con le chiamate singole dei flussi 2 e 3, richiamate in sequenza: la variante a lotti serve solo a ridurre il numero di round-trip quando i volumi lo richiedono.

Ogni scrittura è idempotente, quindi il carico è **ripetibile in sicurezza**: un secondo passaggio aggiorna i record già presenti senza crearne di nuovi.

**Ordine richiesto:** prima pazienti e terapisti, poi i piani terapeutici (flusso 4).

---

## Flusso 2 — Anagrafica pazienti · **EA360 → APP**

| Dir. | Chiamata | Quando |
| --- | --- | --- |
| → | `POST /v1/patients` | EA360 crea o modifica un paziente |
| → | `POST /v1/patients/{external_id}/deactivate` | EA360 disattiva un paziente |
| → | `GET /v1/patients/{external_id}` | EA360 verifica come il paziente risulta su APP |

Create e update sono la **stessa chiamata** (upsert per `external_id`): se il record esiste viene aggiornato, altrimenti creato. Un reinvio non produce doppioni.

Nessuna cancellazione fisica: la disattivazione rende il record non attivo e lo storico resta. Su APP il paziente è **di sola lettura**, quindi non esiste un flusso anagrafico di ritorno verso EA360.

---

## Flusso 3 — Anagrafica terapisti · **EA360 → APP**

| Dir. | Chiamata | Quando |
| --- | --- | --- |
| → | `POST /v1/therapists` | EA360 crea o modifica un terapista |
| → | `POST /v1/therapists/{external_id}/deactivate` | EA360 disattiva un terapista |

Stessa logica di upsert del flusso 2. Al primo create APP crea anche l'account applicativo e assegna il ruolo: le **credenziali di accesso sono generate internamente da APP**, non transitano da o verso EA360 e non compaiono in nessun payload.

La disattivazione blocca immediatamente l'accesso; gli appuntamenti storici restano invariati.

---

## Flusso 4 — Piani terapeutici · **EA360 → APP**

| Dir. | Chiamata | Quando |
| --- | --- | --- |
| → | `POST /v1/therapeutic-plans` | EA360 crea o modifica un piano: **testata + righe di terapia** in un unico payload |
| → | `POST /v1/therapeutic-plans/{external_id}/deactivate` | EA360 chiude un piano |

Il piano viaggia **completo**: le righe di terapia (tipo trattamento, ore settimanali, setting) sono figlie della testata e ne seguono il ciclo di vita.

**Dipendenza:** un piano che cita un paziente o un terapista non ancora sincronizzato viene rifiutato con `422` e il dettaglio di **quale** riferimento manca; EA360 sincronizza e ripete (la chiamata è idempotente). APP non crea pazienti o terapisti impliciti a partire da un piano.

---

## Flusso 5 — Appuntamenti del piano terapeutico · **EA360 → APP (lettura)**

| Dir. | Chiamata | Cosa restituisce |
| --- | --- | --- |
| → | `GET /v1/therapists/{external_id}/appointments?from=…&to=…` | Calendario del terapista nel periodo |
| → | `GET /v1/therapeutic-plans/{external_id}/appointments` | Appuntamenti derivati dalle righe di quel piano |
| ⇠ | | Elenco paginato, ogni voce con paziente e piano di riferimento |

Gli appuntamenti sono creati e gestiti da APP: EA360 li **legge soltanto**.

Sono esposti unicamente gli **appuntamenti del piano terapeutico**. Sedute private, ricorrenze, sostituzioni e assenze del terapista restano gestione interna e non compaiono in questi elenchi.

Ogni appuntamento è identificato dal **numero interno di APP**: non ha un codice EA360.

---

## Flusso 6 — Chiusura di un appuntamento del piano terapeutico · **APP → EA360**

| Dir. | Chiamata | Quando |
| --- | --- | --- |
| ⇢ | `POST <endpoint EA360>` — evento `appointment.completed` | Il terapista chiude l'appuntamento su APP |

Un unico evento copre **tre esiti**:

| Esito | `outcome` |
| --- | --- |
| Visita effettuata | `visit_done` |
| Assenza paziente giustificata | `patient_absent`, con blocco assenza `justified: true` |
| Assenza paziente non giustificata | `patient_absent`, con blocco assenza `justified: false` |

L'assenza del **terapista** non genera evento: non è una chiusura dal punto di vista del paziente.

Ogni notifica porta un `event_id` univoco (chiave di deduplica per EA360) ed è firmata in HMAC-SHA256. Se EA360 non risponde `2xx`, APP riprova **5 volte a intervalli crescenti** (~1 min, 5 min, 30 min, 2 h, 6 h); dopo l'ultimo tentativo l'evento finisce in coda d'errore con alert.

L'esito nasce su APP perché l'appuntamento è un dato di APP: è il terapista che lo chiude nel calendario.

---

## Flusso 7 — Dizionari di riferimento · **EA360 → APP (lettura)**

| Dir. | Chiamata |
| --- | --- |
| → | `GET /v1/reference/regimes` · `/treatment-types` · `/settings` · `/specializations` · `/districts` |
| ⇠ | Elenco `code` + `name` |

EA360 usa questi codici nelle scritture dei flussi 1–4. Un codice inesistente produce `404` con il campo in errore: EA360 riallinea i dizionari e ripete. Cambiano di rado, conviene tenerli in cache.

---

## Riepilogo

| # | Flusso | Direzione | Tipo |
| --- | --- | --- | --- |
| 0 | Autenticazione | EA360 → APP | API |
| 1 | Sincronizzazione iniziale pazienti e terapisti | EA360 → APP | API scrittura (lotti) |
| 2 | Anagrafica pazienti | EA360 → APP | API scrittura + lettura di verifica |
| 3 | Anagrafica terapisti | EA360 → APP | API scrittura |
| 4 | Piani terapeutici | EA360 → APP | API scrittura |
| 5 | Appuntamenti del piano terapeutico | EA360 → APP | API lettura |
| 6 | Chiusura appuntamento del piano terapeutico | APP → EA360 | Webhook |
| 7 | Dizionari di riferimento | EA360 → APP | API lettura |

## Cosa serve da EA360

- L'**endpoint** su cui ricevere il webhook del flusso 6.
- Gli **IP** da cui EA360 chiamerà, per l'allowlist.

---

_Fine documento — v2.0._
