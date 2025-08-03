export interface Specialization {
  treatment_type_id: any;
  id: number;
  name: string;
}

export interface SpecializationTreatment {
  id: number;
  specialization_id: number;
  treatment_type_id: number;
  treatment_type_name: string;
  specialization_name: string;
  full_name: string;
}

export interface Therapist {
  id: number;
  name: string;
  email: string;
  specialization: string;
  specializationId?: number;
  weeklyHours?: number;
  color?: string;
  isAvailable?: boolean;
  unavailabilityReason?: string;
}

export interface Patient {
  id: number;
  name: string;
  birthDate?: string;
  fiscalCode?: string;
  email?: string;
  hasActiveTherapeuticPlans?: boolean;
  therapeuticPlan?: {
    id: number;
    startDate: string;
    endDate: string;
    durationDays: number;
    regime?: {
      id: number;
      nome: string;
      descrizione: string;
      conteggio_ore: string;
    };
  };
  planTherapy?: {
    planTherapyId: number;
    therapeuticPlanId: number;
    startDate: string;
    endDate: string;
    durationDays: number;
    weeklyHours: number;
    notes?: string;
  };
  availableTherapies?: {
    planTherapyId: number;
    treatmentTypeId: number;
    treatmentTypeName: string;
    weeklyHours: number;
    isGroup: boolean;
    notes?: string;
  }[];

  canCreatePrivateAppointments?: boolean; // Sempre true secondo il controller
}

export interface PlanTherapy {
  planTherapyId: number;
  therapeuticPlanId: number;
  patientId: number;
  patientName: string;
  startDate: string;
  endDate: string;
  durationDays: number;
  weeklyHours: number;
  notes?: string;
}

export interface Appointment {
  groupSessionId: null;
  id: number;
  datetime: string; // formato ISO datetime dalla API
  duration: number; // duration_minutes dall'API
  status:
    | "scheduled"
    | "completed"
    | "cancelled"
    | "confirmed"
    | "pending"
    | "therapist_absent"
    | "absent_justified"
    | "absent_not_justified";
  notes?: string;
  // Per appointment di paziente
  treatmentType?: string;
  therapist?: {
    id: number;
    name: string;
  };
  // Per appointment di terapista
  patient?: {
    id: number;
    name: string;
  };
  // Informazioni sul pattern ricorrente
  patternId?: number;
  isRecurring?: boolean;

  // Informazioni sui cicli privati
  privateCycleId?: number;

  appointmentSource?: "therapeutic_plan" | "private";

  isPrivate?: boolean;
  appointmentType?: "terapia" | "parent_training" | "supervisione";
}

export interface AppointmentData {
  therapyType: string;
  duration: number;
  notes?: string;
  isRecurring?: boolean;
  planTherapy?: PlanTherapy;
  appointmentType?: "terapia" | "parent_training" | "supervisione";
  isGroup?: boolean;
}

// Tipi per le richieste API
export interface CreateAppointmentRequest {
  planTherapyId: number;
  treatmentTypeId?: number;
  therapistId: number;
  appointmentDateTime: string; // YYYY-MM-DD HH:mm:ss
  durationMinutes: number;
  notes?: string;
  isGroup?: boolean;
}

export interface CreatePatternRequest {
  planTherapyId: number;
  therapistId: number;
  patientId: number; // AGGIUNGI QUESTA RIGA
  dayOfWeek: number; // 1=Lunedì, 7=Domenica
  startTime: string; // HH:mm
  durationMinutes: number;
  validFrom: string; // YYYY-MM-DD
  validTo?: string; // YYYY-MM-DD - opzionale, verrà ricavato dal backend se non fornito
  isGroup?: boolean;
}

export interface UpdateAppointmentRequest {
  appointmentId: number;
  therapistId: number;
  appointmentDateTime: string;
  durationMinutes: number;
  notes?: string;
}

export interface DeletePatternRequest {
  patternId: number;
  fromDate: string; // YYYY-MM-DD - cancella dal questo appuntamento in poi
}

// Tipi per le risposte API
export interface APIResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
  code?: string;
}

export interface CreatePatternResponse {
  success: boolean;
  appointmentsCreated: number;
  conflicts: ConflictInfo[];
  weeklyLimitExceeded: WeeklyLimitInfo[];
  data: {
    patternId: number;
  };
  message: string;
}

export interface ConflictInfo {
  date?: string;
  time?: string;
  therapistId?: number;
  existingAppointmentId: number;
  existingAppointmentInfo: {
    patientName: string;
    startTime: string;
    endTime: string;
  };
}

export interface WeeklyLimitInfo {
  weekStartDate: string;
  currentHours: number;
  limitHours: number;
  newTotal: number;
}

// Aggiungi questi tipi al file @/types/therapy

export interface TreatmentType {
  id: number;
  name: string;
  description?: string;
}

export interface PrivateAppointmentData {
  treatmentTypeId?: number; // Opzionale nella vista terapista
  treatmentTypeName?: string; // Opzionale nella vista terapista
  duration: number;
  notes?: string;
  isRecurring?: boolean;
}

export interface TherapistSubstitutionRequest {
  appointmentId: number;
  newTherapistId: number;
  reason?: string;
  dontRegisterAbsence?: boolean;
}

export interface TherapistSubstitutionResponse {
  success: boolean;
  message?: string;
  data?: {
    appointmentId: number;
    originalTherapistId: number;
    newTherapistId: number;
    substitutionId: number;
  };
}

export interface TherapistAbsence {
  id: number;
  therapist_id: number;
  start_date: string;
  end_date: string;
  type: "vacation" | "sick" | "training" | "other";
  reason: string;
  status: "pending" | "approved" | "rejected";
  approved_by?: number;
  approved_at?: string;
  notes?: string;
  created_by: number;
  created_at: string;
  updated_at: string;
}

export interface TherapistAbsencesResponse {
  therapist_id: string;
  filters: {
    start_date: string | null;
    end_date: string | null;
  };
  total: number;
  absences: TherapistAbsence[];
}
