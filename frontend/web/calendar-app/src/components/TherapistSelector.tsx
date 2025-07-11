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
import { Therapist } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { Users, Filter, Loader2 } from "lucide-react";

const specializations = ["Tutte"];

interface TherapistSelectorProps {
  selectedTherapist: Therapist | null;
  onTherapistSelect: (therapist: Therapist) => void;
}

export const TherapistSelector: React.FC<TherapistSelectorProps> = ({
  selectedTherapist,
  onTherapistSelect,
}) => {
  const [selectedSpecialization, setSelectedSpecialization] = useState("Tutte");
  const [therapists, setTherapists] = useState<Therapist[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [availableSpecializations, setAvailableSpecializations] = useState<
    string[]
  >(["Tutte"]);

  // Carica i terapisti dal backend
  useEffect(() => {
    const loadTherapists = async () => {
      try {
        setLoading(true);
        setError(null);

        const data = await therapyAPI.getTherapists();
        setTherapists(data);

        // Estrai le specializzazioni uniche e aggiungi colori casuali
        const specs = new Set<string>();
        const therapistsWithColors = data.map((therapist, index) => {
          if (therapist.specialization) {
            specs.add(therapist.specialization);
          }

          // Assegna un colore casuale se non presente
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
        setAvailableSpecializations(["Tutte", ...Array.from(specs).sort()]);
      } catch (err) {
        console.error("Errore caricamento terapisti:", err);
        setError(
          err instanceof Error
            ? err.message
            : "Errore nel caricamento dei terapisti"
        );
      } finally {
        setLoading(false);
      }
    };

    loadTherapists();
  }, []);

  const filteredTherapists = therapists.filter(
    (therapist) =>
      selectedSpecialization === "Tutte" ||
      therapist.specialization === selectedSpecialization
  );

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
            <span className="text-gray-600">Caricamento terapisti...</span>
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
          Selezione Terapista
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <Filter className="inline h-4 w-4 mr-1" />
              Specializzazione
            </label>
            <Select
              value={selectedSpecialization}
              onValueChange={setSelectedSpecialization}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Seleziona specializzazione" />
              </SelectTrigger>
              <SelectContent>
                {availableSpecializations.map((spec) => (
                  <SelectItem key={spec} value={spec}>
                    {spec}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Terapista
            </label>
            <Select
              value={selectedTherapist?.id?.toString() || ""}
              onValueChange={(value) => {
                const therapist = filteredTherapists.find(
                  (t) => t.id.toString() === value
                );
                if (therapist) onTherapistSelect(therapist);
              }}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Seleziona terapista" />
              </SelectTrigger>
              <SelectContent>
                {filteredTherapists.map((therapist) => (
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
                  {selectedTherapist.weeklyHours && (
                    <span className="text-sm text-gray-500">
                      • {selectedTherapist.weeklyHours}h/settimana
                    </span>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {filteredTherapists.length === 0 && !loading && (
          <div className="text-center p-4 text-gray-500">
            <p>Nessun terapista trovato per la specializzazione selezionata</p>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
