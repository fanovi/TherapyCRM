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
import { Therapist, Specialization, TreatmentType } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { Users, Filter, Loader2 } from "lucide-react";

interface TherapistSelectorProps {
  selectedTherapist: Therapist | null;
  onTherapistSelect: (therapist: Therapist) => void;
  patientId?: number;
  isPrivateMode?: boolean;
  onTreatmentTypeSelect?: (treatmentType: TreatmentType | null) => void;
  planId?: number;
}

export const TherapistSelector: React.FC<TherapistSelectorProps> = ({
  selectedTherapist,
  onTherapistSelect,
  patientId,
  isPrivateMode = false,
  onTreatmentTypeSelect,
  planId,
}) => {
  const [selectedSpecialization, setSelectedSpecialization] =
    useState<Specialization | null>(null);
  const [selectedTreatmentType, setSelectedTreatmentType] =
    useState<TreatmentType | null>(null);
  const [therapists, setTherapists] = useState<Therapist[]>([]);
  const [loading, setLoading] = useState(true);
  const [therapistsLoading, setTherapistsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [availableSpecializations, setAvailableSpecializations] = useState<
    Specialization[]
  >([]);
  const [treatmentTypes, setTreatmentTypes] = useState<TreatmentType[]>([]);

  // Carica le specializzazioni o i tipi di trattamento
  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        setError(null);

        if (isPrivateMode) {
          // In Private Mode, carica tutti i tipi di trattamento
          const types = await therapyAPI.getTreatmentTypes();
          setTreatmentTypes(types);
        } else {
          // In modalità normale, carica le specializzazioni del paziente
          if (!planId) {
            setLoading(false);
            return;
          }
          const data = await therapyAPI.getPlanTreatments(planId);
          setAvailableSpecializations(data);
        }
      } catch (err) {
        console.error("Errore caricamento dati:", err);
        setError(
          err instanceof Error ? err.message : "Errore nel caricamento dei dati"
        );
      } finally {
        setLoading(false);
      }
    };

    loadData();
  }, [patientId, isPrivateMode]);

  // Carica i terapisti
  useEffect(() => {
    const loadTherapists = async () => {
      if (isPrivateMode && !selectedTreatmentType) {
        onTherapistSelect(null);
        setTherapists([]);
        return;
      }

      if (!isPrivateMode && !selectedSpecialization) {
        onTherapistSelect(null);
        setTherapists([]);
        return;
      }

      try {
        onTherapistSelect(null);
        setTherapistsLoading(true);
        setError(null);

        let data: Therapist[];

        if (isPrivateMode && selectedTreatmentType) {
          // In Private Mode, carica terapisti per tipo di trattamento
          data = await therapyAPI.getTherapistsByTreatment(
            selectedTreatmentType.id
          );
        } else if (selectedSpecialization) {
          // In modalità normale, carica terapisti per specializzazione
          data = await therapyAPI.getTherapistsBySpecialization(
            selectedSpecialization.id
          );
        } else {
          data = [];
        }

        // Assegna colori casuali ai terapisti
        const therapistsWithColors = data.map((therapist, index) => {
          if (!therapist.color) {
            const colors = [
              "#3b82f6",
              "#16a34a",
              "#dc2626",
              "#7c3aed",
              "#ea580c",
              "#0891b2",
            ];
            therapist.color = colors[index % colors.length];
          }
          return therapist;
        });

        setTherapists(therapistsWithColors);
      } catch (err) {
        console.error("Errore caricamento terapisti:", err);
        setError(
          err instanceof Error
            ? err.message
            : "Errore nel caricamento dei terapisti"
        );
      } finally {
        setTherapistsLoading(false);
      }
    };

    loadTherapists();
  }, [selectedSpecialization, selectedTreatmentType, isPrivateMode]);

  const handleSpecializationChange = (value: string) => {
    const specialization = availableSpecializations.find(
      (spec) => spec.id.toString() === value
    );
    setSelectedSpecialization(specialization || null);
  };

  const handleTreatmentTypeChange = (value: string) => {
    const treatmentType = treatmentTypes.find(
      (type) => type.id.toString() === value
    );
    setSelectedTreatmentType(treatmentType || null);
    if (onTreatmentTypeSelect) {
      onTreatmentTypeSelect(treatmentType || null);
    }
  };

  if (loading) {
    return (
      <Card className="bg-white shadow-lg border-0">
        <CardHeader className="pb-4">
          <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
            <Users className="h-5 w-5 text-blue-600" />
            Selezione Terapista
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin mr-2" />
            <span className="text-gray-600">Caricamento...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card className="bg-white shadow-lg border-0">
        <CardHeader className="pb-4">
          <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
            <Users className="h-5 w-5 text-blue-600" />
            Selezione Terapista
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="text-center p-4">
            <p className="text-red-600 mb-2">Errore nel caricamento</p>
            <p className="text-sm text-gray-600">{error}</p>
            <button
              onClick={() => window.location.reload()}
              className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Riprova
            </button>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-white shadow-lg border-0">
      <CardHeader className="pb-4">
        <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
          <Users className="h-5 w-5 text-blue-600" />
          Selezione Terapista{" "}
          {isPrivateMode && <Badge variant="secondary">Modalità Privata</Badge>}
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <Filter className="inline h-4 w-4 mr-1" />
              {isPrivateMode ? "Tipo di Trattamento" : "Specializzazione"}
            </label>

            {isPrivateMode ? (
              <Select
                value={selectedTreatmentType?.id?.toString() || ""}
                onValueChange={handleTreatmentTypeChange}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Seleziona tipo di trattamento" />
                </SelectTrigger>
                <SelectContent>
                  {treatmentTypes.map((type) => (
                    <SelectItem key={type.id} value={type.id.toString()}>
                      {type.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            ) : (
              <Select
                value={selectedSpecialization?.id?.toString() || ""}
                onValueChange={handleSpecializationChange}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Seleziona specializzazione" />
                </SelectTrigger>
                <SelectContent>
                  {availableSpecializations.map((spec) => (
                    <SelectItem key={spec.id} value={spec.id.toString()}>
                      {spec.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Terapista
            </label>
            <Select
              value={selectedTherapist?.id?.toString() || ""}
              onValueChange={(value) => {
                const therapist = therapists.find(
                  (t) => t.id.toString() === value
                );
                if (therapist) onTherapistSelect(therapist);
              }}
              disabled={
                (!selectedSpecialization && !selectedTreatmentType) ||
                therapistsLoading
              }
            >
              <SelectTrigger className="w-full">
                <SelectValue
                  placeholder={
                    !selectedSpecialization && !selectedTreatmentType
                      ? isPrivateMode
                        ? "Seleziona prima un tipo di trattamento"
                        : "Seleziona prima una specializzazione"
                      : therapistsLoading
                      ? "Caricamento terapisti..."
                      : "Seleziona terapista"
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {therapists.map((therapist) => (
                  <SelectItem
                    key={therapist.id}
                    value={therapist.id.toString()}
                  >
                    <div className="flex items-center gap-2">
                      <div
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: therapist.color }}
                      />
                      {therapist.name}
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {selectedTherapist && (
          <div className="mt-4 p-4 bg-blue-50 rounded-lg">
            <div className="flex items-center gap-3">
              <div
                className="w-4 h-4 rounded-full"
                style={{ backgroundColor: selectedTherapist.color }}
              />
              <div>
                <h3 className="font-semibold text-gray-900">
                  {selectedTherapist.name}
                </h3>
                <div className="flex items-center gap-2 mt-1">
                  <Badge variant="secondary">
                    {selectedTherapist.specialization}
                  </Badge>
                  <span className="text-sm text-gray-600">
                    {selectedTherapist.email}
                  </span>
                  {selectedTherapist.weeklyHours && !isPrivateMode && (
                    <span className="text-sm text-gray-500">
                      • {selectedTherapist.weeklyHours}h/settimana
                    </span>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {therapists.length === 0 &&
          (selectedSpecialization || selectedTreatmentType) &&
          !therapistsLoading && (
            <div className="text-center p-4 text-gray-500">
              <p>
                Nessun terapista trovato per{" "}
                {isPrivateMode
                  ? "il tipo di trattamento"
                  : "la specializzazione"}{" "}
                selezionato/a
              </p>
            </div>
          )}
      </CardContent>
    </Card>
  );
};
