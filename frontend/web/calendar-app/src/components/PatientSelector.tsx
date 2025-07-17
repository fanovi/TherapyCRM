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
import { Patient } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
import { User, Loader2 } from "lucide-react";

interface PatientSelectorProps {
  selectedPatient: Patient | null;
  onPatientSelect: (patient: Patient | null) => void;
}

export const PatientSelector: React.FC<PatientSelectorProps> = ({
  selectedPatient,
  onPatientSelect,
}) => {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Carica tutti i pazienti
  useEffect(() => {
    const loadPatients = async () => {
      try {
        setLoading(true);
        setError(null);

        const data = await therapyAPI.getPatients();
        setPatients(data);
      } catch (err) {
        console.error("Errore caricamento pazienti:", err);
        setError(
          err instanceof Error
            ? err.message
            : "Errore nel caricamento dei pazienti"
        );
      } finally {
        setLoading(false);
      }
    };

    loadPatients();
  }, []);

  const handlePatientChange = (value: string) => {
    if (value === "none") {
      onPatientSelect(null);
    } else {
      const patient = patients.find((p) => p.id.toString() === value);
      onPatientSelect(patient || null);
    }
  };

  if (loading) {
    return (
      <Card className="bg-white shadow-lg border-0">
        <CardHeader className="pb-4">
          <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
            <User className="h-5 w-5 text-blue-600" />
            Selezione Paziente
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-6 w-6 animate-spin mr-2" />
            <span className="text-gray-600">Caricamento pazienti...</span>
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
            <User className="h-5 w-5 text-red-600" />
            Selezione Paziente
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="text-red-600 text-center p-4">
            <p className="font-medium">Errore nel caricamento</p>
            <p className="text-sm mt-1">{error}</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-white shadow-lg border-0">
      <CardHeader className="pb-4">
        <CardTitle className="flex items-center gap-2 text-xl text-gray-800">
          <User className="h-5 w-5 text-blue-600" />
          Selezione Paziente
        </CardTitle>
        <div className="text-sm text-gray-500 mt-2">
          Seleziona il paziente per cui creare l'appuntamento
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <label className="block text-sm font-medium text-gray-700">
            Paziente
          </label>
          <Select
            value={selectedPatient?.id?.toString() || "none"}
            onValueChange={handlePatientChange}
          >
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Seleziona un paziente" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">
                <span className="text-gray-500">
                  Nessun paziente selezionato
                </span>
              </SelectItem>
              {patients.map((patient) => (
                <SelectItem key={patient.id} value={patient.id.toString()}>
                  <div className="flex items-center justify-between w-full">
                    <div className="flex flex-col">
                      <span className="font-medium">{patient.name}</span>
                      {patient.fiscalCode && (
                        <span className="text-xs text-gray-500">
                          CF: {patient.fiscalCode}
                        </span>
                      )}
                    </div>
                    <div className="flex gap-1 ml-2">
                      {patient.hasActiveTherapeuticPlans && (
                        <Badge variant="secondary" className="text-xs">
                          Piano Attivo
                        </Badge>
                      )}
                      {patient.canCreatePrivateAppointments && (
                        <Badge variant="outline" className="text-xs">
                          Privati
                        </Badge>
                      )}
                    </div>
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {selectedPatient && (
          <div className="p-3 bg-blue-50 rounded-lg mt-4">
            <p className="text-sm font-medium text-blue-900">
              Paziente selezionato: {selectedPatient.name}
            </p>
            <div className="flex gap-2 mt-2">
              {selectedPatient.hasActiveTherapeuticPlans && (
                <Badge variant="secondary" className="text-xs">
                  Piano Terapeutico Attivo
                </Badge>
              )}
              {selectedPatient.canCreatePrivateAppointments && (
                <Badge variant="outline" className="text-xs">
                  Appuntamenti Privati Disponibili
                </Badge>
              )}
            </div>
            {selectedPatient.birthDate && (
              <p className="text-xs text-blue-700 mt-1">
                Nato il:{" "}
                {new Date(selectedPatient.birthDate).toLocaleDateString(
                  "it-IT"
                )}
              </p>
            )}
          </div>
        )}

        {patients.length === 0 && (
          <div className="text-center p-4 text-gray-500">
            <p>Nessun paziente trovato nel sistema</p>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
