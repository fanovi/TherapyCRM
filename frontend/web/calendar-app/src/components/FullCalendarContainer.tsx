import React, { useState, useEffect, useRef } from "react";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import listPlugin from "@fullcalendar/list";
import { EventClickArg, DateSelectArg, EventInput } from "@fullcalendar/core";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Calendar, Clock, List, Grid } from "lucide-react";
import { therapyAPI } from "@/lib/api";
import { Appointment } from "@/types/therapy";
import moment from "moment";

interface FullCalendarContainerProps {
  patientId?: string;
  therapistId?: string;
  selectedDate?: Date;
  onDateSelect?: (date: Date) => void;
  onSlotClick?: (date: Date, time: string) => void;
  onAppointmentMove?: (
    appointmentId: string,
    newDate: Date,
    newTime: string,
    eventData?: any
  ) => void;
  readOnly?: boolean;
  currentView?: "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek";
  onViewChange?: (
    view: "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek"
  ) => void;
  externalEvents?: EventInput[];
  onRef?: (ref: any) => void;
  onNavigate?: (date: Date) => void;
}

interface AppointmentEvent {
  id: string;
  title: string;
  start: string;
  end: string;
  patient_name?: string;
  therapist_name?: string;
  status?: string;
  notes?: string;
  backgroundColor?: string;
  borderColor?: string;
  textColor?: string;
}

