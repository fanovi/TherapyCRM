# Modulo Giorni Festivi — Progettazione

> Data: 09/09/2026 · Branch di partenza: `stats_calendario`
> Obiettivo: anagrafica dei giorni di chiusura della struttura, gestita da gestionale con permessi RBAC, e blocco della creazione di appuntamenti in quei giorni.

---

## 1. Decisioni concordate

| Tema | Scelta |
|------|--------|
| **Ambito del blocco** | Tutti gli appuntamenti: piano terapeutico, ABA, privati, cicli privati, gruppi, recuperi. Nessuna eccezione — la struttura è chiusa. |
| **Granularità** | Solo giornata intera. Nessuna chiusura a fasce orarie. |
| **Ricorrenza** | Festività ricorrenti (giorno/mese, valide ogni anno) + festività una tantum (data completa) + Pasqua e Lunedì dell'Angelo calcolate a runtime. |
| **Chiusure settimanali** | La **domenica** è giorno di chiusura: nessun appuntamento. Modellata come regola nella stessa tabella, quindi estendibile ad altri giorni (es. sabato) dal gestionale, senza toccare il codice. |
| **Conflitti** | Se il giorno ha già appuntamenti attivi (`scheduled`), **l'inserimento della festività è bloccato**: gli appuntamenti vanno prima spostati o annullati. |
| **Permessi** | 4 permessi dedicati, assegnati inizialmente ad `admin` e `super_admin`. |
| **Override** | **Nessun permesso di bypass.** Il blocco vale per chiunque, esattamente come i conflitti terapista. |
| **Notifiche** | **Nessun invio** a terapisti, pazienti o direzione in seguito a modifiche dell'anagrafica festività. |

> ℹ️ **Nota sull'elenco fornito**: nel messaggio iniziale compare `25/03`. Le festività nazionali italiane di marzo non esistono; presumo si tratti del **25/04 — Festa della Liberazione**, che è quello che metto nel seed. Se serve davvero il 25/03 (patrono o ricorrenza locale) si aggiunge dal gestionale.

---

## 2. Contesto tecnico rilevato

Ricognizione fatta sul codice attuale:

- **Nessuna implementazione preesistente**: nessuna occorrenza di `holiday`/`festiv` in PHP, TS o TSX.
- **Gli appuntamenti si creano da un solo controller**: `frontend/controllers/TherapeuticPlanManagerController.php` (7.054 righe). L'API mobile (`api/controllers/CalendarController.php`) **non crea** appuntamenti — solo letture, assenze, completamento. Questo restringe molto la superficie da presidiare.
- **`common/models/Appointment.php`** ha già validator custom su `appointment_datetime` (`validateAppointmentDateTime`, `validateFutureDateTime`, `validateTherapistAvailability`) → esiste un punto di enforcement centrale già idiomatico.
- **Pattern RBAC consolidato**: migration che crea il permesso + `addChild` sui ruoli, riga in `{{%permission_metadata}}`, voce descrittiva in `common/helpers/PermissionInfo.php`, link in sidebar condizionato da `Yii::$app->user->can(...)`. Riferimento: `m260615_120000_add_receive_management_notifications_permission.php`.
- **Il calendario React blocca già gli slot sulle assenze terapista** (`FullCalendarContainer.tsx:568-598`): background event + toast + `calendarApi.unselect()`. Stesso identico pattern riusabile per le festività.
- **Cache**: componente `cache` = `FileCache`, con pattern `TagDependency` già in uso (`common/services/statistics/PatientStatisticsService.php`).

---

## 3. Schema dati

### Tabella `{{%holidays}}`

Una sola tabella per **tutti i giorni di chiusura**: festività a data fissa, chiusure straordinarie, festività mobili calcolate e chiusure settimanali ricorrenti (la domenica). Un'unica anagrafica significa un solo CRUD, un solo set di permessi, una sola invalidazione di cache e un solo punto da interrogare in validazione.

