export const TREATMENT_COLORS: Record<string, string> = {
  // Logopedia - Fucsia
  Logopedia: "#D946EF",
  "Logopedista PG": "#D946EF",

  // Neuropsicomotricità - Verde
  Neuropsicomotricità: "#22C55E",
  "Neuropsicomotricità PG": "#22C55E",

  // Fisioterapia - Beige
  "Riabilitazione Neuromotoria": "#D2B48C",
  "FKT respiratoria": "#D2B48C",
  Fisiokinesiterapia: "#D2B48C",

  // Terapia Occupazionale - Celeste
  "Terapia Occupazionale": "#38BDF8",
  "Terapia Occupazionale PG": "#38BDF8",

  // Psicoterapia - Beige
  Psicoterapia: "#D2B48C",
  "Psicoterapia PG": "#D2B48C",

  // Parent Training - Arancione
  "Parental Training": "#F97316",

  // Supervisione - Rosso
  Supervisione: "#EF4444",

  // RBT - Giallo
  "RBT - Registered Behavior Technician": "#FACC15",

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
