# Ambienti e URL

Questo documento riepiloga gli URL/host per i diversi ambienti del progetto TherapyCRM e dove vanno configurati nel codice.

## Produzione

| Componente | URL | File di configurazione |
| --- | --- | --- |
| Gestionale frontend (Yii2) | `https://app.gruppovitolo.local` | nginx / vhost lato server |
| Calendar-app (React SPA) | `https://app.gruppovitolo.local/therapeutic-plan-manager` | `frontend/web/calendar-app/src/lib/api.ts` (mappa `APP_ORIGINS`) |
| Mobile app API (React Native) | `https://app.sanlucacentromedico.it/api` | `tp/src/config/api.js` (campo `BASE_URL`) |

Note:
- Il dominio `app.gruppovitolo.local` è usato per il gestionale e per la calendar-app sullo stesso host.
- Il dominio `app.sanlucacentromedico.it` è dedicato all'API consumata dall'app mobile.

## Test / staging

Tutti i componenti puntano allo stesso host condiviso:

| Componente | URL test |
| --- | --- |
| Gestionale frontend | `https://app-cgm.badil.it` |
| Calendar-app | `https://app-cgm.badil.it/therapeutic-plan-manager` |
| Mobile app API | `https://app-cgm.badil.it/api` |

## Come cambiare ambiente

### Calendar-app (React)

Non serve cambiare nulla: la stessa build funziona in produzione e in stage.
L'host dell'API si ricava a runtime dall'hostname da cui è servita la SPA,
tramite la mappa `APP_ORIGINS` in `frontend/web/calendar-app/src/lib/api.ts`:

| Host della SPA | API |
| --- | --- |
| `app.gruppovitolo.local`, `calendar.gruppovitolo.local` | `https://app.gruppovitolo.local` |
| `app-cgm.badil.it`, `calendar-cgm.badil.it` | `https://app-cgm.badil.it` |
| qualsiasi altro host (localhost, dev server) | `https://app-cgm.badil.it` (stage) |

Per aggiungere un nuovo host della SPA:

1. Aggiungi la voce in `APP_ORIGINS` e l'origin nella whitelist CORS di `TherapeuticPlanManagerController`.
2. Rebuild:
   ```bash
   cd frontend/web/calendar-app
   npm run build
   ```
3. Committa sia il sorgente che la directory `dist/` (il gestionale serve il bundle compilato).

### App mobile (React Native)

1. Modifica `BASE_URL` in `tp/src/config/api.js`.
2. Una nuova release dell'app per Android/iOS richiede rebuild dei rispettivi progetti nativi (`tp/android`, `tp/ios`).

## Convenzione branch (maggio 2026)

- `stats_calendario` — branch di produzione: l'host del mobile deve essere **sanluca**.
- `aba_group_appointments` o altri feature branch — il mobile normalmente punta all'host **test** (`app-cgm.badil.it`).

Prima di portare lavoro da un feature branch verso `stats_calendario`, verifica che `tp/src/config/api.js` sia stato riportato al valore di produzione. La calendar-app sceglie l'host da sola (vedi sopra).
