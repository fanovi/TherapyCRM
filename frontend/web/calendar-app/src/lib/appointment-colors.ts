import { Appointment } from "@/types/therapy";

// Colori predefiniti per i pazienti (modalità terapista)
const PATIENT_COLORS = [
  "#3b82f6", // Blue
  "#16a34a", // Green
  "#dc2626", // Red
  "#7c3aed", // Purple
  "#ea580c", // Orange
  "#0891b2", // Cyan
  "#be185d", // Pink
  "#059669", // Emerald
  "#7c2d12", // Brown
  "#1e40af", // Blue-700
];

// Colori per modalità paziente
const PATIENT_MODE_COLORS = {
  currentPatient: "#3b82f6", // Blue - appuntamenti del paziente corrente
  otherPatients: "#9ca3af", // Gray - appuntamenti con altri pazienti
};

/**
 * Genera un colore per un paziente specifico basato sul suo ID
 */
export function getPatientColor(patientId: number): string {
  return PATIENT_COLORS[patientId % PATIENT_COLORS.length];
}

/**
 * Determina il colore di un appuntamento basato sulla modalità
 */
export function getAppointmentColor(
  appointment: Appointment,
  mode: "patient" | "therapist",
  currentPatientId?: number
): string {
  if (mode === "patient") {
    // Modalità paziente: distingue appuntamenti del paziente corrente vs altri
    if (currentPatientId && appointment.patient?.id === currentPatientId) {
      return PATIENT_MODE_COLORS.currentPatient;
    } else {
      return PATIENT_MODE_COLORS.otherPatients;
    }
  } else {
    // Modalità terapista: ogni paziente ha un colore diverso
    if (appointment.patient?.id) {
      return getPatientColor(appointment.patient.id);
    }
    return PATIENT_COLORS[0]; // Fallback
  }
}

/**
 * Genera una mappa paziente -> colore per la legenda
 */
export function generatePatientColorMap(
  appointments: Appointment[]
): Record<number, { name: string; color: string }> {
  const patientMap: Record<number, { name: string; color: string }> = {};

  appointments.forEach((appointment) => {
    if (appointment.patient?.id && !patientMap[appointment.patient.id]) {
      patientMap[appointment.patient.id] = {
        name: appointment.patient.name,
        color: getPatientColor(appointment.patient.id),
      };
    }
  });

  return patientMap;
}

/**
 * Ottiene informazioni per il tooltip dell'appuntamento
 */
export function getAppointmentTooltip(
  appointment: Appointment,
  mode: "patient" | "therapist"
): string {
  if (mode === "patient") {
    // In modalità paziente mostra tipo di terapia e nome paziente
    const treatmentType = appointment.treatmentType || "Terapia";
    const patientName = appointment.patient?.name || "Paziente sconosciuto";
    return `${treatmentType} - ${patientName}`;
  } else {
    // In modalità terapista mostra tipo di terapia e nome paziente
    const treatmentType = appointment.treatmentType || "Terapia";
    const patientName = appointment.patient?.name || "Paziente sconosciuto";
    return `${treatmentType} - ${patientName}`;
  }
}
