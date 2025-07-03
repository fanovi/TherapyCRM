# Implementazione Sistema Richieste Documenti

## 📋 Panoramica

È stato implementato un sistema completo per la gestione delle richieste di documenti nel TherapyCRM, con controlli di accesso basati sui ruoli e interfaccia utente moderna con Tailwind CSS.

## 🏗️ Struttura Implementata

### 1. Controller

- **File**: `frontend/controllers/DocumentRequestController.php`
- **Azioni**:
  - `actionIndex()`: Lista paginata con filtri
  - `actionView($id)`: Visualizzazione dettagli richiesta
  - `actionUpdateStatus($id)`: Aggiornamento stato (AJAX + normale)

### 2. Model di Ricerca

- **File**: `frontend/models/DocumentRequestSearch.php`
- **Funzionalità**:
  - Filtri per richiedente, paziente, tipo richiesta, stato
  - Ordinamento personalizzato
  - Metodo `searchUnread()` per richieste non lette

### 3. Viste

- **Index**: `frontend/views/document-request/index.php`
  - GridView con stile Tailwind
  - Badge colorati per stati
  - Statistiche in tempo reale
  - Filtri avanzati
- **View**: `frontend/views/document-request/view.php`
  - Layout responsive (2/3 + 1/3)
  - Dettagli completi richiesta
  - Storico cambi di stato
  - Pulsanti azione contestuali

### 4. Navigazione

- **File**: `frontend/views/layouts/_sidebar.php`
- **Funzionalità**:
  - Link "Richieste Documenti" nella sidebar
  - Badge con conteggio richieste non lette
  - Visibile solo a utenti autorizzati

## 🔐 Sistema Permessi

### Ruoli e Accessi

| Ruolo       | Permesso           | Azioni Consentite                                                      |
| ----------- | ------------------ | ---------------------------------------------------------------------- |
| **Admin**   | `manage_documents` | • Prendere in carico (1→2)<br>• Stampare (2→3)<br>• Visualizzare tutto |
| **Manager** | `view_documents`   | • Consegnare (3→4)<br>• Visualizzare tutto                             |

### Stati delle Richieste

| ID  | Nome            | Descrizione                              | Chi può impostare |
| --- | --------------- | ---------------------------------------- | ----------------- |
| 1   | Inviata         | Richiesta appena creata (**da leggere**) | Sistema           |
| 2   | Presa in carico | Admin ha iniziato a lavorarci            | Admin             |
| 3   | Stampato        | Documento pronto per consegna            | Admin             |
| 4   | Consegnato      | Richiesta completata                     | Manager           |

## 🎨 Design e UX

### Styling

- **Framework**: Tailwind CSS
- **Tema**: Coerente con il resto dell'applicativo
- **Responsive**: Layout adattivo per mobile/desktop

### Colori Stati

- 🔴 **Inviata**: Rosso (richiede attenzione)
- 🟡 **Presa in carico**: Giallo (in lavorazione)
- 🔵 **Stampato**: Blu (pronto)
- 🟢 **Consegnato**: Verde (completato)

### Badge Notifiche

- Contatore rosso nella sidebar per richieste non lette
- Aggiornamento automatico del conteggio
- Visibile solo a utenti autorizzati

## 📊 Funzionalità GridView

### Filtri Disponibili

- **ID**: Ricerca per numero richiesta
- **Richiedente**: Nome account che ha fatto la richiesta
- **Paziente**: Nome paziente di riferimento
- **Tipo Richiesta**: Filtro per categoria documento
- **Stato**: Dropdown con tutti gli stati
- **Data Creazione**: Filtro per periodo

### Ordinamento

- Default: Data creazione (più recenti prima)
- Ordinabile per tutti i campi
- Mantiene filtri durante ordinamento

### Azioni

- **Visualizza**: Icona occhio per dettagli
- **Aggiorna Stato**: Icona refresh (se autorizzato)
- Pulsanti disabilitati se non autorizzati

## 🔄 Gestione Stati

### Workflow

```
[1] Inviata → [2] Presa in carico → [3] Stampato → [4] Consegnato
    ↑              ↑ (Admin)         ↑ (Admin)    ↑ (Manager)
   Sistema
```

### Storico

- Ogni cambio di stato viene registrato
- Tracciabilità completa con utente e timestamp
- Visualizzazione timeline nella vista dettagli

### Validazioni

- Controllo permessi prima di ogni aggiornamento
- Prevenzione di salti di stato non autorizzati
- Logging di tutti i tentativi di accesso

## 🚀 Migration Permessi

### File

- `console/migrations/m250201_000035_assign_document_request_permissions.php`

### Applicazione

```bash
./yii migrate
```

### Verifica

```bash
./yii rbac/user-assignments [user_id]
```

## 📱 Supporto AJAX

### Aggiornamento Stati

- Richieste AJAX per aggiornamenti in tempo reale
- Feedback immediato senza refresh pagina
- Gestione errori lato client e server

### Sicurezza AJAX

- CSRF token in tutte le richieste
- Validazione permessi server-side
- Risposte JSON strutturate

## 🔧 Estensibilità

### Nuovi Stati

1. Aggiungere costante in `RequestStatus.php`
2. Aggiornare `getStatusLabels()`
3. Modificare `canUpdateStatus()` nel controller
4. Aggiornare colori nelle viste

### Nuovi Ruoli

1. Creare permesso in migration
2. Assegnare a ruolo appropriato
3. Aggiornare logica controller
4. Testare accessi

### Nuovi Filtri

1. Aggiungere proprietà a `DocumentRequestSearch`
2. Implementare logica in `search()`
3. Aggiungere colonna a GridView
4. Testare performance

## ✅ Checklist Implementazione

- [x] Controller con azioni CRUD
- [x] Model di ricerca con filtri
- [x] Vista index con GridView
- [x] Vista dettagli con azioni
- [x] Link nella sidebar con badge
- [x] Sistema permessi RBAC
- [x] Migration per permessi
- [x] Gestione stati workflow
- [x] Storico cambiamenti
- [x] Supporto AJAX
- [x] Styling Tailwind
- [x] Responsive design
- [x] Validazioni sicurezza

## 🎯 Prossimi Passi

1. **Applicare Migration**: `./yii migrate`
2. **Assegnare Ruoli**: Verificare che utenti abbiano ruoli corretti
3. **Test Funzionalità**: Testare tutti i workflow
4. **Monitoring**: Monitorare performance e usabilità

## 📞 Supporto

Per problemi o domande:

1. Verificare log: `frontend/runtime/logs/app.log`
2. Controllare permessi: `./yii rbac/user-assignments [user_id]`
3. Testare AJAX in console browser
4. Verificare database per dati test
