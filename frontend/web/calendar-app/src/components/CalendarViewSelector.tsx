import React from "react";
import { Button } from "@/components/ui/button";
import { Calendar, CalendarDays } from "lucide-react";

export type CalendarViewType = "day" | "week" | "month";

interface CalendarViewSelectorProps {
  viewType: CalendarViewType;
  onViewTypeChange: (view: CalendarViewType) => void;
}

export const CalendarViewSelector: React.FC<CalendarViewSelectorProps> = ({
  viewType,
  onViewTypeChange,
}) => {
  return (
    <div className="flex gap-2">
      <Button
        variant={viewType === "day" ? "default" : "outline"}
        size="sm"
        onClick={() => onViewTypeChange("day")}
        className="flex items-center gap-2"
      >
        <Calendar className="h-4 w-4" />
        Giorno
      </Button>
      <Button
        variant={viewType === "week" ? "default" : "outline"}
        size="sm"
        onClick={() => onViewTypeChange("week")}
        className="flex items-center gap-2"
      >
        <CalendarDays className="h-4 w-4" />
        Settimana
      </Button>
      <Button
        variant={viewType === "month" ? "default" : "outline"}
        size="sm"
        onClick={() => onViewTypeChange("month")}
        className="flex items-center gap-2"
      >
        <CalendarDays className="h-4 w-4" />
        Mese
      </Button>
    </div>
  );
};