| Colonna | Tipo | Note |
|---------|------|------|
| `id` | pk | |
| `name` | varchar(100) NOT NULL | "Capodanno", "Ponte del 24 aprile", "Domenica" |
| `rule` | varchar(30) NOT NULL DEFAULT `''` | `''` = data esplicita · `easter` · `easter_monday` · `weekday` |
| `month` | tinyint UNSIGNED NOT NULL DEFAULT 0 | 1-12; `0` se `rule` valorizzato |
| `day` | tinyint UNSIGNED NOT NULL DEFAULT 0 | 1-31 se `rule = ''` · **1-7 (ISO 8601, 7 = domenica) se `rule = 'weekday'`** · `0` per le regole calcolate |
| `year` | smallint UNSIGNED NOT NULL DEFAULT 0 | `0` = ricorrente ogni anno; `2026` = una tantum |
| `is_active` | tinyint(1) NOT NULL DEFAULT 1 | disattivabile senza perdere lo storico |
| `is_national` | tinyint(1) NOT NULL DEFAULT 0 | marca le 12 righe del seed nazionale (solo etichetta in UI) |
| `notes` | text NULL | |
| `created_by` | int NULL | FK → `users.id`, `SET NULL` |
| `created_at` / `updated_at` | int NOT NULL | `TimestampBehavior` |

**Indici**

```
UNIQUE uniq-holiday_occurrence (rule, month, day, year)
INDEX  idx-holiday_active      (is_active)
```

**Perché colonne NOT NULL con default 0 e non nullable.** MySQL ammette più `NULL` dentro un indice UNIQUE: con `year` nullable si potrebbero inserire due volte "1 gennaio ricorrente" senza che il vincolo protesti. Usando `0` come sentinella il vincolo di unicità funziona davvero. È la stessa proprietà di MySQL sfruttata in senso opposto da `uniq-therapist_primary` in `m260522_140000_create_therapist_specializations_table.php`, dove i `NULL` servono proprio a permettere i duplicati.

**Alternativa scartata**: una riga per ogni data concreta di ogni anno. Richiederebbe un job annuale di rigenerazione, non distinguerebbe una festività fissa da un ponte straordinario e renderebbe impossibile modificare "Capodanno" una volta sola.

### Combinazioni valide

| Caso | `rule` | `month` | `day` | `year` |
|------|--------|---------|-------|--------|
| Ricorrente fissa (Capodanno) | `''` | 1 | 1 | 0 |
| Una tantum (ponte 2027) | `''` | 4 | 26 | 2027 |
| Calcolata ricorrente (Pasquetta) | `easter_monday` | 0 | 0 | 0 |
| Chiusura settimanale (domenica) | `weekday` | 0 | 7 | 0 |

L'indice `uniq-holiday_occurrence` copre anche le chiusure settimanali: `('weekday', 0, 7, 0)` è unico, quindi la domenica non può essere inserita due volte.

### Seed — 13 righe con `is_national = 1`

| Nome | Regola |
|------|--------|
| Capodanno | 01/01 ricorrente |
| Epifania | 06/01 ricorrente |
| Pasqua | calcolata (`easter`) |
| Lunedì dell'Angelo | calcolata (`easter_monday`) |
| Festa della Liberazione | 25/04 ricorrente |
| Festa del Lavoro | 01/05 ricorrente |
| Festa della Repubblica | 02/06 ricorrente |
| Ferragosto | 15/08 ricorrente |
| Ognissanti | 01/11 ricorrente |
| Immacolata Concezione | 08/12 ricorrente |
| Natale | 25/12 ricorrente |
| Santo Stefano | 26/12 ricorrente |
| **Domenica** | **chiusura settimanale (`weekday` = 7)** |

Il **santo patrono** non è nel seed: dipende dal comune e va aggiunto dal gestionale.

