import {
  Therapist,
  Patient,
  Appointment,
  CreateAppointmentRequest,
  CreatePatternRequest,
  UpdateAppointmentRequest,
  APIResponse,
  CreatePatternResponse,
} from "@/types/therapy";

/**
 * Servizio API centralizzato per TherapeuticPlanManagerController
 * Base URL: /therapeutic-plan-manager/
 */
class TherapeuticPlanManagerAPI {
  private baseURL = "http://localhost/TherapyCRM/therapeutic-plan-manager";

  /**
   * Effettua una richiesta HTTP
   */
  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseURL}/${endpoint}`;

    const defaultHeaders: HeadersInit = {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };

    const config: RequestInit = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, config);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error(`API Error [${endpoint}]:`, error);
      throw error;
    }
  }

  /**
   * Effettua una richiesta GET
   */
  private async get<T>(
    endpoint: string,
    params?: Record<string, string | number>
  ): Promise<T> {
    let url = endpoint;

    if (params) {
      const searchParams = new URLSearchParams();
      Object.entries(params).forEach(([key, value]) => {
        searchParams.append(key, String(value));
      });
      url += `?${searchParams.toString()}`;
    }

    return this.request<T>(url, { method: "GET" });
  }

  /**
   * Effettua una richiesta POST
   */
  private async post<T>(endpoint: string, data?: any): Promise<T> {
    return this.request<T>(endpoint, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  // === GESTIONE TERAPISTI ===

  /**
   * Ottiene la lista di tutti i terapisti attivi
   */
  async getTherapists(): Promise<Therapist[]> {
    const response = await this.get<APIResponse<Therapist[]>>("get-therapists");

    if (!response.success) {
      throw new Error(response.error || "Errore nel caricamento terapisti");
    }

    return response.data || [];
  }

  /**
   * Ottiene la lista dei terapisti filtrati per tipo di trattamento
   */
  async getTherapistsByTreatment(
    treatmentTypeId: number
  ): Promise<Therapist[]> {
    const response = await this.get<APIResponse<Therapist[]>>(
      "get-therapists-by-treatment",
      {
        treatmentTypeId,
      }
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento terapisti per trattamento"
      );
    }

    return response.data || [];
  }

  // === GESTIONE PAZIENTI ===

  /**
   * Ottiene i dati anagrafici di un paziente
   */
  async getPatient(patientId: number): Promise<Patient> {
    const response = await this.get<APIResponse<Patient>>("get-patient", {
      id: patientId,
    });

    if (!response.success) {
      const error = new Error(
        response.error || "Errore nel caricamento dati paziente"
      ) as any;
      // Passa il codice di errore se presente
      if ((response as any).code) {
        error.code = (response as any).code;
      }
      throw error;
    }

    if (!response.data) {
      throw new Error("Paziente non trovato");
    }

    return response.data;
  }

  // === GESTIONE APPUNTAMENTI - LETTURA ===

  /**
   * Ottiene gli appuntamenti di un terapista per un mese specifico
   */
  async getTherapistAppointments(
    therapistId: number,
    month: number,
    year: number
  ): Promise<Appointment[]> {
    const response = await this.get<APIResponse<Appointment[]>>(
      "get-therapist-appointments",
      {
        therapistId,
        month,
        year,
      }
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento appuntamenti terapista"
      );
    }

    return response.data || [];
  }

  /**
   * Ottiene gli appuntamenti di un paziente per un mese specifico
   */
  async getPatientAppointments(
    patientId: number,
    month: number,
    year: number
  ): Promise<Appointment[]> {
    const response = await this.get<APIResponse<Appointment[]>>(
      "get-patient-appointments",
      {
        patientId,
        month,
        year,
      }
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento appuntamenti paziente"
      );
    }

    return response.data || [];
  }

  // === GESTIONE APPUNTAMENTI - CREAZIONE ===

  /**
   * Crea un singolo appuntamento
   */
  async createAppointment(request: CreateAppointmentRequest): Promise<{
    appointmentId: number;
    weeklyLimitExceeded: any[];
  }> {
    const response = await this.post<any>("create-appointment", request);

    if (!response.success) {
      // Se c'è un conflitto, includiamo le informazioni del conflitto nell'errore
      if (response.conflict) {
        const error = new Error(
          response.error || "Conflitto appuntamento rilevato"
        ) as any;
        error.conflict = response.conflict;
        throw error;
      }

      throw new Error(
        response.error || "Errore nella creazione dell'appuntamento"
      );
    }

    return response.data;
  }

  /**
   * Crea un pattern di appuntamenti ricorrenti
   */
  async createPattern(
    request: CreatePatternRequest
  ): Promise<CreatePatternResponse> {
    const response = await this.post<CreatePatternResponse | APIResponse<any>>(
      "create-pattern",
      request
    );

    if (!response.success) {
      const errorMessage =
        "error" in response
          ? response.error
          : "Errore nella creazione del pattern";
      throw new Error(errorMessage || "Errore nella creazione del pattern");
    }

    return response as CreatePatternResponse;
  }

  // === GESTIONE APPUNTAMENTI - MODIFICA ===

  /**
   * Aggiorna un singolo appuntamento
   */
  async updateAppointment(request: UpdateAppointmentRequest): Promise<{
    appointmentId: number;
  }> {
    const response = await this.post<any>("update-appointment", request);

    if (!response.success) {
      // Se c'è un conflitto, includiamo le informazioni del conflitto nell'errore
      if (response.conflict) {
        const error = new Error(
          response.error || "Conflitto appuntamento rilevato"
        ) as any;
        error.conflict = response.conflict;
        throw error;
      }

      throw new Error(
        response.error || "Errore nell'aggiornamento dell'appuntamento"
      );
    }

    return response.data;
  }

  /**
   * Aggiorna tutti gli appuntamenti futuri di un pattern
   */
  async updatePatternAppointments(request: {
    patternId: number;
    therapistId: number;
    startTime: string;
    durationMinutes: number;
    fromDate: string;
  }): Promise<{
    updatedCount: number;
    conflicts: any[];
  }> {
    const response = await this.post<any>(
      "update-pattern-appointments",
      request
    );

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nell'aggiornamento degli appuntamenti del pattern"
      );
    }

    return response.data;
  }

  /**
   * Cancella logicamente un appuntamento
   */
  async deleteAppointment(appointmentId: number): Promise<void> {
    const response = await this.post<any>("delete-appointment", {
      appointmentId,
    });

    if (!response.success) {
      throw new Error(
        response.error || "Errore nella cancellazione dell'appuntamento"
      );
    }
  }
}

// Esporta un'istanza singleton
export const therapyAPI = new TherapeuticPlanManagerAPI();

// Esporta anche la classe per casi speciali
export { TherapeuticPlanManagerAPI };
