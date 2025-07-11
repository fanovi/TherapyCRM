import React, { useState } from "react";
import { format, startOfWeek, addDays, isSameDay } from "date-fns";
import { it } from "date-fns/locale";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, Clock } from "lucide-react";
import { Appointment } from "@/types/therapy";

const timeSlots = [
  "08:00",
  "08:30",
  "09:00",
  "09:30",
  "10:00",
  "10:30",
  "11:00",
  "11:30",
  "12:00",
  "12:30",
  "13:00",
  "13:30",
  "14:00",
  "14:30",
  "15:00",
  "15:30",
  "16:00",
  "16:30",
  "17:00",
  "17:30",
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
}

export const WeekCalendar: React.FC<WeekCalendarProps> = ({
  appointments,
  onSlotClick,
  onAppointmentMove,
  isEditable,
  primaryColor,
}) => {
  const [currentWeek, setCurrentWeek] = useState(new Date());
  const [draggedAppointment, setDraggedAppointment] = useState<string | null>(
    null
  );

  const weekStart = startOfWeek(currentWeek, { weekStartsOn: 1 });
  const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));

  const getAppointmentForSlot = (date: Date, time: string) => {
    return appointments.find(
      (apt) => isSameDay(apt.date, date) && apt.time === time
    );
  };

  const handlePreviousWeek = () => {
    setCurrentWeek((prev) => addDays(prev, -7));
  };

  const handleNextWeek = () => {
    setCurrentWeek((prev) => addDays(prev, 7));
  };

  const handleDragStart = (e: React.DragEvent, appointmentId: string) => {
    if (!isEditable) return;
    setDraggedAppointment(appointmentId);
    e.dataTransfer.effectAllowed = "move";
  };

  const handleDragOver = (e: React.DragEvent) => {
    if (!isEditable) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";
  };

  const handleDrop = (e: React.DragEvent, date: Date, time: string) => {
    if (!isEditable || !draggedAppointment) return;
    e.preventDefault();

    const existingAppointment = getAppointmentForSlot(date, time);
    if (!existingAppointment) {
      onAppointmentMove(draggedAppointment, date, time);
    }

    setDraggedAppointment(null);
  };

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
          {format(weekStart, "dd MMM", { locale: it })} -{" "}
          {format(addDays(weekStart, 6), "dd MMM yyyy", { locale: it })}
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
              <div className="text-sm">
                {format(day, "EEE", { locale: it })}
              </div>
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
                return (
                  <div
                    key={`${day.toISOString()}-${time}`}
                    className={`p-1 border-r last:border-r-0 min-h-[3rem] cursor-pointer transition-colors
                      ${isEditable ? "hover:bg-blue-50" : "hover:bg-gray-50"}
                      ${appointment ? "bg-opacity-10" : ""}
                    `}
                    onClick={() => {
                      console.log("📅 WeekCalendar slot clicked:", {
                        day,
                        time,
                        isEditable,
                        hasAppointment: !!appointment,
                      });
                      if (isEditable && !appointment) {
                        onSlotClick(day, time);
                      }
                    }}
                    onDragOver={handleDragOver}
                    onDrop={(e) => handleDrop(e, day, time)}
                  >
                    {appointment && (
                      <div
                        className={`p-2 rounded text-xs text-white font-medium cursor-move transition-all
                          ${
                            draggedAppointment === appointment.id
                              ? "opacity-50"
                              : "opacity-100"
                          }
                        `}
                        style={{ backgroundColor: primaryColor }}
                        draggable={isEditable}
                        onDragStart={(e) => handleDragStart(e, appointment.id)}
                        title={`${appointment.therapyType} - ${appointment.patientName}`}
                      >
                        <div className="truncate">
                          {appointment.therapyType}
                        </div>
                        <div className="truncate text-xs opacity-90">
                          {appointment.patientName}
                        </div>
                        {!isEditable && (
                          <div className="truncate text-xs opacity-75">
                            {appointment.therapistName}
                          </div>
                        )}
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
