import React, { useState, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { AlertCircle, UserX, UserCheck } from "lucide-react";
import { format } from "date-fns";
import { it } from "date-fns/locale";
import { Appointment, Therapist } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
// Aggiungi questo import per il Checkbox
import { Checkbox } from "@/components/ui/checkbox";

interface TherapistSubstitutionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
    dontRegisterAbsence?: boolean;
  }) => void;
  appointment: Appointment | null;
  therapists: Therapist[];
}

export const TherapistSubstitutionModal: React.FC<
  TherapistSubstitutionModalProps
> = ({ isOpen, onClose, onConfirm, appointment, therapists }) => {
  const isGroupAppointment =
    appointment?.groupSessionId !== null &&
    appointment?.groupPatients &&
    appointment.groupPatients.length > 1;
  const [selectedTherapistId, setSelectedTherapistId] = useState<string>("");
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(false);
  const [availableTherapists, setAvailableTherapists] = useState<Therapist[]>(
    []
  );

  // Aggiorna lo stato del componente aggiungendo:
  const [dontRegisterAbsence, setDontRegisterAbsence] = useState(false);
  const [loadingTherapists, setLoadingTherapists] = useState(false);
  const [originalTherapist, setOriginalTherapist] = useState<Therapist | null>(
    null
  );

  // Reset del form e caricamento terapisti quando si apre/chiude la modale
  useEffect(() => {
    if (isOpen && appointment?.therapist) {
      setSelectedTherapistId("");
      setReason("");
      setDontRegisterAbsence(false);
      setAvailableTherapists([]);
      setOriginalTherapist(null);

      // Carica i terapisti della stessa specializzazione
      loadTherapistsBySpecialization();
    }
  }, [isOpen, appointment]);

  if (!appointment) return null;

  const loadTherapistsBySpecialization = async () => {
    if (!appointment?.therapist?.name) return;

    setLoadingTherapists(true);
    try {
      // Usa l'array therapists già disponibile per trovare il terapista originale
      const originalTherapistData = therapists.find(
        (t) => t.id === appointment.therapist?.id
      );

      if (!originalTherapistData?.specializationId) {
        throw new Error(
          "ID specializzazione del terapista originale non trovato"
        );
      }

      setOriginalTherapist(originalTherapistData);

      // Estrai data e ora dall'appuntamento per il controllo disponibilità
      const appointmentDate = new Date(appointment.datetime);
      const dateString = appointmentDate.toISOString().split("T")[0]; // Y-m-d
      const timeString = appointmentDate.toTimeString().slice(0, 5); // H:i

      // Usa l'API con controllo disponibilità (il backend escluderà automaticamente il terapista originale)
      const therapistsBySpecialization =
        await therapyAPI.getTherapistsBySpecialization(
          originalTherapistData.specializationId,
          {
            date: dateString,
            time: timeString,
            duration: appointment.duration,
            appointmentId: appointment.id, // Backend userà questo per escludere il terapista originale
          }
        );

      setAvailableTherapists(therapistsBySpecialization);
    } catch (error) {
      console.error("Errore nel caricamento terapisti:", error);
      setAvailableTherapists([]);
    } finally {
      setLoadingTherapists(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedTherapistId) {
      return;
    }

    setLoading(true);

    try {
      const response = await therapyAPI.substituteTherapist({
        appointmentId: appointment.id,
        newTherapistId: parseInt(selectedTherapistId),
        reason: reason.trim() || undefined,
        dontRegisterAbsence: dontRegisterAbsence, // 🔥 NUOVO
      });

      // Se la sostituzione è andata a buon fine, chiama anche il callback per aggiornare la UI
      await onConfirm({
        appointmentId: appointment.id,
        newTherapistId: parseInt(selectedTherapistId),
        reason: reason.trim() || undefined,
        dontRegisterAbsence: dontRegisterAbsence, // 🔥 NUOVO
      });

      onClose();
    } catch (error) {
      console.error("Errore durante la sostituzione:", error);
      // L'errore verrà gestito dal componente padre
      throw error;
    } finally {
      setLoading(false);
    }
  };

  const appointmentDate = new Date(appointment.datetime);
  const selectedTherapist = availableTherapists.find(
    (t) => t.id.toString() === selectedTherapistId
  );

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md py-4">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <UserX className="h-5 w-5 text-purple-600" />
            Sostituzione Terapista
            {isGroupAppointment && (
              <span className="text-sm font-normal text-blue-600">
                (Gruppo - {appointment.groupPatients?.length} pazienti)
              </span>
            )}
            {appointment.status === "therapist_absent" && (
              <span className="text-sm font-normal text-purple-600">
                (Assente)
              </span>
            )}
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-3">
          {/* Informazioni appuntamento */}

          <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
            <div className="space-y-1 text-sm text-purple-700">
              {isGroupAppointment ? (
                <>
                  <p>
                    <strong>Appuntamento di gruppo:</strong>{" "}
                  </p>
                  <div className="ml-2">
                    {appointment.groupPatients?.map((patient, index) => (
                      <span key={patient.id}>
                        {patient.name}
                        {index < appointment.groupPatients!.length - 1
                          ? ", "
                          : ""}
                      </span>
                    ))}
                  </div>
                  <p className="mt-2">
                    <strong>Terapista:</strong> {appointment.therapist?.name}
                    {originalTherapist?.specialization && (
                      <>
                        {" "}
                        • <strong>Spec.:</strong>{" "}
                        {originalTherapist.specialization}
                      </>
                    )}
                  </p>
                </>
              ) : (
                <p>
                  <strong>Paziente:</strong> {appointment.patient?.name} •
                  <strong> Terapista:</strong> {appointment.therapist?.name}
                  {originalTherapist?.specialization && (
                    <>
                      {" "}
                      • <strong>Spec.:</strong>{" "}
                      {originalTherapist.specialization}
                    </>
                  )}
                </p>
              )}
              <p>
                <strong>Data:</strong>{" "}
                {format(appointmentDate, "dd MMMM yyyy", { locale: it })} •
                <strong> Orario:</strong> {format(appointmentDate, "HH:mm")} •
                <strong> Durata:</strong> {appointment.duration} min
              </p>
            </div>
          </div>

          {isGroupAppointment && (
            <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
              <div className="flex items-start gap-2">
                <AlertCircle className="h-4 w-4 text-blue-600 mt-0.5" />
                <div className="text-sm text-blue-800">
                  <p className="text-xs mt-1">
                    La sostituzione verrà applicata a tutti i{" "}
                    {appointment.groupPatients?.length} pazienti del gruppo
                    contemporaneamente.
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Loading terapisti */}
          {loadingTherapists ? (
            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
              <div className="flex items-center gap-2">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                <span className="text-blue-800">
                  Caricamento terapisti disponibili...
                </span>
              </div>
            </div>
          ) : availableTherapists.length === 0 ? (
            <div className="bg-orange-50 p-4 rounded-lg border border-orange-200">
              <div className="flex items-center gap-2 mb-2">
                <AlertCircle className="h-4 w-4 text-orange-600" />
                <span className="font-medium text-orange-800">
                  Nessun terapista disponibile
                </span>
              </div>
              <p className="text-sm text-orange-700">
                Non ci sono altri terapisti con specializzazione{" "}
                <strong>{originalTherapist?.specialization}</strong> disponibili
                per la sostituzione.
              </p>
            </div>
          ) : (
            <>
              {/* Messaggio informativo specializzazione */}
              <div className="bg-blue-50 p-2 rounded-lg border border-blue-200">
                <p className="text-sm text-blue-700">
                  Solo terapisti con spec.{" "}
                  <strong>{originalTherapist?.specialization}</strong>
                </p>
              </div>

              {/* Contatore disponibilità */}
              {(() => {
                const available = availableTherapists.filter(
                  (t) => t.isAvailable
                ).length;
                const total = availableTherapists.length;
                const unavailable = total - available;

                return (
                  total > 0 && (
                    <div className="bg-gray-50 p-2 rounded-lg border border-gray-200">
                      <p className="text-sm text-gray-700">
                        <strong>
                          {available}/{total}
                        </strong>{" "}
                        disponibili
                        {unavailable > 0 && (
                          <span className="text-xs text-gray-600 ml-2">
                            (quelli non disponibili sono disabilitati)
                          </span>
                        )}
                      </p>
                    </div>
                  )
                );
              })()}

              <form onSubmit={handleSubmit} className="space-y-3">
                {/* Selezione nuovo terapista */}
                <div className="space-y-2">
                  <Label htmlFor="new-therapist">Nuovo Terapista *</Label>
                  <Select
                    value={selectedTherapistId}
                    onValueChange={setSelectedTherapistId}
                    required
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Seleziona il terapista sostituto" />
                    </SelectTrigger>
                    <SelectContent>
                      {availableTherapists.map((therapist) => (
                        <SelectItem
                          key={therapist.id}
                          value={therapist.id.toString()}
                          disabled={!therapist.isAvailable}
                        >
                          <div className="flex items-center gap-2">
                            {therapist.isAvailable ? (
                              <UserCheck className="h-4 w-4 text-green-600" />
                            ) : (
                              <UserX className="h-4 w-4 text-red-600" />
                            )}
                            <span
                              className={
                                therapist.isAvailable ? "" : "text-gray-500"
                              }
                            >
                              {therapist.name}
                            </span>
                            <span className="text-sm text-gray-500">
                              ({therapist.specialization})
                            </span>
                            {!therapist.isAvailable &&
                              therapist.unavailabilityReason && (
                                <span className="text-xs text-red-600 ml-2">
                                  - {therapist.unavailabilityReason}
                                </span>
                              )}
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Preview nuovo terapista */}
                {selectedTherapist && (
                  <div className="bg-green-50 p-2 rounded-lg border border-green-200 flex items-center gap-2">
                    <UserCheck className="h-4 w-4 text-green-600" />
                    <span className="text-sm text-green-800">
                      <strong>{selectedTherapist.name}</strong> •{" "}
                      {selectedTherapist.specialization}
                      {selectedTherapist.weeklyHours &&
                        ` • ${selectedTherapist.weeklyHours}h/sett`}
                    </span>
                  </div>
                )}

                {/* Motivo sostituzione */}
                <div className="space-y-2">
                  <Label htmlFor="reason">Motivo della sostituzione</Label>
                  <Textarea
                    id="reason"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder="Inserisci il motivo della sostituzione (opzionale)"
                    rows={3}
                  />
                </div>
                {/* 🔥 NUOVO: Checkbox per non registrare assenza */}
                <div className="flex items-center space-x-2 p-2 bg-blue-50 rounded-lg border border-blue-200">
                  <Checkbox
                    id="dont-register-absence"
                    checked={dontRegisterAbsence}
                    onCheckedChange={(checked) =>
                      setDontRegisterAbsence(checked === true)
                    }
                  />
                  <Label
                    htmlFor="dont-register-absence"
                    className="text-sm text-blue-800 cursor-pointer"
                  >
                    Non registrare assenza del terapista originale
                  </Label>
                </div>

                {/* Pulsanti */}
                <div className="flex gap-3 pt-4">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={onClose}
                    className="flex-1"
                    disabled={loading}
                  >
                    Annulla
                  </Button>
                  <Button
                    type="submit"
                    disabled={!selectedTherapistId || loading}
                    className="flex-1 bg-purple-600 hover:bg-purple-700"
                  >
                    {loading ? "Sostituendo..." : "Conferma Sostituzione"}
                  </Button>
                </div>
              </form>
            </>
          )}
          {/* Pulsanti alternativi se non ci sono terapisti disponibili */}
          {!loadingTherapists && availableTherapists.length === 0 && (
            <div className="flex gap-3 pt-4">
              <Button
                type="button"
                variant="outline"
                onClick={onClose}
                className="flex-1"
              >
                Chiudi
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
};
