import React, { useState, useEffect, useCallback } from "react";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import listPlugin from "@fullcalendar/list";
import itLocale from "@fullcalendar/core/locales/it";
import calendarService from "../services/calendarService";
import AppointmentModal from "./AppointmentModal";

// Toast notification component
const Toast = ({ message, type, onClose }) => (
  <div className={`toast toast-${type}`} onClick={onClose}>
    <div className="toast-content">
      <span className="toast-icon">
        {type === "success" ? "✅" : type === "error" ? "❌" : "ℹ️"}
      </span>
      <span className="toast-message">{message}</span>
    </div>
  </div>
);

const CalendarView = ({ userRole = "manager" }) => {
  const [events, setEvents] = useState([]);
  const [therapists, setTherapists] = useState([]);
  const [selectedTherapist, setSelectedTherapist] = useState("all");
  const [loading, setLoading] = useState(true);
  const [selectedAppointment, setSelectedAppointment] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [toasts, setToasts] = useState([]);

  // Toast utilities
  const showToast = (message, type = "info") => {
    const id = Date.now();
    const toast = { id, message, type };
    setToasts((prev) => [...prev, toast]);

    // Auto-remove after 3 seconds
    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 3000);
  };

  const removeToast = (id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  // Carica terapisti
  const loadTherapists = useCallback(async () => {
    try {
      const response = await calendarService.getTherapists();
      if (response.success) {
        setTherapists(response.data);
      }
    } catch (error) {
      console.error("Error loading therapists:", error);
      // In caso di errore, usa array vuoto
      setTherapists([]);
    }
  }, []);

  // Carica appuntamenti in un range di date
  const loadAppointments = useCallback(
    async (start, end) => {
      setLoading(true);
      try {
        const startStr = start.toISOString().split("T")[0];
        const endStr = end.toISOString().split("T")[0];

        let response;
        if (selectedTherapist === "all") {
          response = await calendarService.getAllAppointments(startStr, endStr);
        } else {
          response = await calendarService.getTherapistAppointments(
            selectedTherapist,
            startStr,
            endStr
          );
        }

        if (response.success) {
          setEvents(response.data);
        }
      } catch (error) {
        console.error("Error loading appointments:", error);
        // In caso di errore, usa array vuoto
        setEvents([]);
      } finally {
        setLoading(false);
      }
    },
    [selectedTherapist]
  );

  // Inizializzazione
  useEffect(() => {
    loadTherapists();
  }, [loadTherapists]);

  // Ricarica quando cambia terapista selezionato
  useEffect(() => {
    if (currentDate) {
      const start = new Date(currentDate);
      start.setDate(start.getDate() - 7);
      const end = new Date(currentDate);
      end.setDate(end.getDate() + 14);
      loadAppointments(start, end);
    }
  }, [selectedTherapist, loadAppointments, currentDate]);

  // Gestione click eventi (evita conflitti con drag/resize)
  const handleEventClick = (clickInfo) => {
    // Evita di aprire il modal se si sta facendo drag o resize
    if (clickInfo.jsEvent.defaultPrevented) {
      return;
    }

    console.log("🖱️ Event clicked:", clickInfo.event.id);

    const eventData = {
      id: clickInfo.event.id,
      title: clickInfo.event.title,
      start: clickInfo.event.start,
      end: clickInfo.event.end,
      ...clickInfo.event.extendedProps,
    };

    setSelectedAppointment(eventData);
    setModalOpen(true);
  };

  // Gestione mouse enter/leave per resize handles
  const handleEventMouseEnter = (info) => {
    if (userRole === "manager") {
      const eventEl = info.el;

      // Debug: verifica se l'evento è resizable
      console.log("🖱️ Mouse enter event:", {
        id: info.event.id,
        editable: info.event.editable,
        startEditable: info.event.startEditable,
        durationEditable: info.event.durationEditable,
        resizable: eventEl.classList.contains("fc-event-resizable"),
      });

      // Aggiungi classe per mostrare resize handles
      eventEl.classList.add("fc-event-hover");

      // Tooltip informativo
      eventEl.title = `${info.event.title}\n• Trascina per spostare\n• Usa i bordi per ridimensionare\n• Click per dettagli`;
    }
  };

  const handleEventMouseLeave = (info) => {
    const eventEl = info.el;
    eventEl.classList.remove("fc-event-hover");
    eventEl.title = info.event.title; // Reset tooltip
  };

  // Gestione drag & drop (solo per manager) - NATIVO FullCalendar
  const handleEventDrop = async (dropInfo) => {
    console.log("🎯 Drag & Drop detected!", {
      eventId: dropInfo.event.id,
      oldStart: dropInfo.oldEvent.start,
      newStart: dropInfo.event.start,
      userRole: userRole,
    });

    if (userRole !== "manager") {
      console.log("❌ Drag & Drop blocked - user is not manager");
      dropInfo.revert();
      return;
    }

    try {
      const appointment = dropInfo.event;
      const updateData = {
        appointment_datetime: appointment.start.toISOString(),
        // Se vogliamo permettere cambio terapista, dovremmo implementare
        // una logica più complessa qui
      };

      console.log("📝 Updating appointment via API...", updateData);
      const response = await calendarService.updateAppointment(
        appointment.id,
        updateData
      );

      if (response.success) {
        console.log("✅ Appointment updated successfully!");
        // Aggiorna lo stato locale
        setEvents((prevEvents) =>
          prevEvents.map((event) =>
            event.id === appointment.id
              ? {
                  ...event,
                  start: appointment.start.toISOString(),
                  end: appointment.end.toISOString(),
                }
              : event
          )
        );
        showToast("Appuntamento spostato con successo", "success");
      } else {
        console.log("❌ API returned error:", response.error);
        dropInfo.revert();
        showToast(response.error || "Errore durante lo spostamento", "error");
      }
    } catch (error) {
      console.error("❌ Error updating appointment:", error);
      dropInfo.revert();
      showToast("Errore di connessione durante lo spostamento", "error");
    }
  };

  // Gestione resize eventi
  const handleEventResize = async (resizeInfo) => {
    console.log("🔄 EVENT RESIZE:", {
      eventId: resizeInfo.event.id,
      oldStart: resizeInfo.oldEvent.start,
      oldEnd: resizeInfo.oldEvent.end,
      newStart: resizeInfo.event.start,
      newEnd: resizeInfo.event.end,
      delta: resizeInfo.delta,
    });

    try {
      const updatedEvent = {
        id: resizeInfo.event.id,
        start: resizeInfo.event.start.toISOString(),
        end: resizeInfo.event.end.toISOString(),
        duration_minutes: Math.round(
          (resizeInfo.event.end - resizeInfo.event.start) / (1000 * 60)
        ),
      };

      console.log("📤 Sending resize update:", updatedEvent);

      // Chiamata API per aggiornare l'evento
      const response = await calendarService.updateAppointment(
        resizeInfo.event.id,
        updatedEvent
      );

      if (response.success) {
        console.log("✅ Event resized successfully");
        // Aggiorna lo stato locale se necessario
        setEvents((prevEvents) =>
          prevEvents.map((event) =>
            event.id === resizeInfo.event.id
              ? { ...event, ...updatedEvent }
              : event
          )
        );
        showToast(
          `Durata aggiornata: ${updatedEvent.duration_minutes} minuti`,
          "success"
        );
      } else {
        console.error("❌ Failed to resize event:", response.error);
        resizeInfo.revert(); // Annulla il resize
        showToast(
          response.error || "Errore durante il ridimensionamento",
          "error"
        );
      }
    } catch (error) {
      console.error("💥 Error during event resize:", error);
      resizeInfo.revert(); // Annulla il resize in caso di errore
      showToast("Errore di connessione durante il ridimensionamento", "error");
    }
  };

  // Gestione cambio date nel calendario
  const handleDatesSet = (dateInfo) => {
    setCurrentDate(dateInfo.start);
    loadAppointments(dateInfo.start, dateInfo.end);
  };

  // Aggiorna appointment dopo modifica
  const handleAppointmentUpdate = (updatedAppointment) => {
    setEvents((prevEvents) =>
      prevEvents.map((event) =>
        event.id === updatedAppointment.id
          ? { ...event, ...updatedAppointment }
          : event
      )
    );
    setModalOpen(false);
    setSelectedAppointment(null);
  };

  // Filtro eventi per terapista selezionato
  const filteredEvents =
    selectedTherapist === "all"
      ? events
      : events.filter(
          (event) => event.extendedProps.therapistId == selectedTherapist
        );

  return (
    <div className="calendar-container">
      {/* Filtro terapisti */}
      <div className="calendar-filters">
        <div className="flex items-center gap-4">
          <label htmlFor="therapist-select" className="font-medium">
            Terapista:
          </label>
          <select
            id="therapist-select"
            value={selectedTherapist}
            onChange={(e) => setSelectedTherapist(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">Tutti i terapisti</option>
            {therapists.map((therapist) => (
              <option key={therapist.id} value={therapist.id}>
                {therapist.title} - {therapist.extendedProps.specialization}
              </option>
            ))}
          </select>

          {selectedTherapist !== "all" && (
            <div className="text-sm text-gray-600">
              Specializzazione:{" "}
              {
                therapists.find((t) => t.id == selectedTherapist)?.extendedProps
                  .specialization
              }
            </div>
          )}
        </div>
      </div>

      {loading && (
        <div className="loading-overlay">
          <div className="loading-spinner">Caricamento...</div>
        </div>
      )}

      <FullCalendar
        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
        initialView="timeGridWeek"
        locale="it"
        firstDay={1}
        height="auto"
        // Eventi
        events={filteredEvents}
        // Configurazione orari
        slotMinTime="07:00:00"
        slotMaxTime="20:00:00"
        slotDuration="00:15:00" // Slot più piccoli per precisione
        slotLabelInterval="01:00:00" // Etichette ogni ora
        // Business hours
        businessHours={{
          startTime: "08:00",
          endTime: "19:00",
          daysOfWeek: [1, 2, 3, 4, 5], // Lun-Ven
        }}
        // INTERAZIONE - DRAG & DROP + RESIZE
        editable={userRole === "manager"}
        selectable={userRole === "manager"}
        selectMirror={true}
        // DRAG & DROP
        eventStartEditable={userRole === "manager"}
        // RESIZE - Proprietà corrette FullCalendar
        eventDurationEditable={userRole === "manager"}
        eventResizableFromStart={userRole === "manager"}
        // Configurazioni avanzate
        dragRevertDuration={300}
        dragScroll={true}
        snapDuration="00:15:00"
        // Event handlers
        eventClick={handleEventClick}
        eventDrop={handleEventDrop}
        eventResize={handleEventResize}
        datesSet={handleDatesSet}
        // Header toolbar
        headerToolbar={{
          left: "prev,next today",
          center: "title",
          right: "timeGridWeek,timeGridDay,dayGridMonth,listWeek",
        }}
        // Styling
        eventDisplay="block"
        dayMaxEventRows={3}
        eventMaxStack={3}
        // UX migliorata
        nowIndicator={true}
        weekNumbers={false}
        navLinks={true}
        eventTimeFormat={{
          hour: "2-digit",
          minute: "2-digit",
          hour12: false,
        }}
        slotLabelFormat={{
          hour: "2-digit",
          minute: "2-digit",
          hour12: false,
        }}
        // Gestione mouse
        eventMouseEnter={handleEventMouseEnter}
        eventMouseLeave={handleEventMouseLeave}
        // Custom content
        eventContent={(eventInfo) => (
          <div className="fc-event-content">
            <div className="fc-event-time">{eventInfo.timeText}</div>
            <div className="fc-event-title">{eventInfo.event.title}</div>
            {eventInfo.event.extendedProps.treatmentName && (
              <div className="fc-event-treatment text-xs opacity-75">
                {eventInfo.event.extendedProps.treatmentName}
              </div>
            )}
            {userRole === "manager" && (
              <div className="fc-event-actions text-xs opacity-50">
                📅 Trascina • 📏 Ridimensiona
              </div>
            )}
          </div>
        )}
      />

      {/* Modal dettagli appuntamento */}
      {modalOpen && selectedAppointment && (
        <AppointmentModal
          appointment={selectedAppointment}
          userRole={userRole}
          onClose={() => {
            setModalOpen(false);
            setSelectedAppointment(null);
          }}
          onUpdate={handleAppointmentUpdate}
        />
      )}

      {/* Toast notifications */}
      <div className="toast-container">
        {toasts.map((toast) => (
          <Toast
            key={toast.id}
            message={toast.message}
            type={toast.type}
            onClose={() => removeToast(toast.id)}
          />
        ))}
      </div>
    </div>
  );
};

export default CalendarView;