⚠️ **La riga "Domenica" impatta il pregresso.** A differenza delle festività, che cadono su poche date l'anno, la chiusura domenicale copre ~52 giorni l'anno: è probabile che in produzione esistano già appuntamenti domenicali futuri. La migration di seed **non** li tocca e **non** fallisce, ma stampa a video il conteggio degli appuntamenti `scheduled` in domenica da oggi in avanti, così da sapere cosa va bonificato a mano. Vedi §8.

---

## 4. Componenti backend

### 4.1 `common/models/Holiday.php`

ActiveRecord con `TimestampBehavior` + `ActivityLogBehavior` (coerente con `Appointment`).

- Costanti `RULE_NONE = ''`, `RULE_EASTER = 'easter'`, `RULE_EASTER_MONDAY = 'easter_monday'`, `RULE_WEEKDAY = 'weekday'`.
- `rules()` con validazione di coerenza:
  - se `rule === ''` → `month` 1-12 e `day` 1-31 obbligatori, **e la coppia deve essere una data reale** (no 31/02, no 31/04);
  - se `rule === 'weekday'` → `day` obbligatorio in 1-7, `month` e `year` forzati a 0;
  - se `rule` è una regola calcolata → `month`/`day`/`year` forzati a 0;
  - `year` = 0 oppure compreso in un range sensato (es. 2000-2100);
  - `validateNoExistingAppointments` → vedi §4.4.
- `resolveDate(int $year): ?string` — `Y-m-d` per l'anno richiesto; `null` se la riga è una tantum di un altro anno **o se è una chiusura settimanale** (che non ha una data singola).
- `getClosedWeekday(): ?int` — l'ISO weekday se `rule === 'weekday'`, altrimenti `null`.
- `getOccurrenceLabel()` — "ogni 1 gennaio" / "26 aprile 2027" / "Lunedì dell'Angelo (calcolata)" / "ogni domenica".
- Scope `active()`.
- `afterSave` / `afterDelete` → `TagDependency::invalidate(Yii::$app->cache, HolidayService::CACHE_TAG)`.

### 4.2 `common/services/HolidayService.php`

```php
const CACHE_TAG = 'holidays';
const CACHE_DURATION = 86400;

public static function getDatesForYear(int $year): array;         // ['2026-01-01' => 'Capodanno', ...]
public static function getClosedWeekdays(): array;                // [7 => 'Domenica']
public static function getDatesInRange(string $from, string $to): array;
public static function isClosed(string $datetime): bool;
public static function getClosureName(string $datetime): ?string;  // null se giorno lavorativo
public static function computeEaster(int $year): \DateTimeImmutable;
public static function invalidateCache(): void;
```

- `getDatesForYear()` legge le righe attive a data (`''`, `easter`, `easter_monday`), le espande in date concrete e **mette in cache per anno** con `TagDependency`. Tutte le altre funzioni passano da qui, quindi la validazione di un pattern annuale costa **una sola query**.
- `getClosedWeekdays()` è cachata **a parte**, non espansa in date: infilare ~52 domeniche l'anno nella mappa delle date la gonfierebbe di rumore e renderebbe impossibile distinguere, in UI, "il 25 dicembre è Natale" da "il 7 dicembre è domenica".
- `isClosed()` / `getClosureName()` controllano **prima il giorno della settimana, poi la mappa delle date**: la domenica costa un `format('N')` senza toccare la cache delle festività. Accettano `Y-m-d`, `Y-m-d H:i:s` e `Y-m-d H:i`, normalizzando via `DateTimeImmutable::format('Y-m-d')` e mai con `substr` sulla stringa grezza.
- **`computeEaster()` implementa l'algoritmo di Meeus/Jones/Butcher a mano**, non `easter_date()`: quella funzione richiede l'estensione `calendar` (non garantita sul server) e su build a 32 bit è limitata al range 1970-2037. L'algoritmo è ~10 righe di aritmetica intera e va testato almeno su 2026 (5 aprile), 2027 (28 marzo) e 2030 (21 aprile).

### 4.3 Enforcement — due livelli

**Livello 1 — validator sul modello `Appointment` (rete di sicurezza).**
Una riga in `rules()`:

