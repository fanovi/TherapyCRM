import React, { useState } from "react";
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

interface WeekCalendarProps {
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

// Helper functions implementate manualmente
const parseISO = (dateString: string): Date => {
  return new Date(dateString);
};

const format = (date: Date, formatStr: string): string => {
  const days = ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"];
  const months = [
    "Gen",
    "Feb",
    "Mar",
    "Apr",
    "Mag",
    "Giu",
    "Lug",
    "Ago",
    "Set",
    "Ott",
    "Nov",
    "Dic",
  ];

  if (formatStr === "HH:mm") {
    return date.toTimeString().slice(0, 5);
  } else if (formatStr === "dd") {
    return date.getDate().toString().padStart(2, "0");
  } else if (formatStr === "EEE") {
    return days[date.getDay()];
  } else if (formatStr === "dd MMM") {
    return `${date.getDate()} ${months[date.getMonth()]}`;
  } else if (formatStr === "dd MMM yyyy") {
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
  }
  return date.toString();
};

const startOfWeek = (date: Date): Date => {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(d.setDate(diff));
};

const addDays = (date: Date, days: number): Date => {
  const result = new Date(date);
  result.setDate(result.getDate() + days);
  return result;
};

const isSameDay = (date1: Date, date2: Date): boolean => {
  return (
    date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate()
  );
};

// Helper functions
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

export const WeekCalendar: React.FC<WeekCalendarProps> = ({
  appointments,
  onSlotClick,
  onAppointmentMove,
  isEditable,
  primaryColor,
  mode,
  currentPatientId,
}) => {
  const [currentWeek, setCurrentWeek] = useState(new Date());
  const [draggedAppointment, setDraggedAppointment] = useState<string | null>(
    null
  );
  const [dragPreview, setDragPreview] = useState<{
    date: Date;
    time: string;
    appointment: Appointment;
  } | null>(null);

  const weekStart = startOfWeek(currentWeek);
  const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));

  // Calcola gli slot occupati da un appuntamento
  const getOccupiedSlots = (appointment: Appointment) => {
    const startTime = getAppointmentTime(appointment);
    const duration = appointment.duration;
    const startIndex = timeSlots.indexOf(startTime);

    if (startIndex === -1) return [];

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

  const getAppointmentForSlot = (date: Date, time: string) => {
    return appointments.find((apt) => {
      const aptDate = getAppointmentDate(apt);
      if (!isSameDay(aptDate, date)) {
        return false;
      }

      const occupiedSlots = getOccupiedSlots(apt);
      return occupiedSlots.includes(time);
    });
  };

  // Determina se questo slot è il primo slot dell'appuntamento
  const isFirstSlotOfAppointment = (time: string, appointment: Appointment) => {
    const aptTime = getAppointmentTime(appointment);
    return aptTime === time;
  };

  // Calcola quanti slot mancano fino alla fine dell'appuntamento
  const getRemainingSlots = (time: string, appointment: Appointment) => {
    const occupiedSlots = getOccupiedSlots(appointment);
    const currentIndex = occupiedSlots.indexOf(time);
    return occupiedSlots.length - currentIndex;
  };

  const handlePreviousWeek = () => {
    setCurrentWeek((prev) => addDays(prev, -7));
  };

  const handleNextWeek = () => {
    setCurrentWeek((prev) => addDays(prev, 7));
  };

  const handleDragStart = (e: React.DragEvent, appointmentId: number) => {
    if (!isEditable) return;
    const appointment = appointments.find((apt) => apt.id === appointmentId);
    if (!appointment) return;

    setDraggedAppointment(appointmentId.toString());
    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData("text/plain", appointmentId.toString());
  };

  const handleDragOver = (e: React.DragEvent, date: Date, time: string) => {
    if (!isEditable || !draggedAppointment) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";

    // Aggiorna la preview
    const appointment = appointments.find(
      (apt) => apt.id.toString() === draggedAppointment
    );
    if (appointment) {
      const existingAppointment = getAppointmentForSlot(date, time);
      const canDrop =
        !existingAppointment ||
        existingAppointment.id.toString() === draggedAppointment;

      if (canDrop) {
        setDragPreview({ date, time, appointment });
      } else {
        setDragPreview(null);
      }
    }
  };

  const handleDrop = (e: React.DragEvent, date: Date, time: string) => {
    if (!isEditable || !draggedAppointment) return;
    e.preventDefault();

    const existingAppointment = getAppointmentForSlot(date, time);
    if (!existingAppointment) {
      onAppointmentMove(draggedAppointment, date, time);
    }

    setDraggedAppointment(null);
    setDragPreview(null);
  };

  const handleDragEnd = () => {
    setDraggedAppointment(null);
    setDragPreview(null);
  };

  // Crea una struttura dati per tenere traccia delle celle già renderizzate
  const renderedCells = new Set<string>();

  return (
    <div className="space-y-4">
      {/* Week Navigation */}
      <div className="flex items-center justify-between">
        <Button
          variant="outline"
          size="sm"
          onClick={handlePreviousWeek}
          className="flex items-center gap-1"
        >
          <ChevronLeft className="h-4 w-4" />
          Precedente
        </Button>

        <h3 className="font-semibold text-gray-800">
          {format(weekStart, "dd MMM")} -{" "}
          {format(addDays(weekStart, 6), "dd MMM yyyy")}
        </h3>

        <Button
          variant="outline"
          size="sm"
          onClick={handleNextWeek}
          className="flex items-center gap-1"
        >
          Successiva
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>

      {/* Calendar Grid */}
      <div className="border rounded-lg overflow-hidden bg-white">
        {/* Header Row */}
        <div className="grid grid-cols-8 bg-gray-50 border-b">
          <div className="p-2 text-center font-medium text-gray-600 border-r">
            <Clock className="h-4 w-4 mx-auto" />
          </div>
          {weekDays.map((day) => (
            <div
              key={day.toISOString()}
              className="p-2 text-center font-medium text-gray-800 border-r last:border-r-0"
            >
              <div className="text-sm">{format(day, "EEE")}</div>
              <div className="text-lg">{format(day, "dd")}</div>
            </div>
          ))}
        </div>

        {/* Time Slots */}
        <div className="max-h-96 overflow-y-auto">
          {timeSlots.map((time) => (
            <div
              key={time}
              className="grid grid-cols-8 border-b last:border-b-0"
            >
              <div className="p-2 text-center text-sm text-gray-600 bg-gray-50 border-r font-medium">
                {time}
              </div>
              {weekDays.map((day) => {
                const appointment = getAppointmentForSlot(day, time);
                const hasAppointment = !!appointment;
                const isFirstSlot =
                  appointment && isFirstSlotOfAppointment(time, appointment);

                return (
                  <div
                    key={`${day.toISOString()}-${time}`}
                    className={`border-r last:border-r-0 h-10 transition-colors relative
                      ${
                        hasAppointment && !isFirstSlot
                          ? "bg-gray-100"
                          : isEditable && !hasAppointment
                          ? "hover:bg-blue-50 cursor-pointer"
                          : "hover:bg-gray-50"
                      }
                    `}
                    onClick={() => {
                      if (isEditable && !hasAppointment) {
                        onSlotClick(day, time);
                      }
                    }}
                    onDragOver={(e) => handleDragOver(e, day, time)}
                    onDrop={(e) => handleDrop(e, day, time)}
                  >
                    {appointment && isFirstSlot && (
                      <div
                        className={`absolute inset-0 m-0.5 rounded text-xs text-white font-medium cursor-move transition-all z-10 p-1
                          ${
                            draggedAppointment === appointment.id.toString()
                              ? "opacity-50"
                              : "opacity-100"
                          }
                        `}
                        style={{
                          backgroundColor: primaryColor,
                          height: `${
                            (Math.floor(appointment.duration / 15) + 1) * 40 - 4
                          }px`, // Estende verso il basso
                        }}
                        draggable={isEditable}
                        onDragStart={(e) => handleDragStart(e, appointment.id)}
                        onDragEnd={handleDragEnd}
                        title={`${getAppointmentTreatmentType(
                          appointment
                        )} - ${getAppointmentPatientName(appointment)}`}
                      >
                        <div className="truncate font-semibold text-xs leading-tight">
                          {getAppointmentTreatmentType(appointment)}
                        </div>
                        <div className="truncate text-xs opacity-90 leading-tight">
                          {getAppointmentPatientName(appointment)}
                        </div>
                        <div className="truncate text-xs opacity-75 leading-tight">
                          {appointment.duration} min
                        </div>
                      </div>
                    )}
                    {hasAppointment && !isFirstSlot && (
                      <div className="h-full flex items-center justify-center">
                        <div
                          className="w-1 h-6 rounded"
                          style={{ backgroundColor: primaryColor }}
                        />
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// Componente di esempio con dati demo
export default function WeekCalendarDemo() {
  const [appointments, setAppointments] = useState<Appointment[]>([
    {
      id: 1,
      datetime: "2025-01-13T09:00:00",
      duration: 60,
      patient: { name: "Mario Rossi" },
      therapist: { name: "Dr. Bianchi" },
      treatmentType: "Fisioterapia",
    },
    {
      id: 2,
      datetime: "2025-01-13T11:00:00",
      duration: 45,
      patient: { name: "Laura Verdi" },
      therapist: { name: "Dr. Bianchi" },
      treatmentType: "Massaggio",
    },
    {
      id: 3,
      datetime: "2025-01-14T14:00:00",
      duration: 90,
      patient: { name: "Giuseppe Neri" },
      therapist: { name: "Dr. Rossi" },
      treatmentType: "Riabilitazione",
    },
    {
      id: 4,
      datetime: "2025-01-15T10:30:00",
      duration: 30,
      patient: { name: "Anna Blu" },
      therapist: { name: "Dr. Verdi" },
      treatmentType: "Consulto",
    },
  ]);

  const handleSlotClick = (date: Date, time: string) => {
    console.log("Slot cliccato:", date, time);
  };

  const handleAppointmentMove = (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => {
    console.log("Appuntamento spostato:", appointmentId, newDate, newTime);
  };

  return (
    <div className="p-6 bg-gray-100 min-h-screen">
      <h1 className="text-2xl font-bold mb-6">
        Calendario Settimanale Terapisti
      </h1>
      <WeekCalendar
        appointments={appointments}
        onSlotClick={handleSlotClick}
        onAppointmentMove={handleAppointmentMove}
        isEditable={true}
        primaryColor="#3B82F6"
      />
    </div>
  );
}
