import React, { useState, useEffect } from "react";
import {
  X,
  Calendar,
  Clock,
  User,
  Edit,
  Trash2,
  Save,
  Repeat,
  Lock,
  Stethoscope,
  UserX,
  MoreVertical,
  Users,
  Plus,
  AlertCircle,
} from "lucide-react";
import { Appointment, Patient, Therapist } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { useToast } from "@/components/Toast";
import { TherapistSubstitutionModal } from "./TherapistSubstitutionModal";

interface AppointmentEditModalProps {
  isOpen: boolean;
  onClose: () => void;
  appointment: Appointment | null;
  therapists: Therapist[];
  onAppointmentUpdate: (appointmentId: string, refresh: boolean) => void;
  onAppointmentDelete: (appointmentId: string) => void;
  onTherapistSubstitution?: (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
  }) => void;
  isTherapistView?: boolean;
  onAddTherapyInSlot?: (appointment: Appointment) => void;
  isABARegime?: boolean;
  patient?: Patient | null;
  onSetGroupAppointment?: (appointmentId: number) => Promise<void>;
}

export const AppointmentEditModal: React.FC<AppointmentEditModalProps> = ({
  isOpen,
  onClose,
  appointment,
  therapists,
  onAppointmentUpdate,
  onAppointmentDelete,
  onTherapistSubstitution,
  isTherapistView = false,
  onAddTherapyInSlot,
  isABARegime,
  patient,
  onSetGroupAppointment,
}) => {
  // console.log("appointment IN MODAL", appointment);
  const [isEditing, setIsEditing] = useState(false);
  const [loading, setLoading] = useState(false);
  const [deleteAllFuture, setDeleteAllFuture] = useState(false);
  const [isSubstitutionMode, setIsSubstitutionMode] = useState(false);
  const [showActionMenu, setShowActionMenu] = useState(false);
  const [formData, setFormData] = useState({
    therapistId: 0,
    date: "",
    time: "",
    duration: 60,
    notes: "",
    id_setting: undefined as number | undefined,
  });

  // State per gruppi
  const [canAddToGroup, setCanAddToGroup] = useState(false);
  const [groupPatients, setGroupPatients] = useState<
    Array<{ id: number; name: string; appointmentId: number }>
  >([]);
  const [selectedPatientId, setSelectedPatientId] = useState<number | null>(
    null
  );
  const [currentPatientAppointment, setCurrentPatientAppointment] =
    useState<Appointment | null>(null);
  const [selectedAppointmentId, setSelectedAppointmentId] = useState<
    number | null
  >(null);
  const [applyToWholeGroup, setApplyToWholeGroup] = useState(true);
  const [loadingPatientDetails, setLoadingPatientDetails] = useState(false);

  // Stati per settings
  const [settings, setSettings] = useState<{ id: number; nome: string }[]>([]);
  const [settingsLoading, setSettingsLoading] = useState(false);
  const [settingsError, setSettingsError] = useState<string | null>(null);

  const { showSuccess, showError } = useToast();

  // Determina se è un appuntamento privato
  const isPrivateAppointment =
    appointment?.appointmentSource === "private" || appointment?.isPrivate;

  // Determina se è un appuntamento di gruppo
  const isGroupAppointment =
    appointment?.groupSessionId !== null &&
    appointment?.groupSessionId !== undefined &&
    appointment?.groupPatients &&
    appointment.groupPatients.length > 0;

  // console.log("appointment", appointment);

  // Funzione per caricare i settings
  const loadSettings = async () => {
    try {
      setSettingsLoading(true);
      setSettingsError(null);

      // Carica tutti i settings (potrebbero essere filtrati per regime se necessario)
      const result = await therapyAPI.getSettings();
      setSettings(result);
    } catch (err) {
      const errorMessage =
        err instanceof Error
          ? err.message
          : "Errore nel caricamento dei settings";
      setSettingsError(errorMessage);
      console.error("Errore caricamento settings:", err);
    } finally {
      setSettingsLoading(false);
    }
  };

  // Funzione per caricare i dettagli dell'appuntamento del paziente selezionato
  const loadPatientAppointmentDetails = async (appointmentId: number) => {
    // console.log("📥 DEBUG - Caricamento dettagli appuntamento:", appointmentId);
    setLoadingPatientDetails(true);
    try {
      const details = await therapyAPI.getAppointmentDetails(appointmentId);
      // console.log("📥 DEBUG - Dettagli appuntamento caricati:", details);
      setCurrentPatientAppointment(details);

      // Aggiorna il form con i dati del paziente selezionato
      const [datePart, timePart] = details.datetime.split(" ");
      const [hours, minutes] = timePart.split(":");

      setFormData({
        therapistId: details.therapist?.id || 0,
        date: datePart,
        time: `${hours}:${minutes}`,
        duration: details.duration,
        notes: details.notes || "",
        id_setting: details.id_setting,
      });
    } catch (error) {
      console.error("Errore caricamento dettagli appuntamento:", error);
      showError("Errore", "Impossibile caricare i dettagli dell'appuntamento");
    } finally {
      setLoadingPatientDetails(false);
    }
  };

  useEffect(() => {
    if (appointment) {
      // console.log("📝 AppointmentEditModal ricevuto appointment:", appointment);
      // console.log("📝 appointment.groupSessionId:", appointment.groupSessionId);
      // console.log("📝 appointment.groupPatients:", appointment.groupPatients);

      if (appointment.groupPatients && appointment.groupPatients.length > 0) {
        setGroupPatients(appointment.groupPatients);

        // Se è un gruppo, seleziona il primo paziente per default
        if (appointment.groupPatients.length > 1) {
          const firstPatient = appointment.groupPatients[0];
          setSelectedPatientId(firstPatient.id);
          setSelectedAppointmentId(firstPatient.appointmentId);
          loadPatientAppointmentDetails(firstPatient.appointmentId);
        } else {
          // Gruppo con un solo paziente
          setCurrentPatientAppointment(appointment);
          setSelectedPatientId(appointment.patient?.id || null);
          setSelectedAppointmentId(appointment.id);
        }
      } else {
        // Appuntamento singolo
        setCurrentPatientAppointment(appointment);
        setSelectedPatientId(appointment.patient?.id || null);
        setSelectedAppointmentId(appointment.id);
        setGroupPatients([]);
      }

      // Imposta i dati del form per appuntamenti singoli
      if (!appointment.groupPatients || appointment.groupPatients.length <= 1) {
        const [datePart, timePart] = appointment.datetime.split(" ");
        const [hours, minutes] = timePart.split(":");

        setFormData({
          therapistId: appointment.therapist?.id || 0,
          date: datePart,
          time: `${hours}:${minutes}`,
          duration: appointment.duration,
          notes: appointment.notes || "",
          id_setting: appointment.id_setting,
        });
      }

      // Carica i settings quando si apre la modale
      loadSettings();

      if (patient) {
        checkCanAddToGroup();
      }

      setIsSubstitutionMode(false);
      setShowActionMenu(false);
      setApplyToWholeGroup(true);
    }
  }, [appointment]);

  const checkCanAddToGroup = async () => {
    if (!appointment || !patient) {
      setCanAddToGroup(false);
      return;
    }
    // console.log(appointment);
    // Verifica condizioni base
    if (
      appointment.groupSessionId !== null &&
      !isABARegime &&
      !isPrivateAppointment
    ) {
      try {
        // Chiama l'API per verificare se il paziente è già nel gruppo
        const isInGroup = await therapyAPI.checkPatientInGroup(
          appointment.groupSessionId,
          patient.id
        );

        setCanAddToGroup(!isInGroup);
      } catch (error) {
        console.error("Errore verifica gruppo:", error);
        setCanAddToGroup(false);
      }
    } else {
      setCanAddToGroup(false);
    }
  };

  const handleAddToGroup = async () => {
    if (!appointment || !patient) return;

    try {
      setLoading(true);

      // Recupera il plan therapy per il paziente corrente
      const planTherapyData = await therapyAPI.getPlanTherapyForTherapist(
        patient.id,
        appointment.therapist.id
      );

      // Usa createAppointment passando il groupSessionId esistente
      const request = {
        planTherapyId: planTherapyData.planTherapyId,
        treatmentTypeId: planTherapyData.treatmentTypeId,
        therapistId: appointment.therapist.id,
        appointmentDateTime: appointment.datetime,
        durationMinutes: appointment.duration,
        notes: `Aggiunto al gruppo sessione ${appointment.groupSessionId}`,
        isGroup: true,
        groupSessionId: appointment.groupSessionId,
        id_setting: appointment.id_setting, // Usa lo stesso setting dell'appuntamento di gruppo esistente
      };

      await therapyAPI.createAppointment(request);

      showSuccess(
        "Paziente aggiunto",
        "Il paziente è stato aggiunto al gruppo"
      );
      onAppointmentUpdate(appointment.id.toString(), true);
      onClose();
    } catch (error) {
      console.error("Errore aggiunta al gruppo:", error);
      const errorMessage =
        error instanceof Error ? error.message : "Errore sconosciuto";
      showError(
        "Errore",
        `Non è stato possibile aggiungere il paziente al gruppo: ${errorMessage}`
      );
    } finally {
      setLoading(false);
    }
  };

  const handleTherapistSubstitution = async (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
  }) => {
    if (onTherapistSubstitution) {
      await onTherapistSubstitution(substitutionData);
      setIsSubstitutionMode(false);
      onClose();
    }
  };

  const handleSave = async () => {
    if (!appointment || !selectedAppointmentId) return;

    // Validazione setting obbligatorio
    if (!formData.id_setting) {
      showError("Campo obbligatorio", "Seleziona un setting per l'appuntamento");
      return;
    }

    // Debug: stampa l'ID dell'appuntamento del paziente selezionato
    // console.log("🔍 DEBUG - Appuntamento da modificare:");
    // console.log("  - ID appuntamento principale:", appointment.id);
    // console.log(
    //   "  - ID appuntamento paziente selezionato:",
    //   selectedAppointmentId
    // );
    // console.log(
    //   "  - Paziente selezionato:",
    //   currentPatientAppointment?.patient?.name
    // );
    // console.log("  - applyToGroup:", applyToWholeGroup && isGroupAppointment);

    setLoading(true);
    try {
      const appointmentDateTime = `${formData.date} ${formData.time}:00`;

      const result = await therapyAPI.updateAppointment({
        appointmentId: selectedAppointmentId, // Usa l'ID dell'appuntamento del paziente selezionato
        therapistId: formData.therapistId,
        appointmentDateTime,
        durationMinutes: formData.duration,
        notes: formData.notes,
        applyToGroup: applyToWholeGroup && isGroupAppointment,
        id_setting: formData.id_setting,
      });

      showSuccess(
        "Appuntamento aggiornato",
        applyToWholeGroup && isGroupAppointment
          ? `Aggiornati tutti gli appuntamenti del gruppo`
          : "L'appuntamento è stato modificato con successo"
      );

      onAppointmentUpdate(appointment.id.toString(), true);
      setIsEditing(false);
      onClose();
    } catch (error) {
      console.error("Errore aggiornamento appuntamento:", error);

      if (error instanceof Error && "conflict" in error) {
        const conflict = (error as any).conflict;

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
            conflict?.message || "Conflitto rilevato durante l'aggiornamento";
        }

        showError(conflictTitle, conflictMessage);
      } else {
        const errorMessage =
          error instanceof Error ? error.message : "Errore sconosciuto";
        showError(
          "Errore aggiornamento",
          `Non è stato possibile aggiornare l'appuntamento: ${errorMessage}`
        );
      }
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!appointment || !selectedAppointmentId) return;

    let confirmMessage = "Sei sicuro di voler eliminare questo appuntamento?";

    if (isGroupAppointment && applyToWholeGroup) {
      confirmMessage = `Sei sicuro di voler eliminare tutti i ${appointment.groupPatients?.length} appuntamenti del gruppo?`;
    } else if (isGroupAppointment && !applyToWholeGroup) {
      confirmMessage = `Sei sicuro di voler eliminare solo l'appuntamento di ${currentPatientAppointment.patient?.name}?`;
    } else if (deleteAllFuture) {
      if (appointment.isRecurring && appointment.patternId) {
        confirmMessage =
          "Sei sicuro di voler eliminare questo appuntamento e tutti gli appuntamenti futuri della serie ricorrente?";
      } else if (appointment.isPrivate && appointment.privateCycleId) {
        confirmMessage =
          "Sei sicuro di voler eliminare tutti gli appuntamenti del ciclo privato?";
      }
    }

    if (!confirm(confirmMessage)) {
      return;
    }

    setLoading(true);
    try {
      if (deleteAllFuture) {
        if (appointment.isRecurring && appointment.patternId) {
          const appointmentDate = appointment.datetime.split(" ")[0];
          await therapyAPI.deletePatternAppointments({
            patternId: appointment.patternId,
            fromDate: appointmentDate,
          });
        } else if (appointment.isPrivate && appointment.privateCycleId) {
          await therapyAPI.deletePrivateCycleAppointments(
            appointment.privateCycleId
          );
        }
      } else {
        await therapyAPI.deleteAppointment(selectedAppointmentId, {
          applyToGroup: applyToWholeGroup && isGroupAppointment,
        });
      }

      // Mostra messaggio di successo diverso per i gruppi
      if (isGroupAppointment && applyToWholeGroup) {
        showSuccess(
          "Gruppo eliminato",
          `Tutti i ${appointment.groupPatients?.length} appuntamenti del gruppo sono stati eliminati`
        );
      }

      onAppointmentDelete(appointment.id.toString());
      onClose();
    } catch (error) {
      console.error("Errore eliminazione appuntamento:", error);
      alert("Errore durante l'eliminazione dell'appuntamento");
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("it-IT", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  };

  const formatTime = (dateString: string) => {
    const [, timePart] = dateString.split(" ");
    const [hours, minutes] = timePart.split(":");
    return `${hours}:${minutes}`;
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case "confirmed":
      case "scheduled":
        return "bg-green-100 text-green-800";
      case "pending":
        return "bg-yellow-100 text-yellow-800";
      case "cancelled":
        return "bg-red-100 text-red-800";
      case "completed":
        return "bg-blue-100 text-blue-800";
      default:
        return "bg-gray-100 text-gray-800";
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case "confirmed":
        return "Confermato";
      case "scheduled":
        return "Programmato";
      case "pending":
        return "In attesa";
      case "cancelled":
        return "Annullato";
      case "completed":
        return "Completato";
      case "absent_not_justified":
        return "Assenza non giustificata";
      case "absent_justified":
        return "Assenza giustificata";
      default:
        return "Sconosciuto";
    }
  };

  // Verifica se l'appuntamento corrente può essere modificato
  const canModifyAppointment =
    currentPatientAppointment?.status === "scheduled";

  if (!isOpen || !appointment) return null;

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50">
          <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
            {isEditing ? "Modifica Appuntamento" : "Dettagli Appuntamento"}
            {/* Badge container */}
            <div className="flex items-center gap-2">
              {isPrivateAppointment && (
                <span className="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                  <Lock className="w-3 h-3" />
                  Privato
                </span>
              )}
              {isGroupAppointment && (
                <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                  <Users className="w-3 h-3" />
                  Gruppo
                </span>
              )}
            </div>
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all duration-200"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto">
          {!isEditing ? (
            /* Vista dettagli compatta */
            <div className="space-y-4">
              {/* Status e Info in una riga */}
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusColor(
                      currentPatientAppointment?.status || appointment.status
                    )}`}
                  >
                    {getStatusText(
                      currentPatientAppointment?.status || appointment.status
                    )}
                  </span>
                  {(appointment.isRecurring || appointment.privateCycleId) && (
                    <span className="flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                      <Repeat className="w-3 h-3" />
                      Ricorrente
                    </span>
                  )}

                  {appointment.groupSessionId && (
                    <span className="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-mono">
                      GroupID: {appointment.groupSessionId}
                    </span>
                  )}
                </div>
              </div>

              {/* Selezione paziente per gruppi */}
              {isGroupAppointment &&
                appointment.groupPatients &&
                appointment.groupPatients.length > 1 && (
                  <div className="space-y-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div className="flex items-center justify-between">
                      <h3 className="font-medium text-blue-900">
                        Appuntamento di gruppo
                      </h3>
                      <span className="text-sm text-blue-600">
                        {appointment.groupPatients.length} pazienti
                      </span>
                    </div>

                    {/* Selector paziente */}
                    <div className="space-y-2">
                      <label className="text-sm font-medium text-blue-800">
                        Visualizza dettagli paziente:
                      </label>
                      <select
                        value={selectedPatientId || ""}
                        onChange={(e) => {
                          const patientId = parseInt(e.target.value);
                          setSelectedPatientId(patientId);

                          // Trova l'appointmentId per questo paziente
                          const patientData = appointment.groupPatients?.find(
                            (p) => p.id === patientId
                          );
                          if (patientData) {
                            // console.log(
                            //   "🔄 DEBUG - Cambio paziente selezionato:"
                            // );
                            // console.log(
                            //   "  - Paziente selezionato:",
                            //   patientData.name
                            // );
                            // console.log(
                            //   "  - ID appuntamento paziente:",
                            //   patientData.appointmentId
                            // );
                            // console.log(
                            //   "  - ID appuntamento principale:",
                            //   appointment.id
                            // );

                            // Aggiorna immediatamente l'ID dell'appuntamento selezionato
                            setSelectedAppointmentId(patientData.appointmentId);

                            loadPatientAppointmentDetails(
                              patientData.appointmentId
                            );
                          }
                        }}
                        className="w-full px-3 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        disabled={loadingPatientDetails}
                      >
                        {appointment.groupPatients.map((patient) => (
                          <option key={patient.id} value={patient.id}>
                            {patient.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* Toggle applica a tutto il gruppo */}
                    <div className="flex items-center justify-between p-3 bg-white rounded-lg">
                      <label className="flex items-center gap-3 cursor-pointer flex-1">
                        <input
                          type="checkbox"
                          checked={applyToWholeGroup}
                          onChange={(e) =>
                            setApplyToWholeGroup(e.target.checked)
                          }
                          className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <div>
                          <span className="text-sm font-medium text-gray-700">
                            Applica azioni a tutto il gruppo
                          </span>
                          <p className="text-xs text-gray-500">
                            {applyToWholeGroup
                              ? `Le modifiche verranno applicate a tutti i ${appointment.groupPatients.length} pazienti`
                              : `Le modifiche verranno applicate solo a ${currentPatientAppointment?.patient?.name}`}
                          </p>
                        </div>
                      </label>
                      {applyToWholeGroup ? (
                        <Users className="w-5 h-5 text-blue-600" />
                      ) : (
                        <User className="w-5 h-5 text-gray-600" />
                      )}
                    </div>

                    {/* Avviso stato appuntamento */}
                    {currentPatientAppointment && !canModifyAppointment && (
                      <div className="p-3 bg-orange-50 rounded-lg border border-orange-200">
                        <div className="flex items-center gap-2">
                          <AlertCircle className="w-4 h-4 text-orange-600" />
                          <div className="text-sm text-orange-700">
                            <p className="font-medium">
                              Appuntamento non modificabile
                            </p>
                            <p className="text-xs">
                              L'appuntamento di{" "}
                              {currentPatientAppointment.patient?.name} è in
                              stato "
                              {getStatusText(currentPatientAppointment.status)}"
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                )}

              {/* Info principali in griglia compatta */}
              <div className="grid grid-cols-2 gap-3">
                {/* Paziente/i */}
                <div className="col-span-2 p-3 bg-blue-50 rounded-lg">
                  {loadingPatientDetails ? (
                    <div className="flex items-center gap-2">
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                      <span className="text-sm text-blue-600">
                        Caricamento...
                      </span>
                    </div>
                  ) : (
                    <>
                      {!isGroupAppointment ? (
                        <div className="flex items-center gap-3">
                          <User className="w-5 h-5 text-blue-600 flex-shrink-0" />
                          <div className="min-w-0">
                            <div className="font-semibold text-gray-900 truncate">
                              {currentPatientAppointment?.patient?.name ||
                                "Paziente sconosciuto"}
                            </div>
                            <div className="text-xs text-blue-600">
                              Paziente
                            </div>
                          </div>
                        </div>
                      ) : (
                        <div>
                          <div className="flex items-center gap-2 mb-2">
                            <Users className="w-5 h-5 text-blue-600" />
                            <span className="text-xs text-blue-600 font-semibold">
                              Dettagli per:{" "}
                              {currentPatientAppointment?.patient?.name}
                            </span>
                          </div>
                          <div className="text-xs text-gray-600">
                            Stato:{" "}
                            <span
                              className={`font-medium ${
                                currentPatientAppointment?.status ===
                                "scheduled"
                                  ? "text-green-600"
                                  : "text-orange-600"
                              }`}
                            >
                              {getStatusText(
                                currentPatientAppointment?.status || ""
                              )}
                            </span>
                          </div>
                        </div>
                      )}
                    </>
                  )}
                </div>

                {/* Terapista */}
                <div className="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                  <User className="w-5 h-5 text-green-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 text-sm truncate">
                      {currentPatientAppointment?.therapist?.name ||
                        appointment.therapist?.name ||
                        "N/A"}
                    </div>
                    <div className="text-xs text-green-600">Terapista</div>
                  </div>
                </div>

                {/* Trattamento */}
                {(currentPatientAppointment?.treatmentType ||
                  appointment.treatmentType) && (
                  <div className="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg">
                    <Stethoscope className="w-5 h-5 text-indigo-600 flex-shrink-0" />
                    <div className="min-w-0">
                      <div className="font-semibold text-gray-900 text-sm truncate">
                        {currentPatientAppointment?.treatmentType ||
                          appointment.treatmentType}
                      </div>
                      <div className="text-xs text-indigo-600">Trattamento</div>
                    </div>
                  </div>
                )}

                {/* Setting */}
                {(currentPatientAppointment?.settingName ||
                  appointment.settingName) && (
                  <div className="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                    <svg
                      className="w-5 h-5 text-amber-600 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                      />
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                    </svg>
                    <div className="min-w-0">
                      <div className="font-semibold text-gray-900 text-sm truncate">
                        {currentPatientAppointment?.settingName ||
                          appointment.settingName}
                      </div>
                      <div className="text-xs text-amber-600">Setting</div>
                    </div>
                  </div>
                )}

                {/* Data e ora */}
                <div className="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                  <Calendar className="w-5 h-5 text-purple-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 text-sm">
                      {new Date(
                        currentPatientAppointment?.datetime ||
                          appointment.datetime
                      ).toLocaleDateString("it-IT", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                      })}
                    </div>
                    <div className="text-xs text-purple-600">Data</div>
                  </div>
                </div>

                <div className="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
                  <Clock className="w-5 h-5 text-orange-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 text-sm">
                      {formatTime(
                        currentPatientAppointment?.datetime ||
                          appointment.datetime
                      )}{" "}
                      •{" "}
                      {currentPatientAppointment?.duration ||
                        appointment.duration}
                      min
                    </div>
                    <div className="text-xs text-orange-600">Orario</div>
                  </div>
                </div>
              </div>

              {/* Note (se presenti) */}
              {(currentPatientAppointment?.notes || appointment.notes) && (
                <div className="p-3 bg-gray-50 rounded-lg">
                  <div className="text-xs font-semibold text-gray-600 mb-1">
                    Note
                  </div>
                  <div className="text-sm text-gray-900">
                    {currentPatientAppointment?.notes || appointment.notes}
                  </div>
                </div>
              )}
            </div>
          ) : (
            /* Vista modifica (rimane simile ma con campi più compatti) */
            <div className="space-y-4">
              {isPrivateAppointment && (
                <div className="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                  <p className="text-sm text-purple-800">
                    ⚠️ Stai modificando un appuntamento privato
                  </p>
                </div>
              )}

              {isGroupAppointment && (
                <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                  <p className="text-sm text-blue-800">
                    {applyToWholeGroup
                      ? `⚠️ Stai modificando tutti i ${appointment.groupPatients?.length} appuntamenti del gruppo`
                      : `⚠️ Stai modificando solo l'appuntamento di ${currentPatientAppointment?.patient?.name}`}
                  </p>
                </div>
              )}

              <input
                hidden
                value={formData.therapistId}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    therapistId: parseInt(e.target.value),
                  })
                }
              />

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Data
                  </label>
                  <input
                    type="date"
                    value={formData.date}
                    onChange={(e) =>
                      setFormData({ ...formData, date: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Orario
                  </label>
                  <input
                    type="time"
                    value={formData.time}
                    onChange={(e) =>
                      setFormData({ ...formData, time: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Durata (minuti)
                </label>
                <select
                  value={formData.duration}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      duration: parseInt(e.target.value),
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value={30}>30 minuti</option>
                  <option value={45}>45 minuti</option>
                  <option value={60}>60 minuti</option>
                  <option value={90}>90 minuti</option>
                  <option value={120}>120 minuti</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Note
                </label>
                <textarea
                  value={formData.notes}
                  onChange={(e) =>
                    setFormData({ ...formData, notes: e.target.value })
                  }
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                  placeholder="Note aggiuntive..."
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Setting <span className="text-red-500">*</span>
                </label>
                {settingsLoading ? (
                  <div className="text-sm text-gray-500">
                    Caricamento settings...
                  </div>
                ) : settingsError ? (
                  <div className="text-sm text-red-500">{settingsError}</div>
                ) : (
                  <select
                    value={formData.id_setting || ""}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        id_setting: e.target.value
                          ? parseInt(e.target.value)
                          : undefined,
                      })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                  >
                    {!formData.id_setting && (
                      <option value="" disabled>
                        Seleziona un setting
                      </option>
                    )}
                    {settings.map((setting) => (
                      <option key={setting.id} value={setting.id}>
                        {setting.nome}
                      </option>
                    ))}
                  </select>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Footer con azioni compatte */}
        <div className="p-4 border-t border-gray-200 bg-gray-50">
          {!isEditing && canModifyAppointment && (
            <>
              {/* Checkbox per cancellazione ricorrenti */}
              {((appointment?.isRecurring && appointment?.patternId) ||
                (appointment?.isPrivate && appointment?.privateCycleId)) &&
                !isGroupAppointment && (
                  <div className="mb-3">
                    <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={deleteAllFuture}
                        onChange={(e) => setDeleteAllFuture(e.target.checked)}
                        className="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500"
                      />
                      <span className="flex items-center gap-1">
                        <Repeat className="h-3 w-3" />
                        {appointment?.isPrivate
                          ? "Cancella tutti gli appuntamenti del ciclo"
                          : "Cancella tutti gli appuntamenti futuri"}
                      </span>
                    </label>
                  </div>
                )}

              {/* Checkbox per rendere di gruppo - NUOVA */}
              {!isGroupAppointment &&
                !isABARegime &&
                !isPrivateAppointment &&
                appointment.appointmentSource === "therapeutic_plan" &&
                onSetGroupAppointment && (
                  <div className="mb-3">
                    <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                      <input
                        type="checkbox"
                        onChange={async (e) => {
                          if (e.target.checked) {
                            await onSetGroupAppointment(appointment.id);
                          }
                        }}
                        className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                      />
                      <span className="flex items-center gap-1">
                        <Users className="h-3 w-3" />
                        Rendi appuntamento di gruppo
                      </span>
                    </label>
                  </div>
                )}

              {/* Azioni principali */}
              <div className="flex items-center justify-between">
                {/* Pulsanti azioni a sinistra */}
                <div className="flex gap-2">
                  {!isTherapistView && (
                    <button
                      onClick={() => setIsEditing(true)}
                      className="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                    >
                      <Edit className="w-4 h-4" />
                      Modifica
                    </button>
                  )}

                  {onTherapistSubstitution && (
                    <button
                      onClick={() => setIsSubstitutionMode(true)}
                      className="inline-flex items-center gap-1.5 px-3 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors"
                    >
                      <UserX className="w-4 h-4" />
                      Sostituisci
                    </button>
                  )}

                  {canAddToGroup && appointment.groupSessionId && (
                    <button
                      onClick={handleAddToGroup}
                      disabled={loading}
                      className="inline-flex items-center gap-1.5 px-3 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50"
                    >
                      <Users className="w-4 h-4" />
                      Aggiungi al gruppo
                    </button>
                  )}

                  {appointment.appointmentSource === "therapeutic_plan" &&
                    onAddTherapyInSlot &&
                    isABARegime && (
                      <button
                        onClick={() => {
                          onClose();
                          onAddTherapyInSlot(appointment);
                        }}
                        className="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors"
                      >
                        <Plus className="w-4 h-4" />
                        Aggiungi terapia
                      </button>
                    )}
                </div>

                {/* Pulsanti a destra */}
                <div className="flex gap-2">
                  <button
                    onClick={handleDelete}
                    disabled={loading}
                    className="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 ml-2"
                  >
                    <Trash2 className="w-4 h-4" />
                    Elimina
                  </button>
                </div>
              </div>
            </>
          )}

          {!isEditing && !canModifyAppointment && (
            <div className="flex items-center justify-between">
              <p className="text-sm text-gray-500 italic">
                Non modificabile (stato:{" "}
                {getStatusText(
                  currentPatientAppointment?.status || appointment.status
                )}
                )
              </p>
              <button
                onClick={onClose}
                className="px-4 py-2 text-gray-600 text-sm hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
              >
                Chiudi
              </button>
            </div>
          )}

          {isEditing && (
            <div className="flex items-center justify-between">
              <button
                onClick={() => setIsEditing(false)}
                className="px-4 py-2 text-gray-600 text-sm hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
              >
                Annulla
              </button>
              <button
                onClick={handleSave}
                disabled={loading}
                className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
              >
                <Save className="w-4 h-4" />
                {loading ? "Salvando..." : "Salva modifiche"}
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Modale per sostituzione terapista */}
      <TherapistSubstitutionModal
        isOpen={isSubstitutionMode}
        onClose={() => setIsSubstitutionMode(false)}
        onConfirm={handleTherapistSubstitution}
        appointment={currentPatientAppointment || appointment}
        therapists={therapists}
      />
    </div>
  );
};
