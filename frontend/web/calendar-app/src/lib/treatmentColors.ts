export const TREATMENT_COLORS: Record<string, string> = {
  // Logopedia - Verde
  Logopedia: "#10B981",
  "Logopedista PG": "#059669",

  // Neuropsicomotricità - Blu
  Neuropsicomotricità: "#3B82F6",
  "Neuropsicomotricità PG": "#1D4ED8",

  // Fisioterapia - Arancione
  "Riabilitazione Neuromotoria": "#F59E0B",
  "FKT respiratoria": "#D97706",
  Fisiokinesiterapia: "#F97316",

  // Terapia Occupazionale - Viola
  "Terapia Occupazionale": "#8B5CF6",
  "Terapia Occupazionale PG": "#7C3AED",

  // Psicoterapia - Rosa
  Psicoterapia: "#EC4899",
  "Psicoterapia PG": "#DB2777",

  // Parent Training - Indaco
  "Parental Training": "#6366F1",

  // Supervisione - Teal
  Supervisione: "#14B8A6",

  // Default
  default: "#6B7280",
};

export const getTreatmentColor = (treatmentName: string): string => {
  return TREATMENT_COLORS[treatmentName] || TREATMENT_COLORS.default;
};
