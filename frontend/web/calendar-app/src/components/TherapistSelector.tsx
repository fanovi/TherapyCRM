import React, { useState } from "react";
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
import { Users, Filter } from "lucide-react";

// Mock data - in a real app this would come from an API
const mockTherapists: Therapist[] = [
  {
    id: "1",
    name: "Dr. Mario Rossi",
    specialization: "Fisioterapia",
    email: "mario.rossi@clinic.com",
    color: "#3b82f6",
  },
  {
    id: "2",
    name: "Dr.ssa Laura Bianchi",
    specialization: "Logopedia",
    email: "laura.bianchi@clinic.com",
    color: "#16a34a",
  },
  {
    id: "3",
    name: "Dr. Giuseppe Verdi",
    specialization: "Fisioterapia",
    email: "giuseppe.verdi@clinic.com",
    color: "#dc2626",
  },
  {
    id: "4",
    name: "Dr.ssa Anna Neri",
    specialization: "Psicomotricità",
    email: "anna.neri@clinic.com",
    color: "#7c3aed",
  },
];

const specializations = [
  "Tutte",
  "Fisioterapia",
  "Logopedia",
  "Psicomotricità",
];

interface TherapistSelectorProps {
  selectedTherapist: Therapist | null;
  onTherapistSelect: (therapist: Therapist) => void;
}

export const TherapistSelector: React.FC<TherapistSelectorProps> = ({
  selectedTherapist,
  onTherapistSelect,
}) => {
  const [selectedSpecialization, setSelectedSpecialization] = useState("Tutte");

  const filteredTherapists = mockTherapists.filter(
    (therapist) =>
      selectedSpecialization === "Tutte" ||
      therapist.specialization === selectedSpecialization
  );

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
                {specializations.map((spec) => (
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
              value={selectedTherapist?.id || ""}
              onValueChange={(value) => {
                const therapist = filteredTherapists.find(
                  (t) => t.id === value
                );
                if (therapist) onTherapistSelect(therapist);
              }}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Seleziona terapista" />
              </SelectTrigger>
              <SelectContent>
                {filteredTherapists.map((therapist) => (
                  <SelectItem key={therapist.id} value={therapist.id}>
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
                </div>
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
