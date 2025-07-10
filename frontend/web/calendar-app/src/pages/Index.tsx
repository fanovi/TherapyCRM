import React, { useState } from "react";
import { TherapistSelector } from "@/components/TherapistSelector";
import { DualCalendarView } from "@/components/DualCalendarView";
import { AppointmentModal } from "@/components/AppointmentModal";
import { Appointment, Therapist, AppointmentData } from "@/types/therapy";
import { CalendarViewType } from "@/components/CalendarViewSelector";

const Index = () => {
  const [selectedTherapist, setSelectedTherapist] = useState<Therapist | null>(
    null
  );
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedSlot, setSelectedSlot] = useState<{
    date: Date;
    time: string;
  } | null>(null);
  const [viewType, setViewType] = useState<CalendarViewType>("week");

  const handleAppointmentCreate = (appointmentData: AppointmentData) => {
    if (!selectedTherapist || !selectedSlot) return;

    const newAppointment: Appointment = {
      id: Date.now().toString(),
      therapistId: selectedTherapist.id,
      therapistName: selectedTherapist.name,
      patientName: "Paziente Esempio", // In a real app, this would be dynamic
      date: selectedSlot.date,
      time: selectedSlot.time,
      duration: appointmentData.duration,
      therapyType: appointmentData.therapyType,
      notes: appointmentData.notes,
      isRecurring: appointmentData.isRecurring,
    };

    setAppointments((prev) => [...prev, newAppointment]);
    setIsModalOpen(false);
    setSelectedSlot(null);
  };

  const handleSlotClick = (date: Date, time: string) => {
    setSelectedSlot({ date, time });
    setIsModalOpen(true);
  };

  const handleAppointmentMove = (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => {
    setAppointments((prev) =>
      prev.map((apt) =>
        apt.id === appointmentId
          ? { ...apt, date: newDate, time: newTime }
          : apt
      )
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-green-50">
      <div className="container mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-4xl font-bold text-gray-900 mb-2">
            Gestione Terapie
          </h1>
          <p className="text-gray-600 text-lg">
            Sistema di pianificazione appuntamenti terapeutici
          </p>
        </div>

        <div className="mb-8">
          <TherapistSelector
            selectedTherapist={selectedTherapist}
            onTherapistSelect={setSelectedTherapist}
          />
        </div>

        <DualCalendarView
          selectedTherapist={selectedTherapist}
          appointments={appointments}
          onSlotClick={handleSlotClick}
          onAppointmentMove={handleAppointmentMove}
          viewType={viewType}
          onViewTypeChange={setViewType}
        />

        <AppointmentModal
          isOpen={isModalOpen}
          onClose={() => {
            setIsModalOpen(false);
            setSelectedSlot(null);
          }}
          onConfirm={handleAppointmentCreate}
          selectedSlot={selectedSlot}
        />
      </div>
    </div>
  );
};

export default Index;
