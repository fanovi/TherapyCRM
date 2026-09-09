# Analisi tecnica — Integrazione OAuth2 con la piattaforma esterna "B"

> **Documento di analisi e proposta.** Non contiene codice di implementazione: definisce flussi, struttura delle chiamate e strategia di sincronizzazione dati tra la piattaforma esterna **B** e il nostro sistema. Ogni scelta è presentata come **proposta operativa già decisa** (nessun punto lasciato aperto).

**Versione:** 1.0 · **Data:** 2026-07-10 · **Stato:** proposta per validazione con il gestore di B

---

## Indice

0. [Contesto e scope](#0-contesto-e-scope)
1. [Modello dati esposto](#1-modello-dati-esposto)
2. [Autenticazione OAuth2 (Client Credentials)](#2-autenticazione-oauth2-client-credentials)
3. [Struttura delle chiamate (request/response)](#3-struttura-delle-chiamate)
4. [Strategia di riconciliazione](#4-strategia-di-riconciliazione)
5. [Decisioni e proposte trasversali](#5-decisioni-e-proposte-trasversali)
6. [Modifiche allo schema richieste](#6-modifiche-allo-schema-richieste)

---

## 0. Contesto e scope

### 0.1 Scenario

La piattaforma **B** sincronizza i propri dati anagrafici e clinici verso il nostro sistema tramite chiamate **server-to-server** (nessun utente umano coinvolto). L'autenticazione è **OAuth2 grant Client Credentials**: noi agiamo sia da **Authorization Server** (emissione token) sia da **Resource Server** (validazione e servizio dei dati).

L'integrazione è realizzata come **modulo dedicato e isolato**, con proprio spazio di autenticazione, propri endpoint e proprie credenziali, indipendente da qualsiasi altra interfaccia del sistema.

### 0.2 Direzione dei flussi (chi è la sorgente)

Il punto centrale dell'integrazione è **chi possiede il dato**:

| Entità | Sorgente | Direzione | Operazioni |
|---|---|---|---|
| **Pazienti** | **B** | Inbound (B → noi) | create / update / deactivate + carico iniziale massivo. Read-only sul nostro gestionale |
| **Terapisti** | **B** | Inbound (B → noi) | create / update / deactivate + carico iniziale. Al create **generiamo le credenziali di accesso**. Read-only sul nostro gestionale |
| **Piani terapeutici** | **B** | Inbound (B → noi) | create / update / deactivate + carico iniziale. Read-only sul nostro gestionale |
| **Appuntamenti** | **Noi** | Outbound (noi → B) | Creati e gestiti esclusivamente nel nostro sistema. B li **legge** (calendario per terapista, elenco per piano) |
| **Evento completamento appuntamento** | **Noi** | Outbound push (noi → B) | Ad ogni completamento/assenza notifichiamo **B** su un suo endpoint |
| **Dizionari di riferimento** | **Noi** | Outbound (noi → B) | Read-only: regimi, trattamenti, setting, specializzazioni, distretti |

**Conseguenza per il gestionale interno:** per pazienti, terapisti e piani terapeutici viene **rimossa la gestione anagrafica** (creazione/modifica) dall'interfaccia interna; questi dati diventano di **sola lettura** perché la loro fonte di verità è B.

### 0.3 Fuori scope

- Appuntamenti come dato scritto da B (restano interamente di nostra competenza).
- Cicli di sedute private, pattern di ricorrenza, sostituzioni: gestione interna, non esposta a B.
- Assenze del **terapista** (ferie/malattia): gestione interna.

---

## 1. Modello dati esposto

Questa sezione descrive **solo** le entità e i campi rilevanti per l'integrazione con B.

### 1.1 Entità e relazioni

```
  PAZIENTE (patients)
     │ 1
     │
     │ N
  PIANO TERAPEUTICO (therapeutic_plans)  ── testata: paziente, periodo, regime, distretto
     │ 1
     │
     │ N
  RIGA DI TERAPIA (plan_therapies)  ── tipo trattamento + ore settimanali + setting
     │ 1
     │
     │ N
  APPUNTAMENTO (appointments)  ── data/ora, durata, esito
     │ N                    │ N
     │                      │
     │ 1                    │ 1
  TERAPISTA (therapists)   SPECIALIZZAZIONE / SETTING / TRATTAMENTO
```

**Regola di derivazione del paziente** — un appuntamento **non contiene** un riferimento diretto al paziente: il paziente si ricava a cascata

```
appuntamento → riga di terapia → piano terapeutico → paziente
```

**Un terapista è anche un utente del sistema**: alla creazione di un terapista corrisponde la creazione di un account applicativo con credenziali di accesso (vedi §3.2.2).

### 1.2 Campi delle entità (nomi reali)

#### Paziente (`patients`)

| Campo | Tipo | Note |
|---|---|---|
| `first_name` | string(100) | obbligatorio |
| `last_name` | string(100) | obbligatorio |
| `birth_date` | date | obbligatorio |
| `fiscal_code` | string(16) | **chiave naturale di riconciliazione** |
| `gender` | string(1) | valori validi **solo `M` / `F`** (la validazione rifiuta altri valori; `N` esiste unicamente come default interno e **non** è inviabile da B) |
| `birth_city` | string(100) | testo (denormalizzato) |
| `birth_province_name` | string(100) | testo |
| `birth_province_code` | string(2) | sigla provincia (es. `SA`) |
| `residence_address` | string(255) | |
| `residence_city` | string(100) | |
| `residence_province_name` | string(100) | |
| `residence_province_code` | string(2) | sigla provincia |
| `residence_postal_code` | string(5) | CAP |
| `phone_number` | string(20) | |
| `district_id` → `districts.code` | lookup | distretto/ASL |
| `notes` | text | |

> **Geografia denormalizzata:** i dati di nascita/residenza sono salvati come **stringhe**, non come chiavi esterne. B invia direttamente città + sigla provincia; nessun ID geografico richiesto.

#### Piano terapeutico — testata (`therapeutic_plans`)

| Campo | Tipo | Note |
|---|---|---|
| `patient_id` | rif. paziente | via `external_id`/`fiscal_code` (vedi §4) |
| `start_date` | date | obbligatorio |
| `duration_days` | int | 1–1095 |
| `end_date` | date | **calcolato dal sistema** (start + durata), sola lettura |
| `regime_id` → `regime.nome` | lookup | `L11` / `ABA` / `FKT 377` |
| `protocol_number` | string(50) | **chiave naturale di riconciliazione** |
| `district_id` → `districts.code` | lookup | |
| `status` | enum | `draft`/`pending`/`active`/`suspended`/`completed`/`terminated`/`expired` |
| `notes` | text | |

#### Piano terapeutico — righe di terapia (`plan_therapies`)

| Campo | Tipo | Note |
|---|---|---|
| `therapeutic_plan_id` | rif. piano | figlio della testata (cancellazione a cascata) |
| `treatment_type_id` → `treatment_types.code` | lookup | es. `LOG_IND` |
| `weekly_hours` | decimal(4,2) | ore settimanali |
| `is_group` | bool | terapia di gruppo |
| `setting_id` → `setting.code` | lookup | vedi §6 per `code` |
| `notes` | text | |

#### Terapista (`therapists`)

| Campo | Tipo | Note |
|---|---|---|
| `user_id` | account | **generato da noi** al create (vedi §3.2.2) |
| `specialization_id` → `specializations.code` | lookup | specializzazione principale |
| `weekly_hours_contract` | int | 1–40 |
| `calendar_color` | string(7) | `#RRGGBB` |
| `is_active` | bool | usato per la disattivazione |
| `is_internal` | bool | interno/esterno |
| `can_supervise` | bool | |
| `is_aba` | bool | |

Anagrafica dell'operatore (in `user_profiles`, generata al create): `first_name`, `last_name`, `fiscal_code`, `phone`, `address`. L'account (`users`) porta `email` e `username`.

#### Appuntamento (`appointments`) — sola lettura verso B

| Campo | Tipo | Note |
|---|---|---|
| `id` | int | identificativo interno |
| `appointment_datetime` | datetime | |
| `duration_minutes` | int | 15–180 |
| `status` | enum | `scheduled`/`completed`/`absent_justified`/`absent_not_justified`/`therapist_absent`/`cancelled` |
| `appointment_type` | string | `terapia`/`supervisione`/`parent_training` |
| `appointment_category` | enum | `regular`/`recovery`/`advance`/`extra`/`compensation` |
| `therapist_id` | rif. terapista | |
| `plan_therapy_id` | rif. riga terapia | catena verso piano/paziente |
| `treatment_type_id` / `specialization_id` / `id_setting` | lookup | |
| `notes` | text | motivazione assenza/nota di completamento (testo libero) |

### 1.3 Dizionari di riferimento

Valori con cui B deve "parlare" al posto dei nostri ID interni. Esposti in sola lettura (§3.5).

| Dizionario | Chiave per B | Valori attuali |
|---|---|---|
| `districts` | `code` | `65`, `58`, `000`, … |
| `regime` | `nome` | `L11`, `ABA`, `FKT 377` |
| `treatment_types` | `code` | `LOG_IND`, `NPM_IND`, `FKT_IND`, `TO_IND`, `PSICOT_IND`, `PT`, `SUP`, `RBT`, `TER_STRUM`, … |
| `specializations` | `code` | `LOGOP`, `NPM`, `FISIOT`, `TO`, `PSICOT`, `ABA` |
| `setting` | `code` (da introdurre, §6) | `Ambulatoriale`, `Domiciliare`, `ABA SP/PT/RBT`, `Centro Diurno …`, `Scuola`, … |

> **Nota `setting`:** oggi identificabile solo per nome testuale (mutato nel tempo). La proposta §6 introduce una colonna `code` stabile per rendere robusto il matching.

---

## 2. Autenticazione OAuth2 (Client Credentials)

### 2.1 Attori

- **Client** = piattaforma B (un solo client applicativo, credenziali dedicate).
- **Authorization Server** = nostro sistema (emette i token).
- **Resource Server** = nostro sistema (valida il token ad ogni richiesta e serve i dati).

### 2.2 Credenziali fornite a B

Al gestore di B vengono consegnate (canale sicuro, una tantum):

| Parametro | Descrizione |
|---|---|
| `client_id` | identificativo pubblico del client B |
| `client_secret` | segreto condiviso (rigenerabile/revocabile) |
| `token_url` | endpoint di emissione token |
| Base URL API | radice degli endpoint dati |
| `scope` disponibili | ambiti autorizzati (vedi §2.6) |

### 2.3 Flusso di ottenimento e uso del token

1. B richiede un token al **token endpoint** presentando `client_id` + `client_secret` con `grant_type=client_credentials`.
2. Verifichiamo le credenziali; se valide, **emettiamo un access token** (JWT firmato) con scadenza e scope.
3. B include il token come header `Authorization: Bearer <token>` in **ogni** chiamata alle risorse.
4. Ad ogni richiesta, il Resource Server **valida** il token (firma, scadenza, audience, scope, non-revoca).
5. Alla scadenza, B **richiede un nuovo token** ripetendo il passo 1 (il grant Client Credentials **non prevede refresh token**: non c'è un utente da mantenere loggato).

### 2.4 Diagramma del flusso

```
   Piattaforma B (client)                         Nostro sistema
   ─────────────────────                          ──────────────────────
          │                                                │
          │  (1) POST /oauth/token                         │
          │      grant_type=client_credentials             │
          │      client_id + client_secret + scope         │
          │ ─────────────────────────────────────────────► │
          │                                                │ verifica credenziali
          │                                                │ emette JWT (exp, scope, aud)
          │                                                │ registra token (revocabile)
          │  (2) 200 { access_token, token_type: Bearer,   │
          │           expires_in: 3600, scope }            │
          │ ◄───────────────────────────────────────────── │
          │                                                │
          │  (3) GET/POST /v1/...                           │
          │      Authorization: Bearer <access_token>      │
          │ ─────────────────────────────────────────────► │
          │                                                │ (4) valida token:
          │                                                │     firma RS256, exp,
          │                                                │     audience, scope, non-revoca
          │  risposta risorsa (JSON)                        │
          │ ◄───────────────────────────────────────────── │
          │                                                │
          │  ... alla scadenza del token → torna a (1) ...  │
```

### 2.5 Formato e ciclo di vita del token — **proposta**

| Aspetto | Scelta proposta |
|---|---|
| **Formato** | **JWT firmato RS256** (chiave asimmetrica **dedicata** a questa integrazione, distinta da qualsiasi altra chiave del sistema) |
| **Perché JWT** | auto-verificabile (firma + claim), nessuna lettura DB per la validazione base; la coppia di chiavi dedicata isola completamente questo dominio |
| **Claim** | `iss` (noi), `aud` (`b-integration`), `sub` (`client_id`), `scope`, `iat`, `nbf`, `exp`, `jti` (id univoco token per la revoca) |
| **Durata access token** | **3600 s (1 h)**, configurabile |
| **Refresh token** | **assente** (non previsto dal grant; B ri-richiede un token alla scadenza) |
| **Revoca** | ogni token emesso è registrato lato server (`jti`); una lista di revoca consente di invalidare token prima della scadenza |
| **Trasporto credenziali** | `client_secret` accettato sia via header `Authorization: Basic base64(client_id:client_secret)` sia nel body form-urlencoded |

### 2.6 Scope proposti

Ambiti minimi per rispettare il principio del privilegio minimo:

| Scope | Consente |
|---|---|
| `patients:write` | create/update/deactivate pazienti |
| `therapists:write` | create/update/deactivate terapisti |
| `plans:write` | create/update/deactivate piani terapeutici |
| `appointments:read` | lettura calendario/appuntamenti |
| `reference:read` | lettura dizionari |

### 2.7 Token endpoint

**Request**
```http
POST /oauth/token HTTP/1.1
Host: <nostro-host>
Content-Type: application/x-www-form-urlencoded
Authorization: Basic base64(client_id:client_secret)

grant_type=client_credentials&scope=patients:write%20plans:write%20appointments:read
```

**Response `200 OK`**
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "patients:write plans:write appointments:read"
}
```

**Response `401 Unauthorized`** (credenziali errate)
```json
{ "error": "invalid_client", "error_description": "Client authentication failed" }
```

**Response `400 Bad Request`** (grant/scope non validi)
```json
{ "error": "invalid_scope", "error_description": "Requested scope is not allowed for this client" }
```

---

## 3. Struttura delle chiamate

### 3.0 Convenzioni comuni

| Aspetto | Convenzione |
|---|---|
| **Base path** | `/v1` (versionato) |
| **Formato** | `application/json` in richiesta e risposta (UTF-8) |
| **Autenticazione** | header `Authorization: Bearer <access_token>` su ogni chiamata |
| **Fuso orario** | tutte le date/ora in ISO-8601 con offset (`Europe/Rome`, es. `2026-07-10T15:00:00+02:00`) |
| **Idempotenza scritture** | header `Idempotency-Key: <uuid>` opzionale (vedi §4.3); upsert per `external_id` intrinsecamente idempotente |
| **Paginazione (letture)** | query `page` (1-based) e `per_page` (default 50, max 200); metadati in `meta` |
| **Inviluppo risposta** | successo `{ "success": true, "data": {...} }`; errore `{ "success": false, "error": {...} }` |

**Formato errore standard**
```json
{
  "success": false,
  "error": {
    "code": "UNRESOLVED_REFERENCE",
    "message": "Il piano riferisce un paziente non ancora sincronizzato",
    "details": [
      { "field": "patient.external_id", "value": "B-PAT-987", "reason": "not_found" }
    ]
  }
}
```

**Catalogo codici di stato / errore**

| HTTP | `error.code` | Quando |
|---|---|---|
| 200 | — | lettura o upsert andati a buon fine |
| 201 | — | risorsa creata |
| 400 | `VALIDATION_ERROR` | payload malformato o campi non validi |
| 401 | `UNAUTHORIZED` | token assente/scaduto/non valido |
| 403 | `INSUFFICIENT_SCOPE` | token privo dello scope richiesto |
| 404 | `NOT_FOUND` | risorsa inesistente |
| 409 | `CONFLICT` | violazione di unicità non risolvibile |
| 422 | `UNRESOLVED_REFERENCE` | riferimento a entità non ancora sincronizzata (vedi §4.4) |
| 429 | `RATE_LIMITED` | troppe richieste |
| 500 | `INTERNAL_ERROR` | errore interno |

---

### 3.1 Pazienti — inbound (B → noi)

Sorgente: B. Modalità: **upsert per `external_id`** (create se assente, update se presente). Vedi §4 per la logica di matching.

#### 3.1.1 Create / Update paziente

```http
POST /v1/patients
Authorization: Bearer <token>
Content-Type: application/json
Idempotency-Key: 5f9c2a1e-...
```
```json
{
  "external_id": "B-PAT-987",
  "fiscal_code": "RSSMRA10A01F839X",
  "first_name": "Mario",
  "last_name": "Rossi",
  "birth_date": "2010-01-01",
  "gender": "M",
  "birth_city": "Napoli",
  "birth_province_code": "NA",
  "residence_address": "Via Roma 1",
  "residence_city": "Salerno",
  "residence_province_code": "SA",
  "residence_postal_code": "84100",
  "phone_number": "3331234567",
  "district_code": "65",
  "notes": "…"
}
```

**Response `201 Created` / `200 OK`**
```json
{
  "success": true,
  "data": {
    "external_id": "B-PAT-987",
    "internal_id": 4521,
    "fiscal_code": "RSSMRA10A01F839X",
    "matched_by": "external_id",
    "created": false,
    "is_active": true
  }
}
```

**Errori tipici:** `400 VALIDATION_ERROR` (es. `fiscal_code` malformato), `404 NOT_FOUND` (`district_code` inesistente → dettaglio nel campo).

#### 3.1.2 Disattivazione paziente

Nessuna cancellazione fisica (dato sanitario storicizzato): la disattivazione imposta `is_active = false`.

```http
POST /v1/patients/{external_id}/deactivate
```
```json
{ "reason": "Chiuso su piattaforma B" }
```
**Response `200 OK`** → `{ "success": true, "data": { "external_id": "B-PAT-987", "is_active": false } }`

#### 3.1.3 Lettura paziente (verifica sincronizzazione)

```http
GET /v1/patients/{external_id}
```
Ritorna la vista corrente del paziente come registrata da noi (per riconciliazione lato B).

---

### 3.2 Terapisti — inbound (B → noi)

Sorgente: B. Al **create** generiamo l'account applicativo e le credenziali.

#### 3.2.1 Create / Update terapista

```http
POST /v1/therapists
```
```json
{
  "external_id": "B-THE-12",
  "fiscal_code": "BNCNNA85M41H703Z",
  "first_name": "Anna",
  "last_name": "Bianchi",
  "email": "anna.bianchi@example.com",
  "phone": "3339876543",
  "specialization_code": "LOGOP",
  "weekly_hours_contract": 36,
  "is_internal": true,
  "can_supervise": false,
  "is_aba": false
}
```

**Response `201 Created`**
```json
{
  "success": true,
  "data": {
    "external_id": "B-THE-12",
    "internal_id": 88,
    "matched_by": "external_id",
    "created": true,
    "is_active": true
  }
}
```

#### 3.2.2 Provisioning account — **proposta**

Al primo create di un terapista il sistema, in un'unica transazione:

1. crea l'**account** (`users`): `username` = email, `email` (obbligatoria, univoca), stato attivo;
2. crea il **profilo** (`user_profiles`): nome, cognome, `fiscal_code`, telefono, indirizzo;
3. assegna il **ruolo RBAC** di terapista;
4. crea il record `therapists` collegato all'account.

Le **credenziali di accesso del terapista sono generate internamente dal nostro sistema** e gestite secondo le nostre procedure interne; B non le riceve e non le fornisce (non fanno parte del payload né della risposta).

**Update**: aggiorna anagrafica/attributi; **non** tocca le credenziali. Il cambio email aggiorna anche lo `username`.

#### 3.2.3 Disattivazione terapista

```http
POST /v1/therapists/{external_id}/deactivate
```
Imposta `therapists.is_active = false` e disattiva l'account (`users.status = inactive`) → l'accesso è immediatamente bloccato. Gli appuntamenti storici restano invariati.

---

### 3.3 Piani terapeutici — inbound (B → noi)

Sorgente: B. Il piano include la **testata** e le sue **righe di terapia** in un unico payload (le righe sono figlie e seguono il ciclo di vita del piano).

#### 3.3.1 Create / Update piano

```http
POST /v1/therapeutic-plans
```
```json
{
  "external_id": "B-PLAN-55",
  "protocol_number": "2026/0123",
  "patient_external_id": "B-PAT-987",
  "start_date": "2026-07-01",
  "duration_days": 180,
  "regime_name": "L11",
  "district_code": "65",
  "status": "active",
  "notes": "…",
  "therapies": [
    {
      "external_id": "B-PLANLINE-1",
      "treatment_type_code": "LOG_IND",
      "setting_code": "AMBUL",
      "weekly_hours": 2.0,
      "is_group": false
    },
    {
      "external_id": "B-PLANLINE-2",
      "treatment_type_code": "NPM_IND",
      "setting_code": "DOMIC",
      "weekly_hours": 1.5,
      "is_group": false
    }
  ]
}
```

**Response `201 Created`**
```json
{
  "success": true,
  "data": {
    "external_id": "B-PLAN-55",
    "internal_id": 341,
    "protocol_number": "2026/0123",
    "matched_by": "external_id",
    "created": true,
    "end_date": "2026-12-28",
    "status": "active",
    "therapies": [
      { "external_id": "B-PLANLINE-1", "internal_id": 1201 },
      { "external_id": "B-PLANLINE-2", "internal_id": 1202 }
    ]
  }
}
```

> `end_date` è **calcolato dal sistema** (`start_date` + `duration_days`) e restituito a titolo informativo; B non lo invia.

> **Campo obbligatorio `created_by` (non inviato da B).** Il modello del piano richiede `created_by` (FK verso un utente del sistema). Nell'integrazione non esiste un utente umano che crea il piano: il modulo valorizza `created_by` con l'**utente di servizio dell'integrazione** (account tecnico dedicato, vedi §6). Vale per ogni entità sincronizzata da B che richiede un autore; tra le entità inbound riguarda i **piani terapeutici** (pazienti e terapisti non hanno `created_by`).

> **Vincolo regime ABA.** Se `regime_name = "ABA"`, la validazione del piano impone che l'array `therapies` contenga **almeno una terapia "principale"** (diversa da supervisione e parent training). Inoltre, se il **paziente ha meno di 14 anni**, è **obbligatorio** includere anche una riga di **SUPERVISIONE** e una di **PARENT TRAINING** (`treatment_type_code` `SUP` e `PT`): un piano ABA under-14 privo di queste righe **viene rifiutato** con `400 VALIDATION_ERROR`.

**Errori tipici:**
- `422 UNRESOLVED_REFERENCE` se `patient_external_id` non è ancora sincronizzato (vedi §4.4);
- `404 NOT_FOUND` su `regime_name` / `treatment_type_code` / `setting_code` inesistenti;
- `400 VALIDATION_ERROR` se un piano ABA non soddisfa i requisiti di composizione sopra.

#### 3.3.2 Disattivazione piano

```http
POST /v1/therapeutic-plans/{external_id}/deactivate
```
```json
{ "status": "terminated", "termination_reason": "Chiuso su B" }
```
Imposta uno stato terminale (`terminated`/`completed`) senza cancellazione fisica.

---

### 3.4 Appuntamenti — outbound letture (B ← noi)

Sorgente: nostro sistema. B **legge** soltanto.

#### 3.4.1 Calendario di un terapista (per data, paginato)

```http
GET /v1/therapists/{external_id}/appointments?from=2026-07-01&to=2026-07-31&page=1&per_page=50
```

**Response `200 OK`**
```json
{
  "success": true,
  "data": [
    {
      "internal_id": 12345,
      "datetime": "2026-07-10T15:00:00+02:00",
      "duration_minutes": 45,
      "status": "scheduled",
      "type": "terapia",
      "category": "regular",
      "treatment_type_code": "LOG_IND",
      "specialization_code": "LOGOP",
      "setting_code": "AMBUL",
      "patient": { "external_id": "B-PAT-987", "full_name": "Mario Rossi" },
      "plan": { "external_id": "B-PLAN-55", "protocol_number": "2026/0123" }
    }
  ],
  "meta": { "page": 1, "per_page": 50, "total": 128, "total_pages": 3 }
}
```

Parametri: `from`/`to` (obbligatori, intervallo date), `status` (opzionale), `page`/`per_page`.

#### 3.4.2 Appuntamenti di un piano terapeutico

```http
GET /v1/therapeutic-plans/{external_id}/appointments?page=1&per_page=50
```
Stessa forma di risposta; l'elenco è filtrato sugli appuntamenti derivati dalle righe del piano indicato.

---

### 3.5 Dizionari di riferimento — outbound read-only (B ← noi)

Consentono a B di scoprire i codici validi da usare nelle scritture. Cambiano di rado; consigliata cache lato B.

```http
GET /v1/reference/regimes
GET /v1/reference/treatment-types
GET /v1/reference/settings
GET /v1/reference/specializations
GET /v1/reference/districts
```

**Esempio `GET /v1/reference/treatment-types` → `200 OK`**
```json
{
  "success": true,
  "data": [
    { "code": "LOG_IND", "name": "Logopedia Individuale" },
    { "code": "NPM_IND", "name": "Neuropsicomotricità Individuale" },
    { "code": "FKT_IND", "name": "Fisiokinesiterapia" },
    { "code": "PT", "name": "Parental Training" },
    { "code": "SUP", "name": "Supervisione" }
  ]
}
```

**Esempio `GET /v1/reference/regimes`**
```json
{ "success": true, "data": [ { "code": "L11", "name": "Regime L11" }, { "code": "ABA", "name": "Regime ABA" }, { "code": "FKT_377", "name": "FKT 377" } ] }
```

> Per `regime` e `setting`, che oggi non hanno un `code` stabile, la proposta §6 introduce/normalizza un codice; nelle risposte il campo `code` è già previsto.

---

### 3.6 Evento di completamento appuntamento — outbound push (noi → B)

Ad ogni **completamento** di un appuntamento notifichiamo **B** su un suo endpoint (che B ci comunica in fase di setup). "Completamento" copre **tre stati**:

| Stato interno | `outcome` nel payload | Significato |
|---|---|---|
| `completed` | `visit_done` | visita effettuata |
| `absent_justified` | `patient_absent` | assenza paziente **giustificata** |
| `absent_not_justified` | `patient_absent` | assenza paziente **non giustificata** |

> Lo stato `therapist_absent` (assenza del **terapista**) **non** genera l'evento: non è un completamento del paziente.

**Punto di emissione (proposta):** l'evento è emesso **all'interno delle azioni applicative** che completano o segnano l'assenza dell'appuntamento. In quel punto sono disponibili con certezza `reason` dell'assenza, il tipo (giustificata/non) e l'operatore — dati che nel record persistito restano solo come testo libero. (In sistema non esiste una colonna `completed_at`: il timestamp dell'evento coincide con l'istante di emissione ed è accurato.)

#### 3.6.1 Body proposto della richiesta verso B

```http
POST {endpoint_di_B}
Content-Type: application/json
X-Signature: sha256=<hmac>            # firma HMAC del corpo (vedi §5)
Idempotency-Key: <event_id>          # per deduplica lato B
```
```json
{
  "event": "appointment.completed",
  "event_id": "evt_9f1c…",
  "event_timestamp": "2026-07-10T15:32:04+02:00",
  "appointment": {
    "internal_id": 12345,
    "datetime": "2026-07-10T15:00:00+02:00",
    "duration_minutes": 45,
    "status": "absent_justified",
    "outcome": "patient_absent",
    "type": "terapia",
    "category": "regular",
    "treatment_type_code": "LOG_IND",
    "specialization_code": "LOGOP",
    "setting_code": "AMBUL"
  },
  "absence": {
    "justified": true,
    "reason": "Malattia",
    "is_group_absence": false
  },
  "patient":   { "external_id": "B-PAT-987", "fiscal_code": "RSSMRA10A01F839X", "full_name": "Mario Rossi" },
  "plan":      { "external_id": "B-PLAN-55", "protocol_number": "2026/0123" },
  "therapist": { "external_id": "B-THE-12", "full_name": "Anna Bianchi" },
  "operator":  { "therapist_external_id": "B-THE-12" },
  "notes": "…"
}
```

- Il blocco `absence` è presente **solo** quando `outcome = patient_absent`; per `visit_done` è omesso.
- `event_id` identifica univocamente l'evento e funge da chiave di **idempotenza** per B.
- Risposta attesa da B: `2xx` = ricevuto; qualsiasi altro esito o timeout attiva la politica di retry (§5).

---

## 4. Strategia di riconciliazione

### 4.1 Identificatori esterni

Su ogni entità sincronizzata da B introduciamo (vedi §6) due colonne:

| Colonna | Uso |
|---|---|
| `external_id` | identificativo del record **sul sistema di B** |
| `external_system` | sistema di origine (default `B`; predispone più sorgenti future) |

Vincolo di **unicità** su `(external_system, external_id)` per entità. Questo è il legame primario e stabile tra i due sistemi.

### 4.2 Attributo di **prima riconciliazione**

Al primo carico (o quando B non conosce/ non invia l'`external_id`, o per allineare dati preesistenti), il match avviene su un **attributo naturale**:

| Entità | Attributo di prima riconciliazione |
|---|---|
| **Paziente** | `fiscal_code` (codice fiscale) |
| **Terapista** | `fiscal_code` dell'operatore (in subordine `email`) |
| **Piano terapeutico** | **terna** `protocol_number` + distretto + regime (il vincolo di unicità del piano è sulla combinazione dei tre, non sul solo `protocol_number`: lo stesso numero di protocollo può ripetersi su distretti o regimi diversi) |

### 4.3 Matching a cascata (anti-duplicazione)

Per ogni scrittura inbound il sistema applica, nell'ordine:

```
1. Cerca per (external_system, external_id)
      └─ trovato  → UPDATE del record collegato        (matched_by: "external_id")
      └─ assente  → passo 2
2. Cerca per attributo naturale (fiscal_code | terna protocol_number+distretto+regime)
      └─ trovato  → COLLEGA external_id al record esistente + UPDATE   (matched_by: "natural_key")
      └─ assente  → passo 3
3. CREATE nuovo record, salvando external_id/external_system           (matched_by: "created")
```

La risposta espone sempre `matched_by` e `created`, così B sa se ha creato o agganciato un record esistente. Questo previene i **duplicati** anche se B ripete la stessa create (chiamata idempotente).

**Idempotenza esplicita:** l'header `Idempotency-Key` consente a B di ripetere in sicurezza una richiesta dopo un timeout: a parità di chiave restituiamo l'esito della prima esecuzione senza applicare due volte l'effetto.

### 4.4 Dipendenze tra entità

Le entità hanno un **ordine di dipendenza**:

```
paziente ──▶ piano terapeutico ──▶ (righe di terapia)
terapista ─▶ (necessario per leggere il calendario; gli appuntamenti sono nostri)
```

**Ordine di sincronizzazione richiesto a B:** `pazienti` e `terapisti` **prima**, poi `piani terapeutici`.

**Cosa succede se un riferimento arriva prima del suo referente** (es. un piano che cita un `patient_external_id` non ancora sincronizzato):

- Risposta **`422 UNRESOLVED_REFERENCE`** con il dettaglio del riferimento mancante (campo, valore, motivo).
- B **ri-invia** dopo aver sincronizzato l'entità mancante. La chiamata è idempotente, quindi il reinvio non crea duplicati.
- **Nessuno stub silenzioso:** non creiamo pazienti/terapisti "fantasma" a partire da un piano — garantisce l'integrità del dato clinico e una diagnostica chiara.

Per i lookup (`regime`, `treatment_type`, `setting`, `specialization`, `district`) un codice inesistente produce `404 NOT_FOUND` con il campo in errore: B allinea i dizionari (§3.5) e ripete.

### 4.5 Prima sincronizzazione massiva (bulk)

Oltre agli eventi incrementali, è previsto un **carico iniziale**:

- **Stessi endpoint** delle scritture incrementali, richiamati in sequenza rispettando l'ordine di dipendenza (§4.4). Ogni chiamata è un upsert idempotente, quindi il bulk è ripetibile in sicurezza.
- Per volumi elevati è disponibile una **variante batch** per ridurre il numero di round-trip:

```http
POST /v1/patients/batch
```
```json
{ "items": [ { "external_id": "B-PAT-1", "...": "..." }, { "external_id": "B-PAT-2", "...": "..." } ] }
```
**Response `200 OK` (esito per elemento):**
```json
{
  "success": true,
  "data": {
    "processed": 2,
    "results": [
      { "external_id": "B-PAT-1", "internal_id": 4600, "matched_by": "created", "status": "ok" },
      { "external_id": "B-PAT-2", "status": "error", "error": { "code": "VALIDATION_ERROR", "message": "fiscal_code non valido" } }
    ]
  }
}
```

- Ogni elemento è valutato indipendentemente: gli errori del singolo non bloccano il lotto (report per-elemento).
- Dimensione massima lotto: 200 elementi per pagina (allineata a `per_page`).

---

## 5. Decisioni e proposte trasversali

Tutte le scelte seguenti sono **proposte già definite**, non punti aperti.

| Tema | Decisione proposta |
|---|---|
| **Formato token** | JWT **RS256** con coppia di chiavi dedicata all'integrazione; claim `aud=b-integration`, `scope`, `jti`; validazione locale (firma+exp+aud+scope) e lista di revoca per `jti`. Nessun token opaco (evita lookup DB per la validazione base pur mantenendo la revoca via `jti`). |
| **Refresh token** | Non previsti (grant Client Credentials): B ri-richiede il token alla scadenza. |
| **Gestione errori** | Formato errore unico `{ success:false, error:{ code, message, details } }` + catalogo codici (§3.0). Sui lookup e i riferimenti mancanti, sempre dettaglio campo-per-campo. |
| **Idempotenza scritture** | Doppio livello: (a) upsert per `external_id` intrinsecamente idempotente; (b) header `Idempotency-Key` per neutralizzare i reinvii post-timeout. |
| **Idempotenza evento verso B** | `event_id` univoco nel payload di completamento, usato da B come chiave di deduplica. |
| **Affidabilità webhook uscente** | Firma **HMAC-SHA256** del corpo (`X-Signature`), **retry con backoff esponenziale** (5 tentativi: ~1m, 5m, 30m, 2h, 6h), coda con dead-letter e alert dopo l'ultimo tentativo fallito. Timeout richiesta breve (es. 10 s). |
| **Cancellazioni** | **Mai** cancellazione fisica dei dati clinici: disattivazione via `is_active` (pazienti/terapisti) o stato terminale (piani). Preserva storico e integrità referenziale. |
| **Credenziali terapisti** | Generate internamente dal nostro sistema al create; non transitano da/verso B. |
| **Matching `setting`** | Introduzione di una colonna `code` **stabile** su `setting` (§6): elimina la fragilità del match per nome mutevole. |
| **Privacy dati sanitari** | Vedi §5.1. |
| **Rate limiting** | Limite per client (es. 600 req/min) con `429 RATE_LIMITED` e header `Retry-After`; parametrizzabile. |
| **Versionamento** | Prefisso `/v1`; modifiche non retro-compatibili → `/v2`. |
| **Osservabilità** | Log applicativo di ogni chiamata (senza PII in chiaro nei log) + audit trail degli accessi ai dati clinici. |

### 5.1 Privacy e sicurezza dei dati sanitari

Pazienti e piani terapeutici sono **dati sanitari** (categoria particolare, art. 9 GDPR). Misure proposte:

- **Trasporto:** solo HTTPS/TLS 1.2+; rifiuto delle connessioni in chiaro. La protezione dei dati verso/da B è garantita in transito tramite TLS.
- **Privilegio minimo:** scope OAuth2 limitati al necessario per B; nessuno scope di lettura pazienti/piani se non richiesto dai casi d'uso.
- **Data minimization:** i payload contengono solo i campi necessari; i dizionari non espongono dati personali.
- **Audit trail:** registrazione di chi (client), cosa e quando, per ogni accesso ai dati clinici.
- **Restrizione di rete:** allowlist degli IP di B a livello infrastrutturale (difesa aggiuntiva oltre a OAuth2).
- **No PII nei log:** i log applicativi mascherano codice fiscale e dati personali.
- **Accordo sul trattamento:** DPA/nomina a responsabile del trattamento tra le parti; definizione di titolarità e retention.
- **Firma degli eventi uscenti:** HMAC per garantire a B autenticità e integrità delle notifiche.

---

## 6. Modifiche allo schema richieste

Interventi minimi necessari a supportare l'integrazione (dettaglio implementativo fuori scope di questo documento; qui elencati come requisito).

| Oggetto | Modifica | Motivo |
|---|---|---|
| `patients` | + `external_id`, `external_system`, `is_active` (bool, default 1); indice unico `(external_system, external_id)` | riconciliazione + disattivazione (oggi manca un flag attivo/inattivo sul paziente) |
| `therapists` | + `external_id`, `external_system`; indice unico `(external_system, external_id)` | riconciliazione (`is_active` già presente) |
| `therapeutic_plans` | + `external_id`, `external_system`; indice unico `(external_system, external_id)` | riconciliazione (`status` già gestisce la disattivazione) |
| `plan_therapies` | + `external_id`, `external_system` (opzionale) | riconciliazione delle singole righe di terapia |
| `setting` | + `code` (string, unico, stabile) valorizzato per i setting esistenti | matching robusto per codice invece che per nome mutevole |
| **Utente di servizio** | creazione di un account tecnico dedicato (`users`) usato come `created_by` per le entità sincronizzate da B (piani terapeutici) | il campo `created_by` è obbligatorio e non esiste un utente umano nell'integrazione |
| **Nuove tabelle** | `oauth_client` (client_id, secret hash, scope, stato), `oauth_client_token` (jti, scope, iat, exp, revoked) | store credenziali e revoca token, **isolati** dal resto del sistema |

**Nota implementativa collegata:** le nuove colonne `external_id`/`external_system` vanno aggiunte anche agli `scenarios()` `create`/`update` dei modelli interessati (es. `Patient`, che elenca esplicitamente i campi assegnabili), altrimenti non risulterebbero valorizzabili in scrittura.

> Gli appuntamenti **non** richiedono `external_id`: sono di nostra proprietà e vengono esposti a B tramite `internal_id`; nel calendario e negli eventi sono correlati alle entità di B tramite gli `external_id` di paziente/piano/terapista.

---

*Fine documento — v1.0. Da validare con il gestore della piattaforma B prima della pubblicazione su Confluence.*
