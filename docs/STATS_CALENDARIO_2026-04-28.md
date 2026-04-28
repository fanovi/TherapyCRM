# Branch `stats_calendario` — punto situazione 2026-04-28

Documento di passaggio per chi riprende il lavoro: cosa e' stato fatto in
questa sessione, dove vivono i fix, cosa serve per il deploy.

## TL;DR

40 commit nuovi su `stats_calendario` (HEAD: `7ea74cd`). Quattro grandi
filoni: RBAC/permessi, flusso account paziente, calendario coordinator
con CORS cross-site, redesign view minori. Due migration DB.

## Migrazioni da applicare in pre-prod

```bash
cd /var/www/TherapyCRM
git pull origin stats_calendario
php yii migrate/up
sudo cp -r frontend/web/calendar-app/dist/ /var/www/calendarCGM/
sudo systemctl reload php8.2-fpm
```

| Migration | Cosa fa |
|---|---|
| `m260428_194122_normalize_self_account_role_to_patient_family` | Cambia auth_assignment dei self account: role `patient` -> `patient_family` (l'unico con `app_login` dopo m260303). Era il motivo per cui i self non potevano loggarsi all'app e non comparivano nelle liste account |
| `m260428_200209_set_parental_authority_for_self_accounts` | Setta `has_parental_authority = 1` su tutti gli `account_patients` con `relationship_type = 'self'`. Implicito: il paziente sopra se stesso ha autorita |

## RBAC e permessi

- **Self/family role**: tutti gli account creati via `actionCreateCredentials`
  e `actionLinkPatient` ora ricevono **sempre** il role `patient_family`
  (anche per relation `self`). Stesso fix in `TestDataController`.
- **Editor permessi**: `app_login` non e' piu' visibile/assegnabile come
  permesso extra a ruoli che non sono `therapist` o `patient_family`
  (`PermissionController::getPermissionData` + `saveExtraPermissions`).
- **Pagina "I miei permessi"** (`/site/my-permissions`): nuova vista in sola
  lettura accessibile a chiunque sia autenticato. Mostra ruoli assegnati,
  permessi derivati e permessi extra direct. Filtra via `PermissionMetadata.is_active = 0`
  per coerenza con `/permission/roles`. Link nel dropdown utente del header.

## Flusso account paziente

Sezione `/patient/...` molto rifatta:

- **`/patient/accounts`** (grid):
  - Bottoni icona-only (Visualizza/Modifica/Rigenera) stile `/patient/index`
  - Modale "Nuovo Account" rifatta in SweetAlert2 (era handcrafted con
    backdrop e markup inline, dropdown che stretchava la modale)
- **`/patient/edit-account?id=N`** (nuova vista dedicata): rimpiazza la
  vecchia modale `editAccountModal` di `view-account`. Form full-page
  con anche sezione "Pazienti Collegati" per modificare `relationship_type`
  e `has_parental_authority` di ciascun AccountPatient. Self bloccato
  (relazione fissa, autorita implicita).
- **`/patient/view-account?id=N`**: badge espliciti per relazione
  (icona persone) e autorita parentale (Si/No). Modale rimossa.
- **`/patient/view?id=N`**: 4 icone-only sugli account collegati
  (view, update, send-credentials, reset-password).
- **`/patient/create-credentials?id=N`**: bottone Modifica accanto agli
  account esistenti ora porta a `/patient/edit-account` (era `/view-account`),
  icona-only matita gialla coerente.

## Notifiche pazienti

Helper centralizzato per gating notifiche/comunicazioni:

```php
\common\models\AccountPatient::getNotifiableUserIdsForPatient(int $patientId): int[]
```

Restituisce gli user id degli account `self` o `has_parental_authority = 1`
del paziente, filtrati per `users.status = active`. Usato in
`AbsenceController::sendRemoveAbsenceNotifications` (prima notificava
tutti i familiari indistintamente). Helper riutilizzabile per qualunque
nuovo flusso notifiche-paziente futuro.

## Calendario / coordinator filter / CORS

Tutta la zona `/calendar/...` (Yii) + iframe React `calendar-cgm.badil.it`:

- **Read-only patient view**: `/calendar/<patient_id>` per coordinator
  senza `manage_calendar` mostra solo il calendario paziente a larghezza
  piena, niente selettori/toggle. Con `manage_calendar` comportamento
  invariato (doppio calendario + creazione).
- **Read-only flag**: calcolato lato Yii (`view-source` in pagina padre)
  e passato all'iframe via `?readOnly=1`. Era basato su `update_appointment`
  (dead permission) -> sostituito con `manage_calendar`.
- **Filtro coordinator**: il coordinator (puro, no admin/manager) vede
  SOLO terapisti del proprio gruppo + specializzazioni coperte dal gruppo.
  Filtro applicato a 5 endpoint API in `TherapeuticPlanManagerController`:
  - `actionGetTherapists`
  - `actionGetTherapistsBySpecialization`
  - `actionGetTherapistsByTreatment`
  - `actionGetTreatmentTypes`
  - `actionGetPlanTreatments`
  Helper privati: `getCoordinatorTherapistFilter()`,
  `getCoordinatorTreatmentTypeFilter()`. Check via DB query su
  `auth_assignment` (piu robusto di `Yii::$app->user->can($role)`).
- **CORS cross-site cookies** (importantissimo, tante implicazioni):
  - L'iframe React e' su `calendar-cgm.badil.it`, il backend su
    `app-cgm.badil.it`. Default `fetch` non manda cookies cross-origin.
  - Backend vedeva sempre **guest** -> `Yii::$app->user->id` null
    -> `$absence->approved_by ?: 1`, `created_by ?: 1`,
    `substituted_by ?: 1`, ActivityLog con userId sbagliato, etc.
  - Fix in tre punti:
    - React `api.ts`: `credentials: 'include'` su tutti i fetch
    - Backend `corsFilter` in `TherapeuticPlanManagerController`:
      Origin specifici (no wildcard) + `Access-Control-Allow-Credentials: true`
    - Yii session/identity cookie: `SameSite=None`, `Secure` in
      `frontend/config/main.php`
  - Da deploy: gli utenti devono **logout + login fresh** per
    rigenerare il cookie con i nuovi attributi.
- **Vista calendario**:
  - Toolbar Week/Day duplicata rimossa (compariva sia nel
    `CalendarViewSelector` top-right sia nel toolbar custom interno
    di `FullCalendarContainer`). Bottone "Oggi" spostato nel
    headerToolbar di FullCalendar.
  - `baseURL` di api.ts ripristinato a `app-cgm.badil.it/therapeutic-plan-manager`
    (era stato cambiato a `app.gruppovitolo.local` in un commit dev mai revertito).
- **Conflict null safety**: `formatConflictInfo` e
  `formatPatientSlotConflictInfo` non crashano piu' quando
  l'appuntamento confliggente e' privato (no `planTherapy`).

## Sidebar e UI

- Coordinatori e Gruppi Coordinatori **accorpati** in unica voce
  "Coordinatori" con 4 sub-item (Visualizza/Nuovo coordinatore +
  Visualizza/Nuovo gruppo).
- Voci "I Miei Pazienti / I Miei Terapisti" gated **anche** sul role
  `coordinator` (non solo permesso `view_own_group_*`). Admin/manager
  non le vedono piu', anche dentro il dropdown Terapisti.
- `actionMyGroup` (TherapistController + PatientController) richiede
  `coordinator` role oltre al permesso (admin riceve `ForbiddenHttpException`).

## Redesign vista minori

- **Notifiche**: `/notification/index` e `/notification/view`
  ricostruiti in stile coerente con altre pagine (breadcrumb header
  standard, card `rounded-2xl`, badge consistenti, statistic cards
  compatte). Aggiunti contatori `sentCount`/`unsentCount` in
  controller. Nomi utente da profile invece di username.
- **Document Request**: stat cards rimpiccolite per coerenza con
  notification (p-3 + h-9 w-9 icone).
- **404 (`/site/error`)**: testi tradotti in italiano, footer
  "TailAdmin" -> "San Luca".
- **Permission view**: barra ricerca su `/site/my-permissions`
  resa leggibile (icona non sovrapposta).

## Bug fix sparsi

| Cosa | Dove |
|---|---|
| `Setting::name` -> `Setting::nome` | activity-report view + Excel export |
| `user_profile.first_name` -> `user_profiles.first_name` (plurale) | `AccountSearch` |
| `appointment.therapist_id` joinava `user_profiles` direttamente, mancava intermedio `therapists` | `PatientController::actionMyGroup` (mostrava `Terapista #N`) |
| Breadcrumb duplicato | `user/coordinators/update.php` |
| Bottoni invisibili (white bg + white icon) | `/patient/accounts` grid |

## File toccati di rilievo

- `frontend/controllers/PatientController.php` (account flow + my-group + edit-account)
- `frontend/controllers/TherapeuticPlanManagerController.php` (CORS + filtro coordinator + conflict null safety)
- `frontend/controllers/TherapistController.php` (my-group gate)
- `frontend/controllers/PermissionController.php` (app_login filter)
- `frontend/controllers/SiteController.php` (my-permissions)
- `frontend/controllers/NotificationController.php` (sentCount/unsentCount)
- `frontend/controllers/AbsenceController.php` (refactor notifiche)
- `frontend/views/patient/accounts.php`, `view-account.php`, `view.php`,
  `edit-account.php` (NEW), `create-credentials.php`, `my-group.php`
- `frontend/views/notification/index.php`, `view.php`, `_notification_item.php`
- `frontend/views/site/my-permissions.php` (NEW), `error.php`, `form.php`
- `frontend/views/layouts/_sidebar.php`, `_header.php`
- `frontend/web/calendar-app/src/lib/api.ts`,
  `pages/Index.tsx`, `components/DualFullCalendarView.tsx`,
  `components/FullCalendarContainer.tsx`
- `frontend/config/main.php` (cookie SameSite/Secure)
- `common/models/AccountPatient.php` (helper getNotifiableUserIdsForPatient)
- `console/migrations/m260428_194122_normalize_self_account_role_to_patient_family.php`
- `console/migrations/m260428_200209_set_parental_authority_for_self_accounts.php`

## Punti aperti / TODO futuri

- **Cleanup permessi orfani**: il role `patient` ora non e' assegnato
  a nessuno (tutto e' `patient_family`), ma resta in `auth_item`. Decidere
  se rimuoverlo o lasciarlo come legacy.
- **Mobile-only permission audit**: verificare in DB se ci sono utenti
  non `therapist`/`patient_family` con `app_login` direct assignment
  (residuo storico). Query nel doc commit `0e66cd3`.
- **Bundle.js orfano**: `frontend/web/js/bundle.js` ha riferimenti
  inerti a "TailAdmin" in stringhe minificate. Build artifact da
  sorgente non in repo. Da rigenerare se trovata la source.
- **Piano AppNotify wrapper** (decisione strategica): grep dell'inizio
  sessione aveva trovato 24 `alert()`, 9 `confirm()`, 3 redefinizioni
  locali di `showNotification` in vari file frontend. Mai messo in atto
  l'omologazione globale su SweetAlert2. Se si riprende: file da fare
  e' `frontend/web/js/app-notify.js`, registrarlo prima di `bundle.js`
  in `AppAsset.php`.

## Comandi utili

```bash
# Vedere i miei commit di oggi
git log --oneline --since='2026-04-28' --author=marco.rispoli stats_calendario

# Verificare a quale commit e' allineato il server
ssh server 'cd /var/www/TherapyCRM && git log --oneline -1'

# Reload PHP opcache (necessario dopo ogni pull)
sudo systemctl reload php8.2-fpm
```
