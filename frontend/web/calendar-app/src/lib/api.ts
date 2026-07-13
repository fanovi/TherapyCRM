import {
  Therapist,
  Patient,
  Appointment,
  CreateAppointmentRequest,
  CreatePatternRequest,
  UpdateAppointmentRequest,
  DeletePatternRequest,
  APIResponse,
  CreatePatternResponse,
  TherapistSubstitutionRequest,
  TherapistSubstitutionResponse,
  SpecializationTreatment,
  TherapistAbsence,
  TherapistAbsencesResponse,
  ResolveSpecializationResponse,
  ActivePatientPlan,
} from "@/types/therapy";

/**
 * Servizio API centralizzato per TherapeuticPlanManagerController
 * Base URL: /therapeutic-plan-manager/
 */
class TherapeuticPlanManagerAPI {
  private baseURL = "https://app.gruppovitolo.local/therapeutic-plan-manager"; //"https://app-cgm.badil.it/therapeutic-plan-manager"; //app-cgm.badil.it

  /**
   * Restituisce l'origin dell'app (senza /therapeutic-plan-manager).
   * Usato per costruire link esterni come /calendar/{id}.
   */
  getAppOrigin(): string {
    return this.baseURL.replace(/\/therapeutic-plan-manager\/?$/, "");
  }

  // Cache per i settings
  private settingsCache: { id: number; nome: string }[] | null = null;
  private settingsCacheTimestamp: number = 0;
  private readonly CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 ore

  /**
   * Effettua una richiesta HTTP
   */
  private async request<T>(
    endpoint: string,
    options: RequestInit = {},
  ): Promise<T> {
    const url = `${this.baseURL}/${endpoint}`;

    const defaultHeaders: HeadersInit = {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };

    const config: RequestInit = {
      ...options,
      credentials: 'include',
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
    params?: Record<string, string | number>,
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
   * Ottiene la lista di tutti i terapisti attivi.
   * Se patientId viene passato e il paziente ha un piano ABA attivo,
   * il backend filtra automaticamente per is_aba=1.
   */
  async getTherapists(patientId?: number): Promise<Therapist[]> {
    const params: Record<string, string | number> = {};
    if (patientId) {
      params.patientId = patientId;
    }
    const response = await this.get<APIResponse<Therapist[]>>(
      "get-therapists",
      params,
    );

    if (!response.success) {
      throw new Error(response.error || "Errore nel caricamento terapisti");
    }

    return response.data || [];
  }

  /**
   * Ottiene la lista dei terapisti filtrati per tipo di trattamento.
   * Se patientId viene passato e il paziente ha un piano ABA attivo,
   * il backend filtra automaticamente per is_aba=1.
   */
  async getTherapistsByTreatment(
    treatmentTypeId: number,
    patientId?: number,
  ): Promise<Therapist[]> {
    const params: Record<string, string | number> = {
      treatmentTypeId,
    };
    if (patientId) {
      params.patientId = patientId;
    }
    const response = await this.get<APIResponse<Therapist[]>>(
      "get-therapists-by-treatment",
      params,
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento terapisti per trattamento",
      );
    }

    return response.data || [];
  }

  /**
   * Ottiene le specializzazioni disponibili per un paziente basate sul suo piano terapeutico
   */
  async getPlanTreatments(planId: number): Promise<
    {
      id: number;
      name: string;
    }[]
  > {
    const response = await this.get<
      APIResponse<
        {
          id: number;
          name: string;
        }[]
      >
    >("get-plan-treatments", {
      planId,
    });

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento specializzazioni paziente",
      );
    }

