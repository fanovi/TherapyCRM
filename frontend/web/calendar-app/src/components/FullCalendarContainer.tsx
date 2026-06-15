import React, { useState, useEffect, useRef } from "react";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import listPlugin from "@fullcalendar/list";
import { EventClickArg, DateSelectArg, EventInput } from "@fullcalendar/core";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Calendar, Clock, Grid } from "lucide-react";
import { therapyAPI } from "@/lib/api";
import { Appointment, TherapistAbsence } from "@/types/therapy";
import { getEventBaseColor, lightenColor } from "@/lib/treatmentColors";
import moment from "moment";
import "../styles/calendar.css";

interface FullCalendarContainerProps {
  patientId?: string;
  therapistId?: string;
  selectedDate?: Date;
  onDateSelect?: (date: Date) => void;
  onSlotClick?: (date: Date, time: string) => void;
  onAppointmentClick?: (appointmentId: string) => void;
  onAppointmentMove?: (
    appointmentId: string,
    newDate: Date,
    newTime: string,
    eventData?: any
  ) => Promise<boolean> | void;
  readOnly?: boolean;
  currentView?: "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek";
  onViewChange?: (
    view: "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek"
  ) => void;
  externalEvents?: EventInput[];
  onRef?: (ref: any) => void;
  onNavigate?: (date: Date) => void;
  onVisibleRangeChange?: (start: Date, end: Date) => void;
  therapistAbsences?: TherapistAbsence[];
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
  onAppointmentClick,
  onAppointmentMove,
  readOnly = false,
  currentView: externalCurrentView,
  onViewChange,
  externalEvents,
  onRef,
  onNavigate,
  onVisibleRangeChange,
  therapistAbsences = [],
}) => {
  const [internalCurrentView, setInternalCurrentView] = useState<
    "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek"
  >("timeGridWeek");

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

          // Determina il colore basato su status E tipo di appuntamento
          const getAppointmentColor = (appointment: Appointment) => {
            // Prima controlla se è privato
            if (
              appointment.appointmentSource === "private" ||
              appointment.isPrivate
            ) {
              // Colori viola per appuntamenti privati
              switch (appointment.status) {
                case "confirmed":
                case "scheduled":
                  return "#8B5CF6"; // Viola
                case "pending":
                  return "#A78BFA"; // Viola chiaro
                case "cancelled":
                  return "#C4B5FD"; // Viola molto chiaro
                case "completed":
                  return "#6D28D9"; // Viola scuro
                default:
                  return "#9333EA"; // Viola default
              }
            }

            // Per appuntamenti ABA, usa colori specifici per tipo
            if (appointment.appointmentType) {
              const baseColors: Record<string, string> = {
                parent_training: "#8B5CF6", // Viola per Parent Training
                supervisione: "#F59E0B", // Arancione per Supervisione
                terapia: "#3B82F6", // Blu per Terapia (default)
              };

              const baseColor =
                baseColors[appointment.appointmentType] ||
                baseColors["terapia"];

              // Modifica la saturazione in base allo status
              switch (appointment.status) {
                case "confirmed":
                case "scheduled":
                  return baseColor;
                case "pending":
                  return baseColor + "CC"; // Aggiungi trasparenza
                case "cancelled":
                  return "#9CA3AF"; // Grigio per cancellati
                case "completed":
                  return baseColor;
                default:
                  return baseColor;
              }
            }

            // Colori normali per appuntamenti da piano terapeutico
            switch (appointment.status) {
              case "confirmed":
              case "scheduled":
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

          const backgroundColor = getAppointmentColor(appointment);

          // Determina se l'evento è modificabile
          const isEditable = appointment.status === "scheduled";

          // Mappa per le abbreviazioni dei tipi di appuntamento ABA
          const typeAbbreviations: Record<string, string> = {
            terapia: "", // Nessuna abbreviazione per terapia (default)
            parent_training: " (PT)",
            supervisione: " (S)",
          };

          const isGroupSession =
            appointment.groupSessionId !== null &&
            appointment.groupSessionId !== undefined;

          const isRecovery = appointment.appointment_category === "recovery";

          const typeAbbr =
            typeAbbreviations[appointment.appointmentType || "terapia"];
          const baseTitle =
            appointment.therapist?.name ||
            appointment.patient?.name ||
            "Appuntamento";
          const title = isGroupSession
            ? `👥 ${baseTitle}`
            : baseTitle + typeAbbr;

          return {
            id: appointment.id.toString(),
            title: title,
            start: startTime.toISOString(),
            end: endTime.toISOString(),
            backgroundColor,
            borderColor: getStatusColor(appointment.status),
            textColor: "#000000",
            // Disabilita drag & drop per appuntamenti non scheduled
            editable: isEditable,
            // Aggiungi classe CSS personalizzata per styling
            classNames: isEditable ? [] : ["non-editable-event"],
            extendedProps: {
              patient_name: appointment.patient?.name || "N/A",
              therapist_name: appointment.therapist?.name || "N/A",
              therapist_color: appointment.therapist?.color || null,
              patient_id: appointment.patient?.id,
              therapist_id: appointment.therapist?.id,
              status: appointment.status,
              notes: appointment.notes,
              type: appointment.treatmentType,
              duration: appointment.duration,
              appointmentSource: appointment.appointmentSource,
              appointmentType: appointment.appointmentType, // Aggiungi il tipo di appuntamento
              isPrivate:
                appointment.isPrivate ||
                appointment.appointmentSource === "private",
              isEditable: isEditable, // Passa questa info per il modal
              isGroupSession: appointment.isGroup,
              groupSessionId: appointment.groupSessionId,
              isRecovery: isRecovery,
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

  // Naviga alla data selezionata quando cambia
  // Naviga alla data selezionata solo quando il componente si monta
  useEffect(() => {
    if (selectedDate && calendarRef.current && loading === false) {
      const calendarApi = calendarRef.current.getApi();

      calendarApi.gotoDate(selectedDate);
    }
  }, [loading]); // Dipende solo da loading, non da selectedDate

  // Crea eventi per le assenze del terapista
  const absenceEvents = therapistAbsences.map((absence) => {
    // FullCalendar end-date e' esclusivo: per una assenza che termina il
    // 25/05 (inclusivo) dobbiamo passare 26/05 come end.
    // Calcolo in LOCALE per evitare off-by-one timezone (CEST UTC+2).
    const padN = (n: number) => (n < 10 ? "0" + n : String(n));
    const endParts = absence.end_date.split("-");
    const endLocal = new Date(
      parseInt(endParts[0], 10),
      parseInt(endParts[1], 10) - 1,
      parseInt(endParts[2], 10)
    );
    endLocal.setDate(endLocal.getDate() + 1);
    const displayEndDateStr =
      endLocal.getFullYear() + "-" + padN(endLocal.getMonth() + 1) + "-" + padN(endLocal.getDate());

    const absenceTypeLabels: Record<string, string> = {
      vacation: "Ferie",
      sick_leave: "Congedo Malattia",
      personal: "Personale",
      training: "Formazione",
      other: "Altro",
    };

    // Colore unico "grigio topo" per tutte le assenze: sobrio, non
    // confondibile con gli appuntamenti colorati per terapista.
    const ABSENCE_COLOR = "#6B7280";

    const typeLabel = absenceTypeLabels[absence.type] || absence.type;
    const color = ABSENCE_COLOR;
    const isHourly = !!absence.start_time && !!absence.end_time;
    const cssClassType = `absence-${absence.type.replace(/_/g, "-")}`;

    // Assenze orarie: event timed (solo nello slot orario) per non bloccare
    // visivamente l'intera giornata. Render come EVENTO normale (display:auto)
    // cosi' appare come blocco evidente nel calendario timegrid.
    if (isHourly) {
      const startISO = `${absence.start_date}T${absence.start_time}`;
      const endISO = `${absence.start_date}T${absence.end_time}`;
      const timeRange = `${absence.start_time!.substring(0, 5)}-${absence.end_time!.substring(0, 5)}`;
      return {
        id: `absence-${absence.id}`,
        title: `🚫 ${typeLabel} ${timeRange}${absence.reason ? " · " + absence.reason : ""}`,
        start: startISO,
        end: endISO,
        allDay: false,
        backgroundColor: color,
        borderColor: color,
        textColor: "#FFFFFF",
        classNames: ["absence-event", "absence-hourly", cssClassType],
        extendedProps: {
          isAbsence: true,
          isHourlyAbsence: true,
          absenceType: absence.type,
          absenceReason: absence.reason,
          absenceStatus: absence.status,
          absenceTimeRange: timeRange,
        },
      };
    }

    // Assenze full-day: render come allDay block visibile in alto (banner),
    // NON come background (FullCalendar nasconde titolo sui bg events e il
    // colore di sfondo da solo non era abbastanza evidente). Il blocco
    // colorato in cima a ogni giornata fa capire chiaramente che il
    // terapista non e' disponibile.
    return {
      id: `absence-${absence.id}`,
      title: `🚫 ${typeLabel}${absence.reason ? " · " + absence.reason : ""}`,
      start: absence.start_date,
      end: displayEndDateStr,
      allDay: true,
      backgroundColor: color,
      borderColor: color,
      textColor: "#FFFFFF",
      classNames: ["absence-event", "absence-fullday", cssClassType],
      extendedProps: {
        isAbsence: true,
        isHourlyAbsence: false,
        absenceType: absence.type,
        absenceReason: absence.reason,
        absenceStatus: absence.status,
      },
    };
  });

  // Combina eventi normali e assenze
  const allEvents = [...(externalEvents || events), ...absenceEvents];
  const displayEvents = allEvents;

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
    removeEvent: (eventId: string) => {
      setEvents((prev) => prev.filter((event) => event.id !== eventId));
    },
    removeEvents: (eventIds: string[]) => {
      setEvents((prev) => prev.filter((event) => !eventIds.includes(event.id as string)));
    },
    refreshEvents: (startDate?: Date, endDate?: Date) => {
      loadAppointments(startDate, endDate);
    },
    navigateToDate: (date: Date) => {
      const calendarApi = calendarRef.current?.getApi();
      if (calendarApi) {
        calendarApi.gotoDate(date);
      }
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

    // Le assenze terapista non sono appuntamenti: il click non deve aprire
    // alcun modal di modifica appuntamento.
    if (props.isAbsence) {
      return;
    }

    // Se abbiamo onAppointmentClick, usa quello per aprire il modal di modifica
    if (onAppointmentClick) {
      onAppointmentClick(event.id);
    } else {
      const appointmentTypeLabel =
        props.appointmentType === "parent_training"
          ? "Parent Training"
          : props.appointmentType === "supervisione"
          ? "Supervisione"
          : "Terapia";
      // Fallback: mostra toast con informazioni
      toast({
        title: event.title,
        description: (
          <div className="space-y-1">
            <div>Paziente: {props.patient_name || "N/A"}</div>
            <div>Terapista: {props.therapist_name || "N/A"}</div>
            <div>Trattamento: {props.type || "Non specificato"}</div>
            {props.appointmentType && props.appointmentType !== "terapia" && (
              <div>Tipo: {appointmentTypeLabel}</div>
            )}
            <div>Stato: {props.status || "N/A"}</div>
            <div>
              Orario:{" "}
              {event.start?.toLocaleTimeString("it-IT", {
                hour: "2-digit",
                minute: "2-digit",
              })}{" "}
              -{" "}
              {event.end?.toLocaleTimeString("it-IT", {
                hour: "2-digit",
                minute: "2-digit",
              })}
            </div>
            {props.notes && <div>Note: {props.notes}</div>}
          </div>
        ),
        duration: 5000, // Mostra il toast per 5 secondi
      });
    }
  };

  // Gestione selezione date
  const handleDateSelect = (selectInfo: DateSelectArg) => {
    // Se è readOnly, non permettere selezione
    if (readOnly) {
      return;
    }

    // Verifica se lo slot (data + range orario) cade durante una assenza.
    // Per assenze full-day blocca tutto il giorno; per assenze orarie blocca
    // solo se c'e' overlap col range dell'assenza nel singolo giorno.
    // NB: data LOCALE (non toISOString) per evitare off-by-one in CEST.
    const checkDate = selectInfo.start;
    const pad = (n: number) => (n < 10 ? "0" + n : String(n));
    const selectedDateStr =
      checkDate.getFullYear() + "-" + pad(checkDate.getMonth() + 1) + "-" + pad(checkDate.getDate());
    const slotStartHHMM = pad(selectInfo.start.getHours()) + ":" + pad(selectInfo.start.getMinutes());
    const slotEndHHMM = pad(selectInfo.end.getHours()) + ":" + pad(selectInfo.end.getMinutes());

    const ABSENCE_TYPE_LABELS: Record<string, string> = {
      vacation: "Ferie",
      sick_leave: "Congedo Malattia",
      personal: "Personale",
      training: "Formazione",
      other: "Altro",
    };

    const absenceOverlapsSlot = (absence: TherapistAbsence) => {
      if (absence.status !== "approved") return false;
      if (selectedDateStr < absence.start_date || selectedDateStr > absence.end_date) return false;
      const hourly = !!absence.start_time && !!absence.end_time;
      if (!hourly) return true;
      const absStart = absence.start_time!.substring(0, 5);
      const absEnd = absence.end_time!.substring(0, 5);
      // overlap: slot.start < abs.end AND abs.start < slot.end
      return slotStartHHMM < absEnd && absStart < slotEndHHMM;
    };

    const blockedAbsence = therapistAbsences.find(absenceOverlapsSlot);

    if (blockedAbsence && therapistId) {
      const typeLabel = ABSENCE_TYPE_LABELS[blockedAbsence.type] || blockedAbsence.type;
      const hourly = !!blockedAbsence.start_time && !!blockedAbsence.end_time;
      const orarioMsg = hourly
        ? ` dalle ${blockedAbsence.start_time!.substring(0, 5)} alle ${blockedAbsence.end_time!.substring(0, 5)}`
        : "";
      const reasonMsg = blockedAbsence.reason ? ` (${blockedAbsence.reason})` : "";

      toast({
        title: hourly ? "Slot non disponibile" : "Data non disponibile",
        description: `Il terapista è assente${orarioMsg} per: ${typeLabel}${reasonMsg}. Non è possibile creare appuntamenti.`,
        variant: "destructive",
      });

      const calendarApi = calendarRef.current?.getApi();
      calendarApi?.unselect();
      return;
    }

    // Verifica se c'è già un evento in questo slot
    const selectedStart = selectInfo.start.getTime();
    const selectedEnd = selectInfo.end.getTime();

    const hasConflict = displayEvents.some((event) => {
      const eventStart = new Date(event.start as string).getTime();
      const eventEnd = new Date(event.end as string).getTime();

      return selectedStart < eventEnd && selectedEnd > eventStart;
    });

    if (hasConflict) {
      toast({
        title: "Slot occupato",
        description:
          "Questo slot orario è già occupato. Clicca sull'appuntamento per vedere i dettagli.",
        variant: "destructive",
      });
      const calendarApi = calendarRef.current?.getApi();
      calendarApi?.unselect();
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
          backgroundColor: "#DBEAFE",
          borderColor: "#3B82F6",
          textColor: "#000000",
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
  const handleEventDrop = async (dropInfo: any) => {
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

      // Aspetta il risultato e fa revert se fallisce
      const success = await onAppointmentMove(eventId, newDate, newTime, eventData);
      if (success === false) {
        dropInfo.revert();
      }
    }
  };

  // Personalizza rendering eventi
  // Aggiungi questo import all'inizio del file:

  // Modifica la funzione eventContent per includere più dettagli e colori:
  // Personalizza rendering eventi
  const eventContent = (eventInfo: any) => {
    const props = eventInfo.event.extendedProps;

    // Le assenze (blocchi grigi) usano il rendering di default di FullCalendar.
    if (props.isAbsence) {
      return true;
    }

    const isPrivate = props.isPrivate || props.appointmentSource === "private";
    const isEditable = props.isEditable !== false;
    const treatmentType = props.type || "Non specificato";

    // Colore base: colore calendario del terapista (se impostato dal
    // gestionale), fallback sul colore del tipo di trattamento.
    // Sfondo schiarito + testo nero (richiesta direzione).
    const baseColor = getEventBaseColor(props.therapist_color, treatmentType);

    // Imposta il colore dell'evento solo se l'elemento DOM esiste
    if (eventInfo.el) {
      eventInfo.el.style.backgroundColor = lightenColor(baseColor);
      eventInfo.el.style.borderColor = baseColor;
      eventInfo.el.style.borderLeftWidth = "4px";
      eventInfo.el.style.color = "#000000";
    }

    if (currentView === "timeGridDay" || currentView === "timeGridWeek") {
      return (
        <div
          className={`p-1 text-sm text-black ${!isEditable ? "opacity-60" : ""}`}
          title={`${treatmentType}\nPaziente: ${
            props.patient_name
          }\nTerapista: ${props.therapist_name}\nDurata: ${props.duration} min${
            props.notes ? "\nNote: " + props.notes : ""
          }`}
        >
          <div className="font-semibold truncate flex items-center gap-1">
            {isPrivate && <span>🔒</span>}
            {props.isGroupSession && <span className="text-blue-700">👥</span>}
            {props.isRecovery && (
              <span className="text-amber-700" title="Appuntamento di recupero">
                🔁
              </span>
            )}
            {eventInfo.event.title}
            {props.appointmentType === "parent_training" && " (PT)"}
            {props.appointmentType === "supervisione" && " (S)"}
            {!isEditable && <span className="text-xs">✓</span>}
          </div>
          <div className="truncate opacity-90 text-xs">{treatmentType}</div>
          {props.patient_name && (
            <div className="truncate opacity-80">{props.patient_name}</div>
          )}
        </div>
      );
    }

    return (
      <div
        className={`p-1 text-sm text-black ${!isEditable ? "opacity-60" : ""}`}
        title={`${treatmentType}\nPaziente: ${props.patient_name}\nDurata: ${props.duration} min`}
      >
        <div className="font-semibold truncate flex items-center gap-1">
          {isPrivate && <span>🔒</span>}
          {props.isRecovery && (
            <span title="Appuntamento di recupero">🔁</span>
          )}
          {eventInfo.event.title}
          {!isEditable && <span>✓</span>}
        </div>
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
      {/* Toolbar custom rimossa: i bottoni view sono nel CalendarViewSelector
          (top-right di DualFullCalendarView), il bottone Oggi e' nel
          headerToolbar di FullCalendar. */}

      {/* FullCalendar */}
      <div className="fullcalendar-container">
        <style>{`
  .fc-event.non-editable-event {
    opacity: 0.7;
    cursor: default !important;
  }

  .fc-event.non-editable-event:hover {
    opacity: 0.8;
  }

  .fc-event.non-editable-event .fc-event-main {
    cursor: default !important;
  }
  
  .fc-event:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 999 !important;
  }

  .fc-timegrid-event {
    overflow: visible !important;
  }

  .fc-event-title {
    white-space: normal;
  }

  /* Stili per appuntamenti di gruppo */
.fc-event.group-session-event {
  box-shadow: 0 0 0 3px #1E40AF;
  overflow: visible !important;
}

/* Aggiungi un piccolo badge nell'angolo */
.fc-event.group-session-event::after {
  content: "👥";
  position: absolute;
  top: -8px;
  right: -8px;
  background: #1E40AF;
  color: white;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  z-index: 10;
}

/* Tooltip al hover */
.fc-event.group-session-event:hover::before {
  content: "Sessione di gruppo";
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: #1F2937;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  margin-bottom: 4px;
  z-index: 20;
}
`}</style>
        <FullCalendar
          ref={calendarRef}
          plugins={[
            dayGridPlugin,
            timeGridPlugin,
            interactionPlugin,
            listPlugin,
          ]}
          firstDay={1} // AGGIUNGI QUESTA RIGA - 1 = lunedì
          initialView={currentView}
          height="auto"
          headerToolbar={{
            left: "prev,next today",
            center: "title",
            right: "",
          }}
          events={displayEvents}
          eventClick={handleEventClick}
          selectable={!readOnly}
          selectMirror={!readOnly}
          select={handleDateSelect}
          eventContent={eventContent}
          eventDidMount={(info: any) => {
            const props = info.event.extendedProps;

            // Le assenze (blocchi viola) mantengono il proprio stile.
            if (props.isAbsence) {
              return;
            }

            const treatmentType = props.type || "Non specificato";
            const baseColor = getEventBaseColor(
              props.therapist_color,
              treatmentType
            );

            info.el.style.backgroundColor = lightenColor(baseColor);
            info.el.style.borderColor = baseColor;
            info.el.style.borderLeftWidth = "4px";
            info.el.style.color = "#000000";
          }}
          eventDrop={handleEventDrop}
          dayMaxEvents={true}
          weekends={true}
          editable={!readOnly}
          droppable={!readOnly}
          eventAllow={(dropInfo, draggedEvent) => {
            // Impedisci il drop se l'evento non è editable
            return draggedEvent.extendedProps?.isEditable !== false;
          }}
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
          slotDuration="00:05:00"
          allDaySlot={false}
          nowIndicator={true}
          eventDisplay="block"
          eventBackgroundColor="#DBEAFE"
          eventBorderColor="#3B82F6"
          eventTextColor="#000000"
          // Stili personalizzati
          dayHeaderClassNames="bg-gray-50 font-medium text-gray-700 py-2"
          eventClassNames="cursor-pointer hover:opacity-80 transition-opacity"
          // Colora l'intera colonna del giorno per assenze full-day:
          // shading evidente che invade tutti gli slot orari, non solo il
          // banner allDay in cima.
          // NB: usiamo la data LOCALE (non toISOString che e' UTC) per
          // evitare off-by-one in timezone est di Greenwich (CEST UTC+2).
          dayCellClassNames={(arg) => {
            const d = arg.date;
            const pad = (n: number) => (n < 10 ? "0" + n : String(n));
            const dateStr = d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
            const blocked = therapistAbsences.some((a) =>
              a.status === "approved" &&
              !(a.start_time && a.end_time) &&
              dateStr >= a.start_date &&
              dateStr <= a.end_date
            );
            return blocked ? ["absence-day-bg"] : [];
          }}
          datesSet={(dateInfo) => {
            // Notifica la navigazione se definita
            if (onNavigate) {
              const calendarApi = calendarRef.current?.getApi();
              if (calendarApi) {
                const view = calendarApi.view;
                // Chiama onNavigate con la data corrente
                onNavigate(view.currentStart);
              }
            }

            // Notifica il cambio del range visibile
            if (onVisibleRangeChange) {
              onVisibleRangeChange(dateInfo.start, dateInfo.end);
            }

            // Ricarica gli appuntamenti solo se è cambiato il range di date
            if (!externalEvents || externalEvents.length === 0) {
              const start = moment(dateInfo.start);
              const end = moment(dateInfo.end);

              const monthsVisible = new Set<string>();
              let current = start.clone().startOf("month");
              const endMonth = end.clone().endOf("month");

              while (current.isSameOrBefore(endMonth)) {
                monthsVisible.add(current.format("YYYY-MM"));
                current.add(1, "month");
              }

              const newRangeKey = Array.from(monthsVisible).sort().join("|");

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