```php
[['appointment_datetime'], 'validateNotHoliday'],
```

```php
public function validateNotHoliday($attribute, $params)
{
    if (empty($this->$attribute)) {
        return;
    }
    // Vale solo per appuntamenti nuovi o riprogrammati: un semplice cambio di
    // stato su un appuntamento gia' esistente non deve fallire se nel frattempo
    // quel giorno e' diventato di chiusura.
    if (!$this->isNewRecord && !$this->isAttributeChanged($attribute)) {
        return;
    }
    $name = HolidayService::getClosureName($this->$attribute);
    if ($name !== null) {
        $this->addError($attribute, "Struttura chiusa ({$name}): non e' possibile programmare appuntamenti.");
    }
}
```

La guardia su `isAttributeChanged` è essenziale: senza, ogni futuro `save()` su un appuntamento storico caduto in un giorno di chiusura fallirebbe (completamento, note, sostituzione terapista…). Con la domenica in gioco questo non è più un caso di scuola: **gli appuntamenti domenicali già a calendario devono restare gestibili**, altrimenti diventano record impossibili da chiudere.

Questo livello copre da solo **tutti e 9** i punti di creazione, incluso `console/controllers/TestDataController.php`.

**Livello 2 — check espliciti nel controller.**
Il validator produce un errore generico; la SPA si aspetta invece response strutturate `{success:false, error, conflict}` per mostrare toast utili. Si aggiunge un helper privato:

```php
private function checkHolidayConflict($appointmentDateTime): ?array
```

e lo si richiama in `TherapeuticPlanManagerController` così:

| Punto | Riga attuale | Comportamento |
|-------|--------------|---------------|
| `actionCreateAppointment` | 159 | **Blocca**, `code: 'HOLIDAY'`, prima del check conflitto terapista |
| `actionCreateAbaAppointment` | 5937 | **Blocca**, idem |
| `actionCreatePrivateAppointment` → `createPrivateSingleAppointment` | 305 / 1027 | **Blocca**, idem |
| `generateAppointments` (pattern ricorrente) | 4124 | **Salta** la data e aggiunge una voce in `$result['conflicts']` con `code: 'HOLIDAY'` — coerente con come già gestisce gli altri conflitti: il pattern non deve fallire in blocco |
| `generatePrivateMonthlyAppointments` | 3058 | **Salta**, idem |
| `actionUpdateAppointment` → `updateTherapeuticPlanAppointment` / `updatePrivateAppointment` | 2683 / 2728 / 3263 | **Blocca** se la *nuova* data cade in un giorno chiuso |
| `actionUpdatePatternAppointments` | 3401 | **Salta** e segnala |
| `actionAddPatientToRecurringGroup` → `createPatientRecurringExtension` | 6644 / 6881 | **Salta** e segnala |
| `actionSetGroupAppointmentRecurring` | 764 | **Salta** e segnala |
| `actionSubstituteTherapist` | 5191 | Nessun intervento: non modifica la data |

### 4.4 Blocco inserimento festività con appuntamenti attivi

Validator `validateNoExistingAppointments` sul modello `Holiday`:

1. Risolve l'insieme da controllare:
   - **una tantum** → una sola data;
   - **ricorrente / calcolata** → tutte le occorrenze da **oggi** fino a `MAX(DATE(appointment_datetime))` presente a DB (limite naturale: non ha senso controllare anni in cui non esiste ancora nulla);
   - **chiusura settimanale** → nessuna enumerazione di date: si interroga direttamente il giorno della settimana con `WEEKDAY(appointment_datetime) + 1 = :isoDay` e `appointment_datetime >= NOW()`.
2. Conta gli appuntamenti con `status = 'scheduled'`. Gli stati `completed`, `cancelled`, `absent_*`, `therapist_absent` sono storia e non bloccano.
3. Se il conteggio è > 0, aggiunge un errore con l'elenco delle date e il numero di appuntamenti per ciascuna, più il link al calendario per gestirli.

