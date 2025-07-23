import React, { useState, useEffect } from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { SpecializationTreatment } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { Settings, Loader2 } from "lucide-react";

interface SpecializationTreatmentSelectorProps {
  therapistId: number | null;
  selectedTreatment: SpecializationTreatment | null;
  onTreatmentSelect: (treatment: SpecializationTreatment | null) => void;
}

export const SpecializationTreatmentSelector: React.FC<
  SpecializationTreatmentSelectorProps
> = ({ therapistId, selectedTreatment, onTreatmentSelect }) => {
  const [treatments, setTreatments] = useState<SpecializationTreatment[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Carica i trattamenti quando cambia il terapista
  useEffect(() => {
    if (!therapistId) {
      setTreatments([]);
      onTreatmentSelect(null);
      return;
    }

    const loadTreatments = async () => {
      try {
        setLoading(true);
        setError(null);

        const data = await therapyAPI.getTherapistSpecializationTreatments(
          therapistId
        );
        setTreatments(data);

        // Reset selezione quando cambiano i trattamenti disponibili
        onTreatmentSelect(null);
      } catch (err) {
        console.error("Errore caricamento trattamenti:", err);
        setError(
          err instanceof Error
            ? err.message
            : "Errore nel caricamento dei trattamenti"
        );
        setTreatments([]);
        onTreatmentSelect(null);
      } finally {
        setLoading(false);
      }
    };

    loadTreatments();
  }, [therapistId]);

  const handleTreatmentChange = (value: string) => {
    if (value === "none") {
      onTreatmentSelect(null);
    } else {
      const treatment = treatments.find((t) => t.id.toString() === value);
      onTreatmentSelect(treatment || null);
    }
  };

  if (!therapistId) {
    return (
      <Card className="bg-gray-50 shadow-sm border-gray-200">
        <CardHeader className="pb-4">
          <CardTitle className="flex items-center gap-2 text-xl text-gray-500">
            <Settings className="h-5 w-5" />
            Specializzazione Trattamento
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center p-8 text-gray-500">
            <p>Seleziona prima un terapista</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (loading) {
    return (
      <Card className="bg-white shadow-lg border-0">
        <CardHeader className="pb-4">
          <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
            <Settings className="h-5 w-5 text-blue-600" />
            Specializzazione Trattamento
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin mr-2" />
            <span className="text-gray-600">Caricamento trattamenti...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card className="bg-red-50 shadow-lg border-red-200">
        <CardHeader className="pb-4">
          <CardTitle className="flex items-center gap-2 text-xl text-red-800">
            <Settings className="h-5 w-5 text-red-600" />
            Specializzazione Trattamento
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center p-8 text-red-600">
            <p>{error}</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-white shadow-lg border-0">
      <CardHeader className="pb-4">
        <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
          <Settings className="h-5 w-5 text-blue-600" />
          Specializzazione Trattamento
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-3">
          <label className="text-sm font-medium text-gray-700">
            Seleziona il trattamento specializzato *
          </label>
          <Select
            value={selectedTreatment?.id.toString() || "none"}
            onValueChange={handleTreatmentChange}
          >
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Seleziona un trattamento..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">
                <span className="text-gray-500">
                  Nessun trattamento selezionato
                </span>
              </SelectItem>
              {treatments.map((treatment) => (
                <SelectItem key={treatment.id} value={treatment.id.toString()}>
                  <div className="flex flex-col space-y-1">
                    <span className="font-medium">{treatment.full_name}</span>
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {selectedTreatment && (
          <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h4 className="font-medium text-blue-900 mb-2">
              Trattamento Selezionato
            </h4>
            <div className="space-y-1 text-sm text-blue-800">
              <p>
                <strong>Nome:</strong> {selectedTreatment.treatment_type_name}
              </p>
              <p>
                <strong>Specializzazione:</strong>{" "}
                {selectedTreatment.specialization_name}
              </p>
            </div>
          </div>
        )}

        {treatments.length === 0 && (
          <div className="text-center p-8 text-gray-500 bg-gray-50 rounded-lg">
            <p>Nessun trattamento disponibile per questo terapista</p>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
