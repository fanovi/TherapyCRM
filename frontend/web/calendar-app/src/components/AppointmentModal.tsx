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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { AppointmentData, Therapist, Patient } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";

interface AppointmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (data: AppointmentData) => void;
  selectedSlot: { date: Date; time: string } | null;
  selectedTherapist: Therapist | null;
  patient: Patient | null;
  isABARegime?: boolean;
  existingAppointmentTypes?: string[];
}

export const AppointmentModal: React.FC<AppointmentModalProps> = ({
  isOpen,
  onClose,
  onConfirm,
  selectedSlot,
  selectedTherapist,
  patient,
  isABARegime = false,
  existingAppointmentTypes = [],
}) => {
  const [formData, setFormData] = useState<AppointmentData>({
    therapyType: "",
    duration: 60,
    notes: "",
    isRecurring: false,
  });

  const [appointmentType, setAppointmentType] = useState<
    "terapia" | "parent_training" | "supervisione"
  >("terapia");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedTherapist || !patient) {
      console.error("❌ Terapista o paziente non selezionato");
      return;
    }

    setLoading(true);

    try {
      let planTherapyData;

      // Se siamo in modalità ABA e abbiamo selezionato parent_training o supervisione
      if (isABARegime && appointmentType !== "terapia") {
        console.log(
          "🔍 Debug - patient.availableTherapies:",
          patient.availableTherapies
        );
        console.log(
          "🔍 Debug - cercando treatmentTypeId:",
          appointmentType === "parent_training" ? 8 : 9
        );
        // Usa i treatmentTypeId fissi per parent_training (8) e supervisione (9)
        const treatmentTypeId = appointmentType === "parent_training" ? 24 : 25;

        // Cerca il planTherapy corretto tra quelli disponibili del paziente
        const matchingTherapy = patient.availableTherapies?.find(
          (therapy) => therapy.treatmentTypeId === treatmentTypeId
        );

        if (matchingTherapy) {
          // Costruisci l'oggetto planTherapyData manualmente
          planTherapyData = {
            planTherapyId: matchingTherapy.planTherapyId,
            therapeuticPlanId:
              patient.planTherapy?.therapeuticPlanId ||
              patient.therapeuticPlan?.id,
            treatmentTypeId: treatmentTypeId,
            treatmentTypeName: matchingTherapy.treatmentTypeName,
            weeklyHours: matchingTherapy.weeklyHours,
          };
        } else {
          throw new Error(
            `Piano terapeutico per ${appointmentType} non trovato`
          );
        }
      } else {
        // Comportamento normale: usa il terapista selezionato
        planTherapyData = await therapyAPI.getPlanTherapyForTherapist(
          patient.id,
          selectedTherapist.id
        );
      }

      console.log(
        "🔍 Debug AppointmentModal - planTherapyData:",
        planTherapyData
      );

      // Usa la specializzazione del terapista come tipo di terapia
      const therapyType =
        selectedTherapist?.specialization || planTherapyData.treatmentTypeName;

      onConfirm({
        ...formData,
        therapyType,
        appointmentType: isABARegime ? appointmentType : "terapia",
        planTherapy: {
          planTherapyId: planTherapyData.planTherapyId,
          therapeuticPlanId: planTherapyData.therapeuticPlanId,
          patientId: patient.id,
          patientName: patient.name,
          // Usa le date dal piano terapeutico esistente per backward compatibility
          startDate: patient.planTherapy?.startDate || "",
          endDate: patient.planTherapy?.endDate || "",
          durationDays: patient.planTherapy?.durationDays || 0,
          weeklyHours: planTherapyData.weeklyHours,
          notes: patient.planTherapy?.notes || "",
        },
      });

      setFormData({
        therapyType: "",
        duration: 60,
        notes: "",
        isRecurring: false,
      });
    } catch (error) {
      console.error("❌ Errore nel recupero piano terapia:", error);
      alert(
        "Errore: Non è possibile creare l'appuntamento. " +
          (error instanceof Error
            ? error.message
            : "Il terapista selezionato non può gestire questo tipo di terapia.")
      );
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    onClose();
    setFormData({
      therapyType: "",
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
            Nuovo Appuntamento
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

          {/* {isABARegime && (
            <div className="space-y-2">
              <Label htmlFor="appointment-type">Tipo Appuntamento</Label>
              <Select
                value={appointmentType}
                onValueChange={(value) =>
                  setAppointmentType(
                    value as "terapia" | "parent_training" | "supervisione"
                  )
                }
              >
                <SelectTrigger id="appointment-type">
                  <SelectValue placeholder="Seleziona tipo" />
                </SelectTrigger>
                <SelectContent>
                  {!existingAppointmentTypes.includes("terapia") && (
                    <SelectItem value="terapia">Terapia</SelectItem>
                  )}
                  {!existingAppointmentTypes.includes("parent_training") && (
                    <SelectItem value="parent_training">
                      Parent Training
                    </SelectItem>
                  )}
                  {!existingAppointmentTypes.includes("supervisione") && (
                    <SelectItem value="supervisione">Supervisione</SelectItem>
                  )}
                </SelectContent>
              </Select>
            </div>
          )} */}

          <div className="space-y-2">
            <Label htmlFor="notes">Note (opzionale)</Label>
            <Textarea
              id="notes"
              value={formData.notes}
              onChange={(e) =>
                setFormData({ ...formData, notes: e.target.value })
              }
              placeholder="Aggiungi note per l'appuntamento..."
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
              Appuntamento ricorrente
            </Label>
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
              {loading ? "Caricamento..." : "Crea Appuntamento"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};
