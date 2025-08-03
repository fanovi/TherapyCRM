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
  isTherapistView?: boolean;
  onAddTherapyInSlot?: (appointment: Appointment) => void;
  isABARegime?: boolean;
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
}) => {
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
  });

  const { showSuccess, showError } = useToast();

  // Determina se è un appuntamento privato
  const isPrivateAppointment =
    appointment?.appointmentSource === "private" || appointment?.isPrivate;

  // Determina se è un appuntamento di gruppo
  const isGroupAppointment =
    appointment?.groupSessionId !== null &&
    appointment?.groupSessionId !== undefined;

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

      setIsSubstitutionMode(false);
      setShowActionMenu(false);
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

      showSuccess(
        "Appuntamento aggiornato",
        "L'appuntamento è stato modificato con successo"
      );

      onAppointmentUpdate(appointment.id.toString());
      setIsEditing(false);
      onClose();
    } catch (error) {
      console.error("Errore aggiornamento appuntamento:", error);
      onAppointmentUpdate(appointment.id.toString());

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
    if (!appointment) return;

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
              </div>

              {/* Info principali in griglia compatta */}
              <div className="grid grid-cols-2 gap-3">
                {/* Paziente */}
                <div className="col-span-2 flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                  <User className="w-5 h-5 text-blue-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 truncate">
                      {appointment.patient?.name || "Paziente sconosciuto"}
                    </div>
                    <div className="text-xs text-blue-600">Paziente</div>
                  </div>
                </div>

                {/* Terapista */}
                <div className="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                  <User className="w-5 h-5 text-green-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 text-sm truncate">
                      {appointment.therapist?.name || "N/A"}
                    </div>
                    <div className="text-xs text-green-600">Terapista</div>
                  </div>
                </div>

                {/* Trattamento */}
                {appointment.treatmentType && (
                  <div className="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg">
                    <Stethoscope className="w-5 h-5 text-indigo-600 flex-shrink-0" />
                    <div className="min-w-0">
                      <div className="font-semibold text-gray-900 text-sm truncate">
                        {appointment.treatmentType}
                      </div>
                      <div className="text-xs text-indigo-600">Trattamento</div>
                    </div>
                  </div>
                )}

                {/* Data e ora */}
                <div className="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                  <Calendar className="w-5 h-5 text-purple-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 text-sm">
                      {new Date(appointment.datetime).toLocaleDateString(
                        "it-IT",
                        {
                          day: "numeric",
                          month: "short",
                          year: "numeric",
                        }
                      )}
                    </div>
                    <div className="text-xs text-purple-600">Data</div>
                  </div>
                </div>

                <div className="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
                  <Clock className="w-5 h-5 text-orange-600 flex-shrink-0" />
                  <div className="min-w-0">
                    <div className="font-semibold text-gray-900 text-sm">
                      {formatTime(appointment.datetime)} •{" "}
                      {appointment.duration}min
                    </div>
                    <div className="text-xs text-orange-600">Orario</div>
                  </div>
                </div>
              </div>

              {/* Note (se presenti) */}
              {appointment.notes && (
                <div className="p-3 bg-gray-50 rounded-lg">
                  <div className="text-xs font-semibold text-gray-600 mb-1">
                    Note
                  </div>
                  <div className="text-sm text-gray-900">
                    {appointment.notes}
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
            </div>
          )}
        </div>

        {/* Footer con azioni compatte */}
        <div className="p-4 border-t border-gray-200 bg-gray-50">
          {!isEditing && appointment.status === "scheduled" && (
            <>
              {/* Checkbox per cancellazione ricorrenti */}
              {((appointment?.isRecurring && appointment?.patternId) ||
                (appointment?.isPrivate && appointment?.privateCycleId)) && (
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
                appointment.appointmentSource === "therapeutic_plan" && (
                  <div className="mb-3">
                    <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                      <input
                        type="checkbox"
                        onChange={async (e) => {
                          if (e.target.checked) {
                            try {
                              setLoading(true);
                              await therapyAPI.setGroupAppointment(
                                appointment.id
                              );
                              showSuccess(
                                "Appuntamento di gruppo",
                                "L'appuntamento è stato impostato come appuntamento di gruppo"
                              );
                              onAppointmentUpdate(appointment.id.toString());
                              onClose();
                            } catch (error) {
                              showError(
                                "Errore",
                                "Non è stato possibile impostare l'appuntamento come gruppo"
                              );
                            } finally {
                              setLoading(false);
                            }
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

                  {appointment.appointmentSource === "therapeutic_plan" &&
                    onAddTherapyInSlot && (
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

          {!isEditing && appointment.status !== "scheduled" && (
            <div className="flex items-center justify-between">
              <p className="text-sm text-gray-500 italic">
                Non modificabile (stato: {getStatusText(appointment.status)})
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
        appointment={appointment}
        therapists={therapists}
      />
    </div>
  );
};
