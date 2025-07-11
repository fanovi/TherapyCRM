export interface Specialization {
  id: number;
  name: string;
}

export interface Therapist {
  id: number;
  name: string;
  email: string;
  specialization: string;
  weeklyHours?: number;
  color?: string;
}

export interface Patient {
  id: number;
  name: string;
  birthDate?: string;
  fiscalCode?: string;
  email?: string;
  hasActiveTherapeuticPlans?: boolean;
  planTherapy?: {
    planTherapyId: number;
    therapeuticPlanId: number;
    startDate: string;
    endDate: string;
    durationDays: number;
    weeklyHours: number;
    notes?: string;
  };
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
  id: number;
  datetime: string; // formato ISO datetime dalla API
  duration: number; // duration_minutes dall'API
  status: "scheduled" | "completed" | "cancelled";
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
}

export interface AppointmentData {
  therapyType: string;
  duration: number;
  notes?: string;
  isRecurring?: boolean;
  planTherapy?: PlanTherapy;
}

// Tipi per le richieste API
export interface CreateAppointmentRequest {
  planTherapyId: number;
  therapistId: number;
  appointmentDateTime: string; // YYYY-MM-DD HH:mm:ss
  durationMinutes: number;
  notes?: string;
}

export interface CreatePatternRequest {
  planTherapyId: number;
  therapistId: number;
  dayOfWeek: number; // 1=Lunedì, 7=Domenica
  startTime: string; // HH:mm
  durationMinutes: number;
  validFrom: string; // YYYY-MM-DD
  validTo: string; // YYYY-MM-DD
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
