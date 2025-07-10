import React from "react";
import { WeekCalendar } from "./WeekCalendar";
import { DayCalendar } from "./DayCalendar";
import { MonthCalendar } from "./MonthCalendar";
import { CalendarViewType } from "./CalendarViewSelector";
import { Appointment } from "@/types/therapy";

interface CalendarViewProps {
  viewType: CalendarViewType;
  appointments: Appointment[];
  onSlotClick: (date: Date, time: string) => void;
  onAppointmentMove: (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => void;
  isTherapistView: boolean;
}

export const CalendarView: React.FC<CalendarViewProps> = ({
  viewType,
  appointments,
  onSlotClick,
  onAppointmentMove,
  isTherapistView,
}) => {
  // Determina il colore primario in base al tipo di vista
  const primaryColor = isTherapistView ? "#3b82f6" : "#16a34a";

  switch (viewType) {
    case "day":
      return (
        <DayCalendar
          appointments={appointments}
          onSlotClick={onSlotClick}
          onAppointmentMove={onAppointmentMove}
          isEditable={isTherapistView}
          primaryColor={primaryColor}
        />
      );
    case "week":
      return (
        <WeekCalendar
          appointments={appointments}
          onSlotClick={onSlotClick}
          onAppointmentMove={onAppointmentMove}
          isEditable={isTherapistView}
          primaryColor={primaryColor}
        />
      );
    case "month":
      return (
        <MonthCalendar
          appointments={appointments}
          onSlotClick={onSlotClick}
          onAppointmentMove={onAppointmentMove}
          isEditable={isTherapistView}
          primaryColor={primaryColor}
        />
      );
    default:
      return null;
  }
};
