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
} from "lucide-react";
import { Appointment, Therapist } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { useToast } from "@/components/Toast";
import { TherapistSubstitutionModal } from "./TherapistSubstitutionModal";

interface AppointmentEditModalProps {
  isOpen: boolean;
  onClose: () => void;
  appointment: Appointment | null;
  therapists: Therapist[];
  onAppointmentUpdate: (appointmentId: string) => void;
  onAppointmentDelete: (appointmentId: string) => void;
  onTherapistSubstitution?: (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
  }) => void;
  isTherapistView?: boolean; // Nuova prop per distinguere vista terapista
  onAddTherapyInSlot?: (appointment: Appointment) => void;
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
}) => {
  const [isEditing, setIsEditing] = useState(false);
  const [loading, setLoading] = useState(false);
  const [deleteAllFuture, setDeleteAllFuture] = useState(false);
  const [isSubstitutionMode, setIsSubstitutionMode] = useState(false);
  const [formData, setFormData] = useState({
    therapistId: 0,
    date: "",
    time: "",
    duration: 60,
    notes: "",
  });

  const { showSuccess, showError } = useToast();

  // Determina se è un appuntamento privato
  const isPrivateAppointment =
    appointment?.appointmentSource === "private" || appointment?.isPrivate;

  useEffect(() => {
    if (appointment) {
      const [datePart, timePart] = appointment.datetime.split(" ");
      const [hours, minutes] = timePart.split(":");

      setFormData({
        therapistId: appointment.therapist?.id || 0,
        date: datePart,
        time: `${hours}:${minutes}`,
        duration: appointment.duration,
        notes: appointment.notes || "",
      });

      // Reset substitution mode when appointment changes
      setIsSubstitutionMode(false);
    }
  }, [appointment]);

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
    if (!appointment) return;

    setLoading(true);
    try {
      const appointmentDateTime = `${formData.date} ${formData.time}:00`;

      const result = await therapyAPI.updateAppointment({
        appointmentId: appointment.id,
        therapistId: formData.therapistId,
        appointmentDateTime,
        durationMinutes: formData.duration,
        notes: formData.notes,
      });

      console.log("✅ Appuntamento aggiornato con successo:", result);

      // Mostra messaggio di successo
      showSuccess(
        "Appuntamento aggiornato",
        "L'appuntamento è stato modificato con successo"
      );

      // Trigger re-render del calendario
      onAppointmentUpdate(appointment.id.toString());
      setIsEditing(false);
      onClose();
    } catch (error) {
      console.error("Errore aggiornamento appuntamento:", error);

      // Forza il re-render del calendario per rimuovere modifiche "fantasma"
      onAppointmentUpdate(appointment.id.toString());

      // Gestisci conflitti specifici
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
          // Conflitto terapista
          conflictTitle = "Conflitto terapista";
          conflictMessage = `Il terapista ha già un appuntamento in questo orario con ${
            conflict.existingAppointmentInfo.patientName || "un altro paziente"
          }`;
        } else {
          // Fallback generico
          conflictMessage =
            conflict?.message || "Conflitto rilevato durante l'aggiornamento";
        }

        showError(conflictTitle, conflictMessage);
      } else {
        // Errore generico
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
    if (!appointment) return;

    // Determina il messaggio di conferma
    let confirmMessage = "Sei sicuro di voler eliminare questo appuntamento?";
    if (deleteAllFuture) {
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
          // Cancella tutti gli appuntamenti futuri del pattern
          const appointmentDate = appointment.datetime.split(" ")[0]; // Estrae solo la data
          await therapyAPI.deletePatternAppointments({
            patternId: appointment.patternId,
            fromDate: appointmentDate,
          });
        } else if (appointment.isPrivate && appointment.privateCycleId) {
          // Cancella tutti gli appuntamenti del ciclo privato
          await therapyAPI.deletePrivateCycleAppointments(
            appointment.privateCycleId
          );
        }
      } else {
        // Cancella solo questo appuntamento
        await therapyAPI.deleteAppointment(appointment.id);
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

  if (!isOpen || !appointment) return null;

  console.log(appointment);

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50">
          <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
            {isEditing ? "Modifica Appuntamento" : "Dettagli Appuntamento"}
            {isPrivateAppointment && (
              <span className="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                <Lock className="w-3 h-3" />
                Privato
              </span>
            )}
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
          {/* Avviso per appuntamenti privati */}
          {/* {isPrivateAppointment && !isEditing && (
            <div className="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
              <p className="text-sm text-purple-800 flex items-center gap-2">
                <Lock className="h-4 w-4" />
                Questo è un appuntamento privato al di fuori del piano
                terapeutico
              </p>
            </div>
          )} */}

          {!isEditing ? (
            /* Vista dettagli */
            <div className="space-y-6">
              {/* Status */}
              <div className="flex items-center gap-3">
                <span className="text-sm font-medium text-gray-600">
                  Stato:
                </span>
                <span
                  className={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusColor(
                    appointment.status
                  )}`}
                >
                  {getStatusText(appointment.status)}
                </span>
                {(appointment.isRecurring || appointment.privateCycleId) && (
                  <span className="flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                    <Repeat className="w-3 h-3" />
                    Ricorrente
                  </span>
                )}
              </div>

              {/* Paziente */}
              <div className="flex items-center gap-4 p-4 bg-blue-50 rounded-lg">
                <div className="flex-shrink-0">
                  <User className="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <div className="font-semibold text-gray-900">
                    {appointment.patient?.name || "Paziente sconosciuto"}
                  </div>
                  <div className="text-sm text-blue-600 font-medium">
                    Paziente
                  </div>
                </div>
              </div>

              {/* Terapista */}
              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center gap-4 p-4 bg-green-50 rounded-lg">
                  <div className="flex-shrink-0">
                    <User className="w-6 h-6 text-green-600" />
                  </div>
                  <div>
                    <div className="font-semibold text-gray-900">
                      {appointment.therapist?.name || "Terapista sconosciuto"}
                    </div>
                    <div className="text-sm text-green-600 font-medium">
                      Terapista
                    </div>
                  </div>
                </div>
                {appointment.treatmentType && (
                  <div className="flex items-center gap-4 p-4 bg-indigo-50 rounded-lg">
                    <div className="flex-shrink-0">
                      <Stethoscope className="w-6 h-6 text-indigo-600" />
                    </div>
                    <div>
                      <div className="font-semibold text-gray-900">
                        {appointment.treatmentType}
                      </div>
                      <div className="text-sm text-indigo-600 font-medium">
                        Tipo di trattamento
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {/* Data e ora */}
              <div className="grid grid-cols-2 gap-4">
                <div className="flex items-center gap-3 p-4 bg-purple-50 rounded-lg">
                  <Calendar className="w-6 h-6 text-purple-600" />
                  <div>
                    <div className="font-semibold text-gray-900">
                      {formatDate(appointment.datetime)}
                    </div>
                    <div className="text-sm text-purple-600 font-medium">
                      Data
                    </div>
                  </div>
                </div>

                <div className="flex items-center gap-3 p-4 bg-orange-50 rounded-lg">
                  <Clock className="w-6 h-6 text-orange-600" />
                  <div>
                    <div className="font-semibold text-gray-900">
                      {formatTime(appointment.datetime)} ({appointment.duration}{" "}
                      min)
                    </div>
                    <div className="text-sm text-orange-600 font-medium">
                      Orario e durata
                    </div>
                  </div>
                </div>
              </div>

              {/* Note */}
              {appointment.notes && (
                <div className="p-4 bg-gray-50 rounded-lg">
                  <div className="text-sm font-semibold text-gray-700 mb-2">
                    Note:
                  </div>
                  <div className="text-gray-900 leading-relaxed">
                    {appointment.notes}
                  </div>
                </div>
              )}
            </div>
          ) : (
            /* Vista modifica */
            <div className="space-y-6">
              {/* Avviso per appuntamenti privati in modifica */}
              {isPrivateAppointment && (
                <div className="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                  <p className="text-sm text-purple-800">
                    ⚠️ Stai modificando un appuntamento privato
                  </p>
                </div>
              )}

              {/* Terapista */}
              <div>
                <input
                  hidden
                  value={formData.therapistId}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      therapistId: parseInt(e.target.value),
                    })
                  }
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                ></input>
              </div>

              {/* Data e Orario */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-3">
                    Data
                  </label>
                  <input
                    type="date"
                    value={formData.date}
                    onChange={(e) =>
                      setFormData({ ...formData, date: e.target.value })
                    }
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                  />
                </div>

                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-3">
                    Orario
                  </label>
                  <input
                    type="time"
                    value={formData.time}
                    onChange={(e) =>
                      setFormData({ ...formData, time: e.target.value })
                    }
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                  />
                </div>
              </div>

              {/* Durata */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-3">
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
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                >
                  <option value={30}>30 minuti</option>
                  <option value={45}>45 minuti</option>
                  <option value={60}>60 minuti</option>
                  <option value={90}>90 minuti</option>
                  <option value={120}>120 minuti</option>
                </select>
              </div>

              {/* Note */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-3">
                  Note
                </label>
                <textarea
                  value={formData.notes}
                  onChange={(e) =>
                    setFormData({ ...formData, notes: e.target.value })
                  }
                  rows={4}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"
                  placeholder="Note aggiuntive..."
                />
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between p-6 border-t border-gray-200 bg-gray-50">
          <div className="flex flex-col gap-3">
            {/* Checkbox per cancellazione pattern/ciclo - mostra per appuntamenti ricorrenti o privati con ciclo */}
            {!isEditing &&
              appointment.status === "scheduled" &&
              ((appointment?.isRecurring && appointment?.patternId) ||
                (appointment?.isPrivate && appointment?.privateCycleId)) && (
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="deleteAllFuture"
                    checked={deleteAllFuture}
                    onChange={(e) => setDeleteAllFuture(e.target.checked)}
                    className="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 focus:ring-2"
                  />
                  <label
                    htmlFor="deleteAllFuture"
                    className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer"
                  >
                    <Repeat className="h-4 w-4" />
                    {appointment?.isPrivate
                      ? "Cancella tutti gli appuntamenti del ciclo privato"
                      : "Cancella tutti gli appuntamenti futuri"}
                  </label>
                </div>
              )}

            <div className="flex gap-3">
              {!isEditing &&
                appointment.status === "scheduled" &&
                !isTherapistView && ( // Nasconde il pulsante Modifica in vista terapista
                  <button
                    onClick={() => setIsEditing(true)}
                    className="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    <Edit className="w-4 h-4" />
                    Modifica
                  </button>
                )}

              {!isEditing &&
                appointment.status === "scheduled" &&
                onTherapistSubstitution && (
                  <button
                    onClick={() => setIsSubstitutionMode(true)}
                    className="flex items-center gap-2 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    <UserX className="w-4 h-4" />
                    Sostituisci Terapista
                  </button>
                )}

              {!isEditing &&
                appointment.status === "scheduled" &&
                appointment.appointmentSource === "therapeutic_plan" &&
                onAddTherapyInSlot && (
                  <button
                    onClick={() => {
                      onClose();
                      onAddTherapyInSlot(appointment);
                    }}
                    className="flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 font-medium"
                  >
                    <Stethoscope className="w-4 h-4" />
                    Aggiungi terapia
                  </button>
                )}

              {!isEditing && appointment.status === "scheduled" && (
                <button
                  onClick={handleDelete}
                  disabled={loading}
                  className="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Trash2 className="w-4 h-4" />
                  {loading ? "Eliminando..." : "Elimina"}
                </button>
              )}

              {/* Mostra un messaggio se l'appuntamento non è modificabile */}
              {!isEditing && appointment.status !== "scheduled" && (
                <div className="text-sm text-gray-500 italic">
                  Questo appuntamento non può essere modificato (stato:{" "}
                  {getStatusText(appointment.status)})
                </div>
              )}
            </div>
          </div>

          <div className="flex gap-3">
            {isEditing ? (
              <>
                <button
                  onClick={() => setIsEditing(false)}
                  className="px-6 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all duration-200 font-medium"
                >
                  Annulla
                </button>
                <button
                  onClick={handleSave}
                  disabled={loading}
                  className="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Save className="w-4 h-4" />
                  {loading ? "Salvando..." : "Salva"}
                </button>
              </>
            ) : (
              <button
                onClick={onClose}
                className="px-6 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all duration-200 font-medium"
              >
                Chiudi
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Modale per sostituzione terapista */}
      <TherapistSubstitutionModal
        isOpen={isSubstitutionMode}
        onClose={() => setIsSubstitutionMode(false)}
        onConfirm={handleTherapistSubstitution}
        appointment={appointment}
        therapists={therapists}
      />
    </div>
  );
};
