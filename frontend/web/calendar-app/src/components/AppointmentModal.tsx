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
import { AppointmentData, Therapist } from "@/types/therapy";
import { Calendar, Clock, FileText, Repeat, User } from "lucide-react";

const durations = [15, 30, 45, 60, 90, 120];

interface AppointmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (data: AppointmentData) => void;
  selectedSlot: { date: Date; time: string } | null;
  selectedTherapist: Therapist | null;
}

export const AppointmentModal: React.FC<AppointmentModalProps> = ({
  isOpen,
  onClose,
  onConfirm,
  selectedSlot,
  selectedTherapist,
}) => {
  const [formData, setFormData] = useState<AppointmentData>({
    therapyType: "",
    duration: 60,
    notes: "",
    isRecurring: false,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Usa la specializzazione del terapista come tipo di terapia
    const therapyType = selectedTherapist?.specialization || "Terapia generica";

    onConfirm({
      ...formData,
      therapyType,
    });

    setFormData({
      therapyType: "",
      duration: 60,
      notes: "",
      isRecurring: false,
    });
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
          <div
            style={{
              backgroundColor: "#eff6ff",
              padding: "12px",
              borderRadius: "8px",
              marginBottom: "16px",
              border: "1px solid #dbeafe",
            }}
          >
            <div
              style={{
                display: "flex",
                alignItems: "center",
                gap: "8px",
                fontSize: "14px",
                color: "#1e40af",
              }}
            >
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

        {/* Info Terapista */}
        {selectedTherapist && (
          <div
            style={{
              backgroundColor: "#f0f9ff",
              padding: "12px",
              borderRadius: "8px",
              marginBottom: "16px",
              border: "1px solid #bae6fd",
            }}
          >
            <div
              style={{
                display: "flex",
                alignItems: "center",
                gap: "8px",
                fontSize: "14px",
                color: "#0c4a6e",
              }}
            >
              <User className="h-4 w-4" />
              <div>
                <span style={{ fontWeight: "500" }}>
                  {selectedTherapist.name}
                </span>
                <span style={{ color: "#64748b", marginLeft: "8px" }}>
                  • {selectedTherapist.specialization}
                </span>
              </div>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div style={{ marginBottom: "16px" }}>
            <label
              style={{
                display: "block",
                fontSize: "14px",
                fontWeight: "500",
                color: "#374151",
                marginBottom: "6px",
              }}
            >
              Durata (minuti)
            </label>
            <select
              value={formData.duration.toString()}
              onChange={(e) =>
                setFormData((prev) => ({
                  ...prev,
                  duration: parseInt(e.target.value),
                }))
              }
              style={{
                width: "100%",
                padding: "8px 12px",
                border: "1px solid #d1d5db",
                borderRadius: "6px",
                fontSize: "14px",
                backgroundColor: "white",
                color: "#374151",
              }}
            >
              {durations.map((duration) => (
                <option key={duration} value={duration.toString()}>
                  {duration} minuti
                </option>
              ))}
            </select>
          </div>

          <div style={{ marginBottom: "16px" }}>
            <label
              style={{
                display: "flex",
                alignItems: "center",
                gap: "4px",
                fontSize: "14px",
                fontWeight: "500",
                color: "#374151",
                marginBottom: "6px",
              }}
            >
              <FileText className="h-4 w-4" />
              Note (opzionale)
            </label>
            <textarea
              placeholder="Inserisci eventuali note per l'appuntamento..."
              value={formData.notes}
              onChange={(e) =>
                setFormData((prev) => ({ ...prev, notes: e.target.value }))
              }
              style={{
                width: "100%",
                padding: "8px 12px",
                border: "1px solid #d1d5db",
                borderRadius: "6px",
                fontSize: "14px",
                backgroundColor: "white",
                color: "#374151",
                resize: "none",
                minHeight: "80px",
              }}
              rows={3}
            />
          </div>

          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: "8px",
              marginBottom: "20px",
            }}
          >
            <input
              type="checkbox"
              id="recurring"
              checked={formData.isRecurring}
              onChange={(e) =>
                setFormData((prev) => ({
                  ...prev,
                  isRecurring: e.target.checked,
                }))
              }
              style={{
                width: "16px",
                height: "16px",
                accentColor: "#3b82f6",
              }}
            />
            <label
              htmlFor="recurring"
              style={{
                display: "flex",
                alignItems: "center",
                gap: "4px",
                fontSize: "14px",
                color: "#374151",
                cursor: "pointer",
              }}
            >
              <Repeat className="h-4 w-4" />
              Ripeti fino a fine piano terapeutico
            </label>
          </div>

          <div
            style={{
              display: "flex",
              gap: "12px",
              paddingTop: "16px",
            }}
          >
            <button
              type="button"
              onClick={handleClose}
              style={{
                flex: 1,
                padding: "10px 16px",
                border: "1px solid #d1d5db",
                borderRadius: "6px",
                fontSize: "14px",
                fontWeight: "500",
                backgroundColor: "white",
                color: "#374151",
                cursor: "pointer",
                transition: "all 0.2s",
              }}
              onMouseOver={(e) => {
                e.currentTarget.style.backgroundColor = "#f9fafb";
              }}
              onMouseOut={(e) => {
                e.currentTarget.style.backgroundColor = "white";
              }}
            >
              Annulla
            </button>

            <button
              type="submit"
              disabled={!selectedTherapist}
              style={{
                flex: 1,
                padding: "10px 16px",
                border: "none",
                borderRadius: "6px",
                fontSize: "14px",
                fontWeight: "500",
                backgroundColor: selectedTherapist ? "#3b82f6" : "#9ca3af",
                color: "white",
                cursor: selectedTherapist ? "pointer" : "not-allowed",
                transition: "all 0.2s",
              }}
              onMouseOver={(e) => {
                if (selectedTherapist) {
                  e.currentTarget.style.backgroundColor = "#2563eb";
                }
              }}
              onMouseOut={(e) => {
                if (selectedTherapist) {
                  e.currentTarget.style.backgroundColor = "#3b82f6";
                }
              }}
            >
              Conferma Appuntamento
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
