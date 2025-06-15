# Therapy CRM - Calendar App

Sistema di gestione calendario per il Therapy CRM costruito con React e FullCalendar.

## 🚀 Caratteristiche

### Per Manager (Sharon)

- **Vista Calendario Generale**: Vista resource timeline con tutti i terapisti
- **Drag & Drop**: Sposta appuntamenti tra terapisti e orari
- **Gestione Appuntamenti**: Modifica, cancella e riprogramma appuntamenti
- **Vista Multi-Terapista**: Visualizza tutti i terapisti contemporaneamente

### Per Terapisti

- **Vista Giornaliera Personale**: Calendario focalizzato sui propri appuntamenti
- **Marcatura Presenza Rapida**: Bottoni veloci per segnare presenza/assenza
- **Dettagli Pazienti**: Informazioni complete su ogni appuntamento
- **Vista Mobile Ottimizzata**: Interfaccia touch-friendly

### Per Amministratori

- **Vista Completa**: Accesso a tutti i dati del calendario
- **Gestione Avanzata**: Controllo completo su appuntamenti e terapisti

## 🛠️ Tecnologie

- **React 18** - Framework frontend
- **FullCalendar 6** - Libreria calendario con resource timeline
- **Vite** - Build tool e dev server
- **Axios** - Client HTTP per API
- **React Router** - Routing client-side
- **Date-fns** - Manipolazione date
- **CSS3** - Styling moderno e responsive

## 📦 Installazione

```bash
# Installa dipendenze
npm install

# Avvia server di sviluppo
npm run dev

# Build per produzione
npm run build

# Preview build di produzione
npm run preview
```

## 🔧 Configurazione

### Proxy API

Il progetto è configurato per fare proxy delle chiamate API al backend Yii2:

```javascript
// vite.config.js
server: {
  proxy: {
    '/api': 'http://localhost:8080'
  }
}
```

### Variabili d'Ambiente

Crea un file `.env.local` per configurazioni locali:

```env
VITE_API_BASE_URL=http://localhost:8080/api
VITE_APP_TITLE=Therapy CRM Calendar
```

## 📱 Utilizzo

### Accesso Rapido (Sviluppo)

L'app include un sistema di switch utente per test:

1. **Manager**: Vista completa con drag&drop
2. **Terapista**: Vista personale giornaliera
3. **Admin**: Vista amministratore

### Funzionalità Principali

#### Vista Manager

- Calendario resource timeline settimanale
- Drag & drop per spostare appuntamenti
- Click su evento per dettagli completi
- Toolbar con cambio vista (settimana/mese/lista)

#### Vista Terapista

- Calendario giornaliero personale
- Bottoni rapidi per presenza/assenza
- Swipe gestures su mobile
- Notifiche toast per feedback

#### Gestione Appuntamenti

- Modal dettagliato per ogni appuntamento
- Selezione stato (presente/assente/giustificato)
- Campo motivo per assenze
- Salvataggio automatico

## 🎨 Personalizzazione

### Colori Status Appuntamenti

```css
.fc-event.event-completed {
  background: #22c55e;
} /* Verde */
.fc-event.event-absent-justified {
  background: #f59e0b;
} /* Arancione */
.fc-event.event-absent-not-justified {
  background: #ef4444;
} /* Rosso */
.fc-event.event-cancelled {
  background: #6b7280;
} /* Grigio */
.fc-event.event-scheduled {
  background: #3b82f6;
} /* Blu */
```

### Responsive Design

- Desktop: Vista completa con sidebar terapisti
- Tablet: Vista adattiva con toolbar compatto
- Mobile: Vista giornaliera ottimizzata

## 🔌 API Integration

### Endpoint Utilizzati

```javascript
GET /api/calendar/appointments?start=DATE&end=DATE
GET /api/calendar/therapists
PUT /api/calendar/appointment/{id}
POST /api/calendar/appointment/{id}/attendance
GET /api/calendar/available-slots
```

### Formato Dati

```javascript
// Evento FullCalendar
{
  id: '1',
  resourceId: '1', // ID terapista
  title: 'Mario Rossi',
  start: '2024-01-15T09:00:00',
  end: '2024-01-15T10:00:00',
  backgroundColor: '#22c55e',
  extendedProps: {
    patientId: 1,
    therapistId: 1,
    treatmentName: 'Logopedia',
    status: 'completed',
    duration: 60
  }
}
```

## 🧪 Testing

### Test Manuali

1. Avvia il server React: `npm run dev`
2. Avvia il backend Yii2 su porta 8080
3. Testa le diverse viste utente
4. Verifica drag&drop e modal

### Dati Mock

L'app include dati mock per sviluppo quando le API non sono disponibili.

## 📁 Struttura Progetto

```
src/
├── components/
│   ├── CalendarView.jsx      # Vista principale manager
│   ├── TherapistDayView.jsx  # Vista terapista
│   └── AppointmentModal.jsx  # Modal dettagli
├── services/
│   └── calendarService.js    # Client API
├── styles/
│   └── calendar.css          # Stili FullCalendar
├── App.jsx                   # Router e layout
├── App.css                   # Stili globali
└── main.jsx                  # Entry point
```

## 🚀 Deploy

### Build Produzione

```bash
npm run build
```

### Deploy su Server Web

1. Copia il contenuto di `dist/` nella document root
2. Configura il server per servire `index.html` per tutte le route
3. Assicurati che le API siano accessibili

### Nginx Configuration

```nginx
location / {
  try_files $uri $uri/ /index.html;
}

location /api {
  proxy_pass http://backend:8080;
  proxy_set_header Host $host;
}
```

## 🐛 Troubleshooting

### Problemi Comuni

**API non raggiungibili**

- Verifica che il backend Yii2 sia in esecuzione su porta 8080
- Controlla la configurazione proxy in `vite.config.js`

**Eventi non visualizzati**

- Verifica il formato delle date nelle API
- Controlla la console per errori JavaScript

**Drag&drop non funziona**

- Assicurati che l'utente abbia ruolo 'manager'
- Verifica che `editable={true}` sia impostato

**Stili non applicati**

- Verifica che `calendar.css` sia importato in `main.jsx`
- Controlla che non ci siano conflitti CSS

## 📞 Supporto

Per problemi o domande:

1. Controlla la console browser per errori
2. Verifica i log del server di sviluppo
3. Consulta la documentazione FullCalendar
4. Controlla le API con strumenti come Postman

## 🔄 Aggiornamenti

### Prossime Funzionalità

- [ ] Notifiche push per nuovi appuntamenti
- [ ] Esportazione calendario PDF
- [ ] Integrazione calendario Google/Outlook
- [ ] Chat terapista-paziente
- [ ] Dashboard analytics

### Changelog

- **v1.0.0**: Release iniziale con funzionalità base
- Vista manager con resource timeline
- Vista terapista giornaliera
- Modal gestione appuntamenti
- Sistema autenticazione mock
