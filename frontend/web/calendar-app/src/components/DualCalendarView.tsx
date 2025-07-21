import React from "react";
import { Card } from "@/components/ui/card";
import { CalendarView } from "./CalendarView";
import { CalendarViewSelector, CalendarViewType } from "./CalendarViewSelector";
import { Therapist, Appointment } from "@/types/therapy";

interface DualCalendarViewProps {
  selectedTherapist: Therapist | null;
  appointments: Appointment[];
  onSlotClick: (date: Date, time: string) => void;
  onAppointmentMove: (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => void;
  viewType: CalendarViewType;
  onViewTypeChange: (viewType: CalendarViewType) => void;
  hidePatientCalendar?: boolean;
  mode: "patient" | "therapist";
  currentPatientId?: number;
}

export const DualCalendarView: React.FC<DualCalendarViewProps> = ({
  selectedTherapist,
  appointments,
  onSlotClick,
  onAppointmentMove,
  viewType,
  onViewTypeChange,
  hidePatientCalendar = false,
  mode,
  currentPatientId,
}) => {
  const therapistAppointments = selectedTherapist
    ? appointments.filter((apt) => apt.therapist?.id === selectedTherapist.id)
    : [];

  // Estrai il nome del paziente corrente
  const currentPatientName = currentPatientId
    ? appointments.find((apt) => apt.patient?.id === currentPatientId)?.patient
        ?.name
    : null;

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
        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4">
            Calendario di {selectedTherapist.name}
          </h2>
          <CalendarView
            viewType={viewType}
            appointments={therapistAppointments}
            onSlotClick={onSlotClick}
            onAppointmentMove={onAppointmentMove}
            isTherapistView={true}
          />
        </Card>
      </div>
    );
  }

  // Vista normale con due calendari
  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <CalendarViewSelector
          viewType={viewType}
          onViewTypeChange={onViewTypeChange}
        />
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4">
            {selectedTherapist
              ? `Calendario di ${selectedTherapist.name}`
              : "Seleziona un terapista"}
          </h2>
          {selectedTherapist ? (
            <CalendarView
              viewType={viewType}
              appointments={therapistAppointments}
              onSlotClick={onSlotClick}
              onAppointmentMove={onAppointmentMove}
              isTherapistView={true}
            />
          ) : (
            <div className="flex items-center justify-center h-64 text-gray-500">
              <p>Seleziona un terapista per visualizzare il suo calendario</p>
            </div>
          )}
        </Card>
        <Card className="p-6">
          <h2 className="text-2xl font-semibold mb-4 flex items-center gap-3">
            <div className="w-4 h-4 rounded-full bg-green-500" />
            Calendario Paziente
            {currentPatientId && (
              <span className="text-lg font-medium text-gray-600">
                - {currentPatientName || "Nome non disponibile"} (ID:{" "}
                {currentPatientId})
              </span>
            )}
          </h2>
          <CalendarView
            viewType={viewType}
            appointments={appointments}
            onSlotClick={onSlotClick}
            onAppointmentMove={onAppointmentMove}
            isTherapistView={false}
          />
        </Card>
      </div>
    </div>
  );
};
