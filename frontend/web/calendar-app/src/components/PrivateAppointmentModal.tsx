import React, { useState } from "react";
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
import { Checkbox } from "@/components/ui/checkbox";
import {
  PrivateAppointmentData,
  Therapist,
  Patient,
  TreatmentType,
} from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { Badge } from "@/components/ui/badge";

interface PrivateAppointmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (data: PrivateAppointmentData) => void;
  selectedSlot: { date: Date; time: string } | null;
  selectedTherapist: Therapist | null;
  patient: Patient | null;
  treatmentType: TreatmentType | null;
}

export const PrivateAppointmentModal: React.FC<
  PrivateAppointmentModalProps
> = ({
  isOpen,
  onClose,
  onConfirm,
  selectedSlot,
  selectedTherapist,
  patient,
  treatmentType,
}) => {
  const [formData, setFormData] = useState<{
    duration: number;
    notes: string;
    isRecurring: boolean;
  }>({
    duration: 60,
    notes: "",
    isRecurring: false,
  });

  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedTherapist || !patient || !treatmentType) {
      console.error("❌ Dati mancanti per l'appuntamento privato");
      return;
    }

    setLoading(true);

    try {
      onConfirm({
        treatmentTypeId: treatmentType.id,
        treatmentTypeName: treatmentType.name,
        duration: formData.duration,
        notes: formData.notes,
        isRecurring: formData.isRecurring,
      });

      // Reset form
      setFormData({
        duration: 60,
        notes: "",
        isRecurring: false,
      });
    } catch (error) {
      console.error("❌ Errore nella creazione appuntamento privato:", error);
      alert(
        "Errore: Non è possibile creare l'appuntamento privato. " +
          (error instanceof Error ? error.message : "Errore sconosciuto")
      );
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    onClose();
    setFormData({
      duration: 60,
      notes: "",
      isRecurring: false,
    });
  };

  if (!isOpen) return null;

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>
            <div className="flex items-center gap-2">
              Nuovo Appuntamento Privato
              <Badge variant="secondary">Privato</Badge>
            </div>
            {selectedSlot && (
              <span className="text-sm text-gray-500 block mt-1">
                {selectedSlot.date.toLocaleDateString("it-IT")} alle{" "}
                {selectedSlot.time}
              </span>
            )}
          </DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          {selectedTherapist && (
            <div className="p-3 bg-blue-50 rounded-lg">
              <p className="text-sm font-medium text-blue-900">
                Terapista: {selectedTherapist.name}
              </p>
              <p className="text-xs text-blue-700">
                {selectedTherapist.specialization}
              </p>
            </div>
          )}

          {treatmentType && (
            <div className="p-3 bg-purple-50 rounded-lg">
              <p className="text-sm font-medium text-purple-900">
                Tipo di trattamento: {treatmentType.name}
              </p>
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="duration">Durata (minuti)</Label>
            <Input
              id="duration"
              type="number"
              value={formData.duration}
              onChange={(e) =>
                setFormData({ ...formData, duration: parseInt(e.target.value) })
              }
              min="15"
              max="180"
              step="15"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="notes">Note (opzionale)</Label>
            <Textarea
              id="notes"
              value={formData.notes}
              onChange={(e) =>
                setFormData({ ...formData, notes: e.target.value })
              }
              placeholder="Aggiungi note per l'appuntamento privato..."
              rows={3}
            />
          </div>

          <div className="flex items-center space-x-2">
            <Checkbox
              id="recurring"
              checked={formData.isRecurring}
              onCheckedChange={(checked) =>
                setFormData({ ...formData, isRecurring: checked as boolean })
              }
            />
            <Label htmlFor="recurring" className="text-sm">
              Appuntamento ricorrente (settimanale per il mese corrente)
            </Label>
          </div>

          <div className="p-3 bg-amber-50 rounded-lg text-sm">
            <p className="text-amber-800">
              ⚠️ Gli appuntamenti privati sono al di fuori del piano terapeutico
              e non contano per i limiti settimanali.
            </p>
          </div>

          <div className="flex justify-end space-x-2 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={loading}
            >
              Annulla
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? "Caricamento..." : "Crea Appuntamento Privato"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};
