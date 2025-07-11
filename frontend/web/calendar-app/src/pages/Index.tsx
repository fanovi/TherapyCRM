import React, { useState, useEffect } from "react";
import { useParams } from "react-router-dom";
import { TherapistSelector } from "@/components/TherapistSelector";
import { DualCalendarView } from "@/components/DualCalendarView";
import { AppointmentModal } from "@/components/AppointmentModal";
import {
  Appointment,
  Therapist,
  Patient,
  AppointmentData,
} from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { CalendarViewType } from "@/components/CalendarViewSelector";
import { Loader2 } from "lucide-react";

const Index = () => {
  const params = useParams();
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
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Leggi i parametri dall'URL usando React Router
    const patientId = params.id_patient;
    const therapistId = params.id_therapist;

    const fetchData = async () => {
      setLoading(true);
      setError(null);

      try {
        if (therapistId) {
          // Modalità terapista
          setIsTherapistView(true);

          try {
            // Carica i dettagli del terapista dalla lista completa
            const therapists = await therapyAPI.getTherapists();
            const therapist = therapists.find(
              (t) => t.id.toString() === therapistId
            );

            if (!therapist) {
              throw new Error(`Terapista con ID ${therapistId} non trovato`);
            }

            // Assegna un colore se non presente
            if (!therapist.color) {
              const colors = [
                "#3b82f6",
                "#16a34a",
                "#dc2626",
                "#7c3aed",
                "#ea580c",
                "#0891b2",
              ];
              therapist.color = colors[therapist.id % colors.length];
            }

            setSelectedTherapist(therapist);

            // Carica gli appuntamenti del terapista per il mese corrente
            const now = new Date();
            const therapistAppointments =
              await therapyAPI.getTherapistAppointments(
                therapist.id,
                now.getMonth() + 1,
                now.getFullYear()
              );

            setAppointments(therapistAppointments);
          } catch (err) {
            console.error("Errore caricamento dati terapista:", err);
            setError(
              err instanceof Error
                ? err.message
                : "Errore nel caricamento del terapista"
            );
          }
        } else if (patientId) {
          // Modalità paziente
          setIsTherapistView(false);

          try {
            // Carica i dati del paziente
            const patientData = await therapyAPI.getPatient(
              parseInt(patientId)
            );
            setPatient(patientData);

            // Carica gli appuntamenti del paziente per il mese corrente
            const now = new Date();
            const patientAppointments = await therapyAPI.getPatientAppointments(
              patientData.id,
              now.getMonth() + 1,
              now.getFullYear()
            );

            setAppointments(patientAppointments);
          } catch (err) {
            console.error("Errore caricamento dati paziente:", err);

            // Gestione specifica per pazienti senza piani terapeutici attivi
            if (
              err instanceof Error &&
              (err as any).code === "NO_ACTIVE_THERAPEUTIC_PLAN"
            ) {
              setError(
                "Il paziente non ha piani terapeutici attivi. Contattare l'amministratore per attivare un piano terapeutico prima di poter utilizzare il calendario."
              );
            } else {
              setError(
                err instanceof Error
                  ? err.message
                  : "Errore nel caricamento del paziente"
              );
            }
          }
        } else {
          // Nessun ID fornito - questo non dovrebbe accadere secondo le specifiche
          setError("ID paziente o terapista mancante nell'URL");
        }
      } catch (error) {
        console.error("Errore generale nel caricamento dei dati:", error);
        setError(
          error instanceof Error
            ? error.message
            : "Errore generico nel caricamento"
        );
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [params]);

  const handleAppointmentCreate = async (appointmentData: AppointmentData) => {
    if (!selectedTherapist || !selectedSlot) return;

    try {
      // In modalità paziente, il paziente è già caricato
      // In modalità terapista, dobbiamo gestire diversamente (implementazione futura)
      if (!patient && isTherapistView) {
        console.warn(
          "Creazione appuntamento in modalità terapista non ancora implementata"
        );
        return;
      }

      if (!patient) {
        throw new Error("Dati paziente mancanti");
      }

      // TODO: Ottenere il planTherapyId corretto - per ora uso un valore mock
      // Questo dovrà essere implementato quando avremo l'endpoint per ottenere i piani terapia del paziente
      const mockPlanTherapyId = 1;

      // Formatta la data per l'API
      const appointmentDateTime =
        selectedSlot.date.toISOString().split("T")[0] +
        " " +
        selectedSlot.time +
        ":00";

      const request = {
        planTherapyId: mockPlanTherapyId,
        therapistId: selectedTherapist.id,
        appointmentDateTime,
        durationMinutes: appointmentData.duration,
        notes: appointmentData.notes,
      };

      const result = await therapyAPI.createAppointment(request);

      // Crea l'appuntamento locale per aggiornare immediatamente l'UI
      const newAppointment: Appointment = {
        id: result.appointmentId,
        datetime: appointmentDateTime,
        duration: appointmentData.duration,
        status: "scheduled",
        notes: appointmentData.notes,
        therapist: {
          id: selectedTherapist.id,
          name: selectedTherapist.name,
        },
        treatmentType: appointmentData.therapyType,
      };

      setAppointments((prev) => [...prev, newAppointment]);
      setIsModalOpen(false);
      setSelectedSlot(null);

      // Gestisci avvisi sui limiti settimanali se presenti
      if (result.weeklyLimitExceeded.length > 0) {
        console.warn(
          "Limite settimanale superato:",
          result.weeklyLimitExceeded
        );
        // TODO: Mostrare un toast/modal di avviso
      }
    } catch (err) {
      console.error("Errore nella creazione dell'appuntamento:", err);
      // TODO: Mostrare messaggio di errore all'utente

      // Se c'è un conflitto, gestisci specificamente
      if (err instanceof Error && "conflict" in err) {
        console.warn("Conflitto rilevato:", (err as any).conflict);
        // TODO: Mostrare modal di conflitto
      }
    }
  };

  const handleSlotClick = (date: Date, time: string) => {
    setSelectedSlot({ date, time });
    setIsModalOpen(true);
  };

  const handleAppointmentMove = async (
    appointmentId: string,
    newDate: Date,
    newTime: string
  ) => {
    try {
      // Converti l'ID in numero per compatibilità con l'API
      const numericId = parseInt(appointmentId);
      if (isNaN(numericId)) {
        throw new Error("ID appuntamento non valido");
      }

      // Trova l'appuntamento da spostare
      const appointment = appointments.find((apt) => apt.id === numericId);
      if (!appointment) {
        throw new Error("Appuntamento non trovato");
      }

      // Formatta la nuova data/ora per l'API
      const newDateTime =
        newDate.toISOString().split("T")[0] + " " + newTime + ":00";

      const request = {
        appointmentId: numericId,
        therapistId: appointment.therapist?.id || selectedTherapist?.id || 0,
        appointmentDateTime: newDateTime,
        durationMinutes: appointment.duration,
        notes: appointment.notes,
      };

      await therapyAPI.updateAppointment(request);

      // Aggiorna l'UI localmente
      setAppointments((prev) =>
        prev.map((apt) =>
          apt.id === numericId ? { ...apt, datetime: newDateTime } : apt
        )
      );
    } catch (err) {
      console.error("Errore nello spostamento dell'appuntamento:", err);
      // TODO: Mostrare messaggio di errore e ripristinare posizione originale

      // Se c'è un conflitto, gestisci specificamente
      if (err instanceof Error && "conflict" in err) {
        console.warn("Conflitto rilevato:", (err as any).conflict);
        // TODO: Mostrare modal di conflitto
      }
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <Loader2 className="h-8 w-8 animate-spin mr-3" />
        <div className="text-xl">Caricamento...</div>
      </div>
    );
  }

  if (error) {
    const isNoTherapeuticPlan = error.includes("piani terapeutici attivi");

    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-center max-w-lg">
          <div
            className={`text-xl mb-4 ${
              isNoTherapeuticPlan ? "text-orange-600" : "text-red-600"
            }`}
          >
            {isNoTherapeuticPlan ? "⚠️ Accesso non consentito" : "❌ Errore"}
          </div>
          <p className="text-gray-700 mb-6 text-lg leading-relaxed">{error}</p>

          {isNoTherapeuticPlan ? (
            <div className="space-y-3">
              <p className="text-sm text-gray-600">
                Per utilizzare il calendario è necessario che il paziente abbia
                almeno un piano terapeutico attivo.
              </p>
              <button
                onClick={() => window.history.back()}
                className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
              >
                ← Torna indietro
              </button>
            </div>
          ) : (
            <button
              onClick={() => window.location.reload()}
              className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              🔄 Riprova
            </button>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="w-full">
      <div className="p-4">
        {/* Informazioni paziente/terapista nella barra superiore */}
        <div className="mb-6 p-4 bg-white rounded-lg shadow-sm border">
          {isTherapistView && selectedTherapist ? (
            <div className="flex items-center gap-3">
              <div
                className="w-4 h-4 rounded-full"
                style={{ backgroundColor: selectedTherapist.color }}
              />
              <div>
                <h2 className="text-lg font-semibold text-gray-900">
                  Calendario di {selectedTherapist.name}
                </h2>
                <p className="text-sm text-gray-600">
                  {selectedTherapist.specialization} • {selectedTherapist.email}
                  {selectedTherapist.weeklyHours &&
                    ` • ${selectedTherapist.weeklyHours}h/settimana`}
                </p>
              </div>
            </div>
          ) : patient ? (
            <div>
              <h2 className="text-lg font-semibold text-gray-900">
                Calendario di {patient.name}
              </h2>
              <p className="text-sm text-gray-600">
                {patient.email}
                {patient.fiscalCode && ` • ${patient.fiscalCode}`}
              </p>
            </div>
          ) : null}
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
          selectedTherapist={selectedTherapist}
        />
      </div>
    </div>
  );
};

export default Index;
