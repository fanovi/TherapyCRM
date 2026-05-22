import React, { useState, useEffect, useRef } from "react";
import { Card } from "@/components/ui/card";
import FullCalendarContainer from "./FullCalendarContainer";
import { CalendarViewSelector, CalendarViewType } from "./CalendarViewSelector";
import { TherapistWeeklyHours } from "./TherapistWeeklyHours";
import {
  Therapist,
  Appointment,
  Specialization,
  TherapistAbsence,
} from "@/types/therapy";
import moment from "moment";
import { PlanTherapyUsedHours } from "./PlanTherapyUsedHours";

interface DualFullCalendarViewProps {
  selectedTherapist: Therapist | null;
  selectedSpecialization?: Specialization | null; // Aggiungi
  appointments: Appointment[];
  onSlotClick: (date: Date, time: string) => void;
  onAppointmentClick?: (appointmentId: string) => void;
  onAppointmentMove?: (
    appointmentId: string,
    newDate: Date,
    newTime: string,
    eventData?: any
  ) => Promise<boolean> | void;
  viewType: CalendarViewType;
  onViewTypeChange: (viewType: CalendarViewType) => void;
  hidePatientCalendar?: boolean;
  hideTherapistCalendar?: boolean;
  mode: "patient" | "therapist";
  currentPatientId?: number;
  onDateChange?: (date: Date) => void;
  onVisibleRangeChange?: (start: Date, end: Date) => void;
  isPrivateMode?: boolean; // Nuova prop
  selectedDate?: Date;
  isABARegime?: boolean; // Aggiungi
  readOnly?: boolean; // Nuova prop per disabilitare modifiche
  therapistAbsences?: TherapistAbsence[]; // Nuova prop per le assenze
  weeklyHoursRefreshTrigger?: number; // Trigger per aggiornare le ore settimanali
}

