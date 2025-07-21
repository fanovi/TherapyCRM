import React, { useState, useEffect, useMemo } from "react";
import { useParams } from "react-router-dom";
import { TherapistSelector } from "@/components/TherapistSelector";
import { PatientSelector } from "@/components/PatientSelector";
import { DualFullCalendarView } from "@/components/DualFullCalendarView";
import { AppointmentModal } from "@/components/AppointmentModal";
import { PrivateAppointmentModal } from "@/components/PrivateAppointmentModal";
import { AppointmentEditModal } from "@/components/AppointmentEditModal";
import { TherapistSubstitutionModal } from "@/components/TherapistSubstitutionModal";
import {
  Appointment,
  Therapist,
  Patient,
  AppointmentData,
  PrivateAppointmentData,
  TreatmentType,
} from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { CalendarViewType } from "@/components/CalendarViewSelector";
import { Loader2, Lock, LockOpen } from "lucide-react";
import { ToastContainer, useToast } from "@/components/Toast";
import { format } from "date-fns";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";

const Index = () => {
  const params = useParams();
  const { messages, showSuccess, showError, showInfo, closeToast } = useToast();
  const [selectedTherapist, setSelectedTherapist] = useState<Therapist | null>(
    null
  );
  const [selectedTreatmentType, setSelectedTreatmentType] =
    useState<TreatmentType | null>(null);
  const [patient, setPatient] = useState<Patient | null>(null);
  const [selectedPatient, setSelectedPatient] = useState<Patient | null>(null); // Nuovo state per paziente selezionato nella vista terapista
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isPrivateModalOpen, setIsPrivateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isSubstitutionModalOpen, setIsSubstitutionModalOpen] = useState(false);
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
  const [isPrivateMode, setIsPrivateMode] = useState(false);
  const [canCreateNormalAppointments, setCanCreateNormalAppointments] =
    useState(true);
  const [planTherapyCheckMessage, setPlanTherapyCheckMessage] = useState<
    string | null
  >(null);

  // Funzione per ricaricare gli appuntamenti per un mese specifico
  const loadAppointmentsForMonth = async (date: Date) => {
    const month = date.getMonth() + 1;
    const year = date.getFullYear();

    try {
      // Carica appuntamenti del paziente (normale o selezionato nella vista terapista)
      const currentPatient = isTherapistView ? selectedPatient : patient;
      if (currentPatient) {
        const patientAppointments = await therapyAPI.getPatientAppointments(
          currentPatient.id,
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
      const now = new Date();
      await loadAppointmentsForMonth(now);
      return;
    }

    try {
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

      const appointmentPromises: Promise<any[]>[] = [];

      // Determina quale paziente usare
      const currentPatient = isTherapistView ? selectedPatient : patient;

      for (const { month, year } of monthsToLoad) {
        if (currentPatient) {
          appointmentPromises.push(
            therapyAPI.getPatientAppointments(currentPatient.id, month, year)
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

      let patientResults: any[] = [];
      let therapistResults: any[] = [];

      let resultIndex = 0;
      for (const { month, year } of monthsToLoad) {
        if (currentPatient) {
          patientResults = patientResults.concat(results[resultIndex]);
          resultIndex++;
        }
        if (selectedTherapist) {
          therapistResults = therapistResults.concat(results[resultIndex]);
          resultIndex++;
        }
      }

      if (currentPatient) {
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

  // Verifica se il paziente e il terapista possono creare appuntamenti normali
  const checkPlanTherapyCompatibility = async (
    patient: Patient,
    therapist: Therapist
  ) => {
    try {
      await therapyAPI.getPlanTherapyForTherapist(patient.id, therapist.id);
      setCanCreateNormalAppointments(true);
      setPlanTherapyCheckMessage(null);
      return true;
    } catch (error) {
      const errorMessage =
        error instanceof Error
          ? error.message
          : "Errore nella verifica del piano terapeutico";
      setCanCreateNormalAppointments(false);
      setPlanTherapyCheckMessage(errorMessage);

      // Se non può creare appuntamenti normali, forza la modalità privata
      setIsPrivateMode(true);
      return false;
    }
  };

  useEffect(() => {
    const patientId = params.id_patient;
    const therapistId = params.id_therapist;

    const fetchData = async () => {
      setLoading(true);
      setError(null);

      try {
        const therapistsList = await therapyAPI.getTherapists();
        setTherapists(therapistsList);

        if (therapistId) {
          setIsTherapistView(true);

          try {
            const therapists = await therapyAPI.getTherapists();
            const therapist = therapists.find(
              (t) => t.id.toString() === therapistId
            );

            if (!therapist) {
              throw new Error(`Terapista con ID ${therapistId} non trovato`);
            }

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
          setIsTherapistView(false);

          try {
            const patientData = await therapyAPI.getPatient(
              parseInt(patientId)
            );
            setPatient(patientData);

            const now = new Date();
            const patientAppointments = await therapyAPI.getPatientAppointments(
              patientData.id,
              now.getMonth() + 1,
              now.getFullYear()
            );

            setAppointments(patientAppointments);
          } catch (err) {
            console.error("Errore caricamento dati paziente:", err);

            if (
              err instanceof Error &&
              (err as any).code === "NO_ACTIVE_THERAPEUTIC_PLAN"
            ) {
              // In Private Mode, possiamo continuare anche senza piano terapeutico
              setError(null);
              // Imposta il paziente comunque se abbiamo i dati base
              if (patientId) {
                try {
                  const patientData = await therapyAPI.getPatient(
                    parseInt(patientId)
                  );
                  setPatient(patientData);
                } catch (e) {
                  setError("Errore nel caricamento dati paziente");
                }
              }
            } else {
              setError(
                err instanceof Error
                  ? err.message
                  : "Errore nel caricamento del paziente"
              );
            }
          }
        } else {
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

  // Ricarica appuntamenti quando cambia il paziente selezionato nella vista terapista
  useEffect(() => {
    if (isTherapistView && selectedPatient) {
      const now = new Date();
      loadAppointmentsForMonth(now);
    }
  }, [selectedPatient, isTherapistView]);

  // Effetto per impostare automaticamente il treatmentType nella vista terapista
  useEffect(() => {
    const setTreatmentTypeForTherapist = async () => {
      if (isTherapistView && selectedTherapist && !selectedTreatmentType) {
        try {
          // Carica tutti i tipi di trattamento
          const treatmentTypes = await therapyAPI.getTreatmentTypes();

          // Trova il tipo di trattamento che corrisponde alla specializzazione del terapista
          const matchingTreatmentType = treatmentTypes.find(
            (type) => type.name === selectedTherapist.specialization
          );

          if (matchingTreatmentType) {
            setSelectedTreatmentType(matchingTreatmentType);
            console.log(
              "🎯 Impostato automaticamente treatmentType per terapista:",
              matchingTreatmentType
            );
          }
        } catch (error) {
          console.error("Errore nel caricamento tipi di trattamento:", error);
        }
      }
    };

    setTreatmentTypeForTherapist();
  }, [isTherapistView, selectedTherapist, selectedTreatmentType]);

  // Verifica compatibilità piano terapeutico quando cambiano paziente e terapista
  useEffect(() => {
    const checkCompatibility = async () => {
      const currentPatient = isTherapistView ? selectedPatient : patient;

      if (currentPatient && selectedTherapist) {
        await checkPlanTherapyCompatibility(currentPatient, selectedTherapist);
      } else {
        // Reset quando non ci sono entrambi selezionati
        setCanCreateNormalAppointments(true);
        setPlanTherapyCheckMessage(null);
        setIsPrivateMode(false);
      }
    };

    checkCompatibility();
  }, [selectedPatient, patient, selectedTherapist, isTherapistView]);

  const combinedAppointments = useMemo((): Appointment[] => {
    if (isTherapistView) {
      // Vista terapista: mostra sempre gli appuntamenti del terapista
      // Se c'è un paziente selezionato, combina con i suoi appuntamenti
      const combined = [...therapistAppointments];

      if (selectedPatient) {
        // Aggiungi gli appuntamenti del paziente selezionato che non sono duplicati
        appointments.forEach((patientApt) => {
          const isDuplicate = therapistAppointments.some(
            (therapistApt) => therapistApt.id === patientApt.id
          );
          if (!isDuplicate) {
            combined.push(patientApt);
          }
        });
      }

      return combined;
    } else {
      if (!selectedTherapist) {
        return appointments;
      }

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
  }, [
    appointments,
    therapistAppointments,
    isTherapistView,
    selectedTherapist,
    selectedPatient,
  ]);

  const handleAppointmentCreate = async (appointmentData: AppointmentData) => {
    if (!selectedTherapist || !selectedSlot) return;

    try {
      // Determina quale paziente usare in base alla vista
      const currentPatient = isTherapistView ? selectedPatient : patient;

      if (!currentPatient) {
        throw new Error("Dati paziente mancanti");
      }

      const planTherapyId = appointmentData.planTherapy?.planTherapyId;
      if (!planTherapyId) {
        throw new Error("Piano terapeutico non trovato");
      }

      const appointmentDateTime =
        selectedSlot.date.toISOString().split("T")[0] +
        " " +
        selectedSlot.time +
        ":00";

      let result;
      let appointmentsCreated = 1;
      let weeklyLimitExceeded: any[] = [];

      if (appointmentData.isRecurring) {
        const dayOfWeek = selectedSlot.date.getDay() || 7;
        const startTime = selectedSlot.time;
        const validFrom = selectedSlot.date.toISOString().split("T")[0];

        const patternRequest = {
          planTherapyId,
          therapistId: selectedTherapist.id,
          dayOfWeek,
          startTime,
          durationMinutes: appointmentData.duration,
          validFrom,
          // validTo verrà ricavato dal backend dal piano terapeutico
        };

        const patternResult = await therapyAPI.createPattern(patternRequest);
        result = { appointmentId: patternResult.data.patternId };
        appointmentsCreated = patternResult.appointmentsCreated;
        weeklyLimitExceeded = patternResult.weeklyLimitExceeded || [];

        if (patternResult.conflicts && patternResult.conflicts.length > 0) {
          showInfo(
            "Conflitti rilevati",
            `${patternResult.conflicts.length} appuntamenti non sono stati creati a causa di conflitti con altri appuntamenti.`
          );
        }
      } else {
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

      await reloadCurrentVisibleAppointments();
      setRefreshKey((prev) => prev + 1);
      setIsModalOpen(false);
      setSelectedSlot(null);

      if (appointmentData.isRecurring) {
        showSuccess(
          "Pattern ricorrente creato",
          `${appointmentsCreated} appuntamenti sono stati creati con successo`
        );
      } else {
        showSuccess(
          "Appuntamento creato",
          "L'appuntamento è stato creato con successo"
        );
      }

      if (weeklyLimitExceeded.length > 0) {
        console.warn("Limite settimanale superato:", weeklyLimitExceeded);
        showInfo(
          "Attenzione limite settimanale",
          "Il limite di ore settimanali del terapista potrebbe essere stato superato"
        );
      }
    } catch (err) {
      console.error("Errore nella creazione dell'appuntamento:", err);
      setRefreshKey((prev) => prev + 1);

      if (err instanceof Error && "conflict" in err) {
        const conflict = (err as any).conflict;

        let conflictMessage = "";
        let conflictTitle = "Conflitto appuntamento";

        if (conflict?.type === "same_plan_therapy") {
          conflictTitle = "Conflitto terapia specifica";
          conflictMessage =
            conflict.message ||
            `Esiste già un appuntamento di ${conflict.treatmentType} per ${conflict.patientName} in data ${conflict.existingAppointmentDate} alle ore ${conflict.existingAppointmentTime} con ${conflict.existingTherapistName}`;
        } else if (conflict?.type === "same_treatment_type") {
          conflictTitle = "Conflitto tipologia trattamento";
          conflictMessage =
            conflict.message ||
            `Esiste già un appuntamento di ${conflict.treatmentType} per ${conflict.patientName} in data ${conflict.existingAppointmentDate}`;
        } else if (conflict?.existingAppointmentInfo) {
          conflictMessage = `Il terapista ha già un appuntamento in questo orario con ${
            conflict.existingAppointmentInfo.patientName || "un altro paziente"
          }`;
        } else {
          conflictMessage = conflict?.message || "Conflitto rilevato";
        }

        showError(conflictTitle, conflictMessage);
      } else {
        const errorMessage =
          err instanceof Error ? err.message : "Errore sconosciuto";
        showError(
          "Errore creazione",
          `Non è stato possibile creare l'appuntamento: ${errorMessage}`
        );
      }
    } finally {
      setIsModalOpen(false);
      setSelectedSlot(null);
    }
  };

  const handlePrivateAppointmentCreate = async (
    appointmentData: PrivateAppointmentData
  ) => {
    if (!selectedTherapist || !selectedSlot) return;

    try {
      // Determina quale paziente usare in base alla vista
      const currentPatient = isTherapistView ? selectedPatient : patient;

      if (!currentPatient) {
        throw new Error("Dati paziente mancanti");
      }

      const appointmentDateTime =
        selectedSlot.date.toISOString().split("T")[0] +
        " " +
        selectedSlot.time +
        ":00";

      if (appointmentData.isRecurring) {
        // Crea ciclo privato ricorrente
        const dayOfWeek = selectedSlot.date.getDay() || 7;
        const request = {
          patientId: currentPatient.id,
          therapistId: selectedTherapist.id,
          treatmentTypeId: appointmentData.treatmentTypeId,
          dayOfWeek,
          startTime: selectedSlot.time,
          durationMinutes: appointmentData.duration,
          notes: appointmentData.notes,
        };

        const result = await therapyAPI.createPrivateCycle(request);

        if (result.conflicts && result.conflicts.length > 0) {
          showInfo(
            "Conflitti rilevati",
            `${result.conflicts.length} appuntamenti non sono stati creati a causa di conflitti.`
          );
        }

        showSuccess(
          "Ciclo privato creato",
          `${result.appointmentsCreated} appuntamenti privati creati per il mese corrente`
        );
      } else {
        // Crea singolo appuntamento privato
        const request = {
          patientId: currentPatient.id,
          therapistId: selectedTherapist.id,
          treatmentTypeId: appointmentData.treatmentTypeId,
          appointmentDateTime,
          durationMinutes: appointmentData.duration,
          notes: appointmentData.notes,
        };

        await therapyAPI.createPrivateAppointment(request);
        showSuccess(
          "Appuntamento privato creato",
          "L'appuntamento privato è stato creato con successo"
        );
      }

      // Ricarica appuntamenti per il range visibile
      await reloadCurrentVisibleAppointments();

      // Forza anche un refresh immediato con un piccolo delay
      setRefreshKey((prev) => prev + 1);

      setIsPrivateModalOpen(false);
      setSelectedSlot(null);
    } catch (err) {
      console.error("Errore nella creazione dell'appuntamento privato:", err);
      setRefreshKey((prev) => prev + 1);

      if (err instanceof Error && "conflict" in err) {
        const conflict = (err as any).conflict;
        let conflictMessage = conflict?.message || "Conflitto rilevato";
        showError("Conflitto appuntamento", conflictMessage);
      } else {
        const errorMessage =
          err instanceof Error ? err.message : "Errore sconosciuto";
        showError(
          "Errore creazione",
          `Non è stato possibile creare l'appuntamento privato: ${errorMessage}`
        );
      }
    } finally {
      setIsPrivateModalOpen(false);
      setSelectedSlot(null);
    }
  };

  const handleSlotClick = (date: Date, time: string) => {
    setSelectedSlot({ date, time });

    // Nella vista terapista, dobbiamo prima verificare che sia selezionato un paziente
    if (isTherapistView && !selectedPatient) {
      showError(
        "Paziente non selezionato",
        "Seleziona prima un paziente per creare un appuntamento"
      );
      return;
    }

    if (isPrivateMode) {
      // Nella vista terapista, il treatmentType è automaticamente quello del terapista
      // Nella vista normale, deve essere selezionato dal TherapistSelector
      if (!isTherapistView && !selectedTreatmentType) {
        showError(
          "Tipo di trattamento mancante",
          "Seleziona prima un tipo di trattamento per creare un appuntamento privato"
        );
        return;
      }
      setIsPrivateModalOpen(true);
    } else {
      setIsModalOpen(true);
    }
  };

  const handleAppointmentClick = (appointmentId: string) => {
    const appointment = combinedAppointments.find(
      (apt) => apt.id.toString() === appointmentId
    );
    if (appointment) {
      setSelectedAppointment(appointment);

      // Se il terapista è assente, apri la modale di sostituzione
      if (appointment.status === "therapist_absent") {
        setIsSubstitutionModalOpen(true);
      } else {
        // Altrimenti apri la modale di modifica normale
        setIsEditModalOpen(true);
      }
    }
  };

  const handleAppointmentUpdate = async (appointmentId: string) => {
    await reloadCurrentVisibleAppointments();
  };

  const handleAppointmentDelete = async (appointmentId: string) => {
    await handleAppointmentUpdate(appointmentId);
  };

  const handleTherapistSubstitution = async (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
  }) => {
    try {
      console.log("🔄 Sostituzione terapista completata:", substitutionData);

      // La modale ha già chiamato l'API, qui gestiamo solo l'aggiornamento della UI
      await reloadCurrentVisibleAppointments();
      setRefreshKey((prev) => prev + 1);

      showSuccess(
        "Terapista sostituito",
        "Il terapista è stato sostituito con successo"
      );
    } catch (err) {
      console.error("Errore nella sostituzione del terapista:", err);

      const errorMessage =
        err instanceof Error ? err.message : "Errore sconosciuto";
      showError(
        "Errore sostituzione",
        `Non è stato possibile sostituire il terapista: ${errorMessage}`
      );
    }
  };

  const handleAppointmentMove = async (
    appointmentId: string,
    newDate: Date,
    newTime: string,
    eventData?: any
  ) => {
    try {
      const numericId = parseInt(appointmentId);
      if (isNaN(numericId)) {
        throw new Error("ID appuntamento non valido");
      }

      const appointment = combinedAppointments.find(
        (apt) => apt.id === numericId
      );
      if (!appointment) {
        throw new Error("Appuntamento non trovato");
      }

      const newDateTime =
        newDate.toISOString().split("T")[0] + " " + newTime + ":00";

      const request = {
        appointmentId: numericId,
        therapistId: appointment.therapist?.id || 0,
        appointmentDateTime: newDateTime,
        durationMinutes: appointment.duration,
        notes: appointment.notes,
      };

      console.log("🔄 Spostamento appuntamento:", {
        appointmentId: numericId,
        originalTherapist: appointment.therapist?.id,
        newDateTime,
      });

      await therapyAPI.updateAppointment(request);

      setAppointments((prev) =>
        prev.map((apt) =>
          apt.id === numericId ? { ...apt, datetime: newDateTime } : apt
        )
      );

      if (
        selectedTherapist &&
        appointment.therapist?.id === selectedTherapist.id
      ) {
        setTherapistAppointments((prev) =>
          prev.map((apt) =>
            apt.id === numericId ? { ...apt, datetime: newDateTime } : apt
          )
        );
      }

      showSuccess(
        "Appuntamento spostato",
        "L'appuntamento è stato spostato con successo"
      );
    } catch (err) {
      console.error("Errore nello spostamento dell'appuntamento:", err);
      setRefreshKey((prev) => prev + 1);

      if (err instanceof Error && "conflict" in err) {
        const conflict = (err as any).conflict;

        let conflictMessage = "";
        let conflictTitle = "Conflitto appuntamento";

        if (conflict?.type === "same_plan_therapy") {
          conflictTitle = "Conflitto terapia specifica";
          conflictMessage =
            conflict.message ||
            `Esiste già un appuntamento di ${conflict.treatmentType} per ${conflict.patientName} in data ${conflict.existingAppointmentDate} alle ore ${conflict.existingAppointmentTime} con ${conflict.existingTherapistName}`;
        } else if (conflict?.type === "same_treatment_type") {
          conflictTitle = "Conflitto tipologia trattamento";
          conflictMessage =
            conflict.message ||
            `Esiste già un appuntamento di ${conflict.treatmentType} per ${conflict.patientName} in data ${conflict.existingAppointmentDate}`;
        } else if (conflict?.existingAppointmentInfo) {
          conflictTitle = "Conflitto terapista";
          conflictMessage = `Il terapista ha già un appuntamento in questo orario con ${
            conflict.existingAppointmentInfo.patientName || "un altro paziente"
          }`;
        } else {
          conflictMessage =
            conflict?.message || "Conflitto rilevato durante lo spostamento";
        }

        showError(conflictTitle, conflictMessage);
      } else {
        const errorMessage =
          err instanceof Error ? err.message : "Errore sconosciuto";
        showError(
          "Errore spostamento",
          `Non è stato possibile spostare l'appuntamento: ${errorMessage}`
        );
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

  if (error && !isPrivateMode && !patient?.hasActiveTherapeuticPlans) {
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
          <div className="flex items-center justify-between">
            <div>
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
                      {selectedTherapist.specialization} •{" "}
                      {selectedTherapist.email}
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

            {/* Private Mode Toggle - Mostra quando c'è un paziente disponibile */}
            {((isTherapistView && selectedPatient) ||
              (!isTherapistView && patient)) && (
              <div className="flex flex-col items-end space-y-2">
                <div className="flex items-center space-x-3">
                  <Label
                    htmlFor="private-mode"
                    className={`flex items-center gap-2 ${
                      canCreateNormalAppointments
                        ? "cursor-pointer"
                        : "cursor-not-allowed opacity-50"
                    }`}
                  >
                    {isPrivateMode ? (
                      <LockOpen className="h-4 w-4 text-purple-600" />
                    ) : (
                      <Lock className="h-4 w-4 text-gray-400" />
                    )}
                    <span className="text-sm font-medium">
                      Modalità Privata
                    </span>
                  </Label>
                  <Switch
                    id="private-mode"
                    checked={isPrivateMode}
                    onCheckedChange={(checked) => {
                      if (canCreateNormalAppointments || checked) {
                        setIsPrivateMode(checked);
                      }
                    }}
                    disabled={!canCreateNormalAppointments && !isPrivateMode}
                    className="data-[state=checked]:bg-purple-600"
                  />
                </div>
                {planTherapyCheckMessage && !canCreateNormalAppointments && (
                  <div className="text-xs text-orange-600 max-w-sm text-right">
                    <span className="font-medium">
                      Solo appuntamenti privati:
                    </span>{" "}
                    {planTherapyCheckMessage}
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Mostra il selettore terapista solo se non siamo in modalità terapista */}
        {!isTherapistView && (
          <div className="mb-8">
            <TherapistSelector
              selectedTherapist={selectedTherapist}
              onTherapistSelect={setSelectedTherapist}
              patientId={patient?.id}
              isPrivateMode={isPrivateMode}
              onTreatmentTypeSelect={setSelectedTreatmentType}
            />
          </div>
        )}

        {/* Mostra il selettore paziente solo nella vista terapista */}
        {isTherapistView && (
          <div className="mb-8">
            <PatientSelector
              selectedPatient={selectedPatient}
              onPatientSelect={setSelectedPatient}
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
          hidePatientCalendar={isTherapistView && !selectedPatient}
          mode={isTherapistView ? "therapist" : "patient"}
          currentPatientId={isTherapistView ? selectedPatient?.id : patient?.id}
          onDateChange={handleDateChange}
          onVisibleRangeChange={handleVisibleRangeChange}
          isPrivateMode={isPrivateMode}
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
          patient={isTherapistView ? selectedPatient : patient}
        />

        <PrivateAppointmentModal
          isOpen={isPrivateModalOpen}
          onClose={() => {
            setIsPrivateModalOpen(false);
            setSelectedSlot(null);
          }}
          onConfirm={handlePrivateAppointmentCreate}
          selectedSlot={selectedSlot}
          selectedTherapist={selectedTherapist}
          patient={isTherapistView ? selectedPatient : patient}
          treatmentType={selectedTreatmentType}
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

        <TherapistSubstitutionModal
          isOpen={isSubstitutionModalOpen}
          onClose={() => {
            setIsSubstitutionModalOpen(false);
            setSelectedAppointment(null);
          }}
          appointment={selectedAppointment}
          therapists={therapists}
          onConfirm={handleTherapistSubstitution}
        />
      </div>
    </div>
  );
};

export default Index;
