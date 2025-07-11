import React, { useState, useEffect, useMemo } from "react";
import { useParams } from "react-router-dom";
import { TherapistSelector } from "@/components/TherapistSelector";
import { DualFullCalendarView } from "@/components/DualFullCalendarView";
import { AppointmentModal } from "@/components/AppointmentModal";
import { AppointmentEditModal } from "@/components/AppointmentEditModal";
import {
  Appointment,
  Therapist,
  Patient,
  AppointmentData,
} from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { CalendarViewType } from "@/components/CalendarViewSelector";
import { Loader2 } from "lucide-react";
import { ToastContainer, useToast } from "@/components/Toast";
import { format } from "date-fns";

const Index = () => {
  const params = useParams();
  const { messages, showSuccess, showError, showInfo, closeToast } = useToast();
  const [selectedTherapist, setSelectedTherapist] = useState<Therapist | null>(
    null
  );
  const [patient, setPatient] = useState<Patient | null>(null);
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [selectedSlot, setSelectedSlot] = useState<{
    date: Date;
    time: string;
  } | null>(null);
  const [selectedAppointment, setSelectedAppointment] =
    useState<Appointment | null>(null);
  const [therapists, setTherapists] = useState<Therapist[]>([]);
  const [viewType, setViewType] = useState<CalendarViewType>("week");
  const [isTherapistView, setIsTherapistView] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [therapistAppointments, setTherapistAppointments] = useState<
    Appointment[]
  >([]);
  const [currentMonth, setCurrentMonth] = useState(new Date());
  const [currentVisibleRange, setCurrentVisibleRange] = useState<{
    start: Date;
    end: Date;
  } | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  // Funzione per ricaricare gli appuntamenti per un mese specifico
  const loadAppointmentsForMonth = async (date: Date) => {
    const month = date.getMonth() + 1;
    const year = date.getFullYear();

    try {
      if (patient) {
        const patientAppointments = await therapyAPI.getPatientAppointments(
          patient.id,
          month,
          year
        );
        setAppointments(patientAppointments);
      }

      if (selectedTherapist) {
        const therapistAppointments = await therapyAPI.getTherapistAppointments(
          selectedTherapist.id,
          month,
          year
        );
        setTherapistAppointments(therapistAppointments);
      }
    } catch (error) {
      console.error("Errore nel caricamento appuntamenti per il mese:", error);
    }
  };

  // Funzione per ricaricare gli appuntamenti per il range attualmente visibile
  const reloadCurrentVisibleAppointments = async () => {
    if (!currentVisibleRange) {
      // Fallback al mese corrente se non abbiamo il range
      const now = new Date();
      await loadAppointmentsForMonth(now);
      return;
    }

    try {
      // Determina tutti i mesi nel range visibile
      const startDate = new Date(currentVisibleRange.start);
      const endDate = new Date(currentVisibleRange.end);

      const monthsToLoad = new Set<{ month: number; year: number }>();
      const current = new Date(startDate);

      while (current <= endDate) {
        monthsToLoad.add({
          month: current.getMonth() + 1,
          year: current.getFullYear(),
        });
        current.setMonth(current.getMonth() + 1);
      }

      // Carica appuntamenti per tutti i mesi necessari
      const appointmentPromises: Promise<any[]>[] = [];

      for (const { month, year } of monthsToLoad) {
        if (patient) {
          appointmentPromises.push(
            therapyAPI.getPatientAppointments(patient.id, month, year)
          );
        }
        if (selectedTherapist) {
          appointmentPromises.push(
            therapyAPI.getTherapistAppointments(
              selectedTherapist.id,
              month,
              year
            )
          );
        }
      }

      const results = await Promise.all(appointmentPromises);

      // Separa i risultati per paziente e terapista
      let patientResults: any[] = [];
      let therapistResults: any[] = [];

      let resultIndex = 0;
      for (const { month, year } of monthsToLoad) {
        if (patient) {
          patientResults = patientResults.concat(results[resultIndex]);
          resultIndex++;
        }
        if (selectedTherapist) {
          therapistResults = therapistResults.concat(results[resultIndex]);
          resultIndex++;
        }
      }

      if (patient) {
        setAppointments(patientResults);
      }
      if (selectedTherapist) {
        setTherapistAppointments(therapistResults);
      }
    } catch (error) {
      console.error(
        "Errore nel ricaricamento appuntamenti per range visibile:",
        error
      );
    }
  };

  // Gestisce il cambio di mese/data nel calendario
  const handleDateChange = (date: Date) => {
    const newMonth = new Date(date.getFullYear(), date.getMonth(), 1);

    // Se è cambiato il mese, ricarica gli appuntamenti
    if (
      newMonth.getTime() !==
      new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1).getTime()
    ) {
      setCurrentMonth(newMonth);
      loadAppointmentsForMonth(newMonth);
    }
  };

  // Gestisce il cambio del range di date visibile nel calendario
  const handleVisibleRangeChange = (start: Date, end: Date) => {
    setCurrentVisibleRange({ start, end });
  };

  useEffect(() => {
    // Leggi i parametri dall'URL usando React Router
    const patientId = params.id_patient;
    const therapistId = params.id_therapist;

    const fetchData = async () => {
      setLoading(true);
      setError(null);

      try {
        // Carica sempre la lista dei terapisti per il modal di modifica
        const therapistsList = await therapyAPI.getTherapists();
        setTherapists(therapistsList);

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

  // Effect per caricare appuntamenti del terapista selezionato (in modalità paziente)
  useEffect(() => {
    const loadTherapistAppointments = async () => {
      if (!selectedTherapist || isTherapistView) return;

      try {
        const now = new Date();
        const appointments = await therapyAPI.getTherapistAppointments(
          selectedTherapist.id,
          now.getMonth() + 1,
          now.getFullYear()
        );
        setTherapistAppointments(appointments);
      } catch (err) {
        console.error("Errore caricamento appuntamenti terapista:", err);
        setTherapistAppointments([]);
      }
    };

    loadTherapistAppointments();
  }, [selectedTherapist, isTherapistView]);

  // Combina appuntamenti del paziente e del terapista per la visualizzazione
  const combinedAppointments = useMemo((): Appointment[] => {
    if (isTherapistView) {
      // In modalità terapista, mostra solo i suoi appuntamenti
      return appointments;
    } else {
      // In modalità paziente, combina appuntamenti del paziente + tutti quelli del terapista selezionato
      if (!selectedTherapist) {
        return appointments; // Solo appuntamenti del paziente
      }

      // Combina senza duplicati (un appuntamento può essere sia del paziente che del terapista)
      const combined = [...appointments];

      therapistAppointments.forEach((therapistApt) => {
        const isDuplicate = appointments.some(
          (patientApt) => patientApt.id === therapistApt.id
        );
        if (!isDuplicate) {
          combined.push(therapistApt);
        }
      });

      return combined;
    }
  }, [appointments, therapistAppointments, isTherapistView, selectedTherapist]);

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

      // Usa il planTherapyId dal piano terapeutico caricato
      const planTherapyId = appointmentData.planTherapy?.planTherapyId;
      if (!planTherapyId) {
        throw new Error("Piano terapeutico non trovato");
      }

      // Formatta la data per l'API
      const appointmentDateTime =
        selectedSlot.date.toISOString().split("T")[0] +
        " " +
        selectedSlot.time +
        ":00";

      let result;
      let appointmentsCreated = 1;
      let weeklyLimitExceeded: any[] = [];

      if (appointmentData.isRecurring) {
        // Crea pattern ricorrente
        const dayOfWeek = selectedSlot.date.getDay() || 7; // 0 = Domenica -> 7, 1-6 = Lunedì-Sabato
        const startTime = selectedSlot.time; // "HH:mm"
        const validFrom = selectedSlot.date.toISOString().split("T")[0];
        const validTo = appointmentData.planTherapy?.endDate;

        if (!validTo) {
          throw new Error("Data fine piano terapeutico non disponibile");
        }

        const patternRequest = {
          planTherapyId,
          therapistId: selectedTherapist.id,
          dayOfWeek,
          startTime,
          durationMinutes: appointmentData.duration,
          validFrom,
          validTo,
        };

        const patternResult = await therapyAPI.createPattern(patternRequest);
        result = { appointmentId: patternResult.data.patternId };
        appointmentsCreated = patternResult.appointmentsCreated;
        weeklyLimitExceeded = patternResult.weeklyLimitExceeded || [];

        // Gestisci conflitti se presenti
        if (patternResult.conflicts && patternResult.conflicts.length > 0) {
          showInfo(
            "Conflitti rilevati",
            `${patternResult.conflicts.length} appuntamenti non sono stati creati a causa di conflitti con altri appuntamenti.`
          );
        }
      } else {
        // Crea singolo appuntamento
        const request = {
          planTherapyId,
          therapistId: selectedTherapist.id,
          appointmentDateTime,
          durationMinutes: appointmentData.duration,
          notes: appointmentData.notes,
        };

        const singleResult = await therapyAPI.createAppointment(request);
        result = singleResult;
        weeklyLimitExceeded = singleResult.weeklyLimitExceeded || [];
      }

      // Ricarica gli appuntamenti usando la stessa logica del caricamento iniziale
      const now = new Date();
      const currentMonth = now.getMonth() + 1;
      const currentYear = now.getFullYear();

      console.log("🔄 Ricaricando appuntamenti dopo creazione:", {
        currentMonth,
        currentYear,
        patientId: patient?.id,
        therapistId: selectedTherapist?.id,
      });

      console.log("📊 Stato attuale degli appuntamenti:", {
        appointments: appointments.length,
        therapistAppointments: therapistAppointments.length,
      });

      // Ricarica appuntamenti paziente (sempre in modalità paziente)
      if (patient) {
        console.log("📅 Caricando appuntamenti paziente...");
        const patientAppointments = await therapyAPI.getPatientAppointments(
          patient.id,
          currentMonth,
          currentYear
        );
        console.log("✅ Appuntamenti paziente ricevuti:", patientAppointments);
        setAppointments([...patientAppointments]);
      }

      // Ricarica appuntamenti terapista per il calendario di destra
      if (selectedTherapist) {
        console.log("🧑‍⚕️ Caricando appuntamenti terapista...");
        const therapistAppointments = await therapyAPI.getTherapistAppointments(
          selectedTherapist.id,
          currentMonth,
          currentYear
        );
        console.log(
          "✅ Appuntamenti terapista ricevuti:",
          therapistAppointments
        );
        setTherapistAppointments([...therapistAppointments]);
        setRefreshKey((prev) => prev + 1);
      }

      console.log("🎯 Ricaricamento completato!");

      // Forza un piccolo delay per assicurarsi che React rilevi il cambiamento
      setTimeout(() => {
        console.log("📊 Stato finale degli appuntamenti:", {
          appointments: appointments.length,
          therapistAppointments: therapistAppointments.length,
          combinedLength: combinedAppointments.length,
        });
      }, 100);

      setIsModalOpen(false);
      setSelectedSlot(null);

      // Mostra messaggio di successo
      if (appointmentData.isRecurring) {
        showSuccess(
          "Pattern ricorrente creato",
          `${appointmentsCreated} appuntamenti sono stati creati con successo fino al ${format(
            new Date(appointmentData.planTherapy?.endDate || ""),
            "dd/MM/yyyy"
          )}`
        );
      } else {
        showSuccess(
          "Appuntamento creato",
          "L'appuntamento è stato creato con successo"
        );
      }

      // Gestisci avvisi sui limiti settimanali se presenti
      if (weeklyLimitExceeded.length > 0) {
        console.warn("Limite settimanale superato:", weeklyLimitExceeded);
        showInfo(
          "Attenzione limite settimanale",
          "Il limite di ore settimanali del terapista potrebbe essere stato superato"
        );
      }
    } catch (err) {
      console.error("Errore nella creazione dell'appuntamento:", err);

      // Se c'è un conflitto, gestisci specificamente
      if (err instanceof Error && "conflict" in err) {
        const conflict = (err as any).conflict;
        showError(
          "Conflitto appuntamento",
          `Il terapista ha già un appuntamento in questo orario con ${
            conflict?.existingAppointmentInfo?.patientName ||
            "un altro paziente"
          }`
        );
      } else {
        // Errore generico
        const errorMessage =
          err instanceof Error ? err.message : "Errore sconosciuto";
        showError(
          "Errore creazione",
          `Non è stato possibile creare l'appuntamento: ${errorMessage}`
        );
      }
    }
  };

  const handleSlotClick = (date: Date, time: string) => {
    setSelectedSlot({ date, time });
    setIsModalOpen(true);
  };

  const handleAppointmentClick = (appointmentId: string) => {
    // Trova l'appuntamento nel combined array
    const appointment = combinedAppointments.find(
      (apt) => apt.id.toString() === appointmentId
    );
    if (appointment) {
      setSelectedAppointment(appointment);
      setIsEditModalOpen(true);
    }
  };

  const handleAppointmentUpdate = async (appointmentId: string) => {
    // Ricarica gli appuntamenti usando la stessa logica del caricamento iniziale
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();

    console.log("🔄 Ricaricando appuntamenti dopo modifica:", {
      currentMonth,
      currentYear,
      patientId: patient?.id,
      therapistId: selectedTherapist?.id,
    });

    // Ricarica appuntamenti paziente (sempre in modalità paziente)
    if (patient) {
      const patientAppointments = await therapyAPI.getPatientAppointments(
        patient.id,
        currentMonth,
        currentYear
      );
      setAppointments([...patientAppointments]);
    }

    // Ricarica appuntamenti terapista per il calendario di destra
    if (selectedTherapist) {
      const therapistAppointments = await therapyAPI.getTherapistAppointments(
        selectedTherapist.id,
        currentMonth,
        currentYear
      );
      setTherapistAppointments([...therapistAppointments]);
      setRefreshKey((prev) => prev + 1);
    }
  };

  const handleAppointmentDelete = async (appointmentId: string) => {
    // Ricarica gli appuntamenti dopo eliminazione
    await handleAppointmentUpdate(appointmentId);
  };

  const handleAppointmentMove = async (
    appointmentId: string,
    newDate: Date,
    newTime: string,
    eventData?: any
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

      // Mostra messaggio di successo
      showSuccess(
        "Appuntamento spostato",
        "L'appuntamento è stato spostato con successo"
      );
    } catch (err) {
      console.error("Errore nello spostamento dell'appuntamento:", err);

      // Se c'è un conflitto, gestisci specificamente
      if (err instanceof Error && "conflict" in err) {
        const conflict = (err as any).conflict;
        showError(
          "Conflitto appuntamento",
          `Il terapista ha già un appuntamento in questo orario con ${
            conflict?.existingAppointmentInfo?.patientName ||
            "un altro paziente"
          }`
        );
      } else {
        // Errore generico
        const errorMessage =
          err instanceof Error ? err.message : "Errore sconosciuto";
        showError(
          "Errore spostamento",
          `Non è stato possibile spostare l'appuntamento: ${errorMessage}`
        );
      }

      // TODO: Ripristinare posizione originale dell'appuntamento nell'UI
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
      <ToastContainer messages={messages} onClose={closeToast} />
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

        <DualFullCalendarView
          key={refreshKey}
          selectedTherapist={selectedTherapist}
          appointments={combinedAppointments}
          onSlotClick={handleSlotClick}
          onAppointmentClick={handleAppointmentClick}
          onAppointmentMove={handleAppointmentMove}
          viewType={viewType}
          onViewTypeChange={setViewType}
          hidePatientCalendar={isTherapistView}
          mode={isTherapistView ? "therapist" : "patient"}
          currentPatientId={patient?.id}
          onDateChange={handleDateChange}
          onVisibleRangeChange={handleVisibleRangeChange}
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
          patient={patient}
        />

        <AppointmentEditModal
          isOpen={isEditModalOpen}
          onClose={() => {
            setIsEditModalOpen(false);
            setSelectedAppointment(null);
          }}
          appointment={selectedAppointment}
          therapists={therapists}
          onAppointmentUpdate={handleAppointmentUpdate}
          onAppointmentDelete={handleAppointmentDelete}
        />
      </div>
    </div>
  );
};

export default Index;
