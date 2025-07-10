import React, { useState, useEffect } from "react";
import { TherapistSelector } from "@/components/TherapistSelector";
import { DualCalendarView } from "@/components/DualCalendarView";
import { AppointmentModal } from "@/components/AppointmentModal";
import { Appointment, Therapist, AppointmentData } from "@/types/therapy";
import { CalendarViewType } from "@/components/CalendarViewSelector";

// Aggiungi questi tipi per i dati del paziente
interface Patient {
  id: string;
  name: string;
  // Altri campi che verranno definiti
}

const Index = () => {
  const [selectedTherapist, setSelectedTherapist] = useState<Therapist | null>(
    null
  );
  const [patient, setPatient] = useState<Patient | null>(null);
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedSlot, setSelectedSlot] = useState<{
    date: Date;
    time: string;
  } | null>(null);
  const [viewType, setViewType] = useState<CalendarViewType>("week");
  const [isTherapistView, setIsTherapistView] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get("id_patient");
    const therapistId = urlParams.get("id_therapist");

    const fetchData = async () => {
      setLoading(true);
      try {
        if (therapistId) {
          // Modalità terapista
          setIsTherapistView(true);

          // Per ora, usa dati mock finché l'endpoint non esiste
          // TODO: Rimuovere quando l'endpoint sarà disponibile
          const mockTherapistData = {
            id: therapistId,
            name: `Terapista ${therapistId}`,
            specialization: "Fisioterapia",
            email: `therapist${therapistId}@clinic.com`,
            color: "#3b82f6",
          };

          try {
            const response = await fetch(`/api/therapist/${therapistId}`);
            if (response.ok) {
              const therapistData = await response.json();
              setSelectedTherapist({
                id: therapistData.id,
                name: therapistData.name,
                specialization: therapistData.specialization,
                email: therapistData.email,
                color: therapistData.color || "#3b82f6",
              });
            } else {
              // Se l'endpoint non esiste ancora, usa i dati mock
              console.warn(
                `Endpoint /api/therapist/${therapistId} non disponibile, uso dati mock`
              );
              setSelectedTherapist(mockTherapistData);
            }
          } catch (error) {
            // In caso di errore di rete, usa i dati mock
            console.warn(
              "Errore nel caricamento dati terapista, uso dati mock:",
              error
            );
            setSelectedTherapist(mockTherapistData);
          }
        } else if (patientId) {
          // Modalità paziente

          // Per ora, usa dati mock finché l'endpoint non esiste
          // TODO: Rimuovere quando l'endpoint sarà disponibile
          const mockPatientData = {
            id: patientId,
            name: `Paziente ${patientId}`,
          };

          try {
            const response = await fetch(`/api/patient/${patientId}`);
            if (response.ok) {
              const patientData = await response.json();
              setPatient({
                id: patientData.id,
                name: patientData.name,
                // Altri campi del paziente
              });
            } else {
              // Se l'endpoint non esiste ancora, usa i dati mock
              console.warn(
                `Endpoint /api/patient/${patientId} non disponibile, uso dati mock`
              );
              setPatient(mockPatientData);
            }
          } catch (error) {
            // In caso di errore di rete, usa i dati mock
            console.warn(
              "Errore nel caricamento dati paziente, uso dati mock:",
              error
            );
            setPatient(mockPatientData);
          }
        }
      } catch (error) {
        console.error("Errore nel caricamento dei dati:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const handleAppointmentCreate = (appointmentData: AppointmentData) => {
    if (!selectedTherapist || !selectedSlot) return;

    const newAppointment: Appointment = {
      id: Date.now().toString(),
      therapistId: selectedTherapist.id,
      therapistName: selectedTherapist.name,
      patientName: patient?.name || "Paziente Esempio",
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

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-green-50 flex items-center justify-center">
        <div className="text-xl">Caricamento...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-green-50">
      <div className="container mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-4xl font-bold text-gray-900 mb-2">
            Gestione Terapie
          </h1>
          <p className="text-gray-600 text-lg">
            {isTherapistView
              ? `Vista Terapista: ${selectedTherapist?.name || ""}`
              : patient
              ? `Paziente: ${patient.name}`
              : "Sistema di pianificazione appuntamenti terapeutici"}
          </p>
        </div>

        {/* Mostra il selettore terapista solo se non siamo in modalità terapista */}
        {!isTherapistView && (
          <div className="mb-8">
            <TherapistSelector
              selectedTherapist={selectedTherapist}
              onTherapistSelect={setSelectedTherapist}
            />
          </div>
        )}

        <DualCalendarView
          selectedTherapist={selectedTherapist}
          appointments={appointments}
          onSlotClick={handleSlotClick}
          onAppointmentMove={handleAppointmentMove}
          viewType={viewType}
          onViewTypeChange={setViewType}
          hidePatientCalendar={isTherapistView}
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
