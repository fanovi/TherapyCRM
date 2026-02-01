# TherapyCRM - Documentazione Completa

> Sistema multi-piattaforma per la gestione di appuntamenti e terapie in studi medici/terapeutici.

---

## Indice

1. [Panoramica Sistema](#1-panoramica-sistema)
2. [Architettura](#2-architettura)
3. [Stack Tecnologico](#3-stack-tecnologico)
4. [Struttura Cartelle](#4-struttura-cartelle)
5. [Backend API REST](#5-backend-api-rest)
6. [Frontend Web - Calendar App](#6-frontend-web---calendar-app)
7. [App Mobile](#7-app-mobile)
8. [Database](#8-database)
9. [Modelli Dati Principali](#9-modelli-dati-principali)
10. [Flussi Utente](#10-flussi-utente)
11. [Sistema Notifiche](#11-sistema-notifiche)
12. [Autenticazione](#12-autenticazione)
13. [Configurazione e Deploy](#13-configurazione-e-deploy)

---

## 1. Panoramica Sistema

TherapyCRM è una piattaforma completa per gestire:

- **Pazienti**: anagrafica, piani terapeutici, richieste documenti
- **Terapisti**: disponibilità, ore settimanali, specializzazioni, assenze
- **Appuntamenti**: calendario interattivo, pattern ricorrenti, sostituzioni
- **Comunicazioni**: notifiche push, email, messaggi
- **Statistiche**: analisi utilizzo, report ore, performance

### Utenti del Sistema

| Ruolo | Accesso | Funzionalità |
|-------|---------|--------------|
| **Admin** | Web Backend | Gestione completa sistema |
| **Coordinatore** | Web Frontend | Gestione terapisti e pazienti del gruppo |
| **Terapista** | Web + Mobile | Visualizza calendario, gestisce appuntamenti |
| **Paziente** | Mobile App | Visualizza appuntamenti, richieste, notifiche |

---

## 2. Architettura

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT TIER                              │
├─────────────────────────────────────────────────────────────────┤
│  Web Calendar App     │  Mobile App (iOS/Android)  │  Admin Web │
│  React + TypeScript   │  React Native              │  Yii2 PHP  │
│  /frontend/web/       │  /tp/                      │  /frontend/│
│  calendar-app/        │                            │            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         API TIER                                 │
├─────────────────────────────────────────────────────────────────┤
│                    REST API (Yii2)                               │
│                    /api/controllers/                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐   │
│  │   Auth   │ │ Calendar │ │ Requests │ │  Notifications   │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BUSINESS LOGIC TIER                         │
├─────────────────────────────────────────────────────────────────┤
│  Common Models (53)  │  Services  │  Helpers  │  Components     │
│  /common/models/     │            │           │                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       DATA TIER                                  │
├─────────────────────────────────────────────────────────────────┤
│                    MySQL Database                                │
│                    98 migration files                            │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Stack Tecnologico

### Backend
| Tecnologia | Versione | Uso |
|------------|----------|-----|
| PHP | 7.4+ | Linguaggio backend |
| Yii 2 Framework | 2.0.45 | MVC Framework |
| MySQL | 5.7+ | Database |
| JWT | firebase/php-jwt 6.11 | Autenticazione API |
| Swagger | zircote/swagger-php 5.1 | Documentazione API |

### Frontend Web (Calendar App)
| Tecnologia | Versione | Uso |
|------------|----------|-----|
| React | 18.3.1 | UI Framework |
| TypeScript | 5.5.3 | Type safety |
| Vite | 5.4.1 | Build tool |
| TailwindCSS | 3.4.11 | Styling |
| FullCalendar | 6.1.18 | Calendario |
| Shadcn UI | - | Componenti UI |
| React Query | 5.56.2 | Data fetching |
| React Hook Form | 7.53.0 | Form management |
| Zod | 3.23.8 | Validazione |

### App Mobile
| Tecnologia | Versione | Uso |
|------------|----------|-----|
| React Native | 0.79.3 | Framework mobile |
| React | 19.0.0 | UI |
| Redux Toolkit | 2.8.2 | State management |
| React Navigation | 7.x | Navigazione |
| React Native Paper | 5.14.5 | Material Design UI |
| OneSignal | 5.2.12 | Push notifications |
| Axios | 1.9.0 | HTTP client |

---

## 4. Struttura Cartelle

```
TherapyCRM/
├── api/                    # REST API
│   ├── controllers/        # 10 controller (Auth, Calendar, Requests...)
│   ├── config/             # Configurazione API
│   └── docs/               # Documentazione API
│
├── backend/                # Backend amministrativo Yii2
│   ├── controllers/        # 5 controller
│   └── views/              # Template admin
│
├── common/                 # Codice condiviso
│   ├── models/             # 53 modelli dati
│   ├── components/         # Helper, JWT, Services
│   ├── services/           # Business logic
│   └── helpers/            # Funzioni utility
│
├── console/                # Console commands
│   ├── controllers/        # 15 command controller
│   └── migrations/         # 98 file migrazione DB
│
├── frontend/               # Frontend Yii2
│   ├── controllers/        # 19 controller
│   ├── views/              # Template HTML
│   └── web/
│       └── calendar-app/   # ← REACT APP (vedi sezione 6)
│
├── tp/                     # App Mobile React Native
│   ├── src/
│   │   ├── screens/        # 23 schermate
│   │   ├── components/     # 13 componenti
│   │   ├── services/       # 12 servizi
│   │   ├── api/            # 5 moduli API
│   │   ├── hooks/          # 6 custom hooks
│   │   ├── store/          # Redux store
│   │   └── navigation/     # React Navigation
│   ├── android/            # Codice nativo Android
│   └── ios/                # Codice nativo iOS
│
├── vendor/                 # Dipendenze PHP (Composer)
├── environments/           # Config per ambiente
└── imports/                # Import dati
```

---

## 5. Backend API REST

**Base URL**: `/api/`

### Controllers Principali

#### AuthController.php (69 KB)
Gestisce autenticazione e autorizzazione.

```
POST /api/auth/login          # Login con email/password
POST /api/auth/register       # Registrazione nuovo utente
POST /api/auth/refresh        # Refresh JWT token
POST /api/auth/logout         # Logout
GET  /api/auth/me             # Dati utente corrente
POST /api/auth/forgot-password # Reset password
```

#### CalendarController.php (95 KB)
Gestione completa calendario e appuntamenti.

```
# Appuntamenti
GET  /api/calendar/appointments          # Lista appuntamenti
POST /api/calendar/create-appointment    # Crea appuntamento
PUT  /api/calendar/update-appointment    # Modifica appuntamento
DELETE /api/calendar/delete-appointment  # Elimina appuntamento

# Pattern ricorrenti
POST /api/calendar/create-pattern        # Crea pattern ricorrente
DELETE /api/calendar/delete-pattern      # Elimina pattern

# Appuntamenti privati
POST /api/calendar/create-private-appointment
POST /api/calendar/create-private-cycle

# Terapisti
GET  /api/calendar/therapists            # Lista terapisti
GET  /api/calendar/therapist-appointments # Appuntamenti terapista
GET  /api/calendar/therapist-weekly-hours # Ore settimanali

# Pazienti
GET  /api/calendar/patient-appointments  # Appuntamenti paziente
GET  /api/calendar/patient               # Dati paziente

# Assenze
GET  /api/calendar/therapist-absences    # Assenze terapista

# Piano Terapeutico
GET  /api/calendar/plan-therapy-hours    # Ore piano terapeutico
GET  /api/calendar/plan-therapy-for-therapist
```

#### RequestsController.php (82 KB)
Gestione richieste documenti pazienti.

```
GET  /api/requests                    # Lista richieste
GET  /api/requests/types              # Tipi di richiesta disponibili
POST /api/requests/create             # Crea nuova richiesta
GET  /api/requests/{id}               # Dettagli richiesta
PUT  /api/requests/{id}/cancel        # Annulla richiesta
GET  /api/requests/{id}/download      # Download documento
```

#### NotificationController.php (21 KB)
Sistema notifiche.

```
GET  /api/notifications               # Lista notifiche
PUT  /api/notifications/{id}/read     # Segna come letta
GET  /api/notifications/unread-count  # Conteggio non lette
GET  /api/notifications/blocking      # Notifiche bloccanti
```

---

## 6. Frontend Web - Calendar App

**Percorso**: `/frontend/web/calendar-app/`

### Architettura Componenti

```
src/
├── pages/
│   └── Index.tsx                 # Pagina principale (60 KB)
│
├── components/
│   ├── DualFullCalendarView.tsx  # Doppia vista calendario (20 KB)
│   ├── FullCalendarContainer.tsx # Container calendario (29 KB)
│   ├── WeekCalendar.tsx          # Vista settimanale (14 KB)
│   │
│   ├── AppointmentModal.tsx      # Modal creazione appuntamento
│   ├── AppointmentEditModal.tsx  # Modal modifica appuntamento (51 KB)
│   ├── PrivateAppointmentModal.tsx # Modal appuntamento privato
│   │
│   ├── TherapistSelector.tsx     # Selezione terapista
│   ├── PatientSelector.tsx       # Selezione paziente
│   ├── TherapistSubstitutionModal.tsx # Sostituzione terapista
│   │
│   ├── TherapistWeeklyHours.tsx  # Widget ore settimanali
│   ├── PlanTherapyUsedHours.tsx  # Widget ore piano terapeutico
│   │
│   └── ui/                       # 51 componenti Shadcn UI
│
├── lib/
│   └── api.ts                    # Client API (22 KB)
│
├── hooks/
│   └── use-toast.ts              # Toast notifications
│
└── types/
    └── therapy.ts                # TypeScript types
```

### Componente Principale: Index.tsx

Gestisce lo stato globale del calendario:

```typescript
// Stati principali
const [selectedTherapist, setSelectedTherapist] = useState<Therapist | null>(null);
const [appointments, setAppointments] = useState<Appointment[]>([]);
const [therapistAppointments, setTherapistAppointments] = useState<Appointment[]>([]);
const [isPrivateMode, setIsPrivateMode] = useState(false);
const [isABARegime, setIsABARegime] = useState(false);
const [refreshKey, setRefreshKey] = useState(0);
const [weeklyHoursRefreshTrigger, setWeeklyHoursRefreshTrigger] = useState(0);

// Handler principali
handleAppointmentCreate()    // Crea appuntamento
handleAppointmentUpdate()    // Aggiorna appuntamento (local state update)
handleAppointmentDelete()    // Elimina appuntamento
handleAppointmentMove()      // Sposta appuntamento (drag & drop)
handleSlotClick()            // Click su slot calendario
```

### Gestione Stato Locale (Ottimizzazioni)

Per evitare re-render del calendario, gli aggiornamenti usano stato locale:

```typescript
// Aggiornamento locale senza refresh
if (options?.updatedData) {
  setAppointments((prev) => prev.map(updateAppointment));
  setTherapistAppointments((prev) => prev.map(updateAppointment));

  // Aggiorna solo ore settimanali se necessario
  if (options.updatedData.duration || options.updatedData.therapistId) {
    setWeeklyHoursRefreshTrigger((prev) => prev + 1);
  }
}
```

### Configurazione Calendario

```typescript
// FullCalendarContainer.tsx
slotDuration="00:05:00"      // Step 5 minuti
slotMinTime="08:00:00"       // Inizio 8:00
slotMaxTime="20:00:00"       // Fine 20:00
firstDay={1}                 // Lunedì primo giorno
```

---

## 7. App Mobile

**Percorso**: `/tp/`

### Struttura Screens

```
src/screens/
├── auth/                          # Autenticazione
│   ├── LoginScreen.js             # Login
│   ├── RegisterScreen.js          # Registrazione
│   └── ...
│
├── patient/                       # Schermate paziente
│   ├── PatientHomeScreen.js       # Dashboard (14 KB)
│   ├── PatientCalendarScreen.js   # Calendario (23 KB)
│   ├── PatientProfileScreen.js    # Profilo
│   ├── PatientRequestsScreen.js   # Lista richieste
│   ├── CreateRequestScreen.js     # Crea richiesta
│   ├── RequestDetailsScreen.js    # Dettagli richiesta
│   ├── PatientNotificationsScreen.js
│   ├── NotificationDetailScreen.js
│   └── ComplaintsScreen.js        # Reclami
│
└── therapist/                     # Schermate terapista
    ├── TherapistHomeScreen.js     # Dashboard
    ├── TherapistCalendarScreen.js # Calendario (65 KB)
    ├── TherapistPatientsScreen.js # Pazienti assegnati
    ├── TherapistNotificationsScreen.js
    └── ...
```

### Componenti Riutilizzabili

```
src/components/
├── ScreenTemplate.js              # Template base per tutte le schermate
│   Props:
│   - title, subtitle
│   - showBackButton              # Mostra freccia indietro
│   - showNotifications           # Badge notifiche
│   - scrollable                  # Abilita scroll
│
├── BlockingNotificationOverlay.js # Overlay notifiche bloccanti
├── NotificationBadge.js           # Badge contatore notifiche
├── FloatingPatientSelector.js     # Selettore paziente flottante
└── NotificationModal.js           # Modal notifiche
```

### Redux Store

```javascript
// store/store.js
{
  auth: {
    user: { id, name, email, role, ... },
    token: "JWT_TOKEN",
    isAuthenticated: boolean
  },
  patient: {
    patients: [...],              // Lista pazienti (per account con più pazienti)
    currentPatient: { ... }       // Paziente selezionato
  },
  notifications: {
    items: [...],
    unreadCount: number,
    blockingNotifications: [...]
  }
}
```

### Servizi API

```
src/api/
├── auth.js           # Login, logout, refresh token
├── calendar.js       # Appuntamenti paziente
├── requests.js       # Richieste documenti
├── notifications.js  # Notifiche
└── complaints.js     # Reclami
```

---

## 8. Database

### Tabelle Principali

```sql
-- UTENTI
user                    -- Utenti base (login)
user_profile            -- Profili estesi
auth_token              -- JWT tokens
auth_assignment         -- RBAC ruoli
auth_item               -- Definizione ruoli/permessi

-- PAZIENTI
patient                 -- Anagrafica pazienti
account_patient         -- Collegamento account-paziente

-- TERAPISTI
therapist               -- Anagrafica terapisti
coordinator_group       -- Gruppi di coordinamento
group_therapist         -- Appartenenza a gruppi

-- PIANI TERAPEUTICI
therapeutic_plan        -- Piani terapeutici
plan_therapy            -- Terapie nei piani
specialization          -- Specializzazioni
treatment_type          -- Tipologie trattamento
specialization_treatment -- Collegamento spec-treatment

-- APPUNTAMENTI
appointment             -- Appuntamenti singoli
appointment_pattern     -- Pattern ricorrenti
private_cycle           -- Cicli privati
therapist_busy_slot     -- Slot occupati

-- ASSENZE
absence                 -- Assenze terapisti
absence_counter         -- Contatori assenze
absence_recovery        -- Recuperi

-- RICHIESTE
document_request        -- Richieste documenti
document_request_status_history -- Storico stati
request_type            -- Tipi richiesta
request_status          -- Stati possibili

-- COMUNICAZIONI
notification            -- Notifiche
notification_template   -- Template notifiche
complaint               -- Reclami

-- CONFIGURAZIONE
setting                 -- Impostazioni globali
regime_setting          -- Impostazioni per regime
district                -- Distretti
provincia               -- Province
comune                  -- Comuni
```

### Migrazioni

Le migrazioni sono in `/console/migrations/` (98 file).

Per eseguire migrazioni:
```bash
./yii migrate              # Applica tutte le pending
./yii migrate/up 5         # Applica le prossime 5
./yii migrate/down 1       # Rollback ultima
./yii migrate/history      # Storico
```

---

## 9. Modelli Dati Principali

### Appointment (appointment.php)

```php
class Appointment extends ActiveRecord {
    // Campi principali
    public $id;
    public $patient_id;
    public $therapist_id;
    public $plan_therapy_id;
    public $datetime;              // YYYY-MM-DD HH:mm:ss
    public $duration;              // Minuti
    public $status;                // scheduled, completed, cancelled, etc.
    public $notes;
    public $is_group;              // Appuntamento di gruppo
    public $group_session_id;      // ID sessione gruppo
    public $appointment_type;      // terapia, parent_training, supervisione
    public $appointment_source;    // therapeutic_plan, private
    public $appointment_category;  // regular, recovery
    public $setting_id;            // Setting (domicilio, ambulatorio, etc.)
    public $pattern_id;            // Riferimento pattern ricorrente

    // Relazioni
    public function getPatient();
    public function getTherapist();
    public function getPlanTherapy();
    public function getPattern();
}
```

### TherapeuticPlan (therapeutic_plan.php)

```php
class TherapeuticPlan extends ActiveRecord {
    public $id;
    public $patient_id;
    public $start_date;
    public $end_date;
    public $status;               // active, expired, suspended
    public $regime_id;            // ABA, standard, etc.
    public $district_id;
    public $notes;

    // Relazioni
    public function getPatient();
    public function getPlanTherapies();  // Terapie nel piano
    public function getRegime();
}
```

### Patient (patient.php)

```php
class Patient extends ActiveRecord {
    public $id;
    public $name;
    public $fiscal_code;
    public $birth_date;
    public $email;
    public $phone;
    public $address;
    public $city;
    public $provincia;

    // Relazioni
    public function getTherapeuticPlans();
    public function getAppointments();
    public function getAccountPatients();
}
```

### Therapist (therapist.php)

```php
class Therapist extends ActiveRecord {
    public $id;
    public $user_id;
    public $name;
    public $email;
    public $specialization;
    public $color;                 // Colore nel calendario
    public $weekly_hours;          // Ore contrattuali settimanali

    // Relazioni
    public function getUser();
    public function getAppointments();
    public function getAbsences();
    public function getCoordinatorGroups();
}
```

---

## 10. Flussi Utente

### Creazione Appuntamento (Web)

```
1. Utente seleziona terapista (TherapistSelector)
2. Utente clicca su slot vuoto nel calendario
3. Se isPrivateMode → apre PrivateAppointmentModal
   Altrimenti → apre AppointmentModal
4. Compila form (durata, note, tipo, setting)
5. Se isRecurring → crea pattern via createPattern API
   Altrimenti → crea singolo via createAppointment API
6. Aggiornamento stato locale (no refresh calendario)
7. Incrementa weeklyHoursRefreshTrigger per aggiornare widget ore
```

### Modifica Appuntamento (Web)

```
1. Utente clicca su appuntamento esistente
2. Apre AppointmentEditModal con dati precaricati
3. Modifica campi desiderati
4. Salva via updateAppointment API
5. Callback onAppointmentUpdate con updatedData
6. Aggiornamento stato locale (map su appointments)
7. Se cambia durata/terapista → refresh widget ore
```

### Visualizzazione Calendario (Mobile)

```
1. PatientCalendarScreen carica appuntamenti mese corrente
2. Mostra calendario con date evidenziate (markedDates)
3. Utente seleziona data
4. Carica appuntamenti del giorno (getPatientAppointments)
5. Mostra lista appuntamenti scrollabile
6. Click su appuntamento → dettagli
7. Pull-to-refresh → ricarica dati
```

### Creazione Richiesta Documento (Mobile)

```
1. PatientRequestsScreen mostra lista richieste
2. Utente clicca "+" → CreateRequestScreen
3. Seleziona tipo richiesta (dropdown con tipi disponibili)
4. Compila campi richiesti per quel tipo
5. Submit via createRequest API
6. Torna a lista richieste con refresh
7. Notifica push al coordinatore
```

---

## 11. Sistema Notifiche

### Architettura

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Backend   │────▶│  OneSignal  │────▶│ Mobile App  │
│   (PHP)     │     │   (Push)    │     │   (RN)      │
└─────────────┘     └─────────────┘     └─────────────┘
       │
       ▼
┌─────────────┐
│  Database   │
│ (notification)
└─────────────┘
```

### Tipi di Notifica

| Tipo | Destinatario | Trigger |
|------|--------------|---------|
| appointment_reminder | Paziente | 24h prima appuntamento |
| appointment_cancelled | Paziente/Terapista | Cancellazione |
| request_status_update | Paziente | Cambio stato richiesta |
| document_ready | Paziente | Documento disponibile |
| blocking_notification | Paziente | Richiede azione immediata |

### Notifiche Bloccanti

Le notifiche bloccanti impediscono l'uso dell'app finché non vengono gestite:

```javascript
// Mobile: BlockingNotificationOverlay.js
// Si sovrappone a tutta l'app quando ci sono notifiche bloccanti
<Modal visible={hasBlockingNotifications}>
  <NotificationContent />
  <ActionButtons />
</Modal>
```

### Integrazione OneSignal

```javascript
// tp/src/services/oneSignalService.js
OneSignal.initialize(ONESIGNAL_APP_ID);

// Registra device per push
OneSignal.User.pushSubscription.addEventListener('change', (subscription) => {
  // Invia player_id al backend
});

// Gestisce notifica in foreground
OneSignal.Notifications.addEventListener('foregroundWillDisplay', (event) => {
  // Mostra notifica custom o lascia default
});
```

---

## 12. Autenticazione

### Flusso JWT

```
1. Login (email + password)
   POST /api/auth/login
   ← { access_token, refresh_token, expires_in, user }

2. Richieste autenticate
   Headers: Authorization: Bearer {access_token}

3. Token scaduto
   POST /api/auth/refresh
   Body: { refresh_token }
   ← { access_token, refresh_token }

4. Logout
   POST /api/auth/logout
   → Invalida refresh token
```

### Componenti Backend

```php
// common/components/JwtComponent.php
class JwtComponent {
    public function generateToken($user);      // Genera JWT
    public function validateToken($token);     // Valida JWT
    public function refreshToken($refreshToken);
}

// common/components/JwtAuthBehavior.php
class JwtAuthBehavior {
    // Behavior per controller che richiede auth
    public function beforeAction($action);
}
```

### Mobile: Gestione Token

```javascript
// tp/src/services/authService.js
class AuthService {
  async login(email, password);
  async refreshToken();
  async logout();

  // Interceptor Axios per refresh automatico
  setupInterceptors() {
    axios.interceptors.response.use(
      response => response,
      async error => {
        if (error.response?.status === 401) {
          await this.refreshToken();
          return axios.request(error.config);
        }
      }
    );
  }
}
```

---

## 13. Configurazione e Deploy

### Ambienti

```
environments/
├── dev/                  # Sviluppo
├── prod/                 # Produzione
└── test/                 # Test
```

### Configurazione Principale

```php
// common/config/main.php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=therapy_crm',
            'username' => 'root',
            'password' => '',
        ],
        'jwt' => [
            'class' => 'common\components\JwtComponent',
            'key' => 'your-secret-key',
        ],
        'oneSignal' => [
            'class' => 'common\components\OneSignalService',
            'appId' => 'YOUR_APP_ID',
            'apiKey' => 'YOUR_API_KEY',
        ],
    ],
];
```

### Comandi Console Utili

```bash
# Migrazioni
./yii migrate

# RBAC
./yii rbac/init              # Inizializza ruoli e permessi

# Import dati
./yii import/patients        # Importa pazienti
./yii import/therapists      # Importa terapisti

# Manutenzione
./yii appointment/cleanup    # Pulisce appuntamenti vecchi
./yii therapeutic-plan/check-expiry  # Controlla scadenze piani

# Test data
./yii test-data/generate     # Genera dati di test
```

### Build Frontend Calendar App

```bash
cd frontend/web/calendar-app

# Sviluppo
npm install
npm run dev

# Produzione
npm run build
# Output in dist/
```

### Build App Mobile

```bash
cd tp

# Sviluppo
npm install
npx react-native run-android --device DEVICE_ID
npx react-native run-ios

# Release Android
./build-release.sh
# APK in android/app/build/outputs/apk/release/
```

---

## Appendice: File Documentazione Esistenti

| File | Contenuto |
|------|-----------|
| `APPOINTMENT_PATTERNS_RULES.md` | Regole pattern appuntamenti ricorrenti |
| `NOTIFICATION_SYSTEM.md` | Dettagli sistema notifiche |
| `CRONJOB.md` | Configurazione cron jobs |
| `tp/BACKEND_API_DOCUMENTATION.md` | Documentazione API per mobile |
| `tp/ONESIGNAL_SETUP.md` | Setup OneSignal |
| `frontend/docs/CALENDAR_INTEGRATION.md` | Integrazione FullCalendar |
| `frontend/docs/STATISTICS_MODULE.md` | Modulo statistiche |

---

## Changelog Recente

- **2026-01-24**: Nascosto nome terapista per privacy in app mobile
- **2026-01-24**: Formato ore in "Xh Ym" invece di decimali
- **2026-01-24**: Calendario paziente scrollabile
- **2026-01-24**: Pulsante indietro in dettagli richiesta
- **2026-01-24**: Step calendario a 5 minuti
- **2026-01-24**: Ottimizzazioni refresh calendario (local state updates)

---

*Documentazione generata il 2026-02-01*
