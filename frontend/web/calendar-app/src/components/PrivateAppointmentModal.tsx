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
import { Checkbox } from "@/components/ui/checkbox";
import {
  PrivateAppointmentData,
  Therapist,
  Patient,
  TreatmentType,
} from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Loader2 } from "lucide-react";

interface PrivateAppointmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (data: PrivateAppointmentData) => void;
  selectedSlot: { date: Date; time: string } | null;
  selectedTherapist: Therapist | null;
  patient: Patient | null;
  treatmentType: TreatmentType | null;
  isTherapistView?: boolean; // Nuova prop per distinguere la vista
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
  isTherapistView = false,
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
  const [loadingTreatmentType, setLoadingTreatmentType] = useState(false);
  const [effectiveTreatmentType, setEffectiveTreatmentType] =
    useState<TreatmentType | null>(null);

  // Effetto per gestire il treatmentType
  useEffect(() => {
    if (isTherapistView) {
      // Nella vista terapista, non serve caricare il treatmentType dal frontend
      // Il backend lo ricaverà automaticamente dalla specializzazione del terapista
      setEffectiveTreatmentType({
        id: 0, // Placeholder, il backend ignorerà questo valore
        name: selectedTherapist?.specialization || "Trattamento automatico",
        description: `Trattamento basato sulla specializzazione del terapista`,
      });
    } else if (treatmentType) {
      // Vista normale: usa il treatmentType fornito
      setEffectiveTreatmentType(treatmentType);
    } else {
      // Vista normale senza treatmentType: carica automaticamente
      const loadTreatmentType = async () => {
        if (!selectedTherapist || !isOpen) return;

        setLoadingTreatmentType(true);
        try {
          console.log(
            "🔍 Caricamento treatmentType per terapista:",
            selectedTherapist.specialization
          );

          const treatmentTypes = await therapyAPI.getTreatmentTypes();
          const matchingTreatmentType = treatmentTypes.find(
            (type) => type.name === selectedTherapist.specialization
          );

          if (matchingTreatmentType) {
            console.log("✅ TreatmentType trovato:", matchingTreatmentType);
            setEffectiveTreatmentType(matchingTreatmentType);
          } else {
            console.warn(
              "⚠️ Nessun treatmentType trovato per specializzazione:",
              selectedTherapist.specialization
            );
            setEffectiveTreatmentType({
              id: 0,
              name: selectedTherapist.specialization,
              description: `Trattamento di ${selectedTherapist.specialization}`,
            });
          }
        } catch (error) {
          console.error("❌ Errore caricamento treatmentType:", error);
          setEffectiveTreatmentType({
            id: 0,
            name: selectedTherapist.specialization,
            description: `Trattamento di ${selectedTherapist.specialization}`,
          });
        } finally {
          setLoadingTreatmentType(false);
        }
      };

      loadTreatmentType();
    }
  }, [isOpen, treatmentType, selectedTherapist, isTherapistView]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (
      !selectedTherapist ||
      !patient ||
      (!isTherapistView && !effectiveTreatmentType)
    ) {
      console.error("❌ Dati mancanti per l'appuntamento privato", {
        selectedTherapist: !!selectedTherapist,
        patient: !!patient,
        effectiveTreatmentType: !!effectiveTreatmentType,
        isTherapistView,
      });
      return;
    }

    setLoading(true);

    try {
      const appointmentData: PrivateAppointmentData = {
        duration: formData.duration,
        notes: formData.notes,
        isRecurring: formData.isRecurring,
      };

      // Solo nella vista normale aggiungi treatmentTypeId
      if (!isTherapistView && effectiveTreatmentType) {
        appointmentData.treatmentTypeId = effectiveTreatmentType.id;
        appointmentData.treatmentTypeName = effectiveTreatmentType.name;
      }

      onConfirm(appointmentData);

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

  // Mostra loading se sta caricando il treatmentType
  if (loadingTreatmentType) {
    return (
      <Dialog open={isOpen} onOpenChange={handleClose}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Nuovo Appuntamento Privato</DialogTitle>
          </DialogHeader>
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin mr-2" />
            <span className="text-gray-600">
              Caricamento tipo di trattamento...
            </span>
          </div>
        </DialogContent>
      </Dialog>
    );
  }

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

          {effectiveTreatmentType && (
            <div className="p-3 bg-purple-50 rounded-lg">
              <p className="text-sm font-medium text-purple-900">
                Tipo di trattamento: {effectiveTreatmentType.name}
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