    return response.data || [];
  }

  /**
   * Ottiene la lista dei terapisti filtrati per specializzazione (per ID)
   * con controllo opzionale di disponibilità
   */
  async getTherapistsBySpecialization(
    specializationId: number,
    availabilityCheck?: {
      date: string; // Y-m-d
      time: string; // H:i
      duration: number; // minuti
      appointmentId?: number; // ID appuntamento per escludere terapista originale
      force?: boolean; // Se true, ignora filtro specializzazione
      applyABAFilter?: boolean; // Default true; se false, ignora filtro is_aba
    },
    patientId?: number,
  ): Promise<Therapist[]> {
    const params: Record<string, string | number> = {
      specializationId,
    };

    // Aggiungi parametri per controllo disponibilità se forniti
    if (availabilityCheck) {
      params.date = availabilityCheck.date;
      params.time = availabilityCheck.time;
      params.duration = availabilityCheck.duration;
      if (availabilityCheck.appointmentId) {
        params.appointmentId = availabilityCheck.appointmentId;
      }
      if (availabilityCheck.force) {
        params.force = 1;
      }
      if (availabilityCheck.applyABAFilter === false) {
        params.applyABAFilter = 0;
      }
    }

    // Filtro ABA: il backend usa patientId per restringere a is_aba=1 quando
    // il paziente ha un piano ABA attivo (a meno di applyABAFilter=0).
    if (patientId) {
      params.patientId = patientId;
    }

    const response = await this.get<APIResponse<Therapist[]>>(
      "get-therapists-by-specialization",
      params,
    );

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nel caricamento terapisti per specializzazione",
      );
    }

    return response.data || [];
  }

  /**
   * NON RIMUOVERE — usato da PrivateAppointmentModal per decidere se la
   * specializzazione si può derivare in automatico oppure se serve un picker.
   *
   * Calcola l'intersezione fra le specializzazioni possedute dal terapista
   * e quelle che offrono il treatment. Tipico comportamento:
   *  - resolved=true: una sola specializzazione compatibile → si usa quella
   *  - resolved=false con choices.length > 1: terapista multi-spec ambiguo
   *  - resolved=false con choices.length === 0: terapista non compatibile
   */
  async resolveSpecialization(
    therapistId: number,
    treatmentTypeId: number,
  ): Promise<ResolveSpecializationResponse> {
    const response = await this.get<APIResponse<ResolveSpecializationResponse>>(
      "resolve-specialization",
      { therapistId, treatmentTypeId },
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nella risoluzione della specializzazione",
      );
    }

    return response.data || { resolved: false, choices: [] };
  }

  /**
   * Ottiene i specialization_treatments disponibili per un terapista basati sulla sua specializzazione
   */
  async getTherapistSpecializationTreatments(
    therapistId: number,
  ): Promise<SpecializationTreatment[]> {
    const response = await this.get<APIResponse<SpecializationTreatment[]>>(
      "get-therapist-specialization-treatments",
      {
        therapistId,
      },
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento trattamenti specializzati",
      );
    }

    return response.data || [];
  }

  // === GESTIONE PAZIENTI ===

  /**
   * Ottiene la lista di tutti i pazienti
   */
  async getPatients(): Promise<Patient[]> {
    const response = await this.get<APIResponse<Patient[]>>("get-patients");

    if (!response.success) {
      throw new Error(response.error || "Errore nel caricamento pazienti");
    }

    return response.data || [];
  }

  /**
   * Ottiene i dati anagrafici di un paziente.
   * Se planId e' passato, scopa la risposta al piano specificato (purche'
   * sia attivo IN QUESTO MOMENTO per il paziente); altrimenti il backend
   * sceglie il piano attivo piu' recente.
   */
  async getPatient(patientId: number, planId?: number): Promise<Patient> {
    const params: Record<string, number> = { id: patientId };
    if (planId) params.planId = planId;
    const response = await this.get<APIResponse<Patient>>("get-patient", params);

    if (!response.success) {
      const error = new Error(
        response.error || "Errore nel caricamento dati paziente",
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

  /**
   * Ritorna tutti i piani terapeutici attivi IN QUESTO MOMENTO del paziente.
   * Usato per popolare il selettore multi-piano sul calendario quando il
   * paziente ha piu' piani attivi sovrapposti.
   */
  async getActivePatientPlans(patientId: number): Promise<{
    patientId: number;
    count: number;
    plans: ActivePatientPlan[];
  }> {
    const response = await this.get<APIResponse<{
      patientId: number;
      count: number;
      plans: ActivePatientPlan[];
    }>>("get-active-patient-plans", { id: patientId });

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento piani attivi paziente",
      );
    }

    return response.data || { patientId, count: 0, plans: [] };
  }

  // === GESTIONE APPUNTAMENTI - LETTURA ===

  /**
   * Ottiene gli appuntamenti di un terapista per un mese specifico
   */
  async getTherapistAppointments(
    therapistId: number,
    month: number,
    year: number,
  ): Promise<Appointment[]> {
    const response = await this.get<APIResponse<Appointment[]>>(
      "get-therapist-appointments",
      {
        therapistId,
        month,
        year,
      },
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento appuntamenti terapista",
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
    year: number,
  ): Promise<Appointment[]> {
    const response = await this.get<APIResponse<Appointment[]>>(
      "get-patient-appointments",
      {
        patientId,
        month,
        year,
      },
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento appuntamenti paziente",
      );
    }

    return response.data || [];
  }

  /**
   * Ottiene i dettagli di un singolo appuntamento
   */
  async getAppointmentDetails(appointmentId: number): Promise<Appointment> {
    const response = await this.get<APIResponse<Appointment>>(
      "get-appointment-details",
      {
        appointmentId,
      },
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento dettagli appuntamento",
      );
    }

    if (!response.data) {
      throw new Error("Appuntamento non trovato");
    }

    return response.data;
  }

  // === GESTIONE APPUNTAMENTI - CREAZIONE ===

  /**
   * Crea un singolo appuntamento
   */
  async createAppointment(request: CreateAppointmentRequest): Promise<{
    appointmentId: number;
    groupSessionId?: string | null;
    weeklyLimitExceeded: any[];
  }> {
    const response = await this.post<any>("create-appointment", request);

    if (!response.success) {
      // Se c'è un conflitto, includiamo le informazioni del conflitto nell'errore
      if (response.conflict) {
        const error = new Error(
          response.error || "Conflitto appuntamento rilevato",
        ) as any;
        error.conflict = response.conflict;
        throw error;
      }

      throw new Error(
        response.error || "Errore nella creazione dell'appuntamento",
      );
    }

    return response.data;
  }

  /**
   * Crea un pattern di appuntamenti ricorrenti
   */
  async createPattern(
    request: CreatePatternRequest,
  ): Promise<CreatePatternResponse> {
    const response = await this.post<CreatePatternResponse | APIResponse<any>>(
      "create-pattern",
      request,
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
   * Aggiorna un appuntamento esistente
   */
  async updateAppointment(appointmentData: {
    appointmentId: number;
    therapistId: number;
    appointmentDateTime: string;
    durationMinutes: number;
    notes?: string;
    applyToGroup?: boolean;
    id_setting?: number;
    appointmentCategory?: "regular" | "recovery";
  }): Promise<{
    appointmentId: number;
    planTherapyId?: number;
  }> {
    const response = await this.post<{
      success: boolean;
      message: string;
      data?: {
        appointmentId: number;
        planTherapyId?: number;
      };
      error?: string;
      conflict?: any;
    }>("update-appointment", appointmentData);

    if (!response.success) {
      // Se c'è un conflitto, lancia un errore con le informazioni del conflitto
      if (response.conflict) {
        const error = new Error(response.error || "Conflitto rilevato");
        (error as any).conflict = response.conflict;
        throw error;
      }
      throw new Error(
        response.error || "Errore nell'aggiornamento dell'appuntamento",
      );
    }

    return response.data || { appointmentId: appointmentData.appointmentId };
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
      request,
    );

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nell'aggiornamento degli appuntamenti del pattern",
      );
    }

    return response.data;
  }

  /**
   * Cancella logicamente un appuntamento
   */

  async deleteAppointment(
    appointmentId: number,
    options?: { applyToGroup?: boolean },
  ): Promise<void> {
    const response = await this.post<any>("delete-appointment", {
      appointmentId,
      ...options,
    });

    if (!response.success) {
      throw new Error(
        response.error || "Errore nella cancellazione dell'appuntamento",
      );
    }
  }

  /**
   * Sostituisce il terapista di un appuntamento
   */
  async substituteTherapist(
    request: TherapistSubstitutionRequest,
  ): Promise<TherapistSubstitutionResponse> {
    const response = await this.post<TherapistSubstitutionResponse>(
      "substitute-therapist",
      request,
    );

    if (!response.success) {
      throw new Error(
        response.message || "Errore nella sostituzione del terapista",
      );
    }

    return response;
  }

  /**
   * Cancella tutti gli appuntamenti futuri di un pattern ricorrente
   */
  async deletePatternAppointments(request: DeletePatternRequest): Promise<{
    deletedCount: number;
  }> {
    const response = await this.post<any>(
      "delete-pattern-appointments",
      request,
    );

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nella cancellazione degli appuntamenti del pattern",
      );
    }

    return response.data;
  }

  /**
   * Cancella tutti gli appuntamenti di un ciclo privato
   */
  async deletePrivateCycleAppointments(privateCycleId: number): Promise<{
    deletedCount: number;
  }> {
    const response = await this.post<any>("delete-private-cycle-appointments", {
      privateCycleId,
    });

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nella cancellazione degli appuntamenti del ciclo privato",
      );
    }

    return response.data;
  }

  /**
   * Calcola le ore settimanali del terapista per una settimana specifica
   */
  async getTherapistWeeklyHours(
    therapistId: number,
    startDate: string,
  ): Promise<{
    therapistId: number;
    weekStart: string;
    weekEnd: string;
    totalHours: number;
    contractHours: number;
    appointmentCount: number;
    isOverContract: boolean;
    remainingHours: number;
    exceededHours: number;
  }> {
    const response = await this.get<any>("get-therapist-weekly-hours", {
      therapistId,
      startDate,
    });

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel calcolo delle ore settimanali",
      );
    }

    return response.data;
  }

  /**
   * Ottiene il planTherapyId corretto per un paziente e terapista specifico
   */
  async getPlanTherapyForTherapist(
    patientId: number,
    therapistId: number,
    appointmentId?: number,
    treatmentTypeId?: number,
    planId?: number,
  ): Promise<{
    planTherapyId: number;
    treatmentTypeId: number;
    treatmentTypeName: string;
    therapeuticPlanId: number;
    weeklyHours: number;
    settingId?: number;
  }> {
    const response = await fetch(
      `${this.baseURL}/get-plan-therapy-for-therapist`,
      {
        method: "POST",
        credentials: 'include',
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          patientId,
          therapistId,
          ...(appointmentId ? { appointmentId } : {}),
          ...(treatmentTypeId ? { treatmentTypeId } : {}),
          ...(planId ? { planId } : {}),
        }),
      },
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success) {
      throw new Error(
        data.message || data.error || "Errore nel recupero piano terapia",
      );
    }

    return data.data;
  }

  // Aggiungi questi metodi alla classe TherapeuticPlanManagerAPI nel file therapyAPI

  /**
   * Ottiene tutti i tipi di trattamento disponibili
   */
  async getTreatmentTypes(): Promise<
    {
      id: number;
      name: string;
      description?: string;
    }[]
  > {
    const response = await this.get<
      APIResponse<
        {
          id: number;
          name: string;
          description?: string;
        }[]
      >
    >("get-treatment-types");

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento tipi trattamento",
      );
    }

    return response.data || [];
  }

  /**
   * Crea un appuntamento privato
   */
  /**
   * NON RIMUOVERE — usato da PrivateAppointmentModal e dai flussi di creazione
   * appuntamenti privati. Accetta `specializationId` per disambiguare quando
   * il terapista ha più specializzazioni compatibili con il treatment.
   */
  async createPrivateAppointment(request: {
    patientId: number;
    therapistId: number;
    treatmentTypeId: number;
    specializationId?: number;
    appointmentDateTime: string;
    durationMinutes: number;
    notes?: string;
  }): Promise<{
    appointmentId: number;
  }> {
    const response = await this.post<any>(
      "create-private-appointment",
      request,
    );

    if (!response.success) {
      // Se c'è un conflitto, includiamo le informazioni del conflitto nell'errore
      if (response.conflict) {
        const error = new Error(
          response.error || "Conflitto appuntamento rilevato",
        ) as any;
        error.conflict = response.conflict;
        throw error;
      }

      throw new Error(
        response.error || "Errore nella creazione dell'appuntamento privato",
      );
    }

    return response.data;
  }

  /**
   * Crea un ciclo privato mensile di appuntamenti
   */
  async createPrivateCycle(request: {
    patientId: number;
    therapistId: number;
    treatmentTypeId: number;
    dayOfWeek: number;
    startTime: string;
    durationMinutes: number;
    notes?: string;
  }): Promise<{
    privateCycleId: number;
    appointmentsCreated: number;
    conflicts: any[];
  }> {
    const response = await this.post<any>("create-private-cycle", request);

    if (!response.success) {
      throw new Error(
        response.error || "Errore nella creazione del ciclo privato",
      );
    }

    return response.data;
  }

  /**
   * Crea un appuntamento in modalità ABA
   * Permette appointment_type diversi nello stesso slot
   */
  async createABAAppointment(request: {
    planTherapyId: number;
    therapistId: number;
    patientId: number;
    appointmentDateTime: string;
    durationMinutes: number;
    treatmentTypeId: number; // 🔥 AGGIUNTO
    appointmentType: "terapia" | "parent_training" | "supervisione";
    notes?: string;
    isGroup?: boolean;
    groupSessionId?: string | null;
    id_setting?: number;
  }): Promise<{
    appointmentId: number;
    groupSessionId?: string | null;
  }> {
    const response = await this.post<any>("create-aba-appointment", request);

    if (!response.success) {
      if (response.conflict) {
        const error = new Error(
          response.error || "Conflitto appuntamento ABA rilevato",
        ) as any;
        error.conflict = response.conflict;
        throw error;
      }

      throw new Error(
        response.error || "Errore nella creazione dell'appuntamento ABA",
      );
    }

    return response.data;
  }

  /**
   * Ottiene le ore utilizzate per un piano terapia in un piano terapeutico
   */
  async getPlanTherapyUsedHours(
    therapyId: number,
    startDate: string,
  ): Promise<{
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
  }> {
    const response = await this.get<any>("get-plan-therapy-used-hours", {
      therapyId,
      start_date: startDate,
    });

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nel recupero ore utilizzate piano terapeutico",
      );
    }

    return response.data;
  }

  // === GESTIONE SETTINGS ===

  /**
   * Ottiene tutti i settings disponibili con cache automatica
   * I settings vengono cachati per 24 ore per evitare chiamate ripetute
   */
  async getSettings(
    regimeId?: number,
  ): Promise<{ id: number; nome: string }[]> {
    // Controlla se la cache è valida
    const now = Date.now();
    const cacheAge = now - this.settingsCacheTimestamp;

    if (this.settingsCache && cacheAge < this.CACHE_DURATION) {
      return this.settingsCache;
    }

    // Cache non valida o assente, carica da API
    const params: Record<string, string | number> = {};
    if (regimeId) {
      params.regimeId = regimeId;
    }

    const response = await this.get<
      APIResponse<{ id: number; nome: string }[]>
    >("get-settings", params);

    if (!response.success) {
      throw new Error(response.error || "Errore nel caricamento settings");
    }

    // Aggiorna cache
    this.settingsCache = response.data || [];
    this.settingsCacheTimestamp = now;

    return this.settingsCache;
  }

  /**
   * Pulisce la cache dei settings (utile per refresh forzato)
   */
  clearSettingsCache(): void {
    this.settingsCache = null;
    this.settingsCacheTimestamp = 0;
  }

  // === GESTIONE ASSENZE TERAPISTA ===

  /**
   * Ottiene le assenze di un terapista per un range di date
   */
  async getTherapistAbsences(
    therapistId: number,
    startDate?: string,
    endDate?: string,
  ): Promise<TherapistAbsence[]> {
    const params: Record<string, string | number> = {
      therapistId: therapistId,
    };

    if (startDate) {
      params.start_date = startDate;
    }
    if (endDate) {
      params.end_date = endDate;
    }

    const response = await this.get<APIResponse<TherapistAbsencesResponse>>(
      "get-therapist-absences",
      params,
    );

    if (!response.success) {
      throw new Error(
        response.error || "Errore nel caricamento assenze terapista",
      );
    }

    return response.data.absences;
  }

  /**
   * Imposta un appuntamento come appuntamento di gruppo
   */
  async setGroupAppointment(appointmentId: number): Promise<{
    appointmentId: number;
    groupSessionId: string;
  }> {
    const response = await this.post<any>("set-group-appointment", {
      appointmentId,
    });

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nell'impostazione dell'appuntamento di gruppo",
      );
    }

    return response.data;
  }

  /**
   * NON RIMUOVERE — usato da Index.tsx (handleSetGroupAppointmentRecurring)
   * e dal dialog "Tutte le occorrenze future del pattern" in AppointmentEditModal.
   *
   * Trasforma in "gruppo" tutte le occorrenze future di un pattern ricorrente
   * a partire dalla data dell'appuntamento. Ogni occorrenza ottiene un proprio
   * group_session_id. Le occorrenze già di gruppo vengono saltate.
   */
  async setGroupAppointmentRecurring(appointmentId: number): Promise<{
    updatedCount: number;
    updated: Array<{
      appointmentId: number;
      date: string;
      groupSessionId: string;
    }>;
    skipped: Array<{ date: string; time?: string; reason: string }>;
  }> {
    const response = await this.post<any>("set-group-appointment-recurring", {
      appointmentId,
    });

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nell'impostazione del gruppo sulle occorrenze ricorrenti",
      );
    }

    return response.data;
  }

  /**
   * Aggiunge un paziente a un gruppo esistente clonando l'appuntamento
   */
  async addPatientToGroup(
    appointmentId: number,
    patientId: number,
  ): Promise<{
    appointmentId: number;
    groupSessionId: string;
  }> {
    const response = await this.post<any>("add-patient-to-group", {
      appointmentId,
      patientId,
    });

    if (!response.success) {
      throw new Error(
        response.error || "Errore nell'aggiungere il paziente al gruppo",
      );
    }

    return response.data;
  }

  /**
   * NON RIMUOVERE — usato da AppointmentEditModal
   * (handleAddCandidatesToRecurringGroup) e dal dialog "Tutte le occorrenze
   * future del gruppo".
   *
   * Aggiunge un paziente a tutte le occorrenze future di un gruppo ricorrente
   * (a partire dalla data dell'appuntamento clickato). Se il piano terapeutico
   * del paziente prosegue oltre la fine del pattern di gruppo, crea anche un
   * pattern di estensione dedicato. Conflitti per singola occorrenza vengono
   * saltati e riportati.
   */
  async addPatientToRecurringGroup(
    appointmentId: number,
    patientId: number,
  ): Promise<{
    existingInserted: number;
    extensionCreated: number;
    extensionPatternId: number | null;
    conflicts: Array<{ date: string; time?: string; reason: string }>;
  }> {
    const response = await this.post<any>("add-patient-to-recurring-group", {
      appointmentId,
      patientId,
    });

    if (!response.success) {
      throw new Error(
        response.error ||
          "Errore nell'aggiungere il paziente al gruppo ricorrente",
      );
    }

    return response.data;
  }

  /**
   * Verifica se un paziente è già in un gruppo
   */
  async checkPatientInGroup(
    groupSessionId: string,
    patientId: number,
  ): Promise<boolean> {
    const response = await this.get<{ success: boolean; isInGroup: boolean }>(
      "check-patient-in-group",
      {
        groupSessionId,
        patientId,
      },
    );

    if (!response.success) {
      throw new Error("Errore verifica gruppo");
    }

    return response.isInGroup;
  }

  /**
   * Restituisce candidati paziente per aggiunta a gruppo, paginati e con
   * flag eligibility + lista motivazioni di blocco.
   */
  async getGroupCandidatePatients(params: {
    appointmentId: number;
    search?: string;
    page?: number;
    pageSize?: number;
  }): Promise<{
    items: Array<{
      id: number;
      name: string;
      fiscalCode: string | null;
      birthDate: string | null;
      eligible: boolean;
      reasons: string[];
    }>;
    total: number;
    page: number;
    pageSize: number;
    groupContext: {
      appointmentType: "terapia" | "parent_training" | "supervisione";
      treatmentTypeId: number;
      treatmentTypeName: string | null;
      datetime: string;
      durationMinutes: number;
      isABA: boolean;
    };
  }> {
    const response = await this.get<any>("get-group-candidate-patients", {
      appointmentId: params.appointmentId,
      search: params.search ?? "",
      page: params.page ?? 1,
      pageSize: params.pageSize ?? 20,
    });

    if (!response.success) {
      throw new Error(response.error || "Errore caricamento candidati");
    }

    return response.data;
  }
}

// Esporta un'istanza singleton
export const therapyAPI = new TherapeuticPlanManagerAPI();

// Esporta anche la classe per casi speciali
export { TherapeuticPlanManagerAPI };
