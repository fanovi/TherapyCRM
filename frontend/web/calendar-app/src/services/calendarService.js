import axios from "axios";

const api = axios.create({
  baseURL: "/api",
  timeout: 5000,
});

// Dati mock per sviluppo e test
const mockTherapists = [
  {
    id: 1,
    title: "Dott. Marco Bianchi",
    eventColor: "#3b82f6",
    extendedProps: {
      specialization: "Logopedia",
      specializationCode: "LOG",
      weeklyHours: 38,
      isActive: true,
    },
  },
  {
    id: 2,
    title: "Dott.ssa Anna Verdi",
    eventColor: "#10b981",
    extendedProps: {
      specialization: "Fisioterapia",
      specializationCode: "FKT",
      weeklyHours: 40,
      isActive: true,
    },
  },
  {
    id: 3,
    title: "Dott.ssa Laura Rossi",
    eventColor: "#f59e0b",
    extendedProps: {
      specialization: "Psicomotricità",
      specializationCode: "PSI",
      weeklyHours: 36,
      isActive: true,
    },
  },
];

const mockAppointments = [
  {
    id: "1",
    title: "Mario Rossi - Logopedia",
    start: "2025-06-16T09:00:00",
    end: "2025-06-16T10:00:00",
    backgroundColor: "#22c55e",
    borderColor: "#22c55e",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 1,
      therapistId: 1,
      patientName: "Mario Rossi",
      therapistName: "Dott. Marco Bianchi",
      treatmentName: "Logopedia Individuale",
      treatmentCode: "LOG_IND",
      duration: 60,
      status: "completed",
      location: "office",
    },
  },
  {
    id: "2",
    title: "Giulia Neri - Fisioterapia",
    start: "2025-06-16T10:30:00",
    end: "2025-06-16T11:30:00",
    backgroundColor: "#3b82f6",
    borderColor: "#3b82f6",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 2,
      therapistId: 2,
      patientName: "Giulia Neri",
      therapistName: "Dott.ssa Anna Verdi",
      treatmentName: "Fisioterapia Individuale",
      treatmentCode: "FKT_IND",
      duration: 60,
      status: "scheduled",
      location: "office",
    },
  },
  {
    id: "3",
    title: "Luca Bianchi - Psicomotricità",
    start: "2025-06-17T14:00:00",
    end: "2025-06-17T15:00:00",
    backgroundColor: "#f59e0b",
    borderColor: "#d97706",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 3,
      therapistId: 3,
      patientName: "Luca Bianchi",
      therapistName: "Dott.ssa Laura Rossi",
      treatmentName: "Psicomotricità Individuale",
      treatmentCode: "PSI_IND",
      duration: 60,
      status: "absent_justified",
      location: "office",
    },
  },
  {
    id: "4",
    title: "Sofia Verde - Logopedia",
    start: "2025-06-17T15:30:00",
    end: "2025-06-17T16:30:00",
    backgroundColor: "#ef4444",
    borderColor: "#dc2626",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 4,
      therapistId: 1,
      patientName: "Sofia Verde",
      therapistName: "Dott. Marco Bianchi",
      treatmentName: "Logopedia Individuale",
      treatmentCode: "LOG_IND",
      duration: 60,
      status: "absent_not_justified",
      location: "office",
    },
  },
  {
    id: "5",
    title: "Marco Blu - Fisioterapia",
    start: "2025-06-18T09:30:00",
    end: "2025-06-18T10:30:00",
    backgroundColor: "#3b82f6",
    borderColor: "#2563eb",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 5,
      therapistId: 2,
      patientName: "Marco Blu",
      therapistName: "Dott.ssa Anna Verdi",
      treatmentName: "Fisioterapia Individuale",
      treatmentCode: "FKT_IND",
      duration: 60,
      status: "scheduled",
      location: "office",
    },
  },
  {
    id: "6",
    title: "Elena Gialli - Psicomotricità",
    start: "2025-06-18T16:00:00",
    end: "2025-06-18T17:00:00",
    backgroundColor: "#6b7280",
    borderColor: "#4b5563",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 6,
      therapistId: 3,
      patientName: "Elena Gialli",
      therapistName: "Dott.ssa Laura Rossi",
      treatmentName: "Psicomotricità Individuale",
      treatmentCode: "PSI_IND",
      duration: 60,
      status: "cancelled",
      location: "office",
    },
  },
  {
    id: "7",
    title: "Andrea Viola - Logopedia",
    start: "2025-06-19T11:00:00",
    end: "2025-06-19T12:00:00",
    backgroundColor: "#22c55e",
    borderColor: "#16a34a",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 7,
      therapistId: 1,
      patientName: "Andrea Viola",
      therapistName: "Dott. Marco Bianchi",
      treatmentName: "Logopedia Individuale",
      treatmentCode: "LOG_IND",
      duration: 60,
      status: "completed",
      location: "office",
    },
  },
  {
    id: "8",
    title: "Chiara Rosa - Fisioterapia",
    start: "2025-06-19T14:30:00",
    end: "2025-06-19T15:30:00",
    backgroundColor: "#3b82f6",
    borderColor: "#2563eb",
    editable: true,
    startEditable: true,
    durationEditable: true,
    extendedProps: {
      patientId: 8,
      therapistId: 2,
      patientName: "Chiara Rosa",
      therapistName: "Dott.ssa Anna Verdi",
      treatmentName: "Fisioterapia Individuale",
      treatmentCode: "FKT_IND",
      duration: 60,
      status: "scheduled",
      location: "office",
    },
  },
];

