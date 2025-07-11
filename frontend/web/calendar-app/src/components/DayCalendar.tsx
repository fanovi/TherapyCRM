import React, { useState } from "react";
import { format, addDays, parseISO } from "date-fns";
import { it } from "date-fns/locale";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Clock } from "lucide-react";
import { Appointment } from "@/types/therapy";
import {
  getAppointmentColor,
  getAppointmentTooltip,
} from "@/lib/appointment-colors";

const timeSlots = [
  "08:00",
  "08:15",
  "08:30",
  "08:45",
  "09:00",
  "09:15",
  "09:30",
  "09:45",
  "10:00",
  "10:15",
  "10:30",
  "10:45",
  "11:00",
  "11:15",
  "11:30",
  "11:45",
  "12:00",
  "12:15",
  "12:30",
  "12:45",
  "13:00",
  "13:15",
  "13:30",
  "13:45",
  "14:00",
  "14:15",
  "14:30",
  "14:45",
  "15:00",
  "15:15",
  "15:30",
  "15:45",
  "16:00",
  "16:15",
  "16:30",
  "16:45",
  "17:00",
  "17:15",
  "17:30",
  "17:45",
  "18:00",
];

interface DayCalendarProps {
  appointments: Appointment[];
  onSlotClick: (date: Date, time: string) => void;
  onAppointmentMove: (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => void;
  isEditable: boolean;
  primaryColor: string;
  mode: "patient" | "therapist";
  currentPatientId?: number;
}

// Helper functions per gestire la nuova struttura dati
const getAppointmentDate = (appointment: Appointment): Date => {
  return parseISO(appointment.datetime);
};

const getAppointmentTime = (appointment: Appointment): string => {
  const date = parseISO(appointment.datetime);
  return format(date, "HH:mm");
};

const getAppointmentPatientName = (appointment: Appointment): string => {
  return appointment.patient?.name || "Paziente sconosciuto";
};

const getAppointmentTherapistName = (appointment: Appointment): string => {
  return appointment.therapist?.name || "Terapista sconosciuto";
};

const getAppointmentTreatmentType = (appointment: Appointment): string => {
  return appointment.treatmentType || "Trattamento non specificato";
};

export const DayCalendar: React.FC<DayCalendarProps> = ({
  appointments,
  onSlotClick,
  onAppointmentMove,
  isEditable,
  primaryColor,
  mode,
  currentPatientId,
}) => {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [draggedAppointment, setDraggedAppointment] = useState<string | null>(
    null
  );
  const [dragPreview, setDragPreview] = useState<{
    time: string;
    appointment: Appointment;
  } | null>(null);

  // Filtra gli appuntamenti per il giorno corrente
  const todayAppointments = appointments.filter((apt) => {
    const aptDate = getAppointmentDate(apt);
    return format(aptDate, "yyyy-MM-dd") === format(currentDate, "yyyy-MM-dd");
  });

  // Calcola gli slot occupati da un appuntamento
  const getOccupiedSlots = (appointment: Appointment) => {
    const startTime = getAppointmentTime(appointment);
    const duration = appointment.duration;
    const startIndex = timeSlots.indexOf(startTime);

    if (startIndex === -1) return [];

    // Calcola quanti slot da 15 minuti occupa l'appuntamento
    // Include sia lo slot di inizio che quello di fine
    const slotsNeeded = Math.floor(duration / 15) + 1;
    const occupiedSlots = [];

    for (let i = 0; i < slotsNeeded; i++) {
      if (startIndex + i < timeSlots.length) {
        occupiedSlots.push(timeSlots[startIndex + i]);
      }
    }

    return occupiedSlots;
  };

  const getAppointmentForSlot = (time: string) => {
    return todayAppointments.find((apt) => {
      const occupiedSlots = getOccupiedSlots(apt);
      return occupiedSlots.includes(time);
    });
  };

  // Determina se questo slot è il primo slot dell'appuntamento (per il rendering)
  const isFirstSlotOfAppointment = (time: string, appointment: Appointment) => {
    const aptTime = getAppointmentTime(appointment);
    return aptTime === time;
  };

  const handlePreviousDay = () => {
    setCurrentDate((prev) => addDays(prev, -1));
  };

  const handleNextDay = () => {
    setCurrentDate((prev) => addDays(prev, 1));
  };

  const handleDragStart = (e: React.DragEvent, appointmentId: number) => {
    if (!isEditable) return;
    const appointment = todayAppointments.find(
      (apt) => apt.id === appointmentId
    );
    if (!appointment) return;

    setDraggedAppointment(appointmentId.toString());
    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData("text/plain", appointmentId.toString());
  };

  const handleDragOver = (e: React.DragEvent, time: string) => {
    if (!isEditable || !draggedAppointment) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";

    // Aggiorna la preview solo se è una posizione diversa
    const appointment = todayAppointments.find(
      (apt) => apt.id.toString() === draggedAppointment
    );
    if (appointment) {
      const existingAppointment = getAppointmentForSlot(time);
      const canDrop =
        !existingAppointment ||
        existingAppointment.id.toString() === draggedAppointment;

      if (canDrop) {
        setDragPreview({ time, appointment });
      } else {
        setDragPreview(null);
      }
    }
  };

  const handleDrop = (e: React.DragEvent, time: string) => {
    if (!isEditable || !draggedAppointment) return;
    e.preventDefault();

    const existingAppointment = getAppointmentForSlot(time);
    if (
      !existingAppointment ||
      existingAppointment.id.toString() === draggedAppointment
    ) {
      onAppointmentMove(draggedAppointment, currentDate, time);
    }

    setDraggedAppointment(null);
    setDragPreview(null);
  };

  const handleDragEnd = () => {
    setDraggedAppointment(null);
    setDragPreview(null);
  };

  return (
    <div className="space-y-4">
      {/* Day Navigation */}
      <div className="flex items-center justify-between">
        <Button
          variant="outline"
          size="sm"
          onClick={handlePreviousDay}
          className="flex items-center gap-1"
        >
          <ChevronLeft className="h-4 w-4" />
          Precedente
        </Button>

        <h3 className="font-semibold text-gray-800">
          {format(currentDate, "EEEE, dd MMMM yyyy", { locale: it })}
        </h3>

        <Button
          variant="outline"
          size="sm"
          onClick={handleNextDay}
          className="flex items-center gap-1"
        >
          Successivo
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>

      {/* Calendar Grid */}
      <div className="border rounded-lg overflow-hidden bg-white">
        {/* Header Row */}
        <div className="grid grid-cols-2 bg-gray-50 border-b">
          <div className="p-3 text-center font-medium text-gray-600 border-r">
            <Clock className="h-4 w-4 mx-auto mb-1" />
            Orario
          </div>
          <div className="p-3 text-center font-medium text-gray-800">
            Appuntamenti
          </div>
        </div>

        {/* Time Slots */}
        <div className="max-h-96 overflow-y-auto">
          {timeSlots.map((time) => {
            const appointment = getAppointmentForSlot(time);
            const isFirstSlot =
              appointment && isFirstSlotOfAppointment(time, appointment);

            return (
              <div
                key={time}
                className="grid grid-cols-2 border-b last:border-b-0"
              >
                <div className="p-3 text-center text-sm text-gray-600 bg-gray-50 border-r font-medium">
                  {time}
                </div>
                <div
                  className={`h-12 transition-colors relative border-r
                    ${
                      appointment && !isFirstSlot
                        ? "bg-gray-100"
                        : isEditable && !appointment
                        ? "hover:bg-blue-50 cursor-pointer"
                        : "hover:bg-gray-50"
                    }
                  `}
                  onClick={() =>
                    isEditable && !appointment && onSlotClick(currentDate, time)
                  }
                  onDragOver={(e) => handleDragOver(e, time)}
                  onDrop={(e) => handleDrop(e, time)}
                >
                  {appointment && isFirstSlot && (
                    <div
                      className={`absolute inset-0 m-1 rounded text-sm text-white font-medium cursor-move transition-all z-10 p-2
                        ${
                          draggedAppointment === appointment.id.toString()
                            ? "opacity-30"
                            : "opacity-100"
                        }
                      `}
                      style={{
                        backgroundColor: getAppointmentColor(
                          appointment,
                          mode,
                          currentPatientId
                        ),
                        height: `${
                          (Math.floor(appointment.duration / 15) + 1) * 48 - 8
                        }px`, // Estende verso il basso
                      }}
                      draggable={isEditable}
                      onDragStart={(e) => handleDragStart(e, appointment.id)}
                      onDragEnd={handleDragEnd}
                      title={getAppointmentTooltip(appointment, mode)}
                    >
                      <div className="font-semibold text-sm leading-tight">
                        {getAppointmentTreatmentType(appointment)}
                      </div>
                      <div className="text-sm opacity-90 leading-tight mt-1">
                        {getAppointmentPatientName(appointment)}
                      </div>
                      {!isEditable && (
                        <div className="text-sm opacity-75 leading-tight mt-1">
                          {getAppointmentTherapistName(appointment)}
                        </div>
                      )}
                      <div className="text-sm opacity-75 leading-tight mt-1">
                        {appointment.duration} min
                      </div>
                    </div>
                  )}

                  {/* Preview del drag quando l'appuntamento viene trascinato */}
                  {dragPreview && dragPreview.time === time && !appointment && (
                    <div
                      className="absolute inset-0 m-1 rounded text-sm text-white font-medium z-20 p-2 border-2 border-dashed"
                      style={{
                        backgroundColor: getAppointmentColor(
                          dragPreview.appointment,
                          mode,
                          currentPatientId
                        ),
                        opacity: 0.7,
                        height: `${
                          (Math.floor(dragPreview.appointment.duration / 15) +
                            1) *
                            48 -
                          8
                        }px`,
                        borderColor: "rgba(255, 255, 255, 0.8)",
                      }}
                    >
                      <div className="font-semibold text-sm leading-tight">
                        {getAppointmentTreatmentType(dragPreview.appointment)}
                      </div>
                      <div className="text-sm opacity-90 leading-tight mt-1">
                        {getAppointmentPatientName(dragPreview.appointment)}
                      </div>
                      <div className="text-sm opacity-75 leading-tight mt-1">
                        {dragPreview.appointment.duration} min
                      </div>
                      <div className="text-xs opacity-75 leading-tight mt-1 italic">
                        Nuova posizione
                      </div>
                    </div>
                  )}

                  {appointment && !isFirstSlot && (
                    <div className="h-full flex items-center justify-center">
                      <div
                        className="w-2 h-8 rounded"
                        style={{
                          backgroundColor: getAppointmentColor(
                            appointment,
                            mode,
                            currentPatientId
                          ),
                        }}
                      />
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};
