# TherapyCRM - Documentazione Completa del Sistema

> CRM per centri di riabilitazione e terapia. Gestisce pazienti, terapisti, piani terapeutici, appuntamenti, assenze, notifiche e richieste documenti.

**Ultimo aggiornamento**: 2026-02-26

---

## Indice

1. [Panoramica del Sistema](#1-panoramica-del-sistema)
2. [Stack Tecnologico](#2-stack-tecnologico)
3. [Architettura e Struttura Cartelle](#3-architettura-e-struttura-cartelle)
4. [Database e Modelli Dati](#4-database-e-modelli-dati)
5. [Sistema Appuntamenti](#5-sistema-appuntamenti)
6. [Piani Terapeutici e Regimi](#6-piani-terapeutici-e-regimi)
7. [Sistema Assenze](#7-sistema-assenze)
8. [Sistema RBAC - Ruoli e Permessi](#8-sistema-rbac---ruoli-e-permessi)
9. [Controller Frontend (Yii2)](#9-controller-frontend-yii2)
10. [Controller Backend (Admin)](#10-controller-backend-admin)
11. [API REST](#11-api-rest)
12. [Calendar App (React + TypeScript)](#12-calendar-app-react--typescript)
13. [App Mobile (React Native)](#13-app-mobile-react-native)
14. [Sistema Notifiche](#14-sistema-notifiche)
15. [Richieste Documenti](#15-richieste-documenti)
16. [Comandi Console](#16-comandi-console)
17. [Componenti e Servizi Condivisi](#17-componenti-e-servizi-condivisi)
18. [Regole di Business Importanti](#18-regole-di-business-importanti)
19. [Configurazione e Deploy](#19-configurazione-e-deploy)
20. [Documentazione Esistente](#20-documentazione-esistente)

---

## 1. Panoramica del Sistema

TherapyCRM e' una piattaforma completa per la gestione di centri di riabilitazione/terapia. Il sistema gestisce:

- **Pazienti**: anagrafica completa (nome, cognome, data nascita, codice fiscale, indirizzo, distretto)
- **Terapisti**: profilo, specializzazione, ore contrattuali settimanali, colore calendario, capacita' (supervisione, parent training)
- **Piani Terapeutici**: piani con regime, durata, terapie associate, ore settimanali/mensili
- **Appuntamenti**: calendario interattivo, pattern ricorrenti, sessioni di gruppo, sostituzioni terapista
- **Assenze**: gestione assenze terapisti (ferie, malattia, personali, formazione) con contatori annuali e recuperi
- **Comunicazioni**: notifiche interne, promemoria, scadenze, letture obbligatorie
- **Richieste Documenti**: workflow per richieste documenti da pazienti/familiari
- **Statistiche**: analisi utilizzo, report ore, performance
- **Reclami**: gestione reclami pazienti

### Utenti del Sistema

| Ruolo | Accesso | Descrizione |
|-------|---------|-------------|
| **admin** | Backend + Frontend | Gestione completa del sistema, CRUD utenti, ruoli, permessi |
| **manager** | Frontend | Assegna terapisti, crea orari, gestisce comunicazioni e documenti |
| **coordinator** | Frontend | Gestisce i terapisti del proprio gruppo e i relativi pazienti |
| **therapist** | Frontend + Mobile | Visualizza calendario, gestisce appuntamenti propri |
| **patient** | Mobile App | Visualizza appuntamenti, invia richieste documenti, riceve notifiche |
| **patient_family** | Mobile App | Come patient ma con visibilita' dati paziente (genitore/tutore) |

---

## 2. Stack Tecnologico

### Backend (PHP/Yii2)

| Tecnologia | Uso |
|------------|-----|
| PHP 7.4+ | Linguaggio backend |
| Yii 2 Framework (advanced template) | MVC Framework principale |
| MySQL 5.7+ | Database relazionale |
| firebase/php-jwt | Autenticazione JWT per API |
| zircote/swagger-php | Documentazione API OpenAPI |
| mPDF | Generazione documenti PDF |

### Frontend Web (Calendar App)

| Tecnologia | Versione | Uso |
|------------|----------|-----|
| React | 18.3.1 | UI Framework |
| TypeScript | 5.5.3 | Type safety |
| Vite | 5.4.1 | Build tool |
| TailwindCSS | 3.4.11 | Styling |
| FullCalendar | 6.1.18 | Componente calendario |
| Shadcn UI (Radix) | - | Componenti UI (dialog, select, tabs, ecc.) |
| React Hook Form + Zod | 7.53 / 3.23 | Form management e validazione |
| React Query (TanStack) | 5.56.2 | Data fetching e caching |
| Lucide React | - | Icone |

### Frontend Yii2

| Tecnologia | Uso |
|------------|-----|
| Tailwind CSS | Styling delle view PHP |
| Alpine.js | Interattivita' lato client nelle view Yii2 |

### App Mobile

| Tecnologia | Versione | Uso |
|------------|----------|-----|
| React Native | 0.79.3 | Framework mobile cross-platform |
| Redux Toolkit | 2.8.2 | State management globale |
| React Navigation | 7.x | Navigazione tra schermate |
| React Native Paper | 5.14.5 | Material Design UI components |
| OneSignal | 5.2.12 | Push notifications |
| Axios | 1.9.0 | HTTP client per API |

---

## 3. Architettura e Struttura Cartelle

### Architettura Generale

```
+------------------------------------------------------------------+
|                         CLIENT TIER                                |
|  Calendar App (React)  |  Mobile (React Native)  |  Frontend Yii2 |
|  /frontend/web/        |  /tp/                   |  /frontend/    |
|  calendar-app/         |                         |  views/        |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|                         API TIER                                   |
|  REST API (Yii2) - /api/controllers/                              |
|  Auth | Calendar | Requests | Notifications | Complaints | Search |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|                    BUSINESS LOGIC TIER                              |
|  Common Models (52+)  |  Services  |  Components  |  Behaviors    |
|  /common/models/       | /common/  | /common/     | /common/      |
|                        | services/ | components/  | behaviors/    |
+------------------------------------------------------------------+
                              |
                              v
+------------------------------------------------------------------+
|                       DATA TIER                                    |
|  MySQL Database - 106 file di migrazione                          |
+------------------------------------------------------------------+
```

### Struttura Yii2 Advanced Template

```
TherapyCRM/
|
|-- api/                          # REST API per Mobile e Calendar App
|   |-- config/                   # Configurazione API (main.php, routing)
|   |-- controllers/              # Controller API (Auth, Calendar, Requests, ecc.)
|   +-- docs/                     # Documentazione API / Swagger
|
|-- backend/                      # Pannello Amministrativo (Admin)
|   |-- config/                   # Configurazione backend
|   |-- controllers/              # Controller admin (Roles, Permissions, Districts, ecc.)
|   +-- views/                    # Template admin
|
|-- common/                       # Codice Condiviso tra tutte le applicazioni
|   |-- behaviors/                # Behaviors (ActivityLogBehavior)
|   |-- components/               # Helper, JWT, NotificationService, OneSignal, PlanHelper
|   |-- config/                   # Configurazione DB, componenti condivisi
|   |-- models/                   # 52+ modelli ActiveRecord
|   +-- services/                 # Business logic (statistics/)
|
|-- console/                      # Comandi CLI e Migrazioni
|   |-- controllers/              # 13 command controller
|   +-- migrations/               # 106 file di migrazione DB
|
|-- frontend/                     # Applicazione Frontend principale (Yii2)
|   |-- config/                   # Configurazione frontend
|   |-- controllers/              # 17 controller web
|   |-- views/                    # Template PHP (Tailwind CSS + Alpine.js)
|   |   |-- absence/              # Gestione assenze
|   |   |-- calendar/             # Pagina calendario (wrapper per React app)
|   |   |-- communication/        # Comunicazioni
|   |   |-- complaint/            # Reclami
|   |   |-- coordinator-group/    # Gruppi coordinatori
|   |   |-- document-request/     # Richieste documenti
|   |   |-- layouts/              # Layout principale (_sidebar.php, _header.php)
|   |   |-- notification/         # Notifiche
|   |   |-- patient/              # Anagrafica pazienti
|   |   |-- site/                 # Pagine sito (login, dashboard)
|   |   |-- statistics/           # Statistiche
|   |   |-- therapeutic-plan/     # Piani terapeutici
|   |   |-- therapist/            # Anagrafica terapisti
|   |   +-- user/                 # Gestione utenti
|   +-- web/
|       +-- calendar-app/         # <-- APP REACT (calendario interattivo)
|           +-- src/
|               |-- components/   # Componenti React (modali, selettori, widget)
|               |-- config/       # Configurazione
|               |-- hooks/        # Custom hooks
|               |-- lib/          # API client (api.ts)
|               |-- pages/        # Pagine (Index.tsx)
|               |-- styles/       # CSS
|               +-- types/        # TypeScript types (therapy.ts)
|
|-- tp/                           # App Mobile React Native
|   +-- src/
|       |-- screens/              # Schermate (patient/, therapist/, auth/)
|       |-- components/           # Componenti riutilizzabili
|       |-- services/             # Servizi (auth, oneSignal, ecc.)
|       |-- api/                  # Moduli API
|       |-- hooks/                # Custom hooks
|       |-- store/                # Redux store
|       +-- navigation/           # React Navigation config
|
|-- environments/                 # Configurazioni per ambiente (dev, prod, test)
|-- imports/                      # Script importazione dati
+-- vendor/                       # Dipendenze PHP (Composer)
```

### Ruolo di Ogni Applicazione

- **frontend/** = Applicazione web principale usata da coordinatori, manager e admin. Le view PHP usano Tailwind CSS e Alpine.js. La pagina `/calendar/index` contiene un wrapper che carica la React app del calendario.
- **backend/** = Pannello di amministrazione del sistema. Gestione ruoli, permessi, distretti, log attivita'.
- **api/** = Endpoint REST consumati dalla Calendar App React e dall'App Mobile React Native. Autenticazione JWT.
- **console/** = Comandi CLI: migrazioni DB, RBAC, import dati, manutenzione, notifiche, gestione piani terapeutici.
- **common/** = Modelli dati, componenti, servizi e behaviors condivisi tra tutte le applicazioni.

---

## 4. Database e Modelli Dati

### Tabelle Principali e Relazioni

```
UTENTI E AUTENTICAZIONE
========================
users                         -- Account utente (email, password_hash, status: active/inactive)
  |-- user_profiles           -- Profilo esteso (first_name, last_name, fiscal_code, phone, address)
  |-- auth_token              -- JWT refresh tokens
  +-- auth_assignment         -- Associazione utente <-> ruolo/permesso

auth_item                     -- Definizione ruoli (type=1) e permessi (type=2)
auth_item_child               -- Gerarchia: ruolo -> permessi

PAZIENTI
=========
patients                      -- Anagrafica paziente
  |                             (first_name, last_name, birth_date, fiscal_code, gender,
  |                              birth_city, birth_province_code, born_in_italy,
  |                              residence_address/city/province/postal_code,
  |                              phone_number, district_id, notes)
  |-- account_patients        -- Collegamento account utente <-> paziente
  |                             (relationship_type: self/parent/tutor/other,
  |                              has_parental_authority, can_view_appointments,
  |                              can_cancel_appointments)
  |-- therapeutic_plans       -- Piani terapeutici del paziente
  +-- specialist_visits       -- Visite specialistiche (specialist_name, diagnosis, recommendations)

TERAPISTI
==========
therapists                    -- Profilo terapista
  |                             (user_id, specialization_id, weekly_hours_contract,
  |                              calendar_color, is_active, is_internal,
  |                              can_supervise, can_parental_training)
  |-- group_therapists        -- Appartenenza a gruppi coordinatore
  +-- absences                -- Assenze del terapista

coordinator_groups            -- Gruppi di coordinamento
  |                             (name, coordinator_user_id, is_active)
  +-- group_therapists        -- Terapisti nel gruppo
                                (therapist_id, assigned_from, assigned_to, assigned_by)
                                Nota: assigned_to IS NULL = terapista attivo nel gruppo

SPECIALIZZAZIONI E TRATTAMENTI
================================
specializations               -- Specializzazioni terapeutiche (code, name)
treatment_types               -- Tipi di trattamento (code, name)
specialization_treatments     -- Tabella ponte M:N (specialization_id <-> treatment_type_id)

PIANI TERAPEUTICI
==================
therapeutic_plans             -- Piano terapeutico
  |                             (patient_id, start_date, duration_days, end_date [generata],
  |                              regime_id, district_id, status, protocol_number,
  |                              approval_date, suspension_date, suspension_reason)
  |                             Stati: draft, pending, active, suspended, completed, terminated, expired
  +-- plan_therapies          -- Singole terapie nel piano
                                (therapeutic_plan_id, treatment_type_id, weekly_hours,
                                 is_group, setting_id, notes)

REGIMI E SETTING
=================
regime                        -- Regimi terapeutici (nome, descrizione, conteggio_ore: weekly/monthly)
  |                             Es: L11, ABA, FKT, Private, ecc.
setting                       -- Setting di erogazione (nome)
  |                             Es: Ambulatoriale, Domiciliare, ecc.
regime_setting                -- Tabella ponte M:N: quali setting sono disponibili per ogni regime

APPUNTAMENTI
=============
appointments                  -- Appuntamento singolo
  |                             (patient_id, therapist_id, plan_therapy_id, pattern_id,
  |                              appointment_datetime, duration_minutes [15-180],
  |                              status, appointment_source, appointment_category,
  |                              appointment_type, id_setting, group_session_id [UUID],
  |                              original_therapist_id, notes, related_appointment_id,
  |                              treatment_type_id, private_cycle_id)
  |-- therapist_substitutions -- Record sostituzione terapista
  +-- (related) appointments  -- Recuperi collegati (via related_appointment_id)

appointment_patterns          -- Pattern ricorrenti settimanali
  |                             (plan_therapy_id, therapist_id, day_of_week [1-7],
  |                              start_time, duration_minutes, valid_from, valid_to,
  |                              appointment_type, id_setting)
  +-- appointments            -- Appuntamenti generati dal pattern

private_cycles                -- Cicli di appuntamenti privati
  |                             (patient_id, month_year, total_sessions, notes)
  +-- appointments            -- Appuntamenti del ciclo privato

therapist_busy_slots          -- Slot occupati terapista (gestiti via trigger DB)

ASSENZE
========
absences                      -- Assenze terapisti
  |                             (therapist_id, start_date, end_date,
  |                              type: vacation/sick_leave/personal/training/other,
  |                              status: pending/approved/rejected/cancelled,
  |                              reason, approved_by, approved_at)
  +-- absence_recoveries      -- Recuperi assenza
                                (recovery_date, start_time, end_time, minutes_recovered,
                                 status: planned/completed/cancelled)

absence_counters              -- Contatori annuali assenze per terapista
                                (therapist_id, year, vacation_days_total/used,
                                 sick_days_used, personal_days_used, training_days_used)

RICHIESTE DOCUMENTI
====================
document_requests             -- Richiesta documento
  |                             (account_patient_id, patient_id, request_type_id,
  |                              therapeutic_plan_id, therapy_id, notes,
  |                              status: 1=Inviata, 2=Presa in carico, 3=Stampato, 4=Consegnato)
  +-- document_request_status_history -- Storico cambi stato
                                (from_status_id, to_status_id, changed_by_user_id)

request_types                 -- Tipi di richiesta configurabili
                                (name, therapeutic_plan_rule [1=opzionale,2=non associabile,3=obbligatorio],
                                 allow_multiple_requests, require_therapy_assignment, require_notes)

request_statuses              -- Definizione stati possibili

COMUNICAZIONI
==============
notifications                 -- Notifiche
  |                             (recipient_user_id, sender_user_id,
  |                              notification_type: info/reminder/deadline/mandatory_read/internal_communication,
  |                              title, message, requires_read_confirmation,
  |                              read_at, viewed_at, scheduled_for, sent_at)
notification_templates        -- Template notifiche

complaints                    -- Reclami pazienti (account_id, patient_id, title, description)

AUDIT E LOGGING
================
activity_log                  -- Log di tutte le modifiche
                                (user_id, action: create/update/delete,
                                 entity_name, entity_id, old_values [JSON], new_values [JSON],
                                 ip_address, user_agent)

LOCALITA'
==========
districts                     -- Distretti sanitari
province                      -- Province italiane
comuni                        -- Comuni italiani
```

### Relazioni Chiave tra Modelli

```
User (1) ---> (1) UserProfile
User (1) ---> (1) Therapist
User (1) ---> (N) AuthAssignment ---> AuthItem (ruolo/permesso)
User (1) ---> (N) AccountPatient ---> Patient

Patient (1) ---> (N) TherapeuticPlan
Patient (1) ---> (N) Appointment (diretti, per appuntamenti privati)
Patient (1) ---> (1) District

TherapeuticPlan (1) ---> (N) PlanTherapy
TherapeuticPlan ---> Regime
TherapeuticPlan ---> District

PlanTherapy ---> TreatmentType
PlanTherapy ---> Setting
PlanTherapy (1) ---> (N) Appointment (da piano terapeutico)
PlanTherapy (1) ---> (N) AppointmentPattern

Therapist ---> Specialization
Therapist ---> User
Therapist (1) ---> (N) Appointment
Therapist (1) ---> (N) Absence
Therapist (1) ---> (N) GroupTherapist ---> CoordinatorGroup

Appointment ---> Patient (diretto per privati)
Appointment ---> PlanTherapy (per appuntamenti da piano)
Appointment ---> Therapist (attuale)
Appointment ---> Therapist (original_therapist_id, per sostituzioni)
Appointment ---> AppointmentPattern
Appointment ---> Setting
Appointment ---> TreatmentType (per privati)
Appointment ---> PrivateCycle (per privati)
Appointment ---> TherapistSubstitution
Appointment ---> Appointment (related_appointment_id, per recuperi)

Specialization (N) <---> (N) TreatmentType (via specialization_treatments)
Regime (N) <---> (N) Setting (via regime_setting)
```

---

## 5. Sistema Appuntamenti

### Fonti degli Appuntamenti (appointment_source)

| Costante | Valore | Descrizione |
|----------|--------|-------------|
| `SOURCE_THERAPEUTIC_PLAN` | `therapeutic_plan` | Appuntamento da piano terapeutico. Richiede `plan_therapy_id`. |
| `SOURCE_PRIVATE` | `private` | Appuntamento privato. Richiede `patient_id` e `treatment_type_id`. |

### Stati degli Appuntamenti (status)

| Costante | Valore | Label IT | Descrizione |
|----------|--------|----------|-------------|
| `STATUS_SCHEDULED` | `scheduled` | Programmato | Appuntamento futuro pianificato |
| `STATUS_COMPLETED` | `completed` | Completato | Appuntamento avvenuto regolarmente |
| `STATUS_ABSENT_JUSTIFIED` | `absent_justified` | Assente Giustificato | Paziente assente con giustificazione |
| `STATUS_ABSENT_NOT_JUSTIFIED` | `absent_not_justified` | Assente Non Giustificato | Paziente assente senza giustificazione |
| `STATUS_CANCELLED` | `cancelled` | Annullato | Appuntamento cancellato |
| `STATUS_THERAPIST_ABSENT` | `therapist_absent` | Terapista Assente | Terapista non disponibile (permette sostituzione) |

### Categorie Appuntamento (appointment_category)

| Costante | Valore | Label IT | Descrizione |
|----------|--------|----------|-------------|
| `CATEGORY_REGULAR` | `regular` | Appuntamento Normale | Default. Appuntamento regolare da piano. |
| `CATEGORY_RECOVERY` | `recovery` | Recupero | Recupero di un appuntamento mancato. Collegato via `related_appointment_id`. |
| `CATEGORY_ADVANCE` | `advance` | Anticipo | Appuntamento anticipato rispetto al piano. |
| `CATEGORY_EXTRA` | `extra` | Straordinario | Appuntamento extra fuori piano. |
| `CATEGORY_COMPENSATION` | `compensation` | Compensazione | Appuntamento di compensazione. |

### Tipi Appuntamento (appointment_type)

| Costante | Valore | Label IT |
|----------|--------|----------|
| `TYPE_TERAPIA` | `terapia` | Terapia (default) |
| `TYPE_SUPERVISIONE` | `supervisione` | Supervisione |
| `TYPE_PARENT_TRAINING` | `parent_training` | Parent Training |

### Sessioni di Gruppo (group_session_id)

Gli appuntamenti di gruppo condividono lo stesso `group_session_id` (UUID v4). Funzionamento:
- Piu' appuntamenti con lo stesso `group_session_id` rappresentano pazienti diversi nella stessa sessione di gruppo
- Lo stesso terapista, stessa data/ora, stessa durata
- La validazione di conflitti terapista esclude appuntamenti con lo stesso `group_session_id`
- Metodi: `isGroupSession()`, `getGroupSessionAppointments()`, `getGroupSessionPatients()`, `getGroupSessionParticipantsCount()`

### Pattern Ricorrenti (AppointmentPattern)

I pattern definiscono appuntamenti ricorrenti settimanali:
- `day_of_week`: 1=Lunedi ... 7=Domenica
- `start_time`: ora inizio (HH:mm)
- `duration_minutes`: durata (15-180 min)
- `valid_from` / `valid_to`: periodo di validita' del pattern
- `plan_therapy_id`: collegamento alla terapia del piano
- `therapist_id`: terapista assegnato
- `id_setting`: setting (ambulatoriale, domiciliare, ecc.)

I pattern generano appuntamenti singoli nel database. Un comando console puo' generare gli appuntamenti futuri.

### Sostituzioni Terapista (TherapistSubstitution)

Quando un terapista e' assente (`STATUS_THERAPIST_ABSENT`), l'appuntamento puo' essere assegnato a un sostituto:
- `original_therapist_id` sull'appuntamento memorizza il terapista originale
- `therapist_id` viene aggiornato al sostituto
- Record in `therapist_substitutions` traccia: appointment_id, original_therapist_id, substitute_therapist_id, reason, substituted_by
- Validazione: il sostituto deve essere diverso dall'originale

### Appuntamenti Privati (PrivateCycle)

I cicli privati organizzano appuntamenti non legati a piani terapeutici:
- `patient_id`: paziente
- `month_year`: mese/anno del ciclo
- `total_sessions`: numero totale di sessioni previste
- Traccia sessioni programmate, completate e rimanenti

### Validazioni Appuntamento

- `duration_minutes`: minimo 15, massimo 180 minuti
- `appointment_datetime`: formato YYYY-MM-DD HH:mm:ss (accetta anche formati alternativi)
- **Conflitti terapista**: verifica che il terapista non abbia gia' un appuntamento sovrapposto (con 5 minuti di tolleranza)
- **Condizionali per source**:
  - `therapeutic_plan`: richiede `plan_therapy_id`
  - `private`: richiede `patient_id` e `treatment_type_id`
- `id_setting` e' obbligatorio

---

## 6. Piani Terapeutici e Regimi

### Piano Terapeutico (TherapeuticPlan)

Un piano terapeutico associa un paziente a un regime terapeutico per un periodo definito:

- **Creazione**: data inizio + durata in giorni. `end_date` e' una colonna generata nel DB.
- **Stati**: `draft`, `pending`, `active`, `suspended`, `completed`, `terminated`, `expired`
- **Regime**: ogni piano ha un regime (es. ABA, L11, FKT, Private, ecc.)
- **Distretto**: opzionale, distretto sanitario
- **Protocollo**: numero protocollo, univoco per combinazione protocollo+distretto+regime
- **Sospensione**: `suspension_date` e `suspension_reason` (obbligatori quando status=suspended)

### Terapie nel Piano (PlanTherapy)

Ogni piano contiene una o piu' terapie:

- `treatment_type_id`: tipo di trattamento (es. Terapia ABA, Supervisione, Parent Training)
- `weekly_hours`: ore settimanali (min 0.5, max 999.99). Per regime ABA queste possono rappresentare ore mensili.
- `is_group`: flag per terapie di gruppo
- `setting_id`: setting di erogazione (ambulatoriale, domiciliare)
- Metodo `getMonthlyHours()`: weekly_hours * 4.33

### Regimi (Regime)

I regimi determinano le regole di conteggio ore e i setting disponibili:

- `conteggio_ore`: `weekly` (ore settimanali) oppure `monthly` (ore mensili)
- Ogni regime ha setting associati via tabella `regime_setting`
- Regimi noti: L11, L11DOM, L11PG, L11SEM, ABA, FKT, Private, PDOM, Other

### Validazioni ABA

Per il regime ABA ci sono regole speciali:
- Per pazienti sotto 14 anni: Supervisione obbligatoria + Parent Training obbligatorio
- Almeno una terapia principale (diversa da supervisione e parent training) sempre obbligatoria
- Verifica automatica durante la validazione del piano

### Setting

I setting rappresentano il contesto di erogazione della terapia:
- Es: Ambulatoriale, Domiciliare, ecc.
- Ogni setting puo' essere associato a piu' regimi (M:N via `regime_setting`)

---

## 7. Sistema Assenze

### Assenze Terapista (Absence)

Gestione delle assenze dei terapisti con workflow di approvazione:

**Tipi di assenza**:
| Costante | Valore | Label IT |
|----------|--------|----------|
| `TYPE_VACATION` | `vacation` | Ferie |
| `TYPE_SICK_LEAVE` | `sick_leave` | Congedo Malattia |
| `TYPE_PERSONAL` | `personal` | Personale |
| `TYPE_TRAINING` | `training` | Formazione |
| `TYPE_OTHER` | `other` | Altro |

**Stati assenza**:
| Costante | Valore | Label IT |
|----------|--------|----------|
| `STATUS_PENDING` | `pending` | In Attesa |
| `STATUS_APPROVED` | `approved` | Approvato |
| `STATUS_REJECTED` | `rejected` | Rifiutato |
| `STATUS_CANCELLED` | `cancelled` | Annullato |

### Contatori Assenze (AbsenceCounter)

Contatori annuali per terapista:
- `vacation_days_total`: giorni ferie totali disponibili (default 30)
- `vacation_days_used`, `sick_days_used`, `personal_days_used`, `training_days_used`
- Validazione: ferie usate non possono superare il totale
- Metodo `findOrCreate()`: crea automaticamente il contatore se non esiste

### Recuperi Assenza (AbsenceRecovery)

Pianificazione e tracciamento dei recuperi:
- `recovery_date`: data del recupero
- `start_time` / `end_time`: orari
- `minutes_recovered`: minuti recuperati (15-480)
- **Stati**: `planned`, `completed`, `cancelled`

### Assenze Paziente

Le assenze dei pazienti sono tracciate come stati degli appuntamenti:
- `absent_justified`: assente giustificato
- `absent_not_justified`: assente non giustificato
- Modello `PatientAbsenceSearch` per la ricerca e le statistiche sulle assenze dei pazienti

---

## 8. Sistema RBAC - Ruoli e Permessi

Il sistema utilizza il RBAC nativo di Yii2 con tabelle `auth_item`, `auth_item_child` e `auth_assignment`.

### Ruoli

| Ruolo | Descrizione |
|-------|-------------|
| `admin` | Accesso completo a tutto il sistema |
| `manager` | Gestione terapisti, documenti, comunicazioni, statistiche |
| `coordinator` | Gestione appuntamenti, pazienti, piani, terapisti del proprio gruppo |
| `therapist` | Visualizzazione propri appuntamenti, dati pazienti |
| `patient` | Visualizzazione propri appuntamenti e dati |
| `patient_family` | Come patient + visualizzazione dati paziente associato |

### Permessi e Assegnazioni di Default

```
admin:
  create_admin, create_coordinator, create_patient, create_therapist,
  delete_admin, delete_coordinator, delete_therapist,
  manage_appointments, manage_communications, manage_documents,
  manage_patients, manage_plans, manage_system, manage_therapists, manage_users,
  update_admin, update_coordinator, update_therapist,
  view_admin, view_coordinator, view_reports, view_statistics, view_therapist

coordinator:
  manage_appointments, manage_patients, manage_plans, manage_therapists,
  update_therapist, view_documents, view_reports, view_therapist

manager:
  create_therapist, delete_therapist, manage_communications, manage_documents,
  update_patient, update_therapist,
  view_patient, view_statistics, view_therapist

therapist:
  manage_appointments, view_own_appointments, view_patient, view_patient_data

patient:
  view_own_appointments, view_own_data

patient_family:
  view_own_appointments, view_own_data, view_patient_data
```

### Comandi CLI per RBAC

```bash
./yii rbac/create-role [nome] [descrizione]
./yii rbac/create-permission [nome] [descrizione]
./yii rbac/assign-permission-to-role [ruolo] [permesso]
./yii rbac/assign-role-to-user [user_id] [ruolo]
./yii rbac/assign-permission-to-user [user_id] [permesso]
./yii rbac/revoke-from-user [user_id] [ruolo_o_permesso]
./yii rbac/setup-default-permissions    # Inizializza tutte le assegnazioni default
./yii rbac/list-roles
./yii rbac/list-permissions
./yii rbac/user-assignments [user_id]
```

---

## 9. Controller Frontend (Yii2)

Il frontend Yii2 serve le pagine web per coordinatori, manager e admin.

### Elenco Controller

| Controller | Percorso | Descrizione |
|------------|----------|-------------|
| `SiteController` | `/site/` | Login, dashboard, pagine statiche |
| `CalendarController` | `/calendar/` | Wrapper per la React calendar app. Accetta `id_patient` o `id_therapist`. |
| `PatientController` | `/patient/` | CRUD pazienti, anagrafica, ricerca |
| `TherapistController` | `/therapist/` | CRUD terapisti, profilo, gruppi |
| `TherapeuticPlanController` | `/therapeutic-plan/` | CRUD piani terapeutici con terapie |
| `TherapeuticPlanManagerController` | `/therapeutic-plan-manager/` | Gestione avanzata piani (manager) |
| `AbsenceController` | `/absence/` | CRUD assenze terapisti, approvazioni, recuperi |
| `CoordinatorGroupController` | `/coordinator-group/` | CRUD gruppi coordinatore con assegnazione terapisti |
| `DocumentRequestController` | `/document-request/` | Gestione richieste documenti, cambio stato |
| `NotificationController` | `/notification/` | CRUD notifiche, invio, visualizzazione |
| `CommunicationController` | `/communication/` | Comunicazioni interne |
| `ComplaintController` | `/complaint/` | Gestione reclami |
| `StatisticsController` | `/statistics/` | Dashboard statistiche, report |
| `UserController` | `/user/` | Gestione utenti (creazione con ruoli) |
| `SearchController` | `/search/` | Ricerca globale |
| `ServicingController` | `/servicing/` | Servicing/manutenzione |
| `BaseController` | - | Controller base con AccessControl (autenticazione obbligatoria) |

### Accesso

Tutti i controller ereditano da `BaseController` che impone autenticazione (`'roles' => ['@']`). Solo la pagina di errore e' accessibile ai guest.

---

## 10. Controller Backend (Admin)

Il backend e' riservato agli amministratori di sistema.

| Controller | Descrizione |
|------------|-------------|
| `SiteController` | Login admin, dashboard |
| `RolesController` | CRUD ruoli RBAC |
| `PermissionsController` | CRUD permessi RBAC |
| `UserPermissionsController` | Assegnazione ruoli/permessi a utenti |
| `DistrictsController` | CRUD distretti sanitari |
| `ActivityLogController` | Visualizzazione log attivita' |

---

## 11. API REST

**Base URL**: `/api/`

### Controller API

#### AuthController
Autenticazione JWT per mobile e calendar app.

```
POST /api/auth/login            # Login con email/password -> JWT tokens
POST /api/auth/register         # Registrazione nuovo utente
POST /api/auth/refresh          # Refresh del JWT access token
POST /api/auth/logout           # Logout (invalida refresh token)
GET  /api/auth/me               # Dati utente corrente
POST /api/auth/forgot-password  # Reset password
```

#### CalendarController
Gestione completa calendario e appuntamenti (il controller piu' grande, ~95 KB).

```
# Appuntamenti
GET    /api/calendar/appointments              # Lista appuntamenti (filtri per periodo, terapista)
POST   /api/calendar/create-appointment        # Crea appuntamento singolo
PUT    /api/calendar/update-appointment        # Modifica appuntamento
DELETE /api/calendar/delete-appointment         # Elimina appuntamento

# Pattern ricorrenti
POST   /api/calendar/create-pattern            # Crea pattern con generazione appuntamenti
DELETE /api/calendar/delete-pattern             # Elimina pattern e appuntamenti futuri

# Appuntamenti privati
POST   /api/calendar/create-private-appointment  # Crea appuntamento privato
POST   /api/calendar/create-private-cycle        # Crea ciclo privato con appuntamenti

# Terapisti
GET    /api/calendar/therapists                # Lista terapisti con info
GET    /api/calendar/therapist-appointments    # Appuntamenti di un terapista
GET    /api/calendar/therapist-weekly-hours    # Ore settimanali effettive del terapista

# Pazienti
GET    /api/calendar/patient-appointments      # Appuntamenti di un paziente
GET    /api/calendar/patient                   # Dati paziente

# Assenze
GET    /api/calendar/therapist-absences        # Assenze terapista

# Piano Terapeutico
GET    /api/calendar/plan-therapy-hours        # Ore consumate vs pianificate
GET    /api/calendar/plan-therapy-for-therapist # Terapie assegnate al terapista
```

#### RequestsController
Gestione richieste documenti dai pazienti (mobile).

```
GET  /api/requests                  # Lista richieste (filtri: patient_id, status, limit, offset)
GET  /api/requests/types            # Tipi di richiesta disponibili con regole
POST /api/requests/create           # Crea nuova richiesta (validazione multi-livello)
GET  /api/requests/{id}             # Dettagli richiesta
PUT  /api/requests/{id}/cancel      # Annulla richiesta
GET  /api/requests/{id}/download    # Download documento
```

#### NotificationController
Sistema notifiche.

```
GET  /api/notifications               # Lista notifiche utente
PUT  /api/notifications/{id}/read     # Segna come letta
GET  /api/notifications/unread-count  # Conteggio non lette
GET  /api/notifications/blocking      # Notifiche bloccanti (mandatory_read)
```

#### ComplaintController
Gestione reclami.

#### SearchController
Ricerca globale.

### Autenticazione API

Tutte le chiamate API (tranne login/register) richiedono un JWT token:

```
Header: Authorization: Bearer {access_token}
```

Flusso JWT:
1. Login -> restituisce `access_token` (breve durata) + `refresh_token` (lunga durata)
2. Richieste con `access_token` nel header Authorization
3. Token scaduto -> POST /api/auth/refresh con `refresh_token`
4. Logout -> invalida il refresh token

### Formato Risposta Standard

**Successo**:
```json
{
  "success": true,
  "data": { ... },
  "meta": { "total": 10, ... }
}
```

**Errore**:
```json
{
  "success": false,
  "error": "Messaggio errore leggibile",
  "code": "ERROR_CODE",
  "details": { "field_name": "Dettaglio specifico" }
}
```

Codici errore: `INVALID_REQUEST_TYPE`, `MISSING_REQUIRED_FIELD`, `UNAUTHORIZED`, `ACCESS_DENIED`, `NOT_FOUND`, `BAD_REQUEST`, `INTERNAL_ERROR`.

### Documentazione Swagger

Swagger UI disponibile a: `http://localhost/TherapyCRM/api/swagger`

---

## 12. Calendar App (React + TypeScript)

**Percorso**: `/frontend/web/calendar-app/`

La calendar app e' un'applicazione React con TypeScript che viene caricata all'interno di una view Yii2 (`/calendar/index`). Usa FullCalendar come libreria calendario e Shadcn UI (basata su Radix) per i componenti UI.

### Componenti Principali

```
src/
|-- pages/
|   +-- Index.tsx                     # Pagina principale - stato globale del calendario
|
|-- components/
|   |-- FullCalendarContainer.tsx     # Container FullCalendar con configurazione
|   |-- DualFullCalendarView.tsx      # Doppia vista calendario (settimana + mese)
|   |-- WeekCalendar.tsx              # Vista settimanale
|   |
|   |-- AppointmentModal.tsx          # Modal creazione appuntamento (da piano terapeutico)
|   |-- AppointmentEditModal.tsx      # Modal modifica appuntamento (il piu' grande, ~51 KB)
|   |-- PrivateAppointmentModal.tsx   # Modal creazione appuntamento privato
|   |
|   |-- TherapistSelector.tsx         # Selezione terapista (con colori calendario)
|   |-- PatientSelector.tsx           # Selezione paziente con ricerca
|   |-- SpecializationTreatmentSelector.tsx # Selezione specializzazione/trattamento
|   |
|   |-- TherapistSubstitutionModal.tsx # Modal per sostituzione terapista
|   |
|   |-- TherapistWeeklyHours.tsx      # Widget: ore settimanali effettive vs contrattuali
|   |-- PlanTherapyUsedHours.tsx      # Widget: ore consumate vs pianificate per terapia
|   |-- CalendarViewSelector.tsx      # Selettore tipo vista (giorno/settimana/mese)
|   |-- Toast.tsx                     # Notifiche toast
|   +-- ui/                           # ~51 componenti Shadcn UI
|
|-- lib/
|   +-- api.ts                        # Client API (~22 KB) - tutte le chiamate REST
|
|-- types/
|   +-- therapy.ts                    # Definizioni TypeScript per i tipi dati
|
+-- config/                           # Configurazione (API base URL, ecc.)
```

### Stato e Flusso Dati (Index.tsx)

```typescript
// Stati principali
selectedTherapist           // Terapista selezionato per la vista
appointments                // Lista globale appuntamenti
therapistAppointments       // Appuntamenti del terapista selezionato
isPrivateMode               // Toggle modalita' appuntamenti privati
isABARegime                 // Flag regime ABA per il paziente selezionato

// Ottimizzazione: aggiornamenti locali senza refresh del calendario
// Quando un appuntamento viene creato/modificato/eliminato, lo stato
// locale viene aggiornato con map/filter invece di ricaricare tutto.

// Handler principali
handleAppointmentCreate()   // Crea e aggiunge al local state
handleAppointmentUpdate()   // Aggiorna nel local state
handleAppointmentDelete()   // Rimuove dal local state
handleAppointmentMove()     // Drag & drop con update API + local state
handleSlotClick()           // Click su slot vuoto -> apre modal
```

### Configurazione FullCalendar

```
slotDuration: "00:05:00"     # Step di 5 minuti
slotMinTime: "08:00:00"      # Inizio giornata 8:00
slotMaxTime: "20:00:00"      # Fine giornata 20:00
firstDay: 1                  # Lunedi come primo giorno
```

### Build

```bash
cd frontend/web/calendar-app
npm install
npm run dev          # Sviluppo con hot reload
npm run build        # Build produzione -> dist/
npm run build:dev    # Build development
```

---

## 13. App Mobile (React Native)

**Percorso**: `/tp/`

Applicazione mobile cross-platform (iOS/Android) per pazienti e terapisti.

### Struttura Schermate

```
src/screens/
|-- auth/
|   |-- LoginScreen.js             # Login
|   +-- RegisterScreen.js          # Registrazione
|
|-- patient/
|   |-- PatientHomeScreen.js       # Dashboard paziente
|   |-- PatientCalendarScreen.js   # Calendario appuntamenti
|   |-- PatientProfileScreen.js    # Profilo paziente
|   |-- PatientRequestsScreen.js   # Lista richieste documenti
|   |-- CreateRequestScreen.js     # Crea richiesta documento
|   |-- RequestDetailsScreen.js    # Dettagli richiesta
|   |-- PatientNotificationsScreen.js
|   |-- NotificationDetailScreen.js
|   +-- ComplaintsScreen.js        # Reclami
|
+-- therapist/
    |-- TherapistHomeScreen.js     # Dashboard terapista
    |-- TherapistCalendarScreen.js # Calendario terapista (~65 KB)
    |-- TherapistPatientsScreen.js # Lista pazienti assegnati
    +-- TherapistNotificationsScreen.js
```

### Redux Store

```javascript
{
  auth: {
    user: { id, name, email, role, ... },
    token: "JWT_TOKEN",
    isAuthenticated: boolean
  },
  patient: {
    patients: [...],              // Lista pazienti (un account puo' avere piu' pazienti)
    currentPatient: { ... }       // Paziente attualmente selezionato
  },
  notifications: {
    items: [...],
    unreadCount: number,
    blockingNotifications: [...]  // Notifiche che bloccano l'uso dell'app
  }
}
```

### Notifiche Bloccanti

Le notifiche con `requires_read_confirmation = true` (tipo `mandatory_read`) bloccano l'uso dell'app finche' non vengono confermate tramite `BlockingNotificationOverlay`.

### Push Notifications (OneSignal)

L'app si registra su OneSignal al login. Le notifiche push vengono inviate dal backend PHP via `OneSignalService`.

---

## 14. Sistema Notifiche

### Tipi di Notifica

| Tipo | Label IT | Descrizione |
|------|----------|-------------|
| `info` | Informativa | Notifica generica informativa |
| `reminder` | Promemoria | Promemoria per appuntamenti, scadenze |
| `deadline` | Scadenza | Avviso scadenza piano terapeutico, ecc. |
| `mandatory_read` | Lettura Obbligatoria | Richiede conferma di lettura (bloccante su mobile) |
| `internal_communication` | Comunicazione Interna | Comunicazioni tra operatori del gestionale |

### Flusso Notifiche

```
Backend PHP (Yii2)
    |
    |-- Crea record in tabella notifications
    |-- Invia via OneSignalService (push notification)
    |
    v
Mobile App (React Native)
    |-- Riceve push notification
    |-- Mostra in-app notification
    |-- Se mandatory_read -> BlockingNotificationOverlay
    |
Frontend Web (Yii2)
    |-- Mostra in pagina notifiche
    +-- Badge contatore non lette
```

---

## 15. Richieste Documenti

### Workflow

```
1. Paziente/Familiare invia richiesta (app mobile)
   -> Status: INVIATA (1)
   -> Record storico creato automaticamente

2. Coordinatore prende in carico
   -> Status: PRESA_IN_CARICO (2)

3. Documento stampato/pronto
   -> Status: STAMPATO (3) - "Da ritirare"

4. Documento consegnato
   -> Status: CONSEGNATO (4) - "Evaso"
```

### Tipi di Richiesta (RequestType)

Configurabili con regole business:
- `therapeutic_plan_rule`: 1=Opzionale, 2=Non associabile, 3=Obbligatorio
- `allow_multiple_requests`: permette richieste duplicate simultanee
- `require_therapy_assignment`: richiede selezione terapia specifica
- `require_notes`: note obbligatorie

Tipi predefiniti:
1. Copia Piano Terapeutico (piano obbligatorio)
2. Relazione terapista (piano obbligatorio + terapia assegnata)
3. Relazione visita specialistica (piano non associabile)
4. Attestato frequenza (piano opzionale)
5. Altro (piano non associabile + multiple + note)

---

## 16. Comandi Console

```bash
# MIGRAZIONI DATABASE
./yii migrate                            # Applica tutte le migrazioni pending
./yii migrate/up 5                       # Applica le prossime 5
./yii migrate/down 1                     # Rollback ultima migrazione
./yii migrate/history                    # Storico migrazioni applicate

# RBAC
./yii rbac/setup-default-permissions     # Inizializza permessi default per tutti i ruoli
./yii rbac/list-roles                    # Elenca ruoli
./yii rbac/list-permissions              # Elenca permessi
./yii rbac/create-role [nome]            # Crea ruolo
./yii rbac/assign-role-to-user [uid] [ruolo] # Assegna ruolo a utente

# UTENTI
./yii user/...                           # Gestione utenti via CLI

# IMPORT DATI
./yii import/...                         # Import pazienti, terapisti, ecc.

# APPUNTAMENTI
./yii appointment/...                    # Gestione e manutenzione appuntamenti

# PIANI TERAPEUTICI
./yii therapeutic-plan/...               # Gestione piani
./yii therapeutic-plan-expiry/...        # Controllo scadenze piani

# NOTIFICHE
./yii notification/...                   # Invio notifiche programmate

# COMUNICAZIONI
./yii communication/...                  # Gestione comunicazioni

# EMAIL
./yii email/...                          # Invio email

# ASSENZE
./yii absence-data/...                   # Gestione dati assenze

# LOG
./yii activity-log/...                   # Manutenzione log attivita'

# TEST
./yii test-data/...                      # Generazione dati di test

# DATABASE
./yii database/...                       # Utilita' database
```

---

## 17. Componenti e Servizi Condivisi

### Componenti (`common/components/`)

| Componente | Descrizione |
|------------|-------------|
| `JwtComponent` | Generazione, validazione e refresh JWT token |
| `JwtAuthBehavior` | Behavior per controller API che richiede autenticazione JWT |
| `NotificationService` | Creazione e invio notifiche |
| `OneSignalService` | Integrazione push notification via OneSignal |
| `PlanHelper` | Logica helper per piani terapeutici |
| `Helper` | Utilita' generiche |
| `CodiceFiscaleGenerator` | Generazione codice fiscale italiano |

### Behaviors (`common/behaviors/`)

| Behavior | Descrizione |
|----------|-------------|
| `ActivityLogBehavior` | Logging automatico di tutte le operazioni CRUD sui modelli. Registra utente, azione, vecchi/nuovi valori in JSON, IP, user agent. |

### Servizi Statistiche (`common/services/statistics/`)

| Servizio | Descrizione |
|----------|-------------|
| `StatisticsService` | Statistiche generali |
| `AbsenceStatisticsService` | Statistiche assenze |
| `PatientStatisticsService` | Statistiche pazienti |
| `TreatmentStatisticsService` | Statistiche trattamenti |

---

## 18. Regole di Business Importanti

### Ore Terapia e Conteggio

- Ogni `PlanTherapy` ha `weekly_hours` che indica le ore settimanali (o mensili per ABA)
- Il campo `conteggio_ore` del `Regime` determina se il conteggio e' `weekly` o `monthly`
- Il widget `TherapistWeeklyHours` nella calendar app mostra le ore effettive vs contrattuali del terapista
- Il widget `PlanTherapyUsedHours` mostra le ore consumate vs pianificate per ogni terapia

### Regola del 10% Assenze

Pazienti che superano il 10% di assenze ingiustificate rispetto agli appuntamenti totali subiscono conseguenze. Il modello `PatientAbsenceSearch` gestisce queste statistiche.

### Regime ABA - Requisiti Speciali

Per i piani con regime ABA:
- Pazienti sotto 14 anni: obbligatori Supervisione + Parent Training + almeno una terapia
- Pazienti sopra 14 anni: obbligatoria almeno una terapia principale
- Il campo `conteggio_ore` per ABA e' tipicamente `monthly`

### Sostituzioni Terapista

Quando un terapista e' assente:
1. Gli appuntamenti vengono marcati come `therapist_absent`
2. Il coordinatore/manager puo' assegnare un sostituto
3. L'`original_therapist_id` viene salvato sull'appuntamento
4. Il `therapist_id` viene aggiornato al sostituto
5. Un record `TherapistSubstitution` traccia la sostituzione

### Gruppi Coordinatore

- Un coordinatore gestisce un gruppo di terapisti
- I terapisti sono attivi nel gruppo quando `assigned_to IS NULL` nella tabella `group_therapists`
- Quando un terapista viene rimosso dal gruppo, `assigned_to` viene impostata alla data di rimozione
- I coordinatori vedono solo i terapisti del proprio gruppo

### Appuntamenti da Piano vs Privati

- **Da piano** (`therapeutic_plan`): collegati a una `PlanTherapy`, il paziente viene derivato dal piano
- **Privati** (`private`): hanno `patient_id` diretto e `treatment_type_id`, possono essere organizzati in `PrivateCycle`

### Pattern Ricorrenti

I pattern definiscono appuntamenti settimanali ricorrenti:
1. Si crea un pattern con giorno, ora, durata, periodo validita'
2. Il sistema genera appuntamenti singoli per ogni settimana nel periodo
3. Eliminando un pattern si possono eliminare anche gli appuntamenti futuri

### Activity Log

Tutte le operazioni CRUD su tutti i modelli principali vengono automaticamente loggate tramite `ActivityLogBehavior`:
- Azione (create/update/delete)
- Utente che ha eseguito l'azione
- Vecchi e nuovi valori in formato JSON
- IP address e User Agent
- Timestamp

---

## 19. Configurazione e Deploy

### Ambienti

```
environments/
|-- dev/        # Configurazione sviluppo (debug attivo, errori dettagliati)
|-- prod/       # Configurazione produzione
+-- test/       # Configurazione test
```

Per inizializzare l'ambiente:
```bash
php init        # Seleziona ambiente interattivamente
```

### Configurazione Principale

```php
// common/config/main.php - Componenti condivisi
'db' => [...],           // Connessione MySQL
'jwt' => [...],          // JwtComponent
'oneSignal' => [...],    // OneSignalService

// api/config/main.php - Configurazione API
'urlManager' => [...],   // Routing REST
'response' => ['format' => 'json'],  // Risposte JSON

// frontend/config/main.php - Configurazione frontend web
// backend/config/main.php - Configurazione admin
```

### Timezone

- API: `date_default_timezone_set('UTC')` - tutti i timestamp in UTC
- Frontend Yii2/Notifiche: `Europe/Rome`

### Build Calendar App

```bash
cd frontend/web/calendar-app
npm install
npm run dev          # Sviluppo
npm run build        # Produzione -> dist/
```

### Build App Mobile

```bash
cd tp
npm install
npx react-native run-android --device DEVICE_ID
npx react-native run-ios
./build-release.sh   # APK release
```

### Migrazioni Database

```bash
./yii migrate              # Applica tutte le pending
./yii migrate/up 5         # Applica le prossime 5
./yii migrate/down 1       # Rollback ultima
```

---

## 20. Documentazione Esistente

| File | Contenuto |
|------|-----------|
| `APPOINTMENT_PATTERNS_RULES.md` | Regole dettagliate per i pattern appuntamenti ricorrenti |
| `NOTIFICATION_SYSTEM.md` | Dettagli sistema notifiche |
| `NOTIFICATION_DEBUG_GUIDE.md` | Guida debug notifiche |
| `NOTIFICATION_PAGINATION_UPDATE.md` | Aggiornamenti paginazione notifiche |
| `CRONJOB.md` | Configurazione cron jobs |
| `README_ACTIVITY_LOG.md` | Documentazione sistema activity log |
| `PERFORMANCE_ANALYSIS_REPORT.md` | Report analisi performance |
| `DOCUMENT_REQUESTS_IMPLEMENTATION.md` | Implementazione richieste documenti |
| `PROMPT_DOCUMENT_REQUEST_STATUS_HISTORY_IMPLEMENTATION.md` | Storico stati richieste |
| `therapy-rules.mdc` | Regole e pattern di sviluppo (Cursor rules) |
| `docs/` | Cartella documentazione aggiuntiva |

---

*Documentazione generata il 2026-02-26 - Basata sull'analisi diretta del codice sorgente*
