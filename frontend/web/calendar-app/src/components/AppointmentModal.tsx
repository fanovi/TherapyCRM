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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { AppointmentData, Therapist, Patient } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { Users } from "lucide-react";

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
    recurringInterval: 1, // Default: settimanale
    isGroup: false, // Aggiunto campo per appuntamento di gruppo
    id_setting: undefined,
  });

  const [appointmentType, setAppointmentType] = useState<
    "terapia" | "parent_training" | "supervisione"
  >("terapia");
  const [loading, setLoading] = useState(false);

  // Stati per settings
  const [settings, setSettings] = useState<{ id: number; nome: string }[]>([]);
  const [settingsLoading, setSettingsLoading] = useState(false);
  const [settingsError, setSettingsError] = useState<string | null>(null);

  // Carica settings quando il modale si apre
  useEffect(() => {
    if (isOpen) {
      loadSettings();
    }
  }, [isOpen, patient?.therapeuticPlan?.regime?.id]);

  const loadSettings = async () => {
    try {
      setSettingsLoading(true);
      setSettingsError(null);

      // Carica settings filtrati per regime se disponibile
      const regimeId = patient?.therapeuticPlan?.regime?.id;
      const result = await therapyAPI.getSettings(regimeId);

      setSettings(result);

      // Determina il setting di default dalla planTherapy del paziente
      // (quello impostato sul trattamento del piano terapeutico).
      const preferredSettingId = await resolvePreferredSettingId();

      if (
        preferredSettingId &&
        result.some((s) => s.id === preferredSettingId)
      ) {
        setFormData((prev) => ({ ...prev, id_setting: preferredSettingId }));
      } else if (result.length === 1) {
        // Fallback: se c'è un solo setting disponibile, selezionalo
        setFormData((prev) => ({ ...prev, id_setting: result[0].id }));
      }
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

  /**
   * Risolve il setting di default dalla planTherapy del paziente per il
   * terapista/trattamento corrente.
   * - ABA + parent_training/supervisione: legge da availableTherapies
   *   in base al treatment_type_id atteso (24 o 25)
   * - Altrimenti: chiede al backend la planTherapy compatibile con il terapista
   */
  const resolvePreferredSettingId = async (): Promise<
    number | null | undefined
  > => {
    if (!selectedTherapist || !patient) return undefined;

    if (isABARegime && appointmentType !== "terapia") {
      const treatmentTypeId = appointmentType === "parent_training" ? 24 : 25;
      const matching = patient.availableTherapies?.find(
        (t) => t.treatmentTypeId === treatmentTypeId
      );
      return matching?.settingId ?? undefined;
    }

    try {
      const data = await therapyAPI.getPlanTherapyForTherapist(
        patient.id,
        selectedTherapist.id
      );
      return data.settingId ?? undefined;
    } catch {
      return undefined;
    }
  };

  // Quando in regime ABA l'utente cambia il tipo di appuntamento
  // (terapia/parent_training/supervisione), ri-applica il setting di default
  // della planTherapy corrispondente.
  useEffect(() => {
    if (!isOpen || !isABARegime || settings.length === 0) return;
    let cancelled = false;
    (async () => {
      const preferredSettingId = await resolvePreferredSettingId();
      if (cancelled) return;
      if (
        preferredSettingId &&
        settings.some((s) => s.id === preferredSettingId)
      ) {
        setFormData((prev) => ({ ...prev, id_setting: preferredSettingId }));
      }
    })();
    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [appointmentType]);

  const handleSubmit = async () => {
    if (!selectedTherapist || !patient) {
      // console.error("❌ Terapista o paziente non selezionato");
      return;
    }

    // Validazione setting obbligatorio
    if (!formData.id_setting) {
      alert("Seleziona un setting per l'appuntamento");
      return;
    }

    setLoading(true);

    try {
      let planTherapyData;

      // Se siamo in modalità ABA e abbiamo selezionato parent_training o supervisione
      if (isABARegime && appointmentType !== "terapia") {
        // console.log(
        //   "🔍 Debug - patient.availableTherapies:",
        //   patient.availableTherapies
        // );
        // console.log(
        //   "🔍 Debug - cercando treatmentTypeId:",
        //   appointmentType === "parent_training" ? 8 : 9
        // );
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

      // console.log(
      //   "🔍 Debug AppointmentModal - planTherapyData:",
      //   planTherapyData
      // );
      // console.log("🔍 Debug AppointmentModal - isGroup:", formData.isGroup);

      // Usa la specializzazione del terapista come tipo di terapia
      const therapyType =
        selectedTherapist?.specialization || planTherapyData.treatmentTypeName;

      onConfirm({
        ...formData,
        therapyType,
        appointmentType: isABARegime ? appointmentType : "terapia",
        isGroup: formData.isGroup, // Passa il valore isGroup
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

      // Reset form
      setFormData({
        therapyType: "",
        duration: 60,
        notes: "",
        isRecurring: false,
        recurringInterval: 1,
        isGroup: false,
        id_setting: undefined,
      });
    } catch (error) {
      // console.error("❌ Errore nel recupero piano terapia:", error);
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
      recurringInterval: 1,
      isGroup: false,
      id_setting: undefined,
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
        <div className="space-y-4">
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

          <div className="space-y-2">
            <Label htmlFor="setting">
              Setting <span className="text-red-500">*</span>
            </Label>
            {settingsLoading ? (
              <div className="text-sm text-gray-500">
                Caricamento settings...
              </div>
            ) : settingsError ? (
              <div className="text-sm text-red-500">{settingsError}</div>
            ) : (
              <Select
                value={formData.id_setting?.toString()}
                onValueChange={(value) =>
                  setFormData({ ...formData, id_setting: parseInt(value) })
                }
              >
                <SelectTrigger id="setting">
                  <SelectValue placeholder="Seleziona un setting" />
                </SelectTrigger>
                <SelectContent>
                  {settings.map((setting) => (
                    <SelectItem key={setting.id} value={setting.id.toString()}>
                      {setting.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
          </div>

          <div className="space-y-3 border-t pt-3">
            <div className="flex items-center space-x-2">
              <Checkbox
                id="recurring"
                checked={formData.isRecurring}
                onCheckedChange={(checked) =>
                  setFormData({ ...formData, isRecurring: checked as boolean })
                }
              />
              <Label htmlFor="recurring" className="text-sm font-normal">
                Appuntamento ricorrente
              </Label>
            </div>

            {/* Selezione frequenza ricorrenza */}
            {formData.isRecurring && (
              <div className="ml-6 space-y-2">
                <Label className="text-sm">Frequenza</Label>
                <Select
                  value={formData.recurringInterval?.toString() || "1"}
                  onValueChange={(value) =>
                    setFormData({ ...formData, recurringInterval: parseInt(value) as 1 | 2 })
                  }
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Seleziona frequenza" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">Ogni settimana</SelectItem>
                    <SelectItem value="2">Ogni 2 settimane</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            )}

            {/* Mostra checkbox gruppo se paziente ha piano terapeutico (incluso ABA) */}
            {patient?.planTherapy && (
              <>
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="group"
                    checked={formData.isGroup}
                    onCheckedChange={(checked) =>
                      setFormData({ ...formData, isGroup: checked as boolean })
                    }
                  />
                  <Label
                    htmlFor="group"
                    className="text-sm font-normal flex items-center gap-2"
                  >
                    <Users className="w-4 h-4" />
                    Appuntamento di gruppo
                  </Label>
                </div>

                {formData.isGroup && (
                  <div className="ml-6 p-2 bg-amber-50 rounded text-xs text-amber-700">
                    Questo appuntamento potrà ospitare più pazienti nello stesso
                    orario
                  </div>
                )}
              </>
            )}
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
            <Button type="button" disabled={loading} onClick={handleSubmit}>
              {loading ? "Caricamento..." : "Crea Appuntamento"}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};