> ⚠️ **`WEEKDAY()`, non `DAYOFWEEK()`.** In MySQL `DAYOFWEEK()` è 1 = domenica … 7 = sabato, mentre l'ISO 8601 usato in PHP (`format('N')`) è 1 = lunedì … 7 = domenica. `WEEKDAY()` restituisce 0 = lunedì … 6 = domenica, quindi `WEEKDAY() + 1` coincide esattamente con l'ISO. Usare `DAYOFWEEK()` per sbaglio sposterebbe silenziosamente ogni chiusura settimanale di un giorno.

Il controllo si applica a `create` **e** `update` (cambiare 25/04 in 26/04 può spostare la festività su un giorno pieno) e alla **riattivazione** (`is_active` da 0 a 1).

**Il seed di migration è l'unica eccezione**: inserisce a livello di query, senza passare dalla validazione del modello, e quindi non è bloccato dagli appuntamenti domenicali già esistenti. È voluto — altrimenti la migration non sarebbe installabile su un DB di produzione popolato.

### 4.5 Endpoint per la SPA

`GET /therapeutic-plan-manager/get-holidays?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD`

```json
{
  "success": true,
  "message": "Festività recuperate con successo",
  "data": {
    "filters": { "start_date": "...", "end_date": "..." },
    "total": 3,
    "holidays": [ { "date": "2026-12-25", "name": "Natale" } ],
    "closedWeekdays": [ { "isoDay": 7, "name": "Domenica" } ]
  }
}
```

Stessa forma di `actionGetTherapistAbsences` (riga 640) per non introdurre una convenzione nuova.

`holidays` contiene solo le date del range richiesto; `closedWeekdays` viaggia come **regola**, non espansa in date. Così la response resta piccola anche su range annuali e la SPA può marcare le domeniche senza dipendere dal range caricato — e se domani si aggiungesse il sabato, il client lo recepisce senza modifiche.

---

## 5. Modulo di gestione (gestionale Yii)

CRUD standard sul modello di `CoordinatorGroupController` + views Tailwind.

- `frontend/models/HolidaySearch.php` — filtri per nome, mese, anno, attivo/disattivo.
- `frontend/controllers/HolidayController.php` — `index`, `create`, `update`, `delete`, con `Yii::$app->user->can(...)` in testa a ogni azione e `ForbiddenHttpException` (stesso stile di `CoordinatorGroupController`).
- Views `frontend/views/holiday/`: `index.php`, `_form.php`, `create.php`, `update.php`.
  - Il form ha un selettore di **tipo** — *ricorrente annuale · una tantum · calcolata · chiusura settimanale* — che mostra solo i campi pertinenti: le quattro combinazioni della tabella in §3 non devono essere costruite a mano dall'utente incrociando `rule`, `month`, `day` e `year`.
  - `index` mostra la colonna "Prossima occorrenza": `resolveDate(date('Y'))` con fallback all'anno successivo per le festività, "ogni domenica" per le chiusure settimanali.
  - Le righe con `is_national = 1` (comprese "Domenica") restano modificabili ed eliminabili: sono un punto di partenza, non un vincolo. La colonna serve solo a etichettarle in lista.
- **Sidebar**: `frontend/views/layouts/_sidebar.php` — voce "Giorni Festivi" nella sezione impostazioni, condizionata a `view_holiday`, con sotto-voce "Nuova Festività" su `create_holiday`. Da registrare anche in `$menuMappings` e nella lista rotte in testa al file (riga ~21).

### Azione opzionale `actionGenerateItalian($year)`

Utile solo se in futuro si passasse allo schema "una riga per data": con lo schema ricorrente scelto, il seed della migration copre già ogni anno. **Non prevista in questa iterazione.**

---

## 6. Frontend React (`frontend/web/calendar-app`)

- `src/types/therapy.ts`:
  ```ts
  export interface Holiday { date: string; name: string; }
  export interface ClosedWeekday { isoDay: number; name: string; }
  export interface ClosureRules { holidays: Holiday[]; closedWeekdays: ClosedWeekday[]; }
  ```
