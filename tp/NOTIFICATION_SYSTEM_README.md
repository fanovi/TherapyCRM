# Sistema di Notifiche - TherapyCRM Mobile App

## Overview

Abbiamo implementato un sistema completo di notifiche per l'app mobile TherapyCRM, integrato sia per pazienti che per terapisti.

## Componenti Implementati

### 1. API Backend (già esistente)

- **NotificationController.php** (`api/controllers/NotificationController.php`)
- Endpoints completi per gestione notifiche
- Supporto per diversi tipi di notifiche:
  - `info`: Informazioni generali
  - `reminder`: Promemoria
  - `deadline`: Scadenze
  - `mandatory_read`: Lettura obbligatoria (notifiche bloccanti)

### 2. Componenti Frontend Mobile

#### NotificationBadge (`tp/src/components/NotificationBadge.js`)

- Icona campanella nell'header sempre visibile
- Badge con conteggio notifiche non lette
- Clic naviga alla pagina dedicata delle notifiche
- Aggiornamento automatico del conteggio ogni 30 secondi
- Aggiornamento quando si torna in focus dalla pagina notifiche

#### Schermate Dedicate

- **PatientNotificationsScreen** (`tp/src/screens/patient/PatientNotificationsScreen.js`)
- **TherapistNotificationsScreen** (`tp/src/screens/therapist/TherapistNotificationsScreen.js`)

### 3. Integrazione Header

- **ScreenTemplate** aggiornato per includere il badge notifiche
- Presente di default in tutte le schermate (configurabile con `showNotifications={false}`)
- Clic sul badge naviga direttamente alla pagina delle notifiche

### 4. Navigazione

- Nuova tab "Notifiche" aggiunta a entrambi i navigator (paziente e terapista)
- Icona dedicata nella bottom tab bar

## Funzionalità

### Per gli Utenti (Pazienti e Terapisti)

1. **Badge Notifiche nell'Header**

   - Sempre visibile in alto a destra
   - Badge rosso con conteggio non lette
   - Clic naviga alla pagina dedicata delle notifiche
   - Aggiornamento automatico del conteggio

2. **Schermata Notifiche Completa**

   - Lista paginata di tutte le notifiche
   - Filtri per stato (tutte/non lette/lette)
   - Filtri per tipo (terapisti hanno filtri avanzati)
   - Pull-to-refresh
   - Caricamento incrementale

3. **Tipologie Visive**

   - **Info**: Icona info, colore blu
   - **Promemoria**: Icona orologio, colore secondario
   - **Scadenza**: Icona warning, colore arancione
   - **Lettura Obbligatoria**: Icona priorità, colore rosso + bordo sinistro

4. **Stati Notifiche**
   - **Non lette**: Sfondo colorato, testo in grassetto, pallino indicatore
   - **Lette**: Sfondo normale, testo normale, icona check

### Funzionalità Speciali Terapisti

- Statistiche in tempo reale (totali, non lette, scadenze, promemoria)
- Filtri avanzati per tipologia
- Informazioni sul mittente delle notifiche
- Indicatori di priorità visuale

## API Endpoints Utilizzati

```javascript
// Recupera tutte le notifiche (con paginazione)
GET /api/notifications?page=1&limit=20

// Recupera solo le non lette
GET /api/notifications/unread?limit=50

// Segna come letta
POST /api/notifications/{id}/mark-read

// Conferma lettura (per notifiche obbligatorie)
POST /api/notifications/{id}/confirm-read

// Test notifiche (solo in sviluppo)
POST /api/notifications/create-test
```

## Test e Sviluppo

### Pulsanti di Test

In modalità sviluppo (`__DEV__ = true`), le schermate delle notifiche mostrano pulsanti per creare notifiche di test:

- **Test Normale**: Crea una notifica informativa
- **Test Bloccante**: Crea una notifica con lettura obbligatoria

### Configurazione API

URL base configurabile in `tp/src/config/api.js`:

```javascript
BASE_URL: 'https://348b-5-158-70-31.ngrok-free.app/TherapyCRM/api';
```

## Come Testare

1. **Avvia l'app mobile**
2. **Login** con credenziali valide
3. **Osserva l'header**: dovrebbe comparire l'icona campanella in alto a destra
4. **Test Notifiche**:

   - Vai nella tab "Notifiche"
   - Usa i pulsanti "Test Normale" e "Test Bloccante" (solo in sviluppo)
   - Verifica che le notifiche appaiano nel dropdown dell'header
   - Testa la lettura delle notifiche (dovrebbero cambiare aspetto)

5. **Test Badge e Navigazione**:
   - Tocca l'icona campanella nell'header
   - Verifica che navighi alla pagina delle notifiche
   - Controlla che il badge si aggiorni quando torni indietro
   - Verifica il conteggio delle notifiche non lette

## Configurazioni

### Disabilitare Notifiche su una Schermata

```jsx
<ScreenTemplate showNotifications={false}>{/* contenuto */}</ScreenTemplate>
```

### Personalizzare Stile Badge

Il componente `NotificationBadge` accetta una prop `style` per personalizzazioni:

```jsx
<NotificationBadge style={{marginRight: 10}} />
```

## Performance

- **Caching**: Il conteggio delle notifiche non lette viene aggiornato ogni 30 secondi
- **Paginazione**: Le liste supportano caricamento incrementale
- **Ottimizzazioni**: Animazioni con `useNativeDriver` per performance ottimali

## Sicurezza

- Tutte le chiamate API usano autenticazione Bearer token
- Filtro automatico per utente corrente (solo le proprie notifiche)
- Validazione lato server per tutte le operazioni

## Prossimi Sviluppi

1. **Push Notifications**: Integrazione con servizi di notifica push
2. **Suoni/Vibrazioni**: Feedback tattile per nuove notifiche
3. **Notifiche Programmate**: Gestione notifiche con orario specificato
4. **Rich Notifications**: Supporto per immagini e azioni rapide
