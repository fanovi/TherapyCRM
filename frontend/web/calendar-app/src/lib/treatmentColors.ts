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

/**
 * Schiarisce un colore esadecimale miscelandolo con il bianco.
 * factor 0 = colore originale, 1 = bianco. Usato per gli sfondi degli
 * eventi del calendario (richiesta direzione: sfondi chiari + testo nero),
 * cosi' qualunque colore scelto dal terapista resta leggibile.
 */
export const lightenColor = (hex: string, factor = 0.75): string => {
  const match = /^#?([0-9a-f]{6})$/i.exec((hex || "").trim());
  if (!match) {
    return hex;
  }
  const num = parseInt(match[1], 16);
  const channel = (value: number) =>
    Math.round(value + (255 - value) * factor)
      .toString(16)
      .padStart(2, "0");
  const r = (num >> 16) & 255;
  const g = (num >> 8) & 255;
  const b = num & 255;
  return `#${channel(r)}${channel(g)}${channel(b)}`;
};

/**
 * Colore base di un evento: priorita' al colore del terapista impostato
 * dal gestionale (therapists.calendar_color), fallback sul colore del
 * tipo di trattamento.
 */
export const getEventBaseColor = (
  therapistColor: string | null | undefined,
  treatmentName: string
): string => {
  return therapistColor || getTreatmentColor(treatmentName);
};