- `src/lib/api.ts` → `getHolidays(startDate, endDate): Promise<ClosureRules>`, con nel JSDoc il marker **`NON RIMUOVERE`** (nel repo gira un cleanup che elimina i metodi di `api.ts` non referenziati).
- `src/components/FullCalendarContainer.tsx`:
  1. caricamento delle regole di chiusura sul range visibile del calendario;
  2. helper locale `getClosureName(date): string | null` che replica la logica del service (prima il giorno della settimana, poi la data);
  3. background event per ogni giorno chiuso del range (`display: 'background'`, `classNames: ['holiday-event']`, titolo `🎉 {nome}`) — stessa meccanica degli `absenceEvents` (riga 360);
  4. in `handleDateSelect`, **prima** del check assenze (riga 568): se il giorno è chiuso → toast destructive "Struttura chiusa — {nome}. Non è possibile creare appuntamenti." + `calendarApi.unselect()`;
  5. `selectAllow` per impedire anche il drag di selezione su un giorno chiuso.
- Gestione della response `code === 'HOLIDAY'` nei modali di creazione (`AppointmentModal`, `PrivateAppointmentModal`, `AppointmentEditModal`) come toast rosso.
- CSS in `src/styles/` per lo sfondo del giorno chiuso, distinto da quello delle assenze.

> **Le domeniche restano visibili nel calendario.** FullCalendar offre `hiddenDays: [0]` per farle sparire del tutto, ed è la soluzione che verrebbe naturale — ma nasconderebbe anche gli appuntamenti domenicali **già esistenti**, rendendoli invisibili e quindi impossibili da spostare o annullare. La domenica va mostrata, marcata come chiusa e resa non selezionabile.

> ⚠️ **Build**: non eseguo `npm run build`. Compilazione e deploy della SPA su `calendar-cgm.badil.it` restano a carico dell'utente.

---

## 7. Permessi RBAC

Migration `m260909_150100_add_holiday_permissions.php`, sullo stampo di `m260615_120000_...`:

| Permesso | Descrizione | admin | super_admin |
|----------|-------------|:-----:|:-----------:|
| `view_holiday` | Visualizzare i giorni festivi | ✅ | ✅ |
| `create_holiday` | Creare giorni festivi | ✅ | ✅ |
| `update_holiday` | Modificare giorni festivi | ✅ | ✅ |
| `delete_holiday` | Eliminare giorni festivi | ✅ | ✅ |

Nomenclatura al singolare, coerente con `view_coordinator_group` / `create_coordinator_group`.

Grazie all'architettura RBAC per-persona già in essere, ognuno di questi può essere assegnato in seguito al singolo utente da `/user/update` senza cambiargli ruolo.

Contorno obbligatorio:
- 4 righe in `{{%permission_metadata}}` con `is_active = 1`;
- 4 voci in `common/helpers/PermissionInfo.php` (sezione "Giorni Festivi"), altrimenti l'icona info non compare in `/site/my-permissions`;
- aggiornamento di `docs/RBAC_PERMISSIONS_MAP.md` e `docs/RBAC_PERMISSIONS_MAP.csv`.

**Il blocco sugli appuntamenti non è soggetto a permesso**: vale per chiunque, come per i conflitti terapista. I permessi governano solo l'anagrafica. Nessun `bypass_holiday_block`: chi deve fissare un appuntamento in un giorno chiuso deve prima aprire quel giorno, e l'operazione resta tracciata da `ActivityLogBehavior`.

---

## 8. Migration

| # | Nome | Contenuto |
|---|------|-----------|
| 1 | `m260909_150000_create_holidays_table.php` | tabella + indici + FK `created_by` + seed delle 13 righe (12 festività nazionali + chiusura domenicale) + report appuntamenti domenicali |
| 2 | `m260909_150100_add_holiday_permissions.php` | 4 permessi, `addChild` su `admin` e `super_admin`, righe in `permission_metadata` |

