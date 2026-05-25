<?php

namespace api\controllers;

use common\helpers\NotificationHelper;
use common\models\ActivityLog;
use common\models\Appointment;
use common\models\Notification;
use common\models\Patient;
use common\models\Therapist;
use common\models\SystemSetting;
use common\models\TreatmentType;
use yii\db\Query;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

class CalendarController extends ActiveController
{
    public $modelClass = 'common\models\Appointment';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Aggiungi il JwtAuthBehavior per proteggere le azioni del controller
        $behaviors['jwt'] = [
            'class' => 'common\components\JwtAuthBehavior',
            'excludeActions' => [],  // Tutte le azioni richiedono autenticazione
        ];

        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // Disabilita azioni default, usiamo custom
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);

        return $actions;
    }

    /**
     * POST /api/calendar/patient-appointments
     * Recupera appuntamenti di un paziente per una data specifica
     *
     * Body:
     * {
     *   "patient_id": 123,
     *   "date": "2024-01-15"
     * }
     */

    /**
     * POST /api/calendar/patient-appointments
     * Recupera appuntamenti di un paziente per una data specifica
     *
     * Body:
     * {
     *   "patient_id": 123,
     *   "date": "2024-01-15"
     * }
     */
    public function actionPatientAppointments()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $patientId = $data['patient_id'] ?? null;
        $date = $data['date'] ?? null;

        if (!$patientId || !$date) {
            throw new BadRequestHttpException('Parametri patient_id e date sono obbligatori');
        }

        try {
            // Verifica che la data sia valida
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                throw new BadRequestHttpException('Formato data non valido. Utilizzare YYYY-MM-DD');
            }

            // Metodo più efficiente usando ActiveRecord relationships
            $appointments = $this->getPatientAppointmentsOptimized($patientId, $date);

            // Debug per tracciare gli appuntamenti trovati
            Yii::info('=== DEBUG PATIENT APPOINTMENTS ===', __METHOD__);
            Yii::info('Patient ID: ' . $patientId, __METHOD__);
            Yii::info('Date: ' . $date, __METHOD__);
            Yii::info('Appointments found: ' . count($appointments), __METHOD__);
            foreach ($appointments as $idx => $appointment) {
                Yii::info("Appointment $idx - ID: {$appointment->id}, Source: {$appointment->appointment_source}, Patient ID: {$appointment->patient_id}, Plan Therapy ID: {$appointment->plan_therapy_id}", __METHOD__);
            }

            // Formatta i dati per l'app mobile
            $formattedAppointments = [];
            foreach ($appointments as $appointment) {
                $datetime = new \DateTime($appointment->appointment_datetime);

                // Usa il metodo del modello per ottenere il tipo di trattamento
                $treatmentType = $appointment->getActualTreatmentType();
                $treatmentName = $treatmentType ? $treatmentType->name : 'Terapia';
                $treatmentCode = $treatmentType ? $treatmentType->code : null;

                // Controlli di sicurezza per evitare errori su oggetti null
                $therapistName = 'Terapista non disponibile';
                $therapistFirstName = '';
                $therapistLastName = '';
                $therapistSpecialization = null;

                if ($appointment->therapist && $appointment->therapist->user && $appointment->therapist->user->profile) {
                    $therapistFirstName = $appointment->therapist->user->profile->first_name ?? '';
                    $therapistLastName = $appointment->therapist->user->profile->last_name ?? '';
                    $therapistName = trim($therapistFirstName . ' ' . $therapistLastName) ?: 'Terapista non disponibile';
                }

                // Specializzazione storicizzata sull'appuntamento (in che veste
                // il terapista ha erogato il trattamento). Fallback alla legacy
                // del terapista se non presente (record pre-migrazione).
                if ($appointment->specialization) {
                    $therapistSpecialization = $appointment->specialization->name;
                } elseif ($appointment->therapist && $appointment->therapist->specialization) {
                    $therapistSpecialization = $appointment->therapist->specialization->name ?? null;
                }

                // Usa il metodo del modello per ottenere il paziente (che ora è sempre in patient_id)
                $patient = $appointment->patient;
                $patientName = 'Paziente non disponibile';
                $patientFirstName = '';
                $patientLastName = '';

                if ($patient) {
                    $patientFirstName = $patient->first_name ?? '';
                    $patientLastName = $patient->last_name ?? '';
                    $patientName = trim($patientFirstName . ' ' . $patientLastName) ?: 'Paziente non disponibile';
                }

                $formattedAppointments[] = [
                    'id' => $appointment->id,
                    'date' => $date,
                    'time' => $datetime->format('H:i'),
                    'datetime' => $appointment->appointment_datetime,
                    'duration_minutes' => $appointment->duration_minutes,
                    'status' => $this->mapStatusToApp($appointment->status),
                    'type' => $treatmentName,
                    'appointment_type' => $appointment->appointment_type,
                    'treatment_code' => $treatmentCode,
                    'notes' => $appointment->notes,
                    'is_admin_absence' => (bool) $appointment->is_admin_absence,
                    'location' => 'Centro Terapeutico',
                    'therapist' => [
                        'id' => $appointment->therapist->id ?? null,
                        'name' => $therapistName,
                        'first_name' => $therapistFirstName,
                        'last_name' => $therapistLastName,
                        'specialization' => $therapistSpecialization,
                        'avatar' => $this->getTherapistAvatar($appointment->therapist->id ?? null),
                    ],
                    'patient' => [
                        'name' => $patientName,
                        'first_name' => $patientFirstName,
                        'last_name' => $patientLastName,
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => $formattedAppointments,
                'meta' => [
                    'patient_id' => $patientId,
                    'date' => $date,
                    'count' => count($formattedAppointments)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero appuntamenti paziente: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/patient-marked-dates
     * Recupera i giorni del mese che hanno appuntamenti per evidenziarli nel calendario
     *
     * Body:
     * {
     *   "patient_id": 123,
     *   "month": "2024-01"
     * }
     */
    public function actionPatientMarkedDates()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $patientId = $data['patient_id'] ?? null;
        $month = $data['month'] ?? null;

        if (!$patientId || !$month) {
            throw new BadRequestHttpException('Parametri patient_id e month sono obbligatori');
        }

        try {
            // Verifica formato mese (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new BadRequestHttpException('Formato month non valido. Utilizzare YYYY-MM');
            }

            // Calcola primo e ultimo giorno del mese
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            // Metodo più efficiente usando ActiveRecord relationships
            $markedDates = $this->getPatientMarkedDatesOptimized($patientId, $firstDay, $lastDay);

            return [
                'success' => true,
                'data' => $markedDates,
                'meta' => [
                    'patient_id' => $patientId,
                    'month' => $month,
                    'total_days_with_appointments' => count($markedDates)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero date marcate paziente: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/cancel-appointment
     * Cancella un appuntamento del paziente con motivo e note
     *
     * Body:
     * {
     *   "appointment_id": 123,
     *   "reason": "Motivo della cancellazione",
     *   "notes": "Note aggiuntive (opzionale)"
     * }
     */
    public function actionCancelAppointment()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $appointmentId = $data['appointment_id'] ?? null;
        $reason = $data['reason'] ?? null;
        $notes = $data['notes'] ?? '';

        if (!$appointmentId || !$reason) {
            throw new BadRequestHttpException('Parametri appointment_id e reason sono obbligatori');
        }

        try {
            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            // Verifica che l'appuntamento possa essere cancellato
            if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
                throw new BadRequestHttpException('Solo gli appuntamenti confermati possono essere cancellati');
            }

            // Verifica che l'appuntamento non sia nel passato
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);
            $now = new \DateTime();
            if ($appointmentDateTime <= $now) {
                throw new BadRequestHttpException('Non è possibile cancellare appuntamenti passati');
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Aggiorna lo stato dell'appuntamento
                $appointment->status = Appointment::STATUS_ABSENT_JUSTIFIED;

                // Aggiungi le note sulla cancellazione
                $cancellationNote = "CANCELLATO DAL PAZIENTE - Motivo: {$reason}";
                if (!empty($notes)) {
                    $cancellationNote .= " - Note: {$notes}";
                }
                $cancellationNote .= ' - Data cancellazione: ' . date('Y-m-d H:i:s');

                // Aggiungi la nota di cancellazione alle note esistenti
                if (!empty($appointment->notes)) {
                    $appointment->notes .= "\n\n" . $cancellationNote;
                } else {
                    $appointment->notes = $cancellationNote;
                }

                if (!$appointment->save()) {
                    throw new \Exception("Errore nel salvataggio dell'appuntamento: " . json_encode($appointment->errors));
                }

                // Invia notifiche di cancellazione
                $this->sendCancellationNotifications($appointment, $reason, $notes);

                $transaction->commit();

                Yii::info("Appuntamento {$appointmentId} cancellato dal paziente. Motivo: {$reason}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Appuntamento cancellato con successo',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'new_status' => $this->mapStatusToApp($appointment->status),
                        'reason' => $reason,
                        'notes' => $notes,
                        'cancelled_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Yii::error('Errore cancellazione appuntamento: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/therapist-appointments
     * Recupera appuntamenti del terapista autenticato per una data specifica
     *
     * Body:
     * {
     *   "date": "2024-01-15"
     * }
     */
    public function actionTherapistAppointments()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $date = $data['date'] ?? null;

        if (!$date) {
            throw new BadRequestHttpException('Parametro date è obbligatorio');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Verifica che la data sia valida
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                throw new BadRequestHttpException('Formato data non valido. Utilizzare YYYY-MM-DD');
            }

            // Recupera tutti gli appuntamenti del giorno
            $appointments = Appointment::find()
                ->alias('a')
                ->leftJoin('setting s', 's.id = a.id_setting')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType', 'therapist.user.profile', 'specialization', 'setting'])
                ->where(['a.therapist_id' => $therapistId])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->andWhere(['DATE(a.appointment_datetime)' => $date])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->all();

            // Raggruppa gli appuntamenti
            $formattedAppointments = [];
            $processedGroups = [];

            foreach ($appointments as $appointment) {
                // Se è un appuntamento di gruppo
                if ($appointment->group_session_id !== null) {
                    // Se abbiamo già processato questo gruppo, saltalo
                    if (in_array($appointment->group_session_id, $processedGroups)) {
                        continue;
                    }

                    // Trova tutti gli appuntamenti di questo gruppo
                    $groupAppointments = array_filter($appointments, function ($apt) use ($appointment) {
                        return $apt->group_session_id === $appointment->group_session_id;
                    });

                    // Crea l'entità gruppo
                    $formattedAppointments[] = $this->createGroupAppointmentData($appointment, $groupAppointments);

                    // Segna il gruppo come processato
                    $processedGroups[] = $appointment->group_session_id;
                } else {
                    // Appuntamento singolo
                    $formattedAppointments[] = $this->formatSingleAppointment($appointment);
                }
            }

            return [
                'success' => true,
                'data' => $formattedAppointments,
                'meta' => [
                    'date' => $date,
                    'count' => count($formattedAppointments),
                    'groups_count' => count($processedGroups),
                    'therapist_id' => $therapistId
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero appuntamenti terapista: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crea i dati per un appuntamento di gruppo
     */
    private function createGroupAppointmentData($mainAppointment, $groupAppointments)
    {
        $datetime = new \DateTime($mainAppointment->appointment_datetime);
        $patients = [];
        $notes = [];

        // Stati dei pazienti nel gruppo
        $statusCounts = [
            'scheduled' => 0,
            'completed' => 0,
            'absent_justified' => 0,
            'absent_not_justified' => 0,
        ];

        foreach ($groupAppointments as $apt) {
            // Ottieni il paziente corretto
            if ($apt->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                $patient = $apt->planTherapy->therapeuticPlan->patient;
            } else {
                $patient = $apt->patient;
            }

            if (!$patient) {
                continue;
            }

            // Conta gli stati
            if (isset($statusCounts[$apt->status])) {
                $statusCounts[$apt->status]++;
            }

            // Aggiungi paziente alla lista
            $patients[] = [
                'id' => $patient->id,
                'appointment_id' => $apt->id,
                'name' => $patient->getFullName(),
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'phone' => $patient->phone ?? null,
                'avatar' => $this->getPatientAvatar($patient->id),
                'status' => $this->mapStatusToApp($apt->status),
                'notes' => $apt->notes
            ];

            // Raccogli note uniche
            if (!empty($apt->notes) && !in_array($apt->notes, $notes)) {
                $notes[] = $apt->notes;
            }
        }

        // Determina lo stato del gruppo
        $groupStatus = $this->determineGroupStatus($statusCounts, count($groupAppointments));

        // Ottieni il tipo di trattamento
        if ($mainAppointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
            $treatmentType = $mainAppointment->planTherapy->treatmentType;
        } else {
            $treatmentType = $mainAppointment->treatmentType;
        }

        return [
            'id' => $mainAppointment->id,  // ID del primo appuntamento come riferimento
            'setting_id' => $mainAppointment->id_setting,
            'setting_name' => $mainAppointment->setting->nome ?? 'N/D',
            'date' => $datetime->format('Y-m-d'),
            'time' => $datetime->format('H:i'),
            'datetime' => $mainAppointment->appointment_datetime,
            'duration_minutes' => $mainAppointment->duration_minutes,
            'status' => $groupStatus,
            'type' => $treatmentType ? $treatmentType->name : 'Non specificato',
            'appointment_type' => $mainAppointment->appointment_source,
            'treatment_code' => $treatmentType ? $treatmentType->code : null,
            'notes' => implode(' | ', $notes),  // Combina le note
            'location' => 'Centro Terapeutico',
            'is_group' => true,
            'group_session_id' => $mainAppointment->group_session_id,
            'patients_count' => count($patients),
            'status_summary' => [
                'scheduled' => $statusCounts['scheduled'],
                'completed' => $statusCounts['completed'],
                'absent' => $statusCounts['absent_justified'] + $statusCounts['absent_not_justified']
            ],
            'patient' => [  // Per retrocompatibilità
                'id' => 0,
                'name' => 'Gruppo (' . count($patients) . ' pazienti)',
                'first_name' => 'Gruppo',
                'last_name' => '',
                'phone' => null,
                'avatar' => null,
            ],
            'group_patients' => $patients,
            'therapist' => [
                'id' => $mainAppointment->therapist->id,
                'name' => $mainAppointment->therapist->user->profile->last_name . ' ' . $mainAppointment->therapist->user->profile->first_name,
                'first_name' => $mainAppointment->therapist->user->profile->first_name,
                'last_name' => $mainAppointment->therapist->user->profile->last_name,
                'specialization' => $mainAppointment->specialization->name
                    ?? ($mainAppointment->therapist->specialization->name ?? null),
            ],
            'patternId' => $mainAppointment->pattern_id,
            'isRecurring' => $mainAppointment->pattern_id !== null,
            'privateCycleId' => $mainAppointment->private_cycle_id,
            'isPrivate' => $mainAppointment->appointment_source === Appointment::SOURCE_PRIVATE
        ];
    }

    /**
     * Formatta un appuntamento singolo
     */
    private function formatSingleAppointment($appointment)
    {
        $datetime = new \DateTime($appointment->appointment_datetime);

        // Ottieni il paziente corretto basato sul tipo di appuntamento
        if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
            $patient = $appointment->planTherapy->therapeuticPlan->patient;
            $treatmentType = $appointment->planTherapy->treatmentType;
        } else {
            $patient = $appointment->patient;
            $treatmentType = $appointment->treatmentType;
        }

        // Controlli di sicurezza
        if (!$patient) {
            Yii::error("Appuntamento ID {$appointment->id} non ha paziente associato", __METHOD__);
            return null;
        }

        if (!$appointment->therapist || !$appointment->therapist->user || !$appointment->therapist->user->profile) {
            Yii::error("Appuntamento ID {$appointment->id} non ha terapista/profilo completo", __METHOD__);
            return null;
        }

        return [
            'id' => $appointment->id,
            'setting_id' => $appointment->id_setting,
            'setting_name' => $appointment->setting->nome ?? 'N/D',
            'date' => $datetime->format('Y-m-d'),
            'time' => $datetime->format('H:i'),
            'datetime' => $appointment->appointment_datetime,
            'duration_minutes' => $appointment->duration_minutes,
            'status' => $this->mapStatusToApp($appointment->status),
            'type' => $treatmentType ? $treatmentType->name : 'Non specificato',
            'appointment_type' => $appointment->appointment_source,
            'treatment_code' => $treatmentType ? $treatmentType->code : null,
            'notes' => $appointment->notes,
            'location' => 'Centro Terapeutico',
            'is_group' => false,
            'group_session_id' => $appointment->group_session_id,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->getFullName(),
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'phone' => $patient->phone ?? null,
                'avatar' => $this->getPatientAvatar($patient->id),
            ],
            'therapist' => [
                'id' => $appointment->therapist->id,
                'name' => $appointment->therapist->user->profile->last_name . ' ' . $appointment->therapist->user->profile->first_name,
                'first_name' => $appointment->therapist->user->profile->first_name,
                'last_name' => $appointment->therapist->user->profile->last_name,
                'specialization' => $appointment->specialization->name
                    ?? ($appointment->therapist->specialization->name ?? null),
            ],
            'patternId' => $appointment->pattern_id,
            'isRecurring' => $appointment->pattern_id !== null,
            'privateCycleId' => $appointment->private_cycle_id,
            'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE
        ];
    }

    /**
     * Determina lo stato complessivo del gruppo
     */
    private function determineGroupStatus($statusCounts, $totalPatients)
    {
        // Se tutti sono programmati
        if ($statusCounts['scheduled'] == $totalPatients) {
            return 'confermato';
        }

        // Se tutti sono completati
        if ($statusCounts['completed'] == $totalPatients) {
            return 'completato';
        }

        // Se tutti sono assenti
        if (($statusCounts['absent_justified'] + $statusCounts['absent_not_justified']) == $totalPatients) {
            return 'assente';
        }

        // Altrimenti è parziale/misto
        return 'parziale';
    }

    /**
     * POST /api/calendar/therapist-marked-dates
     * Recupera i giorni del mese che hanno appuntamenti per il terapista autenticato
     *
     * Body:
     * {
     *   "month": "2024-01"
     * }
     */
    public function actionTherapistMarkedDates()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $month = $data['month'] ?? null;

        if (!$month) {
            throw new BadRequestHttpException('Parametro month è obbligatorio');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Verifica formato mese (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new BadRequestHttpException('Formato month non valido. Utilizzare YYYY-MM');
            }

            // Calcola primo e ultimo giorno del mese
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            // Recupera date marcate del terapista
            $markedDates = $this->getTherapistMarkedDatesOptimized($therapistId, $firstDay, $lastDay);

            return [
                'success' => true,
                'data' => $markedDates,
                'meta' => [
                    'month' => $month,
                    'total_days_with_appointments' => count($markedDates),
                    'therapist_id' => $therapistId  // Per debug, ma non necessario
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero date marcate terapista: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/therapist-dashboard
     * Recupera le statistiche della dashboard per il terapista autenticato
     *
     * Body: {}
     */
    public function actionTherapistDashboard()
    {
        try {
            // Recupera il terapista dall'utente autenticato
            $therapistId = $this->getAuthenticatedTherapistId();
            if (!$therapistId) {
                throw new BadRequestHttpException('Utente non associato a nessun terapista');
            }

            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));

            // Pazienti attivi (con appuntamenti negli ultimi 30 giorni).
            // Stessa logica di actionTherapistPatients per garantire coerenza tra dashboard e lista:
            // conta solo i Patient reali con appuntamento valido nei 30gg.
            $activePatientsCount = (int) Patient::find()
                ->alias('p')
                ->innerJoin('appointments a', 'a.patient_id = p.id OR (a.plan_therapy_id IS NOT NULL AND EXISTS (
                    SELECT 1 FROM plan_therapies pt
                    LEFT JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                    WHERE pt.id = a.plan_therapy_id AND tp.patient_id = p.id
                ))')
                ->where(['a.therapist_id' => $therapistId])
                ->andWhere(['>=', 'DATE(a.appointment_datetime)', date('Y-m-d', strtotime('-30 days'))])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->count('DISTINCT p.id');

            // Appuntamenti di oggi
            $todayAppointments = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['DATE(appointment_datetime)' => $today])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->count();

            // Appuntamenti completati di oggi
            $todayCompletedAppointments = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['DATE(appointment_datetime)' => $today])
                ->andWhere(['status' => Appointment::STATUS_COMPLETED])
                ->count();

            // Ore lavorate questa settimana
            $weeklyHours = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['between', 'DATE(appointment_datetime)', $weekStart, $weekEnd])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->sum('duration_minutes');
            $weeklyHours = round(($weeklyHours ?? 0) / 60, 1);

            // Prossimi 3 appuntamenti
            $upcomingAppointments = Appointment::find()
                ->alias('a')
                ->leftJoin('setting s', 's.id = a.id_setting')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType', 'setting'])
                ->where(['a.therapist_id' => $therapistId])
                ->andWhere(['>=', 'a.appointment_datetime', date('Y-m-d H:i:s')])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->limit(3)
                ->all();

            $formattedUpcoming = [];
            foreach ($upcomingAppointments as $appointment) {
                $datetime = new \DateTime($appointment->appointment_datetime);

                // Ottieni il paziente corretto basato sul tipo di appuntamento
                if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                    $patient = $appointment->planTherapy->therapeuticPlan->patient;
                    $treatmentType = $appointment->planTherapy->treatmentType;
                } else {
                    $patient = $appointment->patient;
                    $treatmentType = $appointment->treatmentType;
                }

                if (!$patient)
                    continue;

                $formattedUpcoming[] = [
                    'id' => $appointment->id,
                    'setting_id' => $appointment->id_setting,
                    'setting_name' => $appointment->setting->nome ?? 'N/D',
                    'time' => $datetime->format('H:i') . ' - ' . $datetime->modify('+' . $appointment->duration_minutes . ' minutes')->format('H:i'),
                    'type' => $treatmentType ? $treatmentType->name : 'Terapia',
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->getFullName(),
                        'phone' => $patient->phone ?? null,
                    ],
                    'notes' => $appointment->notes,
                    'datetime' => $appointment->appointment_datetime,
                    'category' => $appointment->appointment_category ?? NULL
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'stats' => [
                        'activePatientsCount' => (int) $activePatientsCount,
                        'todayAppointments' => (int) $todayAppointments,
                        'todayCompletedAppointments' => (int) $todayCompletedAppointments,
                        'weeklyHours' => $weeklyHours,
                        'therapistId' => $therapistId
                    ],
                    'upcomingAppointments' => $formattedUpcoming
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero dashboard terapista: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/therapist-profile
     * Recupera i dati del profilo del terapista autenticato
     *
     * Body: {}
     */
    public function actionTherapistProfile()
    {
        try {
            // Recupera il terapista dall'utente autenticato
            $therapistId = $this->getAuthenticatedTherapistId();
            if (!$therapistId) {
                throw new BadRequestHttpException('Utente non associato a nessun terapista');
            }

            $therapist = Therapist::find()
                ->with(['user.profile', 'specialization', 'specializations'])
                ->where(['id' => $therapistId])
                ->one();

            if (!$therapist) {
                throw new NotFoundHttpException('Terapista non trovato');
            }

            // Statistiche del terapista
            $today = date('Y-m-d');
            $thisMonth = date('Y-m');
            $thisYear = date('Y');

            // Appuntamenti totali questo mese
            $monthlyAppointments = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['like', 'appointment_datetime', $thisMonth])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->count();

            // Appuntamenti totali quest'anno
            $yearlyAppointments = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['like', 'appointment_datetime', $thisYear])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->count();

            // Pazienti unici seguiti quest'anno
            $yearlyPatients = Appointment::find()
                ->alias('a')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->where(['a.therapist_id' => $therapistId])
                ->andWhere(['like', 'a.appointment_datetime', $thisYear])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->select('DISTINCT COALESCE(tp.patient_id, a.patient_id) as patient_id')
                ->count();

            // Ore totali lavorate questo mese
            $monthlyHours = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['like', 'appointment_datetime', $thisMonth])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->sum('duration_minutes');
            $monthlyHours = round(($monthlyHours ?? 0) / 60, 1);

            // Prossimo appuntamento
            $nextAppointment = Appointment::find()
                ->alias('a')
                ->leftJoin('setting s', 's.id = a.id_setting')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType', 'setting'])
                ->where(['a.therapist_id' => $therapistId])
                ->andWhere(['>=', 'a.appointment_datetime', date('Y-m-d H:i:s')])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->one();

            $nextAppointmentData = null;
            if ($nextAppointment) {
                $datetime = new \DateTime($nextAppointment->appointment_datetime);

                // Ottieni il paziente corretto basato sul tipo di appuntamento
                if ($nextAppointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                    $patient = $nextAppointment->planTherapy->therapeuticPlan->patient;
                    $treatmentType = $nextAppointment->planTherapy->treatmentType;
                } else {
                    $patient = $nextAppointment->patient;
                    $treatmentType = $nextAppointment->treatmentType;
                }

                if ($patient) {
                    $nextAppointmentData = [
                        'id' => $nextAppointment->id,
                        'setting_id' => $nextAppointment->id_setting,
                        'setting_name' => $nextAppointment->setting->nome,
                        'datetime' => $nextAppointment->appointment_datetime,
                        'date' => $datetime->format('d/m/Y'),
                        'time' => $datetime->format('H:i'),
                        'patient_name' => $patient->getFullName(),
                        'treatment_type' => $treatmentType ? $treatmentType->name : 'Terapia',
                        'category' => $nextAppointment->appointment_category ?? NULL
                    ];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'therapist' => [
                        'id' => $therapist->id,
                        'firstName' => $therapist->user->profile->first_name,
                        'lastName' => $therapist->user->profile->last_name,
                        'fullName' => $therapist->user->profile->getFullName(),
                        'email' => $therapist->user->email,
                        'phone' => $therapist->user->profile->phone,
                        'specialization' => $therapist->specialization->name ?? 'Non specificata',
                        'specializations' => array_map(fn($s) => [
                            'id' => (int)$s->id,
                            'code' => $s->code,
                            'name' => $s->name,
                        ], $therapist->specializations),
                        'registrationDate' => $therapist->user->created_at,
                        'contractualHours' => $therapist->contractual_hours ?? 40,
                        'isActive' => $therapist->user->status === 10,
                    ],
                    'statistics' => [
                        'monthlyAppointments' => (int) $monthlyAppointments,
                        'yearlyAppointments' => (int) $yearlyAppointments,
                        'yearlyPatients' => (int) $yearlyPatients,
                        'monthlyHours' => $monthlyHours,
                    ],
                    'nextAppointment' => $nextAppointmentData,
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero profilo terapista: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mappa gli stati del database agli stati dell'app
     */
    private function mapStatusToApp($dbStatus)
    {
        $statusMap = [
            Appointment::STATUS_SCHEDULED => 'confermato',
            Appointment::STATUS_COMPLETED => 'completato',
            Appointment::STATUS_CANCELLED => 'annullato',
            Appointment::STATUS_ABSENT_JUSTIFIED => 'assente_giustificato',
            Appointment::STATUS_ABSENT_NOT_JUSTIFIED => 'assente_non_giustificato',
        ];

        return $statusMap[$dbStatus] ?? $dbStatus;
    }

    /**
     * Genera URL avatar placeholder per terapista
     */
    private function getTherapistAvatar($therapistId)
    {
        // Avatar placeholder con iniziali reali (Cognome Nome) del terapista.
        $name = '?';
        if ($therapistId) {
            $therapist = \common\models\Therapist::find()
                ->with('user.profile')
                ->where(['id' => $therapistId])
                ->one();
            if ($therapist && $therapist->user && $therapist->user->profile) {
                $p = $therapist->user->profile;
                $candidate = trim(($p->last_name ?? '') . ' ' . ($p->first_name ?? ''));
                if ($candidate !== '') {
                    $name = $candidate;
                }
            }
        }
        return 'https://ui-avatars.com/api/?'
            . http_build_query([
                'name' => $name,
                'background' => 'E3F2FD',
                'color' => '1976D2',
                'size' => 128,
            ]);
    }

    /**
     * Metodo semplificato per recuperare appuntamenti del paziente
     */
    private function getPatientAppointmentsOptimized($patientId, $date)
    {
        return Appointment::find()
            ->with(['planTherapy.treatmentType', 'treatmentType', 'therapist.user.profile', 'therapist.specialization', 'therapist.specializations', 'specialization', 'patient'])
            ->where(['patient_id' => $patientId])
            ->andWhere(['DATE(appointment_datetime)' => $date])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->orderBy('appointment_datetime ASC')
            ->all();
    }

    /**
     * Metodo semplificato per recuperare date marcate del paziente
     */
    private function getPatientMarkedDatesOptimized($patientId, $firstDay, $lastDay)
    {
        $query = Appointment::find()
            ->select([
                'DATE(appointment_datetime) as appointment_date',
                'COUNT(*) as appointment_count'
            ])
            ->where(['patient_id' => $patientId])
            ->andWhere(['between', 'DATE(appointment_datetime)', $firstDay, $lastDay])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->groupBy('DATE(appointment_datetime)')
            ->orderBy('appointment_date ASC')
            ->asArray();

        // Debug: mostra la query SQL generata
        Yii::info('=== DEBUG SQL QUERY ===', __METHOD__);
        Yii::info('SQL: ' . $query->createCommand()->getRawSql(), __METHOD__);

        $results = $query->all();

        // Debug per tracciare gli appuntamenti trovati
        Yii::info('=== DEBUG PATIENT MARKED DATES ===', __METHOD__);
        Yii::info('Patient ID: ' . $patientId, __METHOD__);
        Yii::info('Period: ' . $firstDay . ' to ' . $lastDay, __METHOD__);
        Yii::info('Results found: ' . count($results), __METHOD__);
        Yii::info('Results data: ' . json_encode($results), __METHOD__);

        // Formatta per react-native-calendars
        $markedDates = [];
        foreach ($results as $result) {
            $date = $result['appointment_date'];
            $count = $result['appointment_count'];

            $markedDates[$date] = [
                'marked' => true,
                'dotColor' => '#007AFF',  // Colore del punto
                'activeOpacity' => 0.5,
                'appointment_count' => $count,
                'customStyles' => [
                    'container' => [
                        'backgroundColor' => '#E3F2FD',
                        'borderRadius' => 8
                    ],
                    'text' => [
                        'color' => '#1976D2',
                        'fontWeight' => 'bold'
                    ]
                ]
            ];
        }

        return $markedDates;
    }

    /**
     * Metodo ottimizzato per recuperare date marcate del terapista
     */
    private function getTherapistMarkedDatesOptimized($therapistId, $firstDay, $lastDay)
    {
        $results = Appointment::find()
            ->select([
                'DATE(appointments.appointment_datetime) as appointment_date',
                'COUNT(*) as appointment_count'
            ])
            ->where(['appointments.therapist_id' => $therapistId])
            ->andWhere(['between', 'DATE(appointments.appointment_datetime)', $firstDay, $lastDay])
            ->andWhere(['!=', 'appointments.status', Appointment::STATUS_CANCELLED])
            ->groupBy('DATE(appointments.appointment_datetime)')
            ->orderBy('appointment_date ASC')
            ->asArray()
            ->all();

        // Formatta per react-native-calendars
        $markedDates = [];
        foreach ($results as $result) {
            $date = $result['appointment_date'];
            $count = $result['appointment_count'];

            $markedDates[$date] = [
                'marked' => true,
                'dotColor' => '#007AFF',  // Colore del punto
                'activeOpacity' => 0.5,
                'appointment_count' => $count,
                'customStyles' => [
                    'container' => [
                        'backgroundColor' => '#E3F2FD',
                        'borderRadius' => 8
                    ],
                    'text' => [
                        'color' => '#1976D2',
                        'fontWeight' => 'bold'
                    ]
                ]
            ];
        }

        return $markedDates;
    }

    /**
     * Genera URL avatar placeholder per paziente
     */
    private function getPatientAvatar($patientId)
    {
        // Genera un avatar placeholder con le iniziali reali del paziente
        // (Cognome + Nome) tramite ui-avatars.com.
        $patient = \common\models\Patient::findOne($patientId);
        if (!$patient) {
            return 'https://ui-avatars.com/api/?name=?&background=FFF3E0&color=F57C00&size=128';
        }
        $name = trim(($patient->last_name ?? '') . ' ' . ($patient->first_name ?? ''));
        if ($name === '') {
            $name = '?';
        }
        return 'https://ui-avatars.com/api/?'
            . http_build_query([
                'name' => $name,
                'background' => 'FFF3E0',
                'color' => 'F57C00',
                'size' => 128,
            ]);
    }

    /**
     * Recupera l'ID del terapista dall'utente autenticato
     */
    private function getAuthenticatedTherapistId()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return null;
        }

        // Cerca il terapista associato all'utente
        $therapist = Therapist::findOne(['user_id' => $user->id]);
        return $therapist ? $therapist->id : null;
    }

    /**
     * POST /api/calendar/mark-patient-absent
     * Segna un paziente come assente da parte del terapista
     *
     * Body:
     * {
     *   "appointment_id": 123,
     *   "absence_type": "justified|not_justified",
     *   "reason": "Motivo dell'assenza",
     *   "notes": "Note aggiuntive (opzionale)"
     * }
     */
    public function actionMarkPatientAbsent()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $appointmentId = $data['appointment_id'] ?? null;
        $absenceType = $data['absence_type'] ?? null;
        $reason = $data['reason'] ?? null;
        $notes = $data['notes'] ?? '';
        $applyToGroup = $data['apply_to_group'] ?? false;  // NUOVO parametro

        if (!$appointmentId || !$absenceType || !$reason) {
            throw new BadRequestHttpException('Parametri appointment_id, absence_type e reason sono obbligatori');
        }

        if (!in_array($absenceType, ['justified', 'not_justified'])) {
            throw new BadRequestHttpException('absence_type deve essere "justified" o "not_justified"');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            // Verifica che l'appuntamento appartenga al terapista autenticato
            if ($appointment->therapist_id != $therapistId) {
                throw new BadRequestHttpException('Non sei autorizzato a modificare questo appuntamento');
            }

            // Verifica che l'appuntamento possa essere segnato come assente
            if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
                throw new BadRequestHttpException('Solo gli appuntamenti confermati possono essere segnati come assenti');
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Determina quali appuntamenti aggiornare
                $appointmentsToUpdate = [];
                $isGroupAbsence = false;

                if ($appointment->group_session_id !== null && $applyToGroup) {
                    // Aggiorna tutto il gruppo
                    $isGroupAbsence = true;
                    $appointmentsToUpdate = Appointment::find()
                        ->where(['group_session_id' => $appointment->group_session_id])
                        ->andWhere(['status' => Appointment::STATUS_SCHEDULED])
                        ->andWhere(['therapist_id' => $therapistId])
                        ->all();

                    Yii::info("Segnalazione assenza di gruppo - Group Session ID: {$appointment->group_session_id}, Appuntamenti: " . count($appointmentsToUpdate), __METHOD__);
                } else {
                    // Aggiorna solo l'appuntamento singolo
                    $appointmentsToUpdate = [$appointment];
                    Yii::info("Segnalazione assenza singola - ID: {$appointmentId}", __METHOD__);
                }

                $updatedCount = 0;
                $updatedAppointmentIds = [];
                $patientNames = [];

                foreach ($appointmentsToUpdate as $apt) {
                    // Aggiorna lo stato dell'appuntamento
                    $apt->status = $absenceType === 'justified'
                        ? Appointment::STATUS_ABSENT_JUSTIFIED
                        : Appointment::STATUS_ABSENT_NOT_JUSTIFIED;

                    // Aggiungi le note sull'assenza
                    $absenceNote = 'ASSENZA SEGNALATA DAL TERAPISTA - Tipo: '
                        . ($absenceType === 'justified' ? 'Giustificata' : 'Non Giustificata')
                        . " - Motivo: {$reason}";
                    if (!empty($notes)) {
                        $absenceNote .= " - Note: {$notes}";
                    }
                    if ($isGroupAbsence) {
                        $absenceNote .= ' - ASSENZA DI GRUPPO';
                    }
                    $absenceNote .= ' - Data segnalazione: ' . date('Y-m-d H:i:s');

                    // Aggiungi la nota di assenza alle note esistenti
                    if (!empty($apt->notes)) {
                        $apt->notes .= "\n\n" . $absenceNote;
                    } else {
                        $apt->notes = $absenceNote;
                    }

                    if ($apt->save()) {
                        $updatedCount++;
                        $updatedAppointmentIds[] = $apt->id;

                        // Recupera il nome del paziente per la notifica
                        if ($apt->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                            $patientNames[] = $apt->planTherapy->therapeuticPlan->patient->getFullName();
                        } else {
                            $patientNames[] = $apt->patient->getFullName();
                        }

                        // Invia notifiche di assenza per ogni paziente
                        $this->sendAbsenceNotifications($apt, $absenceType, $reason, $notes);
                    } else {
                        Yii::warning("Errore nel salvataggio dell'appuntamento ID {$apt->id}: " . json_encode($apt->errors), __METHOD__);
                    }
                }

                $transaction->commit();

                $message = $isGroupAbsence
                    ? "Segnata l'assenza per {$updatedCount} pazienti del gruppo"
                    : 'Paziente segnato come assente con successo';

                Yii::info("Assenze segnate - Appuntamenti aggiornati: {$updatedCount}, IDs: " . implode(',', $updatedAppointmentIds), __METHOD__);

                return [
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'updated_count' => $updatedCount,
                        'updated_appointment_ids' => $updatedAppointmentIds,
                        'is_group_absence' => $isGroupAbsence,
                        'was_group_appointment' => $appointment->group_session_id !== null,
                        'absence_type' => $absenceType,
                        'reason' => $reason,
                        'notes' => $notes,
                        'marked_at' => date('Y-m-d H:i:s'),
                        'patient_names' => $patientNames
                    ]
                ];
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Yii::error('Errore segnalazione assenza: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia notifiche di cancellazione al manager e al terapista
     *
     * @param Appointment $appointment
     * @param string $reason
     * @param string $notes
     */
    private function sendCancellationNotifications($appointment, $reason, $notes)
    {
        try {
            // Carica le relazioni necessarie
            $patient = $appointment->planTherapy->therapeuticPlan->patient;
            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);

            // Prepara i dati comuni per le notifiche
            $patientName = $patient->last_name . ' ' . $patient->first_name;
            $therapistName = $therapist->user->profile->last_name . ' ' . $therapist->user->profile->first_name;
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');

            // Notifica al manager
            $managerTitle = 'Appuntamento Cancellato dal Paziente';
            $managerMessage = "Il paziente {$patientName} ha cancellato l'appuntamento del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName}.\n\n";
            $managerMessage .= "Motivo: {$reason}";
            if (!empty($notes)) {
                $managerMessage .= "\nNote: {$notes}";
            }

            $managerData = [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'therapist_id' => $therapist->id,
                'cancellation_reason' => $reason,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'type' => 'appointment_cancellation'
            ];

            NotificationHelper::sendToManagers(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $managerData,
                true
            );

            // Notifica ai coordinatori dei gruppi del terapista (stesso contenuto dei manager)
            NotificationHelper::sendToTherapistCoordinators(
                $therapist->id,
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $managerData
            );

            // Notifica al terapista
            $therapistTitle = 'Appuntamento Cancellato';
            $therapistMessage = "Il tuo appuntamento con {$patientName} del {$appointmentDate} alle {$appointmentTime} è stato cancellato dal paziente.\n\n";
            $therapistMessage .= "Motivo: {$reason}";
            if (!empty($notes)) {
                $therapistMessage .= "\nNote: {$notes}";
            }

            NotificationHelper::sendToUsers(
                [$therapist->user_id],
                $therapistTitle,
                $therapistMessage,
                Notification::TYPE_INFO,
                [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                    'patient_name' => $patientName,
                    'cancellation_reason' => $reason,
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'type' => 'appointment_cancellation'
                ]
            );

            Yii::info("Notifiche di cancellazione inviate per appuntamento {$appointment->id}", __METHOD__);
        } catch (\Exception $e) {
            // Non bloccare l'operazione se l'invio delle notifiche fallisce
            Yii::error("Errore invio notifiche cancellazione appuntamento {$appointment->id}: " . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Invia notifiche di assenza al manager e al paziente
     *
     * @param Appointment $appointment
     * @param string $absenceType
     * @param string $reason
     * @param string $notes
     */
    private function sendAbsenceNotifications($appointment, $absenceType, $reason, $notes)
    {
        try {
            // Carica le relazioni necessarie
            $patient = $appointment->planTherapy->therapeuticPlan->patient;
            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);

            // Prepara i dati comuni per le notifiche
            $patientName = $patient->last_name . ' ' . $patient->first_name;
            $therapistName = $therapist->user->profile->last_name . ' ' . $therapist->user->profile->first_name;
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');
            $absenceTypeLabel = $absenceType === 'justified' ? 'Giustificata' : 'Non Giustificata';

            // Notifica al manager
            $managerTitle = 'Paziente Segnalato Come Assente';
            $managerMessage = "Il terapista {$therapistName} ha segnalato il paziente {$patientName} come assente per l'appuntamento del {$appointmentDate} alle {$appointmentTime}.\n\n";
            $managerMessage .= "Tipo di assenza: {$absenceTypeLabel}\n";
            $managerMessage .= "Motivo: {$reason}";
            if (!empty($notes)) {
                $managerMessage .= "\nNote: {$notes}";
            }

            $managerData = [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'therapist_id' => $therapist->id,
                'absence_type' => $absenceType,
                'absence_reason' => $reason,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'type' => 'patient_absence_reported'
            ];

            NotificationHelper::sendToManagers(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $managerData,
                true
            );

            // Notifica ai coordinatori dei gruppi del terapista (stesso contenuto dei manager)
            NotificationHelper::sendToTherapistCoordinators(
                $therapist->id,
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $managerData
            );

            // Notifica al paziente (trova l'utente associato al paziente)
            $patientUsers = \common\models\User::find()
                ->joinWith('accountPatients')
                ->where(['account_patients.patient_id' => $patient->id])
                ->andWhere(['users.status' => \common\models\User::STATUS_ACTIVE])
                ->all();

            if (!empty($patientUsers)) {
                $patientTitle = 'Assenza Segnalata';
                $patientMessage = "È stata segnalata un'assenza per l'appuntamento di {$patientName} del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName}.\n\n";
                $patientMessage .= "Tipo di assenza: {$absenceTypeLabel}\n";
                $patientMessage .= "Motivo: {$reason}";
                if (!empty($notes)) {
                    $patientMessage .= "\nNote: {$notes}";
                }
                $patientMessage .= "\n\nSe hai domande o contestazioni, contatta il centro.";

                // Invia notifica a tutti gli utenti associati al paziente
                $patientUserIds = [];
                foreach ($patientUsers as $patientUser) {
                    $patientUserIds[] = $patientUser->id;
                }

                NotificationHelper::sendToUsers(
                    $patientUserIds,
                    $patientTitle,
                    $patientMessage,
                    Notification::TYPE_INFO,
                    [
                        'appointment_id' => $appointment->id,
                        'therapist_id' => $therapist->id,
                        'therapist_name' => $therapistName,
                        'absence_type' => $absenceType,
                        'absence_reason' => $reason,
                        'appointment_date' => $appointmentDate,
                        'appointment_time' => $appointmentTime,
                        'type' => 'patient_absence_reported'
                    ]
                );
            }

            Yii::info("Notifiche di assenza inviate per appuntamento {$appointment->id}", __METHOD__);
        } catch (\Exception $e) {
            // Non bloccare l'operazione se l'invio delle notifiche fallisce
            Yii::error("Errore invio notifiche assenza appuntamento {$appointment->id}: " . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * POST /api/calendar/remove-absence
     * Rimuove l'assenza da un appuntamento, riportandolo a 'scheduled'
     *
     * Body:
     * {
     *   "appointment_id": 123,
     *   "notes": "Note opzionali"
     * }
     */
    public function actionRemoveAbsence()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $appointmentId = $data['appointment_id'] ?? null;
        $notes = $data['notes'] ?? '';

        if (!$appointmentId) {
            throw new BadRequestHttpException('Parametro appointment_id è obbligatorio');
        }

        try {
            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            // Verifica che lo status sia assente
            if (!in_array($appointment->status, [Appointment::STATUS_ABSENT_JUSTIFIED, Appointment::STATUS_ABSENT_NOT_JUSTIFIED])) {
                throw new BadRequestHttpException('Solo gli appuntamenti con stato assente possono essere ripristinati');
            }

            // Blocco: assenze inserite da gestionale non sono revocabili dal paziente.
            // Il terapista (autenticato come therapist) puo' comunque rimuoverle.
            $therapistAuthCheck = $this->getAuthenticatedTherapistId();
            if ($appointment->is_admin_absence && !$therapistAuthCheck) {
                throw new BadRequestHttpException('Questa assenza è stata inserita dal gestionale e non può essere revocata dall\'app');
            }

            // Vincolo temporale: almeno 1 ora prima dell'inizio
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);
            $now = new \DateTime();
            $oneHourBefore = (clone $appointmentDateTime)->modify('-1 hour');
            if ($now >= $oneHourBefore) {
                throw new BadRequestHttpException('Non è possibile rimuovere l\'assenza a meno di 1 ora dall\'inizio dell\'appuntamento');
            }

            // Autorizzazione: terapista o paziente associato
            $user = Yii::$app->user->identity;
            if (!$user) {
                throw new BadRequestHttpException('Utente non autenticato');
            }
            $removedBy = null;

            $therapistId = $this->getAuthenticatedTherapistId();
            if ($therapistId) {
                // Terapista autenticato - ok
                $therapist = Therapist::findOne($therapistId);
                if ($therapist && $therapist->user && $therapist->user->profile) {
                    $removedBy = 'Terapista ' . $therapist->user->profile->last_name . ' ' . $therapist->user->profile->first_name;
                } else {
                    $removedBy = 'Terapista ID ' . $therapistId;
                }
            } else {
                // Verifica se è un account paziente associato
                $patientId = $appointment->patient_id;
                if (!$patientId && $appointment->planTherapy && $appointment->planTherapy->therapeuticPlan) {
                    $patientId = $appointment->planTherapy->therapeuticPlan->patient_id;
                }

                if ($patientId && $user) {
                    $isAssociated = \common\models\AccountPatient::find()
                        ->where(['user_id' => $user->id, 'patient_id' => $patientId])
                        ->exists();

                    if (!$isAssociated) {
                        throw new BadRequestHttpException('Non sei autorizzato a modificare questo appuntamento');
                    }
                    $patient = \common\models\Patient::findOne($patientId);
                    if ($patient) {
                        $removedBy = 'Paziente ' . $patient->last_name . ' ' . $patient->first_name . ' (account utente ID ' . $user->id . ')';
                    } else {
                        $removedBy = 'Paziente (account utente ID ' . $user->id . ')';
                    }
                } else {
                    throw new BadRequestHttpException('Non sei autorizzato a modificare questo appuntamento');
                }
            }

            if (!$removedBy) {
                throw new BadRequestHttpException('Impossibile determinare l\'identità dell\'utente');
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Salva lo status precedente per il log
                $oldStatus = $appointment->status;

                // Riporta lo status a scheduled
                $appointment->status = Appointment::STATUS_SCHEDULED;

                if (!$appointment->save()) {
                    throw new \Exception("Errore nel salvataggio dell'appuntamento: " . json_encode($appointment->errors));
                }

                // Log esplicito dell'operazione di rimozione assenza
                $activityLog = new ActivityLog([
                    'user_id' => $user->id,
                    'action' => ActivityLog::ACTION_UPDATE,
                    'entity_name' => 'Appointment',
                    'entity_id' => $appointment->id,
                    'old_values' => json_encode([
                        'status' => $oldStatus,
                        'operation' => 'absence_active',
                    ]),
                    'new_values' => json_encode([
                        'status' => Appointment::STATUS_SCHEDULED,
                        'operation' => 'absence_removed',
                        'removed_by' => $removedBy,
                        'notes' => $notes,
                    ]),
                    'ip_address' => Yii::$app->request->getUserIP(),
                    'user_agent' => Yii::$app->request->getUserAgent(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if (!$activityLog->save()) {
                    Yii::warning('Failed to save activity log for absence removal: ' . json_encode($activityLog->getErrors()), __METHOD__);
                }

                // Invia notifiche
                $this->sendRemoveAbsenceNotifications($appointment, $removedBy, $notes);

                $transaction->commit();

                Yii::info("Assenza rimossa per appuntamento {$appointmentId} da {$removedBy}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Assenza rimossa con successo',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'new_status' => 'confermato',
                        'removed_by' => $removedBy,
                        'removed_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Yii::error('Errore rimozione assenza: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia notifiche di rimozione assenza al manager e al paziente/terapista
     *
     * @param Appointment $appointment
     * @param string $removedBy
     * @param string $notes
     */
    private function sendRemoveAbsenceNotifications($appointment, $removedBy, $notes)
    {
        try {
            // Carica le relazioni necessarie - gestisci sia appuntamenti da piano terapeutico che diretti
            $patient = null;
            if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN && $appointment->planTherapy && $appointment->planTherapy->therapeuticPlan) {
                $patient = $appointment->planTherapy->therapeuticPlan->patient;
            } else {
                $patient = $appointment->patient;
            }

            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);

            $patientName = $patient ? ($patient->last_name . ' ' . $patient->first_name) : 'Paziente non disponibile';
            $therapistName = ($therapist && $therapist->user && $therapist->user->profile)
                ? ($therapist->user->profile->last_name . ' ' . $therapist->user->profile->first_name)
                : 'Terapista non disponibile';
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');

            // Notifica al manager
            $managerTitle = 'Assenza Rimossa';
            $managerMessage = "L'assenza per l'appuntamento del {$appointmentDate} alle {$appointmentTime} è stata rimossa.\n\n";
            $managerMessage .= "Paziente: {$patientName}\n";
            $managerMessage .= "Terapista: {$therapistName}\n";
            $managerMessage .= "Rimossa da: {$removedBy}";
            if (!empty($notes)) {
                $managerMessage .= "\nNote: {$notes}";
            }

            $managerData = [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient ? $patient->id : null,
                'therapist_id' => $therapist ? $therapist->id : null,
                'removed_by' => $removedBy,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'type' => 'absence_removed'
            ];

            NotificationHelper::sendToManagers(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $managerData,
                true
            );

            // Notifica ai coordinatori dei gruppi del terapista (stesso contenuto dei manager)
            if ($therapist) {
                NotificationHelper::sendToTherapistCoordinators(
                    $therapist->id,
                    $managerTitle,
                    $managerMessage,
                    Notification::TYPE_INFO,
                    $managerData
                );
            }

            // Notifica al terapista
            if ($therapist) {
                $therapistTitle = 'Assenza Rimossa - Appuntamento Ripristinato';
                $therapistMessage = "L'assenza del paziente {$patientName} per l'appuntamento del {$appointmentDate} alle {$appointmentTime} è stata rimossa.\n";
                $therapistMessage .= "L'appuntamento è stato ripristinato come confermato.\n";
                $therapistMessage .= "Rimossa da: {$removedBy}";
                if (!empty($notes)) {
                    $therapistMessage .= "\nNote: {$notes}";
                }

                NotificationHelper::sendToUsers(
                    [$therapist->user_id],
                    $therapistTitle,
                    $therapistMessage,
                    Notification::TYPE_INFO,
                    [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $patient ? $patient->id : null,
                        'removed_by' => $removedBy,
                        'appointment_date' => $appointmentDate,
                        'appointment_time' => $appointmentTime,
                        'type' => 'absence_removed'
                    ]
                );
            }

            // Notifica al paziente
            if ($patient) {
                $patientUsers = \common\models\User::find()
                    ->joinWith('accountPatients')
                    ->where(['account_patients.patient_id' => $patient->id])
                    ->andWhere(['users.status' => \common\models\User::STATUS_ACTIVE])
                    ->all();

                if (!empty($patientUsers)) {
                    $patientTitle = 'Appuntamento Ripristinato';
                    $patientMessage = "L'assenza per l'appuntamento del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName} è stata rimossa.\n";
                    $patientMessage .= "L'appuntamento è stato ripristinato come confermato.";
                    if (!empty($notes)) {
                        $patientMessage .= "\nNote: {$notes}";
                    }

                    $patientUserIds = [];
                    foreach ($patientUsers as $patientUser) {
                        $patientUserIds[] = $patientUser->id;
                    }

                    NotificationHelper::sendToUsers(
                        $patientUserIds,
                        $patientTitle,
                        $patientMessage,
                        Notification::TYPE_INFO,
                        [
                            'appointment_id' => $appointment->id,
                            'therapist_id' => $therapist ? $therapist->id : null,
                            'removed_by' => $removedBy,
                            'appointment_date' => $appointmentDate,
                            'appointment_time' => $appointmentTime,
                            'type' => 'absence_removed'
                        ]
                    );
                }
            }

            Yii::info("Notifiche rimozione assenza inviate per appuntamento {$appointment->id}", __METHOD__);
        } catch (\Exception $e) {
            Yii::error("Errore invio notifiche rimozione assenza appuntamento {$appointment->id}: " . $e->getMessage(), __METHOD__);
        }
    }

    /*
     * ========================================
     * FUNZIONI PRECEDENTI COMMENTATE
     * ========================================
     */

    /*
     * /**
     * GET /api/calendar/appointments
     * Recupera appuntamenti in un range di date
     *
     * public function actionAppointments()
     * {
     *     $request = Yii::$app->request;
     *     $startDate = $request->get('start');
     *     $endDate = $request->get('end');
     *     $therapistId = $request->get('therapist_id');
     *
     *     if (!$startDate || !$endDate) {
     *         throw new BadRequestHttpException('Parametri start e end sono obbligatori');
     *     }
     *
     *     $query = Appointment::find()
     *         ->select([
     *             'appointments.id',
     *             'appointments.therapist_id',
     *             'appointments.patient_id',
     *             'appointments.appointment_datetime',
     *             'appointments.duration_minutes',
     *             'appointments.status',
     *             'appointments.location_type',
     *             'patients.first_name as patient_first_name',
     *             'patients.last_name as patient_last_name',
     *             'therapists.calendar_color',
     *             'user_profiles.first_name as therapist_first_name',
     *             'user_profiles.last_name as therapist_last_name',
     *             'treatment_types.name as treatment_name',
     *             'treatment_types.code as treatment_code',
     *         ])
     *         ->leftJoin('patients', 'appointments.patient_id = patients.id')
     *         ->leftJoin('therapists', 'appointments.therapist_id = therapists.id')
     *         ->leftJoin('users', 'therapists.user_id = users.id')
     *         ->leftJoin('user_profiles', 'users.id = user_profiles.user_id')
     *         ->leftJoin('plan_therapies', 'appointments.plan_therapy_id = plan_therapies.id')
     *         ->leftJoin('treatment_types', 'plan_therapies.treatment_type_id = treatment_types.id')
     *         ->where(['between', 'DATE(appointments.appointment_datetime)', $startDate, $endDate])
     *         ->orderBy('appointments.appointment_datetime ASC');
     *
     *     if ($therapistId) {
     *         $query->andWhere(['appointments.therapist_id' => $therapistId]);
     *     }
     *
     *     $appointments = $query->asArray()->all();
     *
     *     // Converte in formato FullCalendar
     *     $events = [];
     *     foreach ($appointments as $appointment) {
     *         $events[] = [
     *             'id' => $appointment['id'],
     *             'resourceId' => $appointment['therapist_id'],
     *             'title' => $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'],
     *             'start' => $appointment['appointment_datetime'],
     *             'end' => date('Y-m-d H:i:s', strtotime($appointment['appointment_datetime'] . ' +' . $appointment['duration_minutes'] . ' minutes')),
     *             'backgroundColor' => $this->getEventColor($appointment['status']),
     *             'borderColor' => $this->getEventColor($appointment['status']),
     *             'extendedProps' => [
     *                 'patientId' => $appointment['patient_id'],
     *                 'therapistId' => $appointment['therapist_id'],
     *                 'patientName' => $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'],
     *                 'therapistName' => $appointment['therapist_first_name'] . ' ' . $appointment['therapist_last_name'],
     *                 'treatmentName' => $appointment['treatment_name'],
     *                 'treatmentCode' => $appointment['treatment_code'],
     *                 'duration' => $appointment['duration_minutes'],
     *                 'status' => $appointment['status'],
     *                 'location' => $appointment['location_type'],
     *                 'therapistColor' => $appointment['calendar_color'],
     *             ]
     *         ];
     *     }
     *
     *     return [
     *         'success' => true,
     *         'data' => $events,
     *         'meta' => [
     *             'count' => count($events),
     *             'period' => ['start' => $startDate, 'end' => $endDate]
     *         ]
     *     ];
     * }
     *
     * /**
     * GET /api/calendar/therapists
     * Recupera lista terapisti per resources FullCalendar
     *
     * public function actionTherapists()
     * {
     *     $therapists = Therapist::find()
     *         ->select([
     *             'therapists.id',
     *             'therapists.calendar_color',
     *             'therapists.is_active',
     *             'therapists.weekly_hours_contract',
     *             'user_profiles.first_name',
     *             'user_profiles.last_name',
     *             'specializations.name as specialization_name',
     *             'specializations.code as specialization_code',
     *         ])
     *         ->leftJoin('users', 'therapists.user_id = users.id')
     *         ->leftJoin('user_profiles', 'users.id = user_profiles.user_id')
     *         ->leftJoin('specializations', 'therapists.specialization_id = specializations.id')
     *         ->where(['therapists.is_active' => true])
     *         ->orderBy('user_profiles.last_name, user_profiles.first_name')
     *         ->asArray()
     *         ->all();
     *
     *     $resources = [];
     *     foreach ($therapists as $therapist) {
     *         $resources[] = [
     *             'id' => $therapist['id'],
     *             'title' => $therapist['first_name'] . ' ' . $therapist['last_name'],
     *             'eventColor' => $therapist['calendar_color'],
     *             'extendedProps' => [
     *                 'specialization' => $therapist['specialization_name'],
     *                 'specializationCode' => $therapist['specialization_code'],
     *                 'weeklyHours' => $therapist['weekly_hours_contract'],
     *                 'isActive' => $therapist['is_active'],
     *             ]
     *         ];
     *     }
     *
     *     return [
     *         'success' => true,
     *         'data' => $resources,
     *         'meta' => ['count' => count($resources)]
     *     ];
     * }
     *
     * /**
     * PUT /api/calendar/appointment/{id}
     * Aggiorna un appuntamento
     *
     * public function actionUpdateAppointment($id)
     * {
     *     $appointment = Appointment::findOne($id);
     *     if (!$appointment) {
     *         throw new NotFoundHttpException('Appuntamento non trovato');
     *     }
     *
     *     $data = Yii::$app->request->getBodyParams();
     *
     *     // Validazione dati
     *     if (isset($data['appointment_datetime'])) {
     *         $appointment->appointment_datetime = $data['appointment_datetime'];
     *     }
     *
     *     if (isset($data['therapist_id'])) {
     *         // Verifica disponibilità terapista
     *         if (!$this->checkTherapistAvailability($data['therapist_id'], $appointment->appointment_datetime, $appointment->duration_minutes, $id)) {
     *             return [
     *                 'success' => false,
     *                 'error' => 'Terapista non disponibile in questo orario',
     *                 'code' => 'THERAPIST_NOT_AVAILABLE'
     *             ];
     *         }
     *         $appointment->therapist_id = $data['therapist_id'];
     *     }
     *
     *     if (isset($data['duration_minutes'])) {
     *         $appointment->duration_minutes = $data['duration_minutes'];
     *     }
     *
     *     if ($appointment->save()) {
     *         return [
     *             'success' => true,
     *             'data' => $appointment->toArray(),
     *             'message' => 'Appuntamento aggiornato con successo'
     *         ];
     *     } else {
     *         return [
     *             'success' => false,
     *             'error' => 'Errore nel salvataggio',
     *             'errors' => $appointment->errors
     *         ];
     *     }
     * }
     *
     * /**
     * POST /api/calendar/appointment/{id}/attendance
     * Segna presenza/assenza
     *
     * public function actionMarkAttendance($id)
     * {
     *     $appointment = Appointment::findOne($id);
     *     if (!$appointment) {
     *         throw new NotFoundHttpException('Appuntamento non trovato');
     *     }
     *
     *     $data = Yii::$app->request->getBodyParams();
     *     $status = $data['status'] ?? null;
     *     $reason = $data['reason'] ?? null;
     *
     *     if (!in_array($status, ['completed', 'absent_justified', 'absent_not_justified'])) {
     *         throw new BadRequestHttpException('Status non valido');
     *     }
     *
     *     $transaction = Yii::$app->db->beginTransaction();
     *     try {
     *         $appointment->status = $status;
     *         $appointment->save();
     *
     *         // Se assente, crea record assenza
     *         if (in_array($status, ['absent_justified', 'absent_not_justified'])) {
     *             $absence = new \common\models\Absence();
     *             $absence->appointment_id = $appointment->id;
     *             $absence->patient_id = $appointment->patient_id;
     *             $absence->absence_date = $appointment->appointment_datetime;
     *             $absence->reason = $reason;
     *             $absence->is_justified = ($status === 'absent_justified');
     *             $absence->is_communicated = true;
     *             $absence->communicated_by = Yii::$app->user->id;
     *             $absence->communicated_at = date('Y-m-d H:i:s');
     *             $absence->save();
     *         }
     *
     *         $transaction->commit();
     *
     *         return [
     *             'success' => true,
     *             'data' => $appointment->toArray(),
     *             'message' => 'Presenza aggiornata con successo'
     *         ];
     *     } catch (\Exception $e) {
     *         $transaction->rollBack();
     *         return [
     *             'success' => false,
     *             'error' => 'Errore nel salvataggio: ' . $e->getMessage()
     *         ];
     *     }
     * }
     *
     * /**
     * GET /api/calendar/available-slots
     * Recupera slot disponibili per recuperi
     *
     * public function actionAvailableSlots()
     * {
     *     $request = Yii::$app->request;
     *     $specializationId = $request->get('specialization_id');
     *     $date = $request->get('date');
     *     $duration = $request->get('duration', 60);
     *
     *     if (!$specializationId || !$date) {
     *         throw new BadRequestHttpException('Parametri specialization_id e date sono obbligatori');
     *     }
     *
     *     // Trova terapisti disponibili
     *     $therapists = Therapist::find()
     *         ->where(['specialization_id' => $specializationId, 'is_active' => true])
     *         ->all();
     *
     *     $availableSlots = [];
     *
     *     foreach ($therapists as $therapist) {
     *         $slots = $this->getAvailableSlots($therapist->id, $date, $duration);
     *         foreach ($slots as $slot) {
     *             $availableSlots[] = [
     *                 'therapist_id' => $therapist->id,
     *                 'therapist_name' => $therapist->user->userProfile->first_name . ' ' . $therapist->user->userProfile->last_name,
     *                 'datetime' => $slot,
     *                 'duration' => $duration,
     *             ];
     *         }
     *     }
     *
     *     return [
     *         'success' => true,
     *         'data' => $availableSlots,
     *         'meta' => ['date' => $date, 'specialization_id' => $specializationId]
     *     ];
     * }
     *
     * /**
     * Determina il colore dell'evento basato sullo status
     *
     * private function getEventColor($status)
     * {
     *     switch ($status) {
     *         case 'completed':
     *             return '#22c55e'; // Verde
     *         case 'absent_justified':
     *             return '#f59e0b'; // Arancione
     *         case 'absent_not_justified':
     *             return '#ef4444'; // Rosso
     *         case 'cancelled':
     *             return '#6b7280'; // Grigio
     *         default: // scheduled
     *             return '#3b82f6'; // Blu
     *     }
     * }
     *
     * /**
     * Verifica disponibilità terapista
     *
     * private function checkTherapistAvailability($therapistId, $datetime, $duration, $excludeId = null)
     * {
     *     $endTime = date('Y-m-d H:i:s', strtotime($datetime . ' +' . $duration . ' minutes'));
     *
     *     $query = Appointment::find()
     *         ->where(['therapist_id' => $therapistId])
     *         ->andWhere(['status' => 'scheduled'])
     *         ->andWhere([
     *             'or',
     *             ['between', 'appointment_datetime', $datetime, $endTime],
     *             ['between', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $datetime, $endTime],
     *             ['and',
     *                 ['<=', 'appointment_datetime', $datetime],
     *                 ['>=', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $endTime]
     *             ]
     *         ]);
     *
     *     if ($excludeId) {
     *         $query->andWhere(['!=', 'id', $excludeId]);
     *     }
     *
     *     return !$query->exists();
     * }
     *
     * /**
     * Recupera slot disponibili per un terapista in una data
     *
     * private function getAvailableSlots($therapistId, $date, $duration)
     * {
     *     $slots = [];
     *     $startHour = 8; // 8:00
     *     $endHour = 19; // 19:00
     *     $slotDuration = 30; // 30 minuti
     *
     *     for ($hour = $startHour; $hour < $endHour; $hour++) {
     *         for ($minute = 0; $minute < 60; $minute += $slotDuration) {
     *             $slotTime = sprintf('%02d:%02d:00', $hour, $minute);
     *             $datetime = $date . ' ' . $slotTime;
     *
     *             if ($this->checkTherapistAvailability($therapistId, $datetime, $duration)) {
     *                 $slots[] = $datetime;
     *             }
     *         }
     *     }
     *
     *     return $slots;
     * }
     */

    /**
     * POST /api/calendar/complete-appointment
     * Segna un appuntamento come completato da parte del terapista
     *
     * Body:
     * {
     *   "appointment_id": 123
     * }
     */
    public function actionCompleteAppointment()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $appointmentId = $data['appointment_id'] ?? null;

        if (!$appointmentId) {
            throw new BadRequestHttpException('Parametro appointment_id è obbligatorio');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            // Verifica che l'appuntamento appartenga al terapista autenticato
            if ($appointment->therapist_id != $therapistId) {
                throw new BadRequestHttpException('Non sei autorizzato a modificare questo appuntamento');
            }

            // Verifica che l'appuntamento sia in stato scheduled
            if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
                throw new BadRequestHttpException('Solo gli appuntamenti confermati possono essere completati');
            }

            // Verifica che l'appuntamento non sia troppo nel passato
            $appointmentEndTime = new \DateTime($appointment->appointment_datetime);
            $appointmentEndTime->modify('+' . $appointment->duration_minutes . ' minutes');
            $now = new \DateTime();

            // Se sono passati più di 15 minuti dalla fine dell'appuntamento, non può essere completato manualmente
            $fifteenMinutesAfterEnd = clone $appointmentEndTime;
            $fifteenMinutesAfterEnd->modify('+60 minutes');

            if ($now > $fifteenMinutesAfterEnd) {
                throw new BadRequestHttpException('Non è possibile completare un appuntamento terminato da più di 15 minuti');
            }

            // Verifica che l'appuntamento sia già iniziato
            $appointmentStartTime = new \DateTime($appointment->appointment_datetime);
            if ($now < $appointmentStartTime) {
                throw new BadRequestHttpException('Non è possibile completare un appuntamento non ancora iniziato');
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Aggiorna lo stato dell'appuntamento
                $appointment->status = Appointment::STATUS_COMPLETED;

                // Aggiungi una nota di completamento
                $completionNote = 'COMPLETATO MANUALMENTE DAL TERAPISTA - Data completamento: ' . date('Y-m-d H:i:s');

                if (!empty($appointment->notes)) {
                    $appointment->notes .= "\n\n" . $completionNote;
                } else {
                    $appointment->notes = $completionNote;
                }

                if (!$appointment->save()) {
                    throw new \Exception("Errore nel salvataggio dell'appuntamento: " . json_encode($appointment->errors));
                }

                $transaction->commit();

                Yii::info("Appuntamento {$appointmentId} completato manualmente dal terapista {$therapistId}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Appuntamento completato con successo',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'new_status' => $this->mapStatusToApp($appointment->status),
                        'completed_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Yii::error('Errore completamento appuntamento: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * GET /api/calendar/therapist-weekly-hours
     * Recupera le ore settimanali del terapista autenticato
     *
     * Query params:
     * - startDate: data di riferimento per la settimana (opzionale, default oggi)
     */
    public function actionTherapistWeeklyHours()
    {
        $request = Yii::$app->request;
        $startDate = $request->get('startDate');

        // Se non viene passata una data, usa oggi
        if (!$startDate) {
            $startDate = date('Y-m-d');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Trova il terapista per ottenere le ore contrattuali
            $therapist = Therapist::findOne($therapistId);
            if (!$therapist) {
                throw new NotFoundHttpException('Terapista non trovato');
            }

            // Calcola inizio e fine settimana (lunedì-domenica)
            $weekStart = new \DateTime($startDate);
            $dayOfWeek = $weekStart->format('N');  // 1 = lunedì, 7 = domenica

            // Calcola sempre il lunedì della settimana corrente
            $daysToSubtract = ($dayOfWeek - 1);
            $weekStart->modify("-{$daysToSubtract} days");

            $weekEnd = (clone $weekStart)->modify('+6 days');

            // Trova tutti gli appuntamenti del terapista per quella settimana
            $appointments = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->andWhere([
                    'between',
                    'appointment_datetime',
                    $weekStart->format('Y-m-d 00:00:00'),
                    $weekEnd->format('Y-m-d 23:59:59')
                ])
                ->all();

            // Calcola ore totali
            $totalMinutes = 0;
            $appointmentCount = 0;

            foreach ($appointments as $appointment) {
                $totalMinutes += $appointment->duration_minutes;
                $appointmentCount++;
            }

            $totalHours = round($totalMinutes / 60, 2);
            $contractHours = $therapist->weekly_hours_contract ?? 0;

            return [
                'success' => true,
                'data' => [
                    'therapistId' => $therapistId,
                    'weekStart' => $weekStart->format('Y-m-d'),
                    'weekEnd' => $weekEnd->format('Y-m-d'),
                    'totalHours' => $totalHours,
                    'contractHours' => $contractHours,
                    'appointmentCount' => $appointmentCount,
                    'isOverContract' => $totalHours > $contractHours,
                    'remainingHours' => max(0, $contractHours - $totalHours),
                    'exceededHours' => max(0, $totalHours - $contractHours)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore calcolo ore settimanali: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * GET /api/calendar/therapist-patients
     * Recupera i pazienti attivi del terapista autenticato
     */
    public function actionTherapistPatients()
    {
        try {
            // Recupera il terapista dall'utente autenticato
            $therapistId = $this->getAuthenticatedTherapistId();
            if (!$therapistId) {
                throw new BadRequestHttpException('Utente non associato a nessun terapista');
            }

            // Recupera pazienti attivi (con appuntamenti negli ultimi 30 giorni)
            $patients = Patient::find()
                ->alias('p')
                ->leftJoin('appointments a', 'a.patient_id = p.id OR (a.plan_therapy_id IS NOT NULL AND EXISTS (
                    SELECT 1 FROM plan_therapies pt 
                    LEFT JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id 
                    WHERE pt.id = a.plan_therapy_id AND tp.patient_id = p.id
                ))')
                ->where(['a.therapist_id' => $therapistId])
                ->andWhere(['>=', 'DATE(a.appointment_datetime)', date('Y-m-d', strtotime('-30 days'))])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->groupBy('p.id')
                ->orderBy('p.last_name ASC, p.first_name ASC')
                ->all();

            $formattedPatients = [];
            foreach ($patients as $patient) {
                // Conta appuntamenti totali del paziente con questo terapista
                $totalAppointments = Appointment::find()
                    ->alias('a')
                    ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                    ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                    ->where(['a.therapist_id' => $therapistId])
                    ->andWhere([
                        'or',
                        ['a.patient_id' => $patient->id],
                        ['tp.patient_id' => $patient->id]
                    ])
                    ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                    ->count();

                // Ultimo appuntamento (solo appuntamenti passati)
                $lastAppointment = Appointment::find()
                    ->alias('a')
                    ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                    ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                    ->where(['a.therapist_id' => $therapistId])
                    ->andWhere([
                        'or',
                        ['a.patient_id' => $patient->id],
                        ['tp.patient_id' => $patient->id]
                    ])
                    ->andWhere(['<', 'a.appointment_datetime', date('Y-m-d H:i:s')])
                    ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                    ->orderBy('a.appointment_datetime DESC')
                    ->one();

                // Prossimo appuntamento
                $nextAppointment = Appointment::find()
                    ->alias('a')
                    ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                    ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                    ->where(['a.therapist_id' => $therapistId])
                    ->andWhere([
                        'or',
                        ['a.patient_id' => $patient->id],
                        ['tp.patient_id' => $patient->id]
                    ])
                    ->andWhere(['>=', 'a.appointment_datetime', date('Y-m-d H:i:s')])
                    ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                    ->orderBy('a.appointment_datetime ASC')
                    ->one();

                // Ottieni i dati di contatto dal primo account collegato (se esiste)
                $linkedUser = $patient->getLinkedUsers()->one();
                $phone = null;
                $email = null;

                if ($linkedUser && $linkedUser->profile) {
                    // Decrittografa il telefono se presente
                    if (!empty($linkedUser->profile->phone)) {
                        try {
                            $encryptionKey = Yii::$app->params['encryptionKey'];
                            $decryptedPhone = Yii::$app->security->decryptByKey(
                                base64_decode($linkedUser->profile->phone),
                                $encryptionKey
                            );
                            $phone = $decryptedPhone;
                        } catch (\Exception $e) {
                            Yii::error('Failed to decrypt phone number: ' . $e->getMessage(), __METHOD__);
                            // Se la decodifica fallisce, mantieni il valore originale
                            $phone = $linkedUser->profile->phone;
                        }
                    }
                    $email = $linkedUser->email;
                }

                $formattedPatients[] = [
                    'id' => $patient->id,
                    'firstName' => $patient->first_name,
                    'lastName' => $patient->last_name,
                    'fullName' => $patient->getFullName(),
                    'phone' => $phone,
                    'email' => $email,
                    'dateOfBirth' => $patient->birth_date,
                    'totalAppointments' => (int) $totalAppointments,
                    'lastAppointment' => $lastAppointment ? $lastAppointment->appointment_datetime : null,
                    'nextAppointment' => $nextAppointment ? $nextAppointment->appointment_datetime : null,
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'patients' => $formattedPatients,
                    'totalCount' => count($formattedPatients)
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero pazienti terapista: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/patient-upcoming-appointments
     * Recupera i prossimi appuntamenti di un paziente
     *
     * Body:
     * {
     *   "patient_id": 123,
     *   "limit": 3
     * }
     */
    public function actionPatientUpcomingAppointments()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();

        $patientId = $data['patient_id'] ?? null;
        $limit = $data['limit'] ?? 5;

        if (!$patientId) {
            throw new BadRequestHttpException('Parametro patient_id è obbligatorio');
        }

        // Validazione limite - massimo 3 appuntamenti per l'app mobile
        if (!is_numeric($limit) || $limit < 1 || $limit > 3) {
            $limit = 3;
        }

        try {
            // Recupera i prossimi appuntamenti del paziente (semplificato)
            $appointments = Appointment::find()
                ->with(['therapist.user.profile', 'planTherapy.treatmentType', 'treatmentType', 'specialization', 'patient'])
                ->where(['patient_id' => $patientId])
                ->andWhere(['>=', 'appointment_datetime', date('Y-m-d H:i:s')])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->orderBy(['appointment_datetime' => SORT_ASC])
                ->limit($limit)
                ->all();

            $formattedAppointments = [];
            foreach ($appointments as $appointment) {
                $datetime = new \DateTime($appointment->appointment_datetime);

                // Usa il metodo del modello per ottenere il tipo di trattamento
                $treatmentType = $appointment->getActualTreatmentType();

                // Informazioni del terapista
                $therapistInfo = null;
                if ($appointment->therapist && $appointment->therapist->user && $appointment->therapist->user->profile) {
                    $profile = $appointment->therapist->user->profile;
                    $therapistInfo = [
                        'id' => $appointment->therapist->id,
                        'name' => trim($profile->last_name . ' ' . $profile->first_name),
                        'first_name' => $profile->first_name,
                        'last_name' => $profile->last_name,
                        'specialization' => $appointment->specialization->name
                            ?? ($appointment->therapist->specialization->name ?? null),
                    ];
                }

                $formattedAppointments[] = [
                    'id' => $appointment->id,
                    'date' => $datetime->format('Y-m-d'),
                    'time' => $datetime->format('H:i'),
                    'datetime' => $appointment->appointment_datetime,
                    'duration_minutes' => $appointment->duration_minutes,
                    'status' => $appointment->getStatusLabel(),
                    'type' => $treatmentType ? $treatmentType->name : 'Terapia',
                    'appointment_type' => $appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN ? 'piano_terapeutico' : 'diretto',
                    'treatment_code' => $treatmentType ? $treatmentType->code : null,
                    'notes' => $appointment->notes,
                    'therapist' => $therapistInfo,
                ];
            }

            return [
                'success' => true,
                'data' => $formattedAppointments,
                'meta' => [
                    'patient_id' => (int) $patientId,
                    'count' => count($formattedAppointments),
                    'limit' => (int) $limit,
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero prossimi appuntamenti paziente: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/patient-calendar-settings
     * Restituisce le date limite (minDate/maxDate) del calendario paziente
     */
    public function actionPatientCalendarSettings()
    {
        try {
            $limits = SystemSetting::getPatientCalendarDateLimits();

            return [
                'success' => true,
                'data' => $limits,
            ];
        } catch (\Exception $e) {
            Yii::error('Errore recupero impostazioni calendario paziente: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Aggiunge note a un appuntamento
     *
     * @return array
     */
    public function actionSetAppointmentNote()
    {
        try {
            $request = Yii::$app->request;
            $appointmentId = $request->get('appointmentId');
            $note = $request->get('note');

            if (!$appointmentId || !$note) {
                throw new BadRequestHttpException('appointmentId e note sono obbligatori');
            }

            // Verifica che l'appuntamento esista e appartenga al terapista autenticato
            $therapistId = $this->getAuthenticatedTherapistId();
            $appointment = Appointment::find()
                ->where(['id' => $appointmentId, 'therapist_id' => $therapistId])
                ->one();

            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato o non autorizzato');
            }

            $appointment->notes = $note;

            if ($appointment->save()) {
                return [
                    'success' => true,
                    'message' => 'Note appuntamento aggiornate con successo',
                    'data' => [
                        'appointmentId' => $appointment->id,
                        'note' => $note
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Errore nell'aggiornamento delle note",
                    'data' => $appointment->errors
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Errore aggiornamento note appuntamento: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }
}