// Simula delay di rete
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export default {
  getTherapistAppointments: async (therapistId, startDate, endDate) => {
    console.log(
      `🔍 Mock: Loading appointments for therapist ${therapistId} from ${startDate} to ${endDate}`
    );
    await delay(300); // Simula latenza

    const filtered = mockAppointments.filter(
      (apt) => apt.extendedProps.therapistId == therapistId
    );

    return {
      success: true,
      data: filtered,
    };
  },

  getAllAppointments: async (startDate, endDate) => {
    console.log(
      `🔍 Mock: Loading all appointments from ${startDate} to ${endDate}`
    );
    await delay(300); // Simula latenza

    return {
      success: true,
      data: mockAppointments,
    };
  },

  getTherapists: async () => {
    console.log("🔍 Mock: Loading therapists");
    await delay(200); // Simula latenza

    return {
      success: true,
      data: mockTherapists,
    };
  },

  markAttendance: async (appointmentId, status, reason) => {
    console.log(
      `✅ Mock: Marking attendance for appointment ${appointmentId} as ${status}`
    );
    await delay(500); // Simula latenza

    // Trova e aggiorna l'appuntamento mock
    const appointment = mockAppointments.find(
      (apt) => apt.id === appointmentId
    );
    if (appointment) {
      appointment.extendedProps.status = status;

      // Aggiorna colore in base al nuovo status
      const colors = {
        completed: { bg: "#22c55e", border: "#16a34a" },
        absent_justified: { bg: "#f59e0b", border: "#d97706" },
        absent_not_justified: { bg: "#ef4444", border: "#dc2626" },
        cancelled: { bg: "#6b7280", border: "#4b5563" },
        scheduled: { bg: "#3b82f6", border: "#2563eb" },
      };

      const color = colors[status] || colors.scheduled;
      appointment.backgroundColor = color.bg;
      appointment.borderColor = color.border;
    }

    return {
      success: true,
      data: appointment,
    };
  },

  updateAppointment: async (id, updates) => {
    console.log(`📝 Mock API: Updating appointment ${id}`, updates);

    // Simula delay di rete
    await new Promise((resolve) => setTimeout(resolve, 300));

    // Trova l'appuntamento nei mock data
    const appointmentIndex = mockAppointments.findIndex((apt) => apt.id === id);

    if (appointmentIndex === -1) {
      console.error(`❌ Appointment ${id} not found`);
      return {
        success: false,
        error: `Appuntamento ${id} non trovato`,
      };
    }

    // Validazioni business logic
    if (updates.start && updates.end) {
      const startTime = new Date(updates.start);
      const endTime = new Date(updates.end);
      const duration = (endTime - startTime) / (1000 * 60); // minuti

      // Controllo durata minima (15 minuti)
      if (duration < 15) {
        console.error(`❌ Duration too short: ${duration} minutes`);
        return {
          success: false,
          error: "La durata minima è di 15 minuti",
        };
      }

      // Controllo durata massima (4 ore)
      if (duration > 240) {
        console.error(`❌ Duration too long: ${duration} minutes`);
        return {
          success: false,
          error: "La durata massima è di 4 ore",
        };
      }

      // Controllo orari di lavoro (8:00 - 19:00)
      const startHour = startTime.getHours();
      const endHour = endTime.getHours();

      if (
        startHour < 8 ||
        endHour > 19 ||
        (endHour === 19 && endTime.getMinutes() > 0)
      ) {
        console.error(
          `❌ Outside business hours: ${startHour}:${startTime.getMinutes()} - ${endHour}:${endTime.getMinutes()}`
        );
        return {
          success: false,
          error: "Gli appuntamenti devono essere tra le 8:00 e le 19:00",
        };
      }

      // Controllo sovrapposizioni con altri appuntamenti dello stesso terapista
      const currentAppointment = mockAppointments[appointmentIndex];
      const conflicts = mockAppointments.filter(
        (apt) =>
          apt.id !== id &&
          apt.extendedProps.therapistId ===
            currentAppointment.extendedProps.therapistId &&
          apt.extendedProps.status === "scheduled" &&
          ((startTime >= new Date(apt.start) &&
            startTime < new Date(apt.end)) ||
            (endTime > new Date(apt.start) && endTime <= new Date(apt.end)) ||
            (startTime <= new Date(apt.start) && endTime >= new Date(apt.end)))
      );

      if (conflicts.length > 0) {
        console.error(
          `❌ Conflict detected with appointments:`,
          conflicts.map((c) => c.id)
        );
        return {
          success: false,
          error: `Conflitto con appuntamento esistente (${conflicts[0].extendedProps.patientName})`,
        };
      }
    }

    // Aggiorna l'appuntamento
    const oldAppointment = { ...mockAppointments[appointmentIndex] };
    mockAppointments[appointmentIndex] = {
      ...mockAppointments[appointmentIndex],
      ...updates,
      updatedAt: new Date().toISOString(),
    };

    const updatedAppointment = mockAppointments[appointmentIndex];

    console.log(`✅ Mock API: Appointment ${id} updated successfully`, {
      old: {
        start: oldAppointment.start,
        end: oldAppointment.end,
        duration: oldAppointment.extendedProps?.duration,
      },
      new: {
        start: updatedAppointment.start,
        end: updatedAppointment.end,
        duration: updatedAppointment.extendedProps?.duration,
      },
    });

    return {
      success: true,
      data: updatedAppointment,
      message: "Appuntamento aggiornato con successo",
    };
  },

  createAppointment: async (appointmentData) => {
    console.log("➕ Mock: Creating new appointment", appointmentData);
    await delay(500);

    const newId = Math.max(...mockAppointments.map((a) => parseInt(a.id))) + 1;
    const newAppointment = {
      id: newId.toString(),
      title: `${appointmentData.patientName} - ${appointmentData.treatmentName}`,
      start: appointmentData.appointment_datetime,
      end: new Date(
        new Date(appointmentData.appointment_datetime).getTime() +
          (appointmentData.duration_minutes || 60) * 60000
      ).toISOString(),
      backgroundColor: "#3b82f6",
      borderColor: "#2563eb",
      editable: true,
      startEditable: true,
      durationEditable: true,
      extendedProps: {
        ...appointmentData,
        status: "scheduled",
      },
    };

    mockAppointments.push(newAppointment);

    return {
      success: true,
      data: newAppointment,
    };
  },

  deleteAppointment: async (id) => {
    console.log(`🗑️ Mock: Deleting appointment ${id}`);
    await delay(300);

    const index = mockAppointments.findIndex((apt) => apt.id === id);
    if (index > -1) {
      mockAppointments.splice(index, 1);
    }

    return {
      success: true,
      message: "Appuntamento eliminato",
    };
  },
};
