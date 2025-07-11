import React, { useState } from "react";
import {
  format,
  startOfMonth,
  endOfMonth,
  startOfWeek,
  endOfWeek,
  addDays,
  isSameMonth,
  isSameDay,
  addMonths,
  parseISO,
} from "date-fns";
import { it } from "date-fns/locale";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { Appointment } from "@/types/therapy";

interface MonthCalendarProps {
  appointments: Appointment[];
  onSlotClick: (date: Date, time: string) => void;
  onAppointmentMove: (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => void;
  isEditable: boolean;
  primaryColor: string;
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

export const MonthCalendar: React.FC<MonthCalendarProps> = ({
  appointments,
  onSlotClick,
  isEditable,
  primaryColor,
}) => {
  const [currentMonth, setCurrentMonth] = useState(new Date());

  const monthStart = startOfMonth(currentMonth);
  const monthEnd = endOfMonth(monthStart);
  const startDate = startOfWeek(monthStart, { weekStartsOn: 1 });
  const endDate = endOfWeek(monthEnd, { weekStartsOn: 1 });

  const getAppointmentsForDay = (date: Date) => {
    return appointments.filter((apt) => {
      const aptDate = getAppointmentDate(apt);
      return isSameDay(aptDate, date);
    });
  };

  const handlePreviousMonth = () => {
    setCurrentMonth((prev) => addMonths(prev, -1));
  };

  const handleNextMonth = () => {
    setCurrentMonth((prev) => addMonths(prev, 1));
  };

  const renderCalendarDays = () => {
    const days = [];
    let day = startDate;

    while (day <= endDate) {
      const dayAppointments = getAppointmentsForDay(day);
      const isCurrentMonth = isSameMonth(day, monthStart);
      const dayForSlot = new Date(day);

      days.push(
        <div
          key={day.toString()}
          className={`min-h-[120px] border border-gray-200 p-2 cursor-pointer transition-colors
            ${
              isCurrentMonth
                ? "bg-white hover:bg-blue-50"
                : "bg-gray-50 text-gray-400"
            }
            ${isSameDay(day, new Date()) ? "bg-blue-100" : ""}
          `}
          onClick={() =>
            isEditable && isCurrentMonth && onSlotClick(dayForSlot, "09:00")
          }
        >
          <div className="font-medium text-sm mb-1">{format(day, "d")}</div>
          <div className="space-y-1">
            {dayAppointments.slice(0, 3).map((appointment) => (
              <div
                key={appointment.id}
                className="text-xs p-1 rounded text-white truncate"
                style={{ backgroundColor: primaryColor }}
                title={`${getAppointmentTime(
                  appointment
                )} - ${getAppointmentTreatmentType(
                  appointment
                )} - ${getAppointmentPatientName(appointment)}`}
              >
                {getAppointmentTime(appointment)}{" "}
                {getAppointmentTreatmentType(appointment)}
              </div>
            ))}
            {dayAppointments.length > 3 && (
              <div className="text-xs text-gray-500">
                +{dayAppointments.length - 3} altri
              </div>
            )}
          </div>
        </div>
      );
      day = addDays(day, 1);
    }

    return days;
  };

  return (
    <div className="space-y-4">
      {/* Month Navigation */}
      <div className="flex items-center justify-between">
        <Button
          variant="outline"
          size="sm"
          onClick={handlePreviousMonth}
          className="flex items-center gap-1"
        >
          <ChevronLeft className="h-4 w-4" />
          Precedente
        </Button>

        <h3 className="font-semibold text-gray-800 text-xl">
          {format(currentMonth, "MMMM yyyy", { locale: it })}
        </h3>

        <Button
          variant="outline"
          size="sm"
          onClick={handleNextMonth}
          className="flex items-center gap-1"
        >
          Successivo
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>

      {/* Calendar Grid */}
      <div className="border rounded-lg overflow-hidden bg-white">
        {/* Header Row - Days of Week */}
        <div className="grid grid-cols-7 bg-gray-50">
          {["Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"].map((day) => (
            <div
              key={day}
              className="p-3 text-center font-medium text-gray-600 border-r last:border-r-0"
            >
              {day}
            </div>
          ))}
        </div>

        {/* Calendar Days */}
        <div className="grid grid-cols-7">{renderCalendarDays()}</div>
      </div>
    </div>
  );
};