export const DualFullCalendarView: React.FC<DualFullCalendarViewProps> = ({
  selectedTherapist,
  selectedSpecialization,
  appointments,
  onSlotClick,
  onAppointmentClick,
  onAppointmentMove,
  viewType,
  onViewTypeChange,
  hidePatientCalendar = false,
  hideTherapistCalendar = false,
  mode,
  currentPatientId,
  onDateChange,
  onVisibleRangeChange,
  isPrivateMode = false,
  selectedDate: externalSelectedDate,
  isABARegime,
  readOnly = false,
  therapistAbsences = [],
  weeklyHoursRefreshTrigger,
}) => {
  const [selectedDate, setSelectedDate] = useState(
    externalSelectedDate || new Date()
  );

  const [sharedEvents, setSharedEvents] = useState<any[]>([]);
  const [currentWeekStart, setCurrentWeekStart] = useState(() => {
    return moment().startOf("isoWeek").toDate(); // Lunedì della settimana corrente
  });

  // Refs per comunicare direttamente con i calendari
  const therapistCalendarRef = useRef<any>(null);
  const patientCalendarRef = useRef<any>(null);

  // Contatore per prevenire loop infiniti durante la sincronizzazione
  // 0 = nessuna sincronizzazione in corso
  // 2 = sincronizzazione iniziata (aspettiamo 2 callback dai calendari)
  const syncNavigationCounter = useRef<number>(0);

  // Converte viewType del componente alle viste di FullCalendar
  const getFullCalendarView = (
    view: CalendarViewType
  ): "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek" => {
    switch (view) {
      case "day":
        return "timeGridDay";
      case "week":
        return "timeGridWeek";
      case "month":
        return "dayGridMonth";
      default:
        return "dayGridMonth";
    }
  };

  const currentFullCalendarView = getFullCalendarView(viewType);

  // Sincronizza eventi quando cambiano gli appuntamenti originali
  useEffect(() => {
    // Trasforma gli appuntamenti in formato FullCalendar per la sincronizzazione
    const formattedEvents = appointments.map((appointment) => {
      const [datePart, timePart] = appointment.datetime.split(" ");
      const startTime = new Date(`${datePart}T${timePart}`);
      const endTime = new Date(
        startTime.getTime() + appointment.duration * 60000
      );

      // Determina se l'appuntamento è modificabile (solo scheduled può essere spostato)
      const isEditable = appointment.status === "scheduled";

      const isGroupSession =
        appointment.groupSessionId !== null &&
        appointment.groupSessionId !== undefined;

      const isRecovery = appointment.appointment_category === "recovery";

      // console.log("isGroupSession", isGroupSession);

      // Determina se l'appuntamento permette click (scheduled o therapist_absent)
      const isClickable =
        appointment.status === "scheduled" ||
        appointment.status === "therapist_absent";

      // Determina colore basato su status
      const getStatusColor = (status: string) => {
        switch (status) {
          case "confirmed":
          case "scheduled":
            return "#10B981"; // Verde
          case "pending":
            return "#F59E0B"; // Arancione
          case "cancelled":
            return "#EF4444"; // Rosso
          case "completed":
            return "#6366F1"; // Blu
          case "therapist_absent":
            return "#8B5CF6"; // Viola per terapista assente
          case "absent_justified":
            return "#F97316"; // Arancione scuro per assenza giustificata
          case "absent_not_justified":
            return "#DC2626"; // Rosso scuro per assenza non giustificata
          default:
            return "#6B7280"; // Grigio
        }
      };

      const baseTitle =
        appointment.therapist?.name ||
        appointment.patient?.name ||
        "Appuntamento";
      const groupPrefix = isGroupSession ? `👥 Gruppo ` : "";
      const recoveryPrefix = isRecovery ? `🔁 ` : "";
      const title = `${recoveryPrefix}${groupPrefix}${baseTitle}`;

      return {
        id: appointment.id.toString(),
        title: title,
        start: startTime.toISOString(),
        end: endTime.toISOString(),
        backgroundColor: getStatusColor(appointment.status),
        borderColor: isGroupSession
          ? "#1E40AF"
          : getStatusColor(appointment.status), // Bordo blu scuro per gruppi

        textColor: "#ffffff",
        editable: isEditable, // Importante: disabilita drag & drop per non scheduled
        classNames: [
          ...(isEditable ? [] : ["non-editable-event"]),
          ...(isGroupSession ? ["group-session-event"] : []), // Classe CSS per sessioni di gruppo
        ],
        extendedProps: {
          patient_name: appointment.patient?.name || "N/A",
          therapist_name: appointment.therapist?.name || "N/A",
          status: appointment.status,
          duration: appointment.duration,
          patient_id: appointment.patient?.id || null,
          therapist_id: appointment.therapist?.id || null,
          notes: appointment.notes,
          type: appointment.treatmentType,

          appointmentType: appointment.appointmentType,
          isEditable: isEditable,
          isClickable: isClickable,
          appointmentSource: appointment.appointmentSource,
          isPrivate: appointment.isPrivate,
          isGroupSession: isGroupSession, // Aggiungi flag per gruppo
          groupSessionId: appointment.groupSessionId,
          isRecovery: isRecovery,
        },
      };
    });

    setSharedEvents(formattedEvents);
  }, [appointments]);

  // Sincronizza con la data esterna quando cambia
  useEffect(() => {
    if (externalSelectedDate) {
      setSelectedDate(externalSelectedDate);
    }
  }, [externalSelectedDate]);

  // Filtri per appuntamenti specifici
  const therapistAppointments = selectedTherapist
    ? appointments.filter((apt) => apt.therapist?.id === selectedTherapist.id)
    : [];

  const patientAppointments = currentPatientId
    ? appointments.filter(
        (apt) =>
          apt.patient &&
          (typeof apt.patient === "object"
            ? apt.patient.id === currentPatientId
            : false)
      )
    : appointments;

  // Estrai il nome del paziente corrente
  const currentPatientName = currentPatientId
    ? patientAppointments.find((apt) => apt.patient?.id === currentPatientId)
        ?.patient?.name
    : null;

  // Gestione selezione data
  const handleDateSelect = (date: Date) => {
    setSelectedDate(date);

    // Aggiorna anche l'inizio settimana per le ore settimanali usando moment
    const startOfWeek = moment(date).startOf("isoWeek").toDate();
    setCurrentWeekStart(startOfWeek);
  };

  // Gestione navigazione calendario terapista (vecchio handler - manteniamo per compatibilità)
  const handleTherapistNavigate = (date: Date) => {
    // Aggiorna l'inizio settimana per le ore settimanali
    const weekStart = moment(date).startOf("isoWeek").toDate();
    setCurrentWeekStart(weekStart);

    // Chiama il callback originale se presente
    if (onDateChange) {
      onDateChange(date);
    }
  };

  // Gestione navigazione unificata con protezione anti-loop tramite contatore
  const handleUnifiedNavigate = (date: Date) => {
    // Se counter > 0, siamo in una sincronizzazione attiva
    if (syncNavigationCounter.current > 0) {
      // console.log(
      //   `🔒 Sincronizzazione in corso (counter: ${syncNavigationCounter.current}), decremento e ignoro`
      // );
      syncNavigationCounter.current--;
      return;
    }

    // console.log("🚀 Navigazione unificata verso:", date.toLocaleDateString());

    // 1. Aggiorna stati locali
    setSelectedDate(date);
    const weekStart = moment(date).startOf("isoWeek").toDate();
    setCurrentWeekStart(weekStart);

    // 2. Setta il contatore a 2 (aspettiamo 2 callback dai calendari)
    syncNavigationCounter.current = 2;

    // 3. Sincronizza entrambi i calendari
    // Ogni calendario triggererà datesSet → onNavigate → decrementerà il counter
    setTimeout(() => {
      therapistCalendarRef.current?.navigateToDate(date);
      patientCalendarRef.current?.navigateToDate(date);
    }, 0);

    // 4. Notifica il componente genitore
    if (onDateChange) {
      onDateChange(date);
    }
  };

  // Gestione cambio range visibile - callback generico
  const handleVisibleRangeChange = (start: Date, end: Date) => {
    if (onVisibleRangeChange) {
      onVisibleRangeChange(start, end);
    }
  };

  // Gestione cambio range visibile specifico per il calendario del terapista
  const handleTherapistVisibleRangeChange = (start: Date, end: Date) => {
    // FullCalendar fornisce il range domenica-sabato
    // Per le ore settimanali, vogliamo il lunedì della stessa settimana
    let weekStart: Date;

    if (moment(start).day() === 0) {
      // Se start è domenica, il lunedì è il giorno successivo
      weekStart = moment(start).add(1, "day").toDate();
    } else {
      // Altrimenti usa startOf('isoWeek') per ottenere il lunedì
      weekStart = moment(start).startOf("isoWeek").toDate();
    }

    setCurrentWeekStart(weekStart);

    // console.log("🧑‍⚕️ Range terapista cambiato:", {
    //   originalStart: start.toLocaleDateString("it-IT"),
    //   originalEnd: end.toLocaleDateString("it-IT"),
    //   startDay: moment(start).format("dddd"),
    //   weekStart: weekStart.toLocaleDateString("it-IT"),
    //   weekStartFormatted: moment(weekStart).format("YYYY-MM-DD"),
    // });

    // Chiama anche il callback generico
    handleVisibleRangeChange(start, end);
  };

  // Gestione cambio range visibile per il calendario del paziente (non aggiorna le ore)
  const handlePatientVisibleRangeChange = (start: Date, end: Date) => {
    // console.log("👤 Range paziente cambiato:", {
    //   start: start.toLocaleDateString(),
    //   end: end.toLocaleDateString(),
    // });

    // Chiama solo il callback generico, non aggiorna le ore settimanali
    handleVisibleRangeChange(start, end);
  };

  // Gestione cambio vista - sincronizza entrambi i calendari
  const handleViewChange = (
    view: "dayGridMonth" | "timeGridWeek" | "timeGridDay" | "listWeek"
  ) => {
    // Converte la vista di FullCalendar al formato del component
    let newViewType: CalendarViewType;
    switch (view) {
      case "timeGridDay":
        newViewType = "day";
        break;
      case "timeGridWeek":
        newViewType = "week";
        break;
      case "dayGridMonth":
        newViewType = "month";
        break;
      case "listWeek":
        newViewType = "week";
        break;
      default:
        newViewType = "month";
        break;
    }

    // Aggiorna la vista per entrambi i calendari
    onViewTypeChange(newViewType);
  };

  // Gestione movimento appuntamenti con sincronizzazione
  const handleAppointmentMoveWithSync = async (
    appointmentId: string,
    newDate: Date,
    newTime: string,
    eventData?: any
  ): Promise<boolean> => {
    if (!onAppointmentMove) return false;

    // Chiama la funzione originale per aggiornare il backend
    const result = await onAppointmentMove(appointmentId, newDate, newTime, eventData);

    // Se fallisce, ritorna false (il chiamante farà revert)
    if (result === false) {
      return false;
    }

    // Calcola il nuovo datetime per sincronizzazione locale
    const newDateTime = new Date(newDate);
    const [hours, minutes] = newTime.split(":");
    newDateTime.setHours(parseInt(hours), parseInt(minutes));

    // Usa i dati dell'evento per ottenere le informazioni necessarie
    const duration = eventData?.duration || 60;
    const patientId = eventData?.patient_id;

    const newStart = newDateTime.toISOString();
    const newEnd = new Date(
      newDateTime.getTime() + duration * 60000
    ).toISOString();

    // Sincronizza con il calendario del paziente se l'evento appartiene al paziente visualizzato
    if (patientId === currentPatientId && patientCalendarRef.current) {
      patientCalendarRef.current.updateEvent(appointmentId, newStart, newEnd);
    }

    return true;
  };

  // Filtra eventi per il calendario del terapista
  const therapistEvents = sharedEvents.filter(
    (event) =>
      selectedTherapist &&
      event.extendedProps?.therapist_id === selectedTherapist.id
  );

  // Filtra eventi per il calendario del paziente
  const patientEvents = sharedEvents.filter(
    (event) =>
      currentPatientId && event.extendedProps?.patient_id === currentPatientId
  );

  // Se hideTherapistCalendar è true e c'è un paziente selezionato,
  // mostra solo il calendario paziente a larghezza piena (read-only).
  if (hideTherapistCalendar && currentPatientId) {
    return (
      <div className="space-y-4">
        <div className="flex justify-end">
          <CalendarViewSelector
            viewType={viewType}
            onViewTypeChange={onViewTypeChange}
          />
        </div>

        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4 flex items-center gap-3">
            <div className="w-4 h-4 rounded-full bg-green-500" />
            Calendario Paziente
            {currentPatientName && (
              <span className="text-lg font-medium text-gray-600">
                - {currentPatientName}
              </span>
            )}
          </h2>
          <FullCalendarContainer
            patientId={currentPatientId.toString()}
            selectedDate={selectedDate}
            onDateSelect={handleDateSelect}
            readOnly={true}
            currentView={currentFullCalendarView}
            onViewChange={handleViewChange}
            onNavigate={handleUnifiedNavigate}
            onVisibleRangeChange={handlePatientVisibleRangeChange}
            externalEvents={patientEvents}
            onRef={(ref) => {
              patientCalendarRef.current = ref;
            }}
          />
        </Card>
      </div>
    );
  }

  // Se hidePatientCalendar è true, mostra solo il calendario del terapista a larghezza piena
  if (hidePatientCalendar && selectedTherapist) {
    return (
      <div className="space-y-4">
        <div className="flex justify-end">
          <CalendarViewSelector
            viewType={viewType}
            onViewTypeChange={onViewTypeChange}
          />
        </div>

        {/* Ore settimanali - visibile solo in vista settimana */}
        {viewType === "week" && selectedTherapist && !isPrivateMode && (
          <div className="space-y-4">
            {/* Ore settimanali terapista */}
            {selectedTherapist && (
              <TherapistWeeklyHours
                therapist={selectedTherapist}
                currentDate={currentWeekStart}
                refreshTrigger={weeklyHoursRefreshTrigger}
              />
            )}

            {/* Ore piano terapeutico */}
            {selectedSpecialization && (
              <PlanTherapyUsedHours
                specialization={selectedSpecialization}
                currentDate={currentWeekStart}
                isABARegime={isABARegime}
                refreshTrigger={weeklyHoursRefreshTrigger}
              />
            )}
          </div>
        )}

        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4 flex items-center gap-3">
            <div
              className="w-4 h-4 rounded-full"
              style={{
                backgroundColor: selectedTherapist.color || "#3b82f6",
              }}
            />
            Calendario di {selectedTherapist.name}
          </h2>
          <FullCalendarContainer
            therapistId={selectedTherapist.id.toString()}
            selectedDate={selectedDate}
            onDateSelect={handleDateSelect}
            onSlotClick={onSlotClick}
            onAppointmentClick={onAppointmentClick}
            onAppointmentMove={handleAppointmentMoveWithSync}
            readOnly={readOnly}
            currentView={currentFullCalendarView}
            onViewChange={handleViewChange}
            onNavigate={handleTherapistNavigate}
            onVisibleRangeChange={handleTherapistVisibleRangeChange}
            therapistAbsences={therapistAbsences}
            externalEvents={therapistEvents}
            onRef={(ref) => {
              therapistCalendarRef.current = ref;
            }}
          />
        </Card>
      </div>
    );
  }

  // Vista normale con due calendari affiancati
  return (
    <div className="space-y-4">
      {/* Ore settimanali - visibile solo in vista settimana */}
      {viewType === "week" && selectedTherapist && !isPrivateMode && (
        <div className="space-y-4">
          {/* Ore settimanali terapista */}
          {selectedTherapist && (
            <TherapistWeeklyHours
              therapist={selectedTherapist}
              currentDate={currentWeekStart}
              refreshTrigger={weeklyHoursRefreshTrigger}
            />
          )}

          {/* Ore piano terapeutico */}
          {selectedSpecialization && (
            <PlanTherapyUsedHours
              specialization={selectedSpecialization}
              currentDate={currentWeekStart}
              isABARegime={isABARegime}
              refreshTrigger={weeklyHoursRefreshTrigger}
            />
          )}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Calendario Terapista */}
        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4 flex items-center gap-3">
            {selectedTherapist ? (
              <>
                <div
                  className="w-4 h-4 rounded-full"
                  style={{
                    backgroundColor: selectedTherapist.color || "#3b82f6",
                  }}
                />
                Calendario di{" "}
                {selectedTherapist && (
                  <span className="text-lg font-medium text-gray-600">
                    - {selectedTherapist.name || "Nome non disponibile"}
                  </span>
                )}
              </>
            ) : (
              "Seleziona un terapista"
            )}
          </h2>
          {selectedTherapist ? (
            <FullCalendarContainer
              therapistId={selectedTherapist.id.toString()}
              selectedDate={selectedDate}
              onDateSelect={handleDateSelect}
              onSlotClick={onSlotClick}
              onAppointmentClick={onAppointmentClick}
              onAppointmentMove={handleAppointmentMoveWithSync}
              readOnly={readOnly}
              currentView={currentFullCalendarView}
              onViewChange={handleViewChange}
              onNavigate={handleUnifiedNavigate}
              onVisibleRangeChange={handleTherapistVisibleRangeChange}
              externalEvents={therapistEvents}
              onRef={(ref) => {
                therapistCalendarRef.current = ref;
              }}
            />
          ) : (
            <div className="flex items-center justify-center h-64 text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
              <div className="text-center">
                <p className="text-lg font-medium">Seleziona un terapista</p>
                <p className="text-sm mt-2">
                  per visualizzare il suo calendario
                </p>
              </div>
            </div>
          )}
        </Card>

        {/* Calendario Paziente */}
        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4 flex items-center gap-3">
            <div className="w-4 h-4 rounded-full bg-green-500" />
            Calendario Paziente
            {currentPatientId && (
              <span className="text-lg font-medium text-gray-600">
                - {currentPatientName || "Nome non disponibile"}
              </span>
            )}
          </h2>
          <FullCalendarContainer
            patientId={currentPatientId?.toString()}
            selectedDate={selectedDate}
            onDateSelect={handleDateSelect}
            readOnly={true}
            currentView={currentFullCalendarView}
            onViewChange={handleViewChange}
            onNavigate={handleUnifiedNavigate}
            onVisibleRangeChange={handlePatientVisibleRangeChange}
            externalEvents={patientEvents}
            onRef={(ref) => {
              patientCalendarRef.current = ref;
            }}
          />
        </Card>
      </div>
    </div>
  );
};
