export interface Therapist {
  id: string;
  name: string;
  specialization: string;
  email: string;
  color: string;
}

export interface Appointment {
  id: string;
  therapistId: string;
  therapistName: string;
  patientName: string;
  date: Date;
  time: string;
  duration: number;
  therapyType: string;
  notes?: string;
  isRecurring: boolean;
}

export interface AppointmentData {
  therapyType: string;
  duration: number;
  notes?: string;
  isRecurring: boolean;
}

export interface TimeSlot {
  time: string;
  isAvailable: boolean;
  appointment?: Appointment;
}