Il seed deve essere **idempotente** (controllo di esistenza prima di ogni insert) perché la migration girerà su un DB di produzione già popolato. `safeDown()` di (1) fa `dropTable`; quella di (2) usa `$auth->remove()` che ripulisce anche `auth_item_child` e `auth_assignment`.

In coda a (1), un report a video — **solo lettura, nessuna modifica**:

```
⚠️  Appuntamenti 'scheduled' in giorni ora chiusi (da oggi in avanti):
    Domenica ................ N appuntamenti
    Festività ............... N appuntamenti
    Vanno spostati o annullati a mano dal calendario.
```

Serve a rendere visibile il pregresso subito dopo il deploy. Non blocca la migration e non altera dati: da qui in avanti quei giorni non accettano nuovi appuntamenti, ma quelli esistenti restano validi e gestibili (§4.3).

---

## 9. Ordine di lavoro

| Fase | Contenuto | Dipendenze |
|------|-----------|------------|
| **1** | Migration tabella + seed + report domenicale · `Holiday` · `HolidayService` (verifica Pasqua su 2026/2027/2030 e mappatura ISO ↔ `WEEKDAY()`) | — |
| **2** | Migration permessi · `PermissionInfo` · aggiornamento doc RBAC | — |
| **3** | `HolidaySearch` · `HolidayController` · views · sidebar | 1, 2 |
| **4** | Validator su `Appointment` · `checkHolidayConflict` sui 9 punti · endpoint `get-holidays` | 1 |
| **5** | `api.ts` · tipi · `FullCalendarContainer` · toast nei modali · CSS | 4 |

Le fasi 1 e 2 sono indipendenti tra loro; la 4 e la 3 possono procedere in parallelo una volta chiusa la 1.

---

## 10. Rischi

- **Il pregresso domenicale è il rischio principale.** La chiusura settimanale copre ~52 giorni l'anno: in produzione esistono con ogni probabilità appuntamenti domenicali futuri. Sono presidiati su tre fronti: la migration li conta e li segnala (§8), il validator non li tocca grazie a `isAttributeChanged` (§4.3), il calendario continua a mostrarli invece di nasconderli con `hiddenDays` (§6). Restano da bonificare a mano, ma nessuno di essi diventa un record bloccato.
- **Appuntamenti storici in giorni festivi**: stessa guardia, stesso esito. Nessuna migrazione dati necessaria.
- **`console/controllers/TestDataController.php`**: dalla fase 4 in poi i suoi appuntamenti in giorni chiusi falliranno silenziosamente — e con la domenica bloccata capiterà spesso. È il comportamento corretto, ma va tenuto presente se uno script di test smette di produrre il dataset atteso.
- **Costo in generazione pattern**: un pattern annuale itera ~365 giorni; senza cache sarebbero 365 query. `getDatesForYear()` in cache le riduce a 1, e il check della domenica non ne costa nessuna.
- **`WEEKDAY()` vs `DAYOFWEEK()`**: convenzioni diverse fra MySQL e ISO 8601, con uno scarto di un giorno che non dà errore ma sposta la chiusura. Dettaglio in §4.4.
- **Migration su produzione**: seed idempotente, report in sola lettura, nessuna query manuale sul DB di prod (le verifiche si fanno in locale).

---

## 11. Fuori ambito

Fissato per evitare che rientri di soppiatto in implementazione:

- **Nessun permesso di override** del blocco (§7).
- **Nessuna notifica** a terapisti, pazienti o direzione al variare dell'anagrafica. Se in futuro servisse, andrà rispettata la regola `sendToManagement` + `sendToTherapistCoordinators`.
- **Nessuna chiusura a fasce orarie**: la granularità è il giorno intero. Le mezze giornate resterebbero comunque modellabili in seguito aggiungendo due colonne nullable, senza rifare l'impianto.
- **Nessun orario di apertura per giorno della settimana**: `weekday` dice solo "chiuso", non "aperto dalle 8 alle 18".

