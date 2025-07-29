import React, { useState, useEffect } from "react";
import { Clock, AlertTriangle, CheckCircle } from "lucide-react";
import { therapyAPI } from "@/lib/api";
import { Specialization } from "@/types/therapy";
import moment from "moment";
import "moment/locale/it"; // Importa la localizzazione italiana

interface PlanTherapyUsedHoursProps {
  specialization: Specialization | null;
  currentDate: Date;
  isABARegime?: boolean;
}

interface PlanTherapyHoursData {
  is_private: boolean;
  treatment_type: string;
  periodo_tipo: string;
  periodo_inizio: string;
  periodo_fine: string;
  ore_assegnate: number;
  minuti_assegnati: string;
  ore_limite: string;
  ore_rimanenti: number;
  minuti_limite: number;
  minuti_rimanenti: number;
  plan_therapy_id: string;
}

export const PlanTherapyUsedHours: React.FC<PlanTherapyUsedHoursProps> = ({
  specialization,
  currentDate,
  isABARegime = false,
}) => {
  const [planHours, setPlanHours] = useState<PlanTherapyHoursData | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadPlanTherapyHours = async () => {
      if (!specialization?.id) return;

      setLoading(true);
      setError(null);

      try {
        // Per ABA usa l'inizio del mese, altrimenti usa l'inizio della settimana
        const startDate = isABARegime
          ? moment(currentDate).startOf("month").format("YYYY-MM-DD")
          : moment(currentDate).startOf("isoWeek").format("YYYY-MM-DD");

        const response = await therapyAPI.getPlanTherapyUsedHours(
          specialization.id,
          startDate
        );

        setPlanHours(response);
      } catch (err) {
        console.error("Errore caricamento ore piano terapeutico:", err);
        setError(err instanceof Error ? err.message : "Errore sconosciuto");
      } finally {
        setLoading(false);
      }
    };

    loadPlanTherapyHours();
  }, [specialization, currentDate, isABARegime]);

  if (loading) {
    return (
      <div className="flex items-center gap-2 text-gray-500">
        <Clock className="w-4 h-4 animate-spin" />
        <span className="text-sm">Caricamento ore piano terapeutico...</span>
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

  if (!planHours || !specialization) {
    return null;
  }

  // ore_assegnate = ore già utilizzate/assegnate nel periodo
  // ore_limite = limite massimo di ore per il periodo
  // ore_rimanenti = ore ancora disponibili rispetto al limite
  const oreLimite = parseFloat(planHours.ore_limite);
  const oreAssegnate = planHours.ore_assegnate;
  const isOverLimit = oreAssegnate > oreLimite;

  const getStatusColor = () => {
    if (isOverLimit) {
      return "text-red-600";
    } else if (oreAssegnate >= oreLimite * 0.8) {
      return "text-yellow-600";
    } else {
      return "text-green-600";
    }
  };

  const getStatusIcon = () => {
    if (isOverLimit) {
      return <AlertTriangle className="w-4 h-4 text-red-500" />;
    } else {
      return <CheckCircle className="w-4 h-4 text-green-500" />;
    }
  };

  const getProgressWidth = () => {
    if (oreLimite === 0) return "0%";
    const percentage = Math.min((oreAssegnate / oreLimite) * 100, 100);
    return `${percentage}%`;
  };

  const getProgressColor = () => {
    if (isOverLimit) {
      return "bg-red-500";
    } else if (oreAssegnate >= oreLimite * 0.8) {
      return "bg-yellow-500";
    } else {
      return "bg-green-500";
    }
  };

  const periodLabel =
    planHours.periodo_tipo === "mensile" ? "Mensili" : "Settimanali";

  return (
    <div className="bg-white rounded-lg border p-4 shadow-sm">
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <Clock className="w-5 h-5 text-gray-600" />
          <h3 className="font-semibold text-gray-900">
            Ore Piano Terapeutico {periodLabel}
          </h3>
        </div>
        {getStatusIcon()}
      </div>

      <div className="space-y-3">
        {/* Nome del piano/specializzazione */}
        <div className="text-sm text-gray-600 font-medium">
          {planHours.treatment_type}
          {planHours.is_private && (
            <span className="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded">
              Privato
            </span>
          )}
        </div>

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
            <span className="text-gray-500">Ore Assegnate:</span>
            <div className={`font-semibold ${getStatusColor()}`}>
              {oreAssegnate}h
            </div>
          </div>
          <div>
            <span className="text-gray-500">Ore Limite:</span>
            <div className="font-semibold text-gray-900">{oreLimite}h</div>
          </div>
          <div>
            <span className="text-gray-500">Minuti Utilizzati:</span>
            <div className="font-semibold text-gray-600">
              {planHours.minuti_assegnati}
            </div>
          </div>
          <div>
            <span className="text-gray-500">Ore Rimanenti:</span>
            <div
              className={`font-semibold ${
                planHours.ore_rimanenti > 0 ? "text-green-600" : "text-red-600"
              }`}
            >
              {planHours.ore_rimanenti}h
            </div>
          </div>
        </div>

        {/* Periodo */}
        <div className="text-xs text-gray-500 pt-2 border-t">
          {planHours.periodo_tipo === "mensile" ? (
            <>Mese: {moment(planHours.periodo_inizio).format("MMMM YYYY")}</>
          ) : (
            <>
              Settimana: {moment(planHours.periodo_inizio).format("DD/MM/YYYY")}{" "}
              - {moment(planHours.periodo_fine).format("DD/MM/YYYY")}
            </>
          )}
        </div>
      </div>
    </div>
  );
};
