import React, { useState, useEffect } from "react";
import { Clock, AlertTriangle, CheckCircle } from "lucide-react";
import { therapyAPI } from "@/lib/api";
import { Therapist } from "@/types/therapy";
import moment from "moment";
interface TherapistWeeklyHoursProps {
  therapist: Therapist;
  currentDate: Date;
  refreshTrigger?: number;
}

interface WeeklyHoursData {
  therapistId: number;
  weekStart: string;
  weekEnd: string;
  totalHours: number;
  contractHours: number;
  appointmentCount: number;
  isOverContract: boolean;
  remainingHours: number;
  exceededHours: number;
}

export const TherapistWeeklyHours: React.FC<TherapistWeeklyHoursProps> = ({
  therapist,
  currentDate,
  refreshTrigger,
}) => {
  const [weeklyHours, setWeeklyHours] = useState<WeeklyHoursData | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadWeeklyHours = async () => {
      if (!therapist) return;

      setLoading(true);
      setError(null);

      try {
        // Passa la domenica precedente al lunedì corrente
        // Così il backend calcolerà "monday this week" correttamente
        const startDate = moment(currentDate).format("YYYY-MM-DD");

        const data = await therapyAPI.getTherapistWeeklyHours(
          therapist.id,
          startDate
        );
        setWeeklyHours(data);
      } catch (err) {
        console.error("Errore caricamento ore settimanali:", err);
        setError(err instanceof Error ? err.message : "Errore sconosciuto");
      } finally {
        setLoading(false);
      }
    };

    loadWeeklyHours();
  }, [therapist, currentDate, refreshTrigger]);

  // Mostra loader solo al primo caricamento (quando non ci sono dati)
  // Durante il refresh, manteniamo i dati vecchi visibili
  if (loading && !weeklyHours) {
    return (
      <div className="flex items-center gap-2 text-gray-500">
        <Clock className="w-4 h-4 animate-spin" />
        <span className="text-sm">Caricamento ore...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center gap-2 text-red-500">
        <AlertTriangle className="w-4 h-4" />
        <span className="text-sm">Errore caricamento ore</span>
      </div>
    );
  }

  if (!weeklyHours) {
    return null;
  }

  const getStatusColor = () => {
    if (weeklyHours.isOverContract) {
      return "text-red-600";
    } else if (weeklyHours.totalHours >= weeklyHours.contractHours * 0.8) {
      return "text-yellow-600";
    } else {
      return "text-green-600";
    }
  };

  const getStatusIcon = () => {
    if (weeklyHours.isOverContract) {
      return <AlertTriangle className="w-4 h-4 text-red-500" />;
    } else {
      return <CheckCircle className="w-4 h-4 text-green-500" />;
    }
  };

  const getProgressWidth = () => {
    if (weeklyHours.contractHours === 0) return "0%";
    const percentage = Math.min(
      (weeklyHours.totalHours / weeklyHours.contractHours) * 100,
      100
    );
    return `${percentage}%`;
  };

  const getProgressColor = () => {
    if (weeklyHours.isOverContract) {
      return "bg-red-500";
    } else if (weeklyHours.totalHours >= weeklyHours.contractHours * 0.8) {
      return "bg-yellow-500";
    } else {
      return "bg-green-500";
    }
  };

  return (
    <div className="bg-white rounded-lg border p-4 shadow-sm">
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <Clock className="w-5 h-5 text-gray-600" />
          <h3 className="font-semibold text-gray-900">Ore Settimanali</h3>
        </div>
        {getStatusIcon()}
      </div>

      <div className="space-y-3">
        {/* Barra di progresso */}
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className={`h-2 rounded-full transition-all duration-300 ${getProgressColor()}`}
            style={{ width: getProgressWidth() }}
          />
        </div>

        {/* Statistiche */}
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="text-gray-500">Ore Effettive:</span>
            <div className={`font-semibold ${getStatusColor()}`}>
              {weeklyHours.totalHours}h
            </div>
          </div>
          <div>
            <span className="text-gray-500">Ore Contratto:</span>
            <div className="font-semibold text-gray-900">
              {weeklyHours.contractHours}h
            </div>
          </div>
          <div>
            <span className="text-gray-500">Appuntamenti:</span>
            <div className="font-semibold text-gray-900">
              {weeklyHours.appointmentCount}
            </div>
          </div>
          <div>
            <span className="text-gray-500">
              {weeklyHours.isOverContract ? "Eccedenza:" : "Rimanenti:"}
            </span>
            <div
              className={`font-semibold ${
                weeklyHours.isOverContract ? "text-red-600" : "text-green-600"
              }`}
            >
              {weeklyHours.isOverContract
                ? weeklyHours.exceededHours
                : weeklyHours.remainingHours}
              h
            </div>
          </div>
        </div>

        {/* Periodo */}
        <div className="text-xs text-gray-500 pt-2 border-t">
          Settimana: {moment(weeklyHours.weekStart).format("DD/MM/YYYY")} -{" "}
          {moment(weeklyHours.weekEnd).format("DD/MM/YYYY")}
        </div>
      </div>
    </div>
  );
};