const FullCalendarContainer: React.FC<FullCalendarContainerProps> = ({
  patientId,
  therapistId,
  selectedDate,
  onDateSelect,
  onSlotClick,
  onAppointmentMove,
  readOnly = false,
  currentView: externalCurrentView,
  onViewChange,
  externalEvents,
  onRef,
  onNavigate,
}) => {
  const [internalCurrentView, setInternalCurrentView] = useState<
    "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek"
  >("dayGridMonth");

  // Usa vista esterna se fornita, altrimenti usa quella interna
  const currentView = externalCurrentView || internalCurrentView;
  const [events, setEvents] = useState<EventInput[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedDate_, setSelectedDate] = useState<Date>(
    selectedDate || new Date()
  );
  const calendarRef = useRef<FullCalendar>(null);
  const { toast } = useToast();
  const [currentDateRange, setCurrentDateRange] = useState<string>(() => {
    const now = moment();
    return `${now.format("YYYY-MM")}`;
  });
  const [isLoadingAppointments, setIsLoadingAppointments] = useState(false);

  // Carica gli appuntamenti dal server per un range di date
  const loadAppointments = async (startDate?: Date, endDate?: Date) => {
    try {
      setIsLoadingAppointments(true);
      if (!startDate) {
        setLoading(true);
      }

      let appointments: Appointment[] = [];

      // Se non vengono fornite date, usa la data corrente
      const start = startDate ? moment(startDate) : moment();
      const end = endDate ? moment(endDate) : moment();

      // Determina tutti i mesi che dobbiamo caricare
      const monthsToLoad = new Set<string>();
      let current = start.clone().startOf("month");
      const endMonth = end.clone().endOf("month");

      while (current.isSameOrBefore(endMonth)) {
        monthsToLoad.add(current.format("YYYY-MM"));
        current.add(1, "month");
      }

      // Crea una chiave unica per il range
      const rangeKey = Array.from(monthsToLoad).sort().join("|");

      // Se stiamo già caricando gli stessi dati, evita chiamate duplicate
      if (isLoadingAppointments) {
        return;
      }

      setCurrentDateRange(rangeKey);

      // Carica gli appuntamenti per tutti i mesi necessari
      const appointmentPromises: Promise<Appointment[]>[] = [];

      for (const monthKey of monthsToLoad) {
        const [year, month] = monthKey.split("-");

        if (patientId) {
          appointmentPromises.push(
            therapyAPI.getPatientAppointments(
              parseInt(patientId),
              parseInt(month),
              parseInt(year)
            )
          );
        } else if (therapistId) {
          appointmentPromises.push(
            therapyAPI.getTherapistAppointments(
              parseInt(therapistId),
              parseInt(month),
              parseInt(year)
            )
          );
        }
      }

      // Attendi tutti i caricamenti e combina i risultati
      const allAppointments = await Promise.all(appointmentPromises);
      appointments = allAppointments.flat();

      // Trasforma i dati in formato FullCalendar
      const formattedEvents: EventInput[] = appointments.map(
        (appointment: Appointment) => {
          // Parsing della data/ora dal formato "YYYY-MM-DD HH:mm:ss"
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
              patient_id: appointment.patient?.id,
              therapist_id: appointment.therapist?.id,
              status: appointment.status,
              notes: appointment.notes,
              type: appointment.treatmentType,
              duration: appointment.duration,
            },
          };
        }
      );

      setEvents(formattedEvents);
    } catch (error) {
      console.error("Error loading appointments:", error);
      toast({
        title: "Errore",
        description: "Impossibile caricare gli appuntamenti",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
      setIsLoadingAppointments(false);
    }
  };

  // Ottieni colore basato sullo status
  const getStatusColor = (status: string) => {
    switch (status) {
      case "confirmed":
        return "#10B981"; // Verde
      case "pending":
        return "#F59E0B"; // Arancione
      case "cancelled":
        return "#EF4444"; // Rosso
      case "completed":
        return "#6366F1"; // Blu
      default:
        return "#6B7280"; // Grigio
    }
  };

  useEffect(() => {
    if (!externalEvents || externalEvents.length === 0) {
      // Carica gli appuntamenti per il mese corrente
      const now = moment();
      const startOfMonth = now.clone().startOf("month").toDate();
      const endOfMonth = now.clone().endOf("month").toDate();
      loadAppointments(startOfMonth, endOfMonth);
    } else {
      // Se usiamo eventi esterni, non siamo in caricamento
      setLoading(false);
    }
  }, [patientId, therapistId, externalEvents]);

  // Sincronizza vista quando cambia dall'esterno
  useEffect(() => {
    if (externalCurrentView) {
      const calendarApi = calendarRef.current?.getApi();
      if (calendarApi) {
        calendarApi.changeView(externalCurrentView);
      }
    }
  }, [externalCurrentView]);

  // Usa eventi esterni se forniti
  const displayEvents = externalEvents || events;

  // Espone funzioni per comunicazione con altri calendari
  const calendarInterface = {
    updateEvent: (eventId: string, newStart: string, newEnd: string) => {
      setEvents((prev) =>
        prev.map((event) => {
          if (event.id === eventId) {
            return {
              ...event,
              start: newStart,
              end: newEnd,
            };
          }
          return event;
        })
      );
    },
    refreshEvents: (startDate?: Date, endDate?: Date) => {
      loadAppointments(startDate, endDate);
    },
  };

  // Passa l'interfaccia al genitore
  useEffect(() => {
    if (onRef) {
      onRef(calendarInterface);
    }
  }, [onRef]);

  // Gestione click su evento
  const handleEventClick = (clickInfo: EventClickArg) => {
    const event = clickInfo.event;
    const props = event.extendedProps;

    toast({
      title: event.title,
      description: `
        Paziente: ${props.patient_name || "N/A"}
        Terapista: ${props.therapist_name || "N/A"}
        Status: ${props.status || "N/A"}
        Orario: ${event.start?.toLocaleTimeString()} - ${event.end?.toLocaleTimeString()}
      `,
    });
  };

  // Gestione selezione date
  const handleDateSelect = (selectInfo: DateSelectArg) => {
    // Se è readOnly, non permettere selezione
    if (readOnly) {
      return;
    }

    const selectedDate = selectInfo.start;
    setSelectedDate(selectedDate);
    onDateSelect?.(selectedDate);

    // Se abbiamo onSlotClick, usa quello invece del prompt
    if (onSlotClick) {
      const time = selectedDate.toTimeString().slice(0, 5); // "HH:mm"
      onSlotClick(selectedDate, time);
    } else {
      // Fallback: prompt per creare nuovo appuntamento
      const title = prompt("Inserisci il titolo dell'appuntamento:");
      if (title) {
        const newEvent: EventInput = {
          id: Date.now().toString(),
          title,
          start: selectInfo.start,
          end: selectInfo.end,
          backgroundColor: "#3B82F6",
          borderColor: "#3B82F6",
          textColor: "#ffffff",
        };
        setEvents((prev) => [...prev, newEvent]);
      }
    }

    // Deseleziona
    const calendarApi = calendarRef.current?.getApi();
    calendarApi?.unselect();
  };

  // Cambia vista
  const changeView = (
    view: "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek"
  ) => {
    // Se abbiamo callback esterno, usa quello, altrimenti aggiorna stato interno
    if (onViewChange) {
      onViewChange(view);
    } else {
      setInternalCurrentView(view);
    }
    const calendarApi = calendarRef.current?.getApi();
    calendarApi?.changeView(view);
  };

  // Naviga a oggi
  const goToToday = () => {
    const calendarApi = calendarRef.current?.getApi();
    calendarApi?.today();
  };

  // Gestione drag and drop eventi
  const handleEventDrop = (dropInfo: any) => {
    // Se è readOnly, non permettere drag and drop
    if (readOnly) {
      dropInfo.revert();
      return;
    }

    if (onAppointmentMove) {
      const eventId = dropInfo.event.id;
      const newDate = dropInfo.event.start;
      const newTime = newDate.toTimeString().slice(0, 5); // "HH:mm"
      const eventData = dropInfo.event.extendedProps;

      onAppointmentMove(eventId, newDate, newTime, eventData);
    }
  };

  // Personalizza rendering eventi
  const eventContent = (eventInfo: any) => {
    const props = eventInfo.event.extendedProps;

    if (currentView === "timeGridDay" || currentView === "timeGridWeek") {
      return (
        <div className="p-1 text-xs">
          <div className="font-semibold truncate">{eventInfo.event.title}</div>
          {props.therapist_name && (
            <div className="truncate opacity-80">{props.therapist_name}</div>
          )}
        </div>
      );
    }

    return (
      <div className="p-1 text-xs">
        <div className="font-semibold truncate">{eventInfo.event.title}</div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Caricamento calendario...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-lg p-6">
      {/* Toolbar personalizzata */}
      <div className="flex flex-wrap items-center justify-between mb-6 gap-4">
        <div className="flex items-center gap-2">
          <Button
            variant={currentView === "dayGridMonth" ? "default" : "outline"}
            size="sm"
            onClick={() => changeView("dayGridMonth")}
          >
            <Grid className="h-4 w-4 mr-1" />
            Mese
          </Button>
          <Button
            variant={currentView === "timeGridWeek" ? "default" : "outline"}
            size="sm"
            onClick={() => changeView("timeGridWeek")}
          >
            <Calendar className="h-4 w-4 mr-1" />
            Settimana
          </Button>
          <Button
            variant={currentView === "timeGridDay" ? "default" : "outline"}
            size="sm"
            onClick={() => changeView("timeGridDay")}
          >
            <Clock className="h-4 w-4 mr-1" />
            Giorno
          </Button>
          <Button
            variant={currentView === "listWeek" ? "default" : "outline"}
            size="sm"
            onClick={() => changeView("listWeek")}
          >
            <List className="h-4 w-4 mr-1" />
            Lista
          </Button>
        </div>

        <Button variant="outline" size="sm" onClick={goToToday}>
          Oggi
        </Button>
      </div>

      {/* FullCalendar */}
      <div className="fullcalendar-container">
        <FullCalendar
          ref={calendarRef}
          plugins={[
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin,
            listPlugin,
          ]}
          initialView={currentView}
          height="auto"
          headerToolbar={{
            left: "prev,next",
            center: "title",
            right: "",
          }}
          events={displayEvents}
          eventClick={handleEventClick}
          selectable={!readOnly}
          selectMirror={!readOnly}
          select={handleDateSelect}
          eventContent={eventContent}
          eventDrop={handleEventDrop}
          dayMaxEvents={true}
          weekends={true}
          editable={!readOnly}
          droppable={!readOnly}
          locale="it"
          buttonText={{
            today: "Oggi",
            month: "Mese",
            week: "Settimana",
            day: "Giorno",
            list: "Lista",
          }}
          slotMinTime="08:00:00"
          slotMaxTime="20:00:00"
          slotDuration="00:30:00"
          allDaySlot={false}
          nowIndicator={true}
          eventDisplay="block"
          eventBackgroundColor="#3B82F6"
          eventBorderColor="#3B82F6"
          eventTextColor="#ffffff"
          // Stili personalizzati
          dayHeaderClassNames="bg-gray-50 font-medium text-gray-700 py-2"
          eventClassNames="cursor-pointer hover:opacity-80 transition-opacity"
          datesSet={(dateInfo) => {
            // Chiamato quando cambia la vista o si naviga
            if (onNavigate) {
              onNavigate(dateInfo.start);
            }

            // Ricarica gli appuntamenti solo se è cambiato il range di date e non stiamo usando eventi esterni
            if (!externalEvents || externalEvents.length === 0) {
              const start = moment(dateInfo.start);
              const end = moment(dateInfo.end);

              // Determina tutti i mesi visibili nel range corrente
              const monthsVisible = new Set<string>();
              let current = start.clone().startOf("month");
              const endMonth = end.clone().endOf("month");

              while (current.isSameOrBefore(endMonth)) {
                monthsVisible.add(current.format("YYYY-MM"));
                current.add(1, "month");
              }

              const newRangeKey = Array.from(monthsVisible).sort().join("|");

              // Ricarica solo se è cambiato il range di date
              if (newRangeKey !== currentDateRange && !isLoadingAppointments) {
                loadAppointments(dateInfo.start, dateInfo.end);
              }
            }
          }}
        />
      </div>
    </div>
  );
};

export default FullCalendarContainer;
