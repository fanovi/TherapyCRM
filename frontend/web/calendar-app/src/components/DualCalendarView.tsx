import React, { useState } from "react";
import { CalendarContainer } from "./CalendarContainer";
import { CalendarViewSelector, CalendarViewType } from "./CalendarViewSelector";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Calendar, User } from "lucide-react";
import { Appointment, Therapist } from "@/types/therapy";

interface DualCalendarViewProps {
  selectedTherapist: Therapist | null;
  appointments: Appointment[];
  onSlotClick: (date: Date, time: string) => void;
  onAppointmentMove: (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => void;
}

export const DualCalendarView: React.FC<DualCalendarViewProps> = ({
  selectedTherapist,
  appointments,
  onSlotClick,
  onAppointmentMove,
}) => {
  const [therapistView, setTherapistView] = useState<CalendarViewType>("week");
  const [patientView, setPatientView] = useState<CalendarViewType>("week");

  const therapistAppointments = appointments.filter(
    (apt) => selectedTherapist && apt.therapistId === selectedTherapist.id
  );

  // Patient view shows all appointments for the current patient across all therapists
  const patientAppointments = appointments; // In a real app, this would be filtered by patient

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* Therapist Calendar */}
      <Card className="bg-white shadow-lg border-0">
        <CardHeader className="pb-4">
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
              <User className="h-5 w-5 text-blue-600" />
              Calendario Terapista
              {selectedTherapist && (
                <span className="text-sm font-normal text-gray-600">
                  - {selectedTherapist.name}
                </span>
              )}
            </CardTitle>
            <CalendarViewSelector
              currentView={therapistView}
              onViewChange={setTherapistView}
            />
          </div>
        </CardHeader>
        <CardContent>
          {selectedTherapist ? (
            <CalendarContainer
              view={therapistView}
              appointments={therapistAppointments}
              onSlotClick={onSlotClick}
              onAppointmentMove={onAppointmentMove}
              isEditable={true}
              primaryColor={selectedTherapist.color}
            />
          ) : (
            <div className="h-96 flex items-center justify-center text-gray-500">
              <div className="text-center">
                <Calendar className="h-12 w-12 mx-auto mb-3 opacity-50" />
                <p>Seleziona un terapista per visualizzare il calendario</p>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Patient Calendar */}
      <Card className="bg-white shadow-lg border-0">
        <CardHeader className="pb-4">
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
              <Calendar className="h-5 w-5 text-green-600" />
              Calendario Paziente
              <span className="text-sm font-normal text-gray-600">
                - Vista Aggregata
              </span>
            </CardTitle>
            <CalendarViewSelector
              currentView={patientView}
              onViewChange={setPatientView}
            />
          </div>
        </CardHeader>
        <CardContent>
          <CalendarContainer
            view={patientView}
            appointments={patientAppointments}
            onSlotClick={() => {}} // Patient calendar is read-only
            onAppointmentMove={() => {}} // Patient calendar is read-only
            isEditable={false}
            primaryColor="#16a34a"
          />
        </CardContent>
      </Card>
    </div>
  );
};
