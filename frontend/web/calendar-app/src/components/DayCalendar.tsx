import React, { useState } from "react";
import { format, addDays } from "date-fns";
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
}

export const DayCalendar: React.FC<DayCalendarProps> = ({
  appointments,
  onSlotClick,
  onAppointmentMove,
  isEditable,
  primaryColor,
}) => {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [draggedAppointment, setDraggedAppointment] = useState<string | null>(
    null
  );

  const getAppointmentForSlot = (time: string) => {
    return appointments.find(
      (apt) =>
        format(apt.date, "yyyy-MM-dd") === format(currentDate, "yyyy-MM-dd") &&
        apt.time === time
    );
  };

  const handlePreviousDay = () => {
    setCurrentDate((prev) => addDays(prev, -1));
  };

  const handleNextDay = () => {
    setCurrentDate((prev) => addDays(prev, 1));
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

  const handleDrop = (e: React.DragEvent, time: string) => {
    if (!isEditable || !draggedAppointment) return;
    e.preventDefault();

    const existingAppointment = getAppointmentForSlot(time);
    if (!existingAppointment) {
      onAppointmentMove(draggedAppointment, currentDate, time);
    }

    setDraggedAppointment(null);
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
            return (
              <div
                key={time}
                className="grid grid-cols-2 border-b last:border-b-0"
              >
                <div className="p-3 text-center text-sm text-gray-600 bg-gray-50 border-r font-medium">
                  {time}
                </div>
                <div
                  className={`p-2 min-h-[4rem] cursor-pointer transition-colors
                    ${isEditable ? "hover:bg-blue-50" : "hover:bg-gray-50"}
                    ${appointment ? "bg-opacity-10" : ""}
                  `}
                  onClick={() =>
                    isEditable && !appointment && onSlotClick(currentDate, time)
                  }
                  onDragOver={handleDragOver}
                  onDrop={(e) => handleDrop(e, time)}
                >
                  {appointment && (
                    <div
                      className={`p-3 rounded text-sm text-white font-medium cursor-move transition-all h-full
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
                      <div className="font-semibold">
                        {appointment.therapyType}
                      </div>
                      <div className="text-sm opacity-90">
                        {appointment.patientName}
                      </div>
                      {!isEditable && (
                        <div className="text-sm opacity-75">
                          {appointment.therapistName}
                        </div>
                      )}
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
