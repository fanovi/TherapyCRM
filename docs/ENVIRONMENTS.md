# Ambienti e URL

Questo documento riepiloga gli URL/host per i diversi ambienti del progetto TherapyCRM e dove vanno configurati nel codice.

## Produzione

| Componente | URL | File di configurazione |
| --- | --- | --- |
| Gestionale frontend (Yii2) | `https://app.gruppovitolo.local` | nginx / vhost lato server |
| Calendar-app (React SPA) | `https://app.gruppovitolo.local/therapeutic-plan-manager` | `frontend/web/calendar-app/src/lib/api.ts` (campo `baseURL`) |
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

1. Modifica `private baseURL = ...` in `frontend/web/calendar-app/src/lib/api.ts`.
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

- `stats_calendario` — branch di produzione: gli host devono essere **gruppovitolo** (calendar-app) e **sanluca** (mobile).
- `aba_group_appointments` o altri feature branch — normalmente puntano all'host **test** (`app-cgm.badil.it`).

Prima di portare lavoro da un feature branch verso `stats_calendario`, verifica che `api.ts` e `tp/src/config/api.js` siano stati riportati ai valori di produzione.
