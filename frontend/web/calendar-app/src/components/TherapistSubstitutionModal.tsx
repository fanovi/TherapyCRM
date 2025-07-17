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

interface TherapistSubstitutionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
  }) => void;
  appointment: Appointment | null;
  therapists: Therapist[];
}

export const TherapistSubstitutionModal: React.FC<
  TherapistSubstitutionModalProps
> = ({ isOpen, onClose, onConfirm, appointment, therapists }) => {
  const [selectedTherapistId, setSelectedTherapistId] = useState<string>("");
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(false);
  const [availableTherapists, setAvailableTherapists] = useState<Therapist[]>(
    []
  );
  const [loadingTherapists, setLoadingTherapists] = useState(false);
  const [originalTherapist, setOriginalTherapist] = useState<Therapist | null>(
    null
  );

  // Reset del form e caricamento terapisti quando si apre/chiude la modale
  useEffect(() => {
    if (isOpen && appointment?.therapist) {
      setSelectedTherapistId("");
      setReason("");
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

      // Usa l'API esistente per ottenere i terapisti della stessa specializzazione
      const therapistsBySpecialization =
        await therapyAPI.getTherapistsBySpecialization(
          originalTherapistData.specializationId
        );

      // Filtra escludendo il terapista originale
      const filtered = therapistsBySpecialization.filter(
        (therapist) => therapist.id !== appointment.therapist?.id
      );

      setAvailableTherapists(filtered);
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
      });

      // Se la sostituzione è andata a buon fine, chiama anche il callback per aggiornare la UI
      await onConfirm({
        appointmentId: appointment.id,
        newTherapistId: parseInt(selectedTherapistId),
        reason: reason.trim() || undefined,
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
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <UserX className="h-5 w-5 text-purple-600" />
            Sostituzione Terapista
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          {/* Informazioni appuntamento */}
          <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
            <div className="flex items-center gap-2 mb-2">
              <AlertCircle className="h-4 w-4 text-purple-600" />
              <span className="font-medium text-purple-800">
                Terapista Assente
              </span>
            </div>
            <div className="space-y-1 text-sm text-purple-700">
              <p>
                <strong>Paziente:</strong> {appointment.patient?.name}
              </p>
              <p>
                <strong>Terapista originale:</strong>{" "}
                {appointment.therapist?.name}
              </p>
              {originalTherapist?.specialization && (
                <p>
                  <strong>Specializzazione:</strong>{" "}
                  {originalTherapist.specialization}
                </p>
              )}
              <p>
                <strong>Data:</strong>{" "}
                {format(appointmentDate, "dd MMMM yyyy", { locale: it })}
              </p>
              <p>
                <strong>Orario:</strong> {format(appointmentDate, "HH:mm")}
              </p>
              <p>
                <strong>Durata:</strong> {appointment.duration} minuti
              </p>
            </div>
          </div>

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
              <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
                <p className="text-sm text-blue-700">
                  <strong>Filtro:</strong> Vengono mostrati solo i terapisti con
                  specializzazione{" "}
                  <strong>{originalTherapist?.specialization}</strong>
                </p>
              </div>

              <form onSubmit={handleSubmit} className="space-y-4">
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
                        >
                          <div className="flex items-center gap-2">
                            <UserCheck className="h-4 w-4 text-green-600" />
                            <span>{therapist.name}</span>
                            <span className="text-sm text-gray-500">
                              ({therapist.specialization})
                            </span>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Preview nuovo terapista */}
                {selectedTherapist && (
                  <div className="bg-green-50 p-3 rounded-lg border border-green-200">
                    <div className="flex items-center gap-2 text-green-800">
                      <UserCheck className="h-4 w-4" />
                      <span className="font-medium">
                        Nuovo Terapista: {selectedTherapist.name}
                      </span>
                    </div>
                    <p className="text-sm text-green-700 mt-1">
                      {selectedTherapist.specialization}
                      {selectedTherapist.weeklyHours &&
                        ` • ${selectedTherapist.weeklyHours}h/settimana`}
                    </p>
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
