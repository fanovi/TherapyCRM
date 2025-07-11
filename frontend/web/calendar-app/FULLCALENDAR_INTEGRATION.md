# FullCalendar Integration - TherapyCRM

## Overview

Il sistema di calendario di TherapyCRM è stato aggiornato per utilizzare **FullCalendar** (versione gratuita/open source) al posto dei componenti personalizzati. Questo offre una maggiore stabilità, performance e funzionalità avanzate.

## Pacchetti Installati

Sono stati installati solo i pacchetti **gratuiti** di FullCalendar:

```bash
npm install @fullcalendar/react @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/interaction @fullcalendar/list
```

- `@fullcalendar/core` - Libreria core
- `@fullcalendar/react` - Adapter React
- `@fullcalendar/daygrid` - Vista mensile/griglia
- `@fullcalendar/timegrid` - Vista settimanale/giornaliera
- `@fullcalendar/interaction` - Interazioni (click, selezione, drag)
- `@fullcalendar/list` - Vista lista

## Struttura dei File

### Nuovi File Creati

1. **`/src/components/FullCalendarContainer.tsx`**

   - Componente principale che sostituisce i componenti personalizzati
   - Gestisce caricamento dati, eventi, e viste

2. **`/src/styles/fullcalendar.css`**
   - Stili personalizzati per FullCalendar
   - Integrazione con Tailwind CSS
   - Responsive design

### File Modificati

1. **`/src/App.tsx`**

   - Aggiunto import per `fullcalendar.css`

2. **`/src/pages/Index.tsx`**
   - Sostituito `DualCalendarView` con `FullCalendarContainer`
   - Mantenuta compatibilità con logica esistente

## Funzionalità Implementate

### 🎯 Viste Calendario

- **Vista Mensile** (`dayGridMonth`) - Griglia mensile
- **Vista Settimanale** (`timeGridWeek`) - Griglia settimanale con ore
- **Vista Giornaliera** (`timeGridDay`) - Griglia giornaliera dettagliata
- **Vista Lista** (`listWeek`) - Lista degli appuntamenti

### 🎨 Personalizzazione

- **Colori Status**: Diversi colori per stato appuntamento

  - `confirmed`: Verde (#10B981)
  - `pending`: Arancione (#F59E0B)
  - `cancelled`: Rosso (#EF4444)
  - `completed`: Blu (#6366F1)

- **Tema**: Integrazione con tema Tailwind CSS esistente
- **Responsive**: Design adattivo per mobile e desktop

### 🔄 Integrazione API

- Utilizza `therapyAPI` esistente
- Carica appuntamenti per paziente o terapista
- Formato dati compatibile con `Appointment` type

### 🎪 Interazioni

- **Click evento**: Mostra dettagli appuntamento
- **Selezione date**: Permette creazione nuovi appuntamenti
- **Drag & Drop**: Supporto per spostamento appuntamenti (configurabile)
- **Navigazione**: Pulsanti mese precedente/successivo

## API Integration

### Caricamento Dati

```typescript
// Per paziente
const appointments = await therapyAPI.getPatientAppointments(
  patientId,
  month,
  year
);

// Per terapista
const appointments = await therapyAPI.getTherapistAppointments(
  therapistId,
  month,
  year
);
```

### Formato Dati

I dati vengono trasformati dal formato `Appointment` a `EventInput` di FullCalendar:

```typescript
const formattedEvents: EventInput[] = appointments.map(
  (appointment: Appointment) => {
    const [datePart, timePart] = appointment.datetime.split(" ");
    const startTime = new Date(`${datePart}T${timePart}`);
    const endTime = new Date(
      startTime.getTime() + appointment.duration * 60000
    );

    return {
      id: appointment.id.toString(),
      title: appointment.therapist?.name || "Appuntamento",
      start: startTime.toISOString(),
      end: endTime.toISOString(),
      backgroundColor: getStatusColor(appointment.status),
      borderColor: getStatusColor(appointment.status),
      textColor: "#ffffff",
      extendedProps: {
        patient_name: appointment.patient?.name || "N/A",
        therapist_name: appointment.therapist?.name || "N/A",
        status: appointment.status,
        notes: appointment.notes,
        type: appointment.treatmentType,
        duration: appointment.duration,
      },
    };
  }
);
```

## Configurazione

### Props del Componente

```typescript
interface FullCalendarContainerProps {
  patientId?: string; // ID paziente
  therapistId?: string; // ID terapista
  selectedDate?: Date; // Data selezionata
  onDateSelect?: (date: Date) => void; // Callback selezione data
}
```

### Configurazione FullCalendar

```typescript
<FullCalendar
  plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
  initialView="dayGridMonth"
  locale="it"
  selectable={true}
  editable={true}
  droppable={true}
  slotMinTime="08:00:00"
  slotMaxTime="20:00:00"
  slotDuration="00:30:00"
  allDaySlot={false}
  nowIndicator={true}
  // ... altre configurazioni
/>
```

## Stili CSS

### Variabili CSS

Gli stili utilizzano variabili CSS per consistenza:

```css
.fullcalendar-container {
  font-family: ui-sans-serif, system-ui, sans-serif;
  min-height: 600px;
}

.fc-event {
  border-radius: 0.375rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}
```

### Responsive Design

```css
@media (max-width: 768px) {
  .fc-toolbar {
    flex-direction: column;
    gap: 0.5rem;
  }
}
```

## Vantaggi dell'Integrazione

### ✅ Vantaggi

1. **Stabilità**: Libreria matura e ben mantenuta
2. **Performance**: Rendering ottimizzato per grandi quantità di dati
3. **Funzionalità**: Viste multiple, drag & drop, responsive
4. **Manutenibilità**: Codice più semplice e standard
5. **Documentazione**: Documentazione completa disponibile
6. **Compatibilità**: Funziona con tutti i browser moderni

### 🔄 Migrazione

- **Backward Compatibility**: Mantiene interfaccia esistente
- **Graduale**: Possibile rollback se necessario
- **Data Format**: Compatibile con API esistente

## Possibili Estensioni Future

### 📅 Funzionalità Aggiuntive

1. **Ricorrenza**: Appuntamenti ricorrenti
2. **Risorse**: Gestione sale/attrezzature
3. **Stampa**: Esportazione PDF calendario
4. **Sincronizzazione**: Sync con calendari esterni
5. **Notifiche**: Promemoria automatici

### 🔧 Personalizzazioni

1. **Temi**: Temi personalizzati per clinica
2. **Campi Custom**: Campi aggiuntivi per appuntamenti
3. **Workflow**: Stati personalizzati
4. **Reporting**: Analytics e report

## Troubleshooting

### Problemi Comuni

1. **Date/Time Parsing**: Verificare formato datetime dall'API
2. **Timezone**: Gestire correttamente i fusi orari
3. **Performance**: Limitare range date caricate
4. **Responsive**: Testare su dispositivi mobili

### Debug

```typescript
// Abilita debug FullCalendar
console.log("FullCalendar events:", events);
console.log("FullCalendar appointments:", appointments);
```

## Compatibilità

- **React**: 18+
- **TypeScript**: 4.9+
- **FullCalendar**: 6.1+
- **Browser**: Chrome, Firefox, Safari, Edge (moderne)

## Conclusione

L'integrazione di FullCalendar migliora significativamente la user experience e la manutenibilità del sistema calendario di TherapyCRM, mantenendo la compatibilità con l'infrastruttura esistente.

---

_Per domande tecniche o supporto, consultare la documentazione ufficiale di FullCalendar: https://fullcalendar.io/docs_