---

## 12. Stato implementazione (10/09/2026)

Fasi 1–5 implementate sul branch `stats_calendario`, **non ancora committate**. Migration **non eseguite** (nessuna query sul DB di produzione), SPA **non buildata**.

### Scostamenti dal piano

| Punto | Piano | Implementato | Motivo |
|-------|-------|--------------|--------|
| `created_at`/`updated_at` | `int` | `timestamp` con `CURRENT_TIMESTAMP` | convenzione del resto del DB (`coordinator_groups`, `appointments`) |
| Form tipo | selettore di tipo | campi virtuali `kind`/`date`/`weekday` su `Holiday`, tradotti in `rule/month/day/year` da `applyKind()` | il form non deve comporre a mano le combinazioni; senza `kind` (seed, console) si lavora sulle colonne |
| Normalizzazione | `beforeValidate` | validator `normalizeOccurrence` dopo `applyKind` | deve girare dopo la traduzione del tipo |
| Tabella mancante | — | `HolidayService` restituisce "nessuna chiusura" e logga se la query fallisce (senza cache) | nella finestra fra `git pull` e `./yii migrate` il validator su `Appointment` farebbe fallire ogni salvataggio |
| Riprogrammazione | blocco se la nuova data è chiusa | blocco solo se data/ora cambiano davvero (confronto normalizzato) | stessa regola del validator: un appuntamento domenicale pregresso resta modificabile per terapista, durata, note |
| Link al calendario | link dal messaggio di errore | tabella degli appuntamenti in conflitto nel form, con link "Apri calendario" del terapista | la SPA non accetta una data in URL: serve sapere *quali* appuntamenti spostare |
| `selectAllow` | impedire la selezione | non usato: la selezione parte e `handleDateSelect` mostra il toast "Struttura chiusa" | con `selectAllow` il click su un giorno chiuso non darebbe alcun riscontro |
| Drag & drop | — | `eventAllow` rifiuta il drop su un giorno chiuso | coerente col blocco lato backend |
| Titolo sfondo | `🎉 {nome}` | `Chiuso · {nome}` | "🎉 Domenica" su ogni domenica risultava fuori luogo |
| Mappa categorie permessi | — | `'holiday' => 'Giorni Festivi'` in `PermissionController` e `backend/RolesController` | altrimenti i 4 permessi finiscono in "Altro" nell'editor |
| Gruppi ricorrenti | salta e segnala | anche le occorrenze esistenti di `actionAddPatientToRecurringGroup` saltano con `reason` "Struttura chiusa (…)" | prima dell'estensione il nuovo appuntamento sarebbe fallito con un errore di validazione grezzo |

### Verifiche eseguite (senza DB, cache in memoria e schema simulato)

- Pasqua confrontata con `easter_days()` su 1900–2200; espansione del seed, 29/02, date unite (Pasquetta = 25/04 nel 2095).
- Validator del modello `Holiday` (tutti i tipi, errori sul campo giusto del form, blocco su inserimento/riattivazione/cambio ricorrenza, nessun blocco su rinomina).
- Helper del controller, endpoint `get-holidays`, `Appointment::validateNotHoliday` (nuovo/invariato/spostato).
- Rendering delle view `holiday/*` e classi Tailwind verificate contro `frontend/web/css/style.css` (il CSS è compilato e purgato: molte classi delle altre view non esistono).
- `tsc --noEmit` sulla SPA: nessun errore nei file toccati (restano errori preesistenti in `TherapistSelector.tsx` e `WeekCalendar.tsx`).

### Da fare

1. `./yii migrate` su un DB non di produzione e lettura del report domenicale.
2. Prova manuale: CRUD `/holiday`, blocco inserimento con appuntamenti, calendario (sfondo, toast, drag).
3. Build e deploy della SPA (a cura dell'utente).
4. In produzione: migrate subito dopo il pull; bonificare gli appuntamenti domenicali futuri segnalati dal report.
