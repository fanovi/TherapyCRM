import React, { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
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
import { Switch } from "@/components/ui/switch";
import { format } from "date-fns";
import { it } from "date-fns/locale";
import { AppointmentData } from "@/types/therapy";
import { Calendar, Clock, FileText, Repeat } from "lucide-react";

const therapyTypes = [
  "Fisioterapia",
  "Logopedia",
  "Psicomotricità",
  "Terapia Occupazionale",
  "Massoterapia",
];

const durations = [30, 45, 60, 90, 120];

interface AppointmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (data: AppointmentData) => void;
  selectedSlot: { date: Date; time: string } | null;
}

export const AppointmentModal: React.FC<AppointmentModalProps> = ({
  isOpen,
  onClose,
  onConfirm,
  selectedSlot,
}) => {
  console.log("🔍 AppointmentModal render:", { isOpen, selectedSlot });

  const [formData, setFormData] = useState<AppointmentData>({
    therapyType: "",
    duration: 60,
    notes: "",
    isRecurring: false,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.therapyType) return;

    onConfirm(formData);
    setFormData({
      therapyType: "",
      duration: 60,
      notes: "",
      isRecurring: false,
    });
  };

  const handleClose = () => {
    console.log("🔍 AppointmentModal handleClose called");
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
    <div
      style={{
        position: "fixed",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: "rgba(0, 0, 0, 0.5)",
        zIndex: 999999,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
      }}
      onClick={handleClose}
    >
      <div
        style={{
          background: "white",
          borderRadius: "8px",
          padding: "24px",
          minWidth: "400px",
          maxWidth: "500px",
          maxHeight: "80vh",
          overflow: "auto",
          boxShadow: "0 25px 50px -12px rgba(0, 0, 0, 0.25)",
        }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div style={{ marginBottom: "20px" }}>
          <h2
            style={{
              fontSize: "18px",
              fontWeight: "600",
              color: "#1f2937",
              display: "flex",
              alignItems: "center",
              gap: "8px",
              margin: 0,
            }}
          >
            <Calendar className="h-5 w-5 text-blue-600" />
            Nuovo Appuntamento
          </h2>
          <p
            style={{
              fontSize: "14px",
              color: "#6b7280",
              marginTop: "4px",
              margin: 0,
            }}
          >
            Compila i dettagli per creare un nuovo appuntamento.
          </p>
        </div>

        {selectedSlot && (
          <div className="bg-blue-50 p-3 rounded-lg mb-4">
            <div className="flex items-center gap-2 text-sm text-blue-800">
              <Clock className="h-4 w-4" />
              <span>
                {format(selectedSlot.date, "EEEE dd MMMM yyyy", {
                  locale: it,
                })}{" "}
                alle {selectedSlot.time}
              </span>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="therapyType">Tipo di Terapia *</Label>
            <Select
              value={formData.therapyType}
              onValueChange={(value) =>
                setFormData((prev) => ({ ...prev, therapyType: value }))
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="Seleziona tipo di terapia" />
              </SelectTrigger>
              <SelectContent>
                {therapyTypes.map((type) => (
                  <SelectItem key={type} value={type}>
                    {type}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor="duration">Durata (minuti)</Label>
            <Select
              value={formData.duration.toString()}
              onValueChange={(value) =>
                setFormData((prev) => ({
                  ...prev,
                  duration: parseInt(value),
                }))
              }
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {durations.map((duration) => (
                  <SelectItem key={duration} value={duration.toString()}>
                    {duration} minuti
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor="notes" className="flex items-center gap-1">
              <FileText className="h-4 w-4" />
              Note (opzionale)
            </Label>
            <Textarea
              id="notes"
              placeholder="Inserisci eventuali note per l'appuntamento..."
              value={formData.notes}
              onChange={(e) =>
                setFormData((prev) => ({ ...prev, notes: e.target.value }))
              }
              className="resize-none"
              rows={3}
            />
          </div>

          <div className="flex items-center space-x-2">
            <Switch
              id="recurring"
              checked={formData.isRecurring}
              onCheckedChange={(checked) =>
                setFormData((prev) => ({ ...prev, isRecurring: checked }))
              }
            />
            <Label htmlFor="recurring" className="flex items-center gap-1">
              <Repeat className="h-4 w-4" />
              Ripeti fino a fine piano terapeutico
            </Label>
          </div>

          <div className="flex gap-3 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              className="flex-1"
            >
              Annulla
            </Button>
            <Button
              type="submit"
              className="flex-1 bg-blue-600 hover:bg-blue-700"
              disabled={!formData.therapyType}
            >
              Conferma Appuntamento
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
