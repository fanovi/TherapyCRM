import React, { useState, useEffect, useCallback } from "react";
import FullCalendar from "@fullcalendar/react";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import calendarService from "../services/calendarService";
import AppointmentModal from "./AppointmentModal";

const TherapistDayView = ({ therapistId, userRole = "therapist" }) => {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedAppointment, setSelectedAppointment] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [currentDate, setCurrentDate] = useState(new Date());

  // Carica appuntamenti del terapista per la giornata
  const loadTherapistAppointments = useCallback(
    async (date) => {
      if (!therapistId) return;

      setLoading(true);
      try {
        const startStr = date.toISOString().split("T")[0];
        const endDate = new Date(date);
        endDate.setDate(endDate.getDate() + 1);
        const endStr = endDate.toISOString().split("T")[0];

        const response = await calendarService.getTherapistAppointments(
          therapistId,
          startStr,
          endStr
        );
        if (response.success) {
          setEvents(response.data);
        }
      } catch (error) {
        console.error("Error loading therapist appointments:", error);
        // Fallback con dati mock per sviluppo
        setEvents([
          {
            id: "1",
            title: "Mario Rossi",
            start: "2024-06-15T09:00:00",
            end: "2024-06-15T10:00:00",
            backgroundColor: "#3b82f6",
            borderColor: "#3b82f6",
            extendedProps: {
              patientId: 1,
              therapistId: therapistId,
              patientName: "Mario Rossi",
              treatmentName: "Logopedia Individuale",
              duration: 60,
              status: "scheduled",
              location: "office",
            },
          },
          {
            id: "2",
            title: "Anna Verdi",
            start: "2024-06-15T10:30:00",
            end: "2024-06-15T11:30:00",
            backgroundColor: "#22c55e",
            borderColor: "#22c55e",
            extendedProps: {
              patientId: 2,
              therapistId: therapistId,
              patientName: "Anna Verdi",
              treatmentName: "Logopedia Individuale",
              duration: 60,
              status: "completed",
              location: "office",
            },
          },
        ]);
      } finally {
        setLoading(false);
      }
    },
    [therapistId]
  );

  // Inizializzazione
  useEffect(() => {
    loadTherapistAppointments(currentDate);
  }, [loadTherapistAppointments, currentDate]);

  // Gestione click su evento
  const handleEventClick = (clickInfo) => {
    const appointment = {
      id: clickInfo.event.id,
      title: clickInfo.event.title,
      start: clickInfo.event.start,
      end: clickInfo.event.end,
      ...clickInfo.event.extendedProps,
    };
    setSelectedAppointment(appointment);
    setModalOpen(true);
  };

  // Gestione cambio data
  const handleDatesSet = (dateInfo) => {
    setCurrentDate(dateInfo.start);
    loadTherapistAppointments(dateInfo.start);
  };

  // Marca presenza/assenza rapida
  const handleQuickAttendance = async (appointmentId, status) => {
    try {
      const response = await calendarService.markAttendance(
        appointmentId,
        status
      );
      if (response.success) {
        // Aggiorna evento nel calendario
        setEvents((prevEvents) =>
          prevEvents.map((event) => {
            if (event.id === appointmentId) {
              const newColor = getStatusColor(status);
              return {
                ...event,
                backgroundColor: newColor,
                borderColor: newColor,
                extendedProps: {
                  ...event.extendedProps,
                  status: status,
                },
              };
            }
            return event;
          })
        );

        // Mostra feedback
        showToast(`Presenza aggiornata: ${getStatusLabel(status)}`);
      }
    } catch (error) {
      console.error("Error marking attendance:", error);
      alert("Errore durante l'aggiornamento della presenza");
    }
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

  // Utility functions
  const getStatusColor = (status) => {
    const colors = {
      completed: "#22c55e",
      absent_justified: "#f59e0b",
      absent_not_justified: "#ef4444",
      cancelled: "#6b7280",
      scheduled: "#3b82f6",
    };
    return colors[status] || colors.scheduled;
  };

  const getStatusLabel = (status) => {
    const labels = {
      completed: "Presente",
      absent_justified: "Assente Giustificato",
      absent_not_justified: "Assente Non Giustificato",
      cancelled: "Annullato",
      scheduled: "Programmato",
    };
    return labels[status] || "Programmato";
  };

  const showToast = (message) => {
    // Implementazione semplice toast
    const toast = document.createElement("div");
    toast.className = "toast-message";
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #22c55e;
      color: white;
      padding: 12px 20px;
      border-radius: 6px;
      z-index: 1000;
      font-weight: 500;
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
      document.body.removeChild(toast);
    }, 3000);
  };

  return (
    <div className="therapist-day-view h-full">
      {loading && (
        <div className="loading-overlay">
          <div className="loading-spinner">Caricamento...</div>
        </div>
      )}

      {/* Header con azioni rapide */}
      <div className="day-view-header p-4 bg-white border-b">
        <h2 className="text-xl font-semibold text-gray-800">
          I miei appuntamenti
        </h2>
        <p className="text-sm text-gray-600">
          {currentDate.toLocaleDateString("it-IT", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
          })}
        </p>
      </div>

      <FullCalendar
        plugins={[timeGridPlugin, interactionPlugin]}
        initialView="timeGridDay"
        locale="it"
        firstDay={1}
        height="calc(100% - 80px)"
        // Eventi
        events={events}
        // Configurazione orari
        slotMinTime="07:00:00"
        slotMaxTime="20:00:00"
        slotDuration="00:30:00"
        businessHours={{
          startTime: "08:00",
          endTime: "19:00",
          daysOfWeek: [1, 2, 3, 4, 5],
        }}
        // Interazione
        editable={false}
        selectable={false}
        // Event handlers
        eventClick={handleEventClick}
        datesSet={handleDatesSet}
        // Header toolbar
        headerToolbar={{
          left: "prev,next",
          center: "title",
          right: "today",
        }}
        // Styling
        eventDisplay="block"
        allDaySlot={false}
        // Custom content per eventi
        eventContent={(eventInfo) => {
          const props = eventInfo.event.extendedProps;
          const isPast = eventInfo.event.end < new Date();
          const canMarkAttendance = isPast && props.status === "scheduled";

          return (
            <div className="fc-event-content-therapist">
              <div className="fc-event-time">{eventInfo.timeText}</div>
              <div className="fc-event-title font-semibold">
                {eventInfo.event.title}
              </div>
              <div className="fc-event-treatment text-xs">
                {props.treatmentName}
              </div>
              <div className="fc-event-status text-xs">
                {getStatusLabel(props.status)}
              </div>

              {/* Bottoni azione rapida per appuntamenti passati */}
              {canMarkAttendance && (
                <div className="fc-event-actions mt-1">
                  <button
                    className="btn-present"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleQuickAttendance(eventInfo.event.id, "completed");
                    }}
                    style={{
                      fontSize: "10px",
                      padding: "2px 6px",
                      marginRight: "4px",
                      backgroundColor: "#22c55e",
                      color: "white",
                      border: "none",
                      borderRadius: "3px",
                    }}
                  >
                    ✓
                  </button>
                  <button
                    className="btn-absent"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleQuickAttendance(
                        eventInfo.event.id,
                        "absent_not_justified"
                      );
                    }}
                    style={{
                      fontSize: "10px",
                      padding: "2px 6px",
                      backgroundColor: "#ef4444",
                      color: "white",
                      border: "none",
                      borderRadius: "3px",
                    }}
                  >
                    ✗
                  </button>
                </div>
              )}
            </div>
          );
        }}
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

      {/* Stili CSS inline per mobile */}
      <style jsx>{`
        .therapist-day-view {
          background: #f8fafc;
        }

        .loading-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(255, 255, 255, 0.8);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 100;
        }

        .loading-spinner {
          padding: 20px;
          background: white;
          border-radius: 8px;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .fc-event-content-therapist {
          padding: 4px;
          font-size: 12px;
          line-height: 1.2;
        }

        @media (max-width: 768px) {
          .day-view-header {
            padding: 12px 16px;
          }

          .fc-event-content-therapist {
            font-size: 11px;
            padding: 2px;
          }

          .fc-event-actions button {
            font-size: 9px !important;
            padding: 1px 4px !important;
          }
        }
      `}</style>
    </div>
  );
};

export default TherapistDayView;
