# TherapyCRM

Sistema multi-piattaforma per la gestione di appuntamenti e terapie in studi medici/terapeutici.

## Quick Start

```bash
# Backend
composer install
./yii migrate

# Frontend Calendar App
cd frontend/web/calendar-app
npm install
npm run dev

# Mobile App
cd tp
npm install
npx react-native run-android
```

## Documentazione

Consulta **[DOCS.md](./DOCS.md)** per la documentazione completa che include:

- Architettura del sistema
- Stack tecnologico
- Struttura cartelle
- API REST endpoints
- Componenti frontend
- App mobile
- Database schema
- Flussi utente
- Sistema notifiche
- Autenticazione JWT
- Configurazione e deploy

## Stack Tecnologico

| Layer | Tecnologia |
|-------|------------|
| Backend | PHP 7.4+, Yii 2 Framework |
| Database | MySQL |
| Frontend Web | React 18, TypeScript, Vite, TailwindCSS |
| Mobile | React Native, Redux Toolkit |
| Notifiche | OneSignal |

## Struttura Progetto

```
TherapyCRM/
├── api/                 # REST API (10 controllers)
├── backend/             # Admin backend
├── common/              # Modelli e servizi condivisi (53 modelli)
├── console/             # Console commands e migrazioni
├── frontend/            # Frontend Yii2
│   └── web/calendar-app/ # React Calendar App
└── tp/                  # Mobile App React Native
```

## Licenza

Vedi [LICENSE.md](./LICENSE.md)
