import React from "react";
import { Button } from "@/components/ui/button";
import { Calendar, CalendarDays } from "lucide-react";

export type CalendarViewType = "day" | "week" | "month";

interface CalendarViewSelectorProps {
  currentView: CalendarViewType;
  onViewChange: (view: CalendarViewType) => void;
}

export const CalendarViewSelector: React.FC<CalendarViewSelectorProps> = ({
  currentView,
  onViewChange,
}) => {
  return (
    <div className="flex gap-2">
      <Button
        variant={currentView === "day" ? "default" : "outline"}
        size="sm"
        onClick={() => onViewChange("day")}
        className="flex items-center gap-2"
      >
        <Calendar className="h-4 w-4" />
        Giorno
      </Button>
      <Button
        variant={currentView === "week" ? "default" : "outline"}
        size="sm"
        onClick={() => onViewChange("week")}
        className="flex items-center gap-2"
      >
        <CalendarDays className="h-4 w-4" />
        Settimana
      </Button>
      <Button
        variant={currentView === "month" ? "default" : "outline"}
        size="sm"
        onClick={() => onViewChange("month")}
        className="flex items-center gap-2"
      >
        <CalendarDays className="h-4 w-4" />
        Mese
      </Button>
    </div>
  );
};
