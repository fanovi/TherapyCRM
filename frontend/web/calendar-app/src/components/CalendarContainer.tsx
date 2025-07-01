import React from "react";
import { WeekCalendar } from "./WeekCalendar";
import { DayCalendar } from "./DayCalendar";
import { MonthCalendar } from "./MonthCalendar";
import { CalendarViewType } from "./CalendarViewSelector";
import { Appointment } from "@/types/therapy";

interface CalendarContainerProps {
  view: CalendarViewType;
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

export const CalendarContainer: React.FC<CalendarContainerProps> = ({
  view,
  appointments,
  onSlotClick,
  onAppointmentMove,
  isEditable,
  primaryColor,
}) => {
  switch (view) {
    case "day":
      return (
        <DayCalendar
          appointments={appointments}
          onSlotClick={onSlotClick}
          onAppointmentMove={onAppointmentMove}
          isEditable={isEditable}
          primaryColor={primaryColor}
        />
      );
    case "week":
      return (
        <WeekCalendar
          appointments={appointments}
          onSlotClick={onSlotClick}
          onAppointmentMove={onAppointmentMove}
          isEditable={isEditable}
          primaryColor={primaryColor}
        />
      );
    case "month":
      return (
        <MonthCalendar
          appointments={appointments}
          onSlotClick={onSlotClick}
          onAppointmentMove={onAppointmentMove}
          isEditable={isEditable}
          primaryColor={primaryColor}
        />
      );
    default:
      return null;
  }
};
