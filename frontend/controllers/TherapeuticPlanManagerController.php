<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use common\models\AppointmentPattern;
use common\models\Appointment;
use common\models\PlanTherapy;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\Patient;
use DateTime;
use Exception;

/**
 * TherapeuticPlanManagerController gestisce la creazione di pattern e appuntamenti
 * per i piani terapeutici
 */
class TherapeuticPlanManagerController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'create-pattern', 
                            'create-appointment', 
                            'get-therapists',
                            'get-therapists-by-treatment',
                            'get-patient',
                            'get-therapist-appointments',
                            'get-patient-appointments',
                            'update-appointment',
                            'update-pattern-appointments',
                            'delete-appointment'
                        ],
                        'permissions' => ['manage_appointments'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create-pattern' => ['post'],
                    'create-appointment' => ['post'],
                    'get-therapists' => ['get'],
                    'get-therapists-by-treatment' => ['get'],
                    'get-patient' => ['get'],
                    'get-therapist-appointments' => ['get'],
                    'get-patient-appointments' => ['get'],
                    'update-appointment' => ['post'],
                    'update-pattern-appointments' => ['post'],
                    'delete-appointment' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Crea un pattern di appuntamenti e genera i relativi appuntamenti
     * 
     * @return array
     */
    public function actionCreatePattern()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $response = $this->initializeResponse();

        try {
            // Validazione input
            $data = Yii::$app->request->post();
            $this->validateRequiredFields($data);

            // Verifica e carica entità correlate
            $planTherapy = $this->findPlanTherapy($data['planTherapyId']);
            $this->validateTherapeuticPlan($planTherapy->therapeuticPlan);
            $therapist = $this->findTherapist($data['therapistId']);

            // Verifica date
            $this->validateDates($data['validFrom'], $data['validTo'], $planTherapy->therapeuticPlan);

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Crea pattern
                $pattern = $this->createAppointmentPattern($data);
                
                // Genera appuntamenti
                $result = $this->generateAppointments($pattern, $therapist, $planTherapy);
                
                $response = array_merge($response, $result);
                $response['success'] = true;
                $response['message'] = 'Pattern creato con successo';
                $response['data']['patternId'] = $pattern->id;

                $transaction->commit();
                
                Yii::info("Pattern creato con successo: ID {$pattern->id}, Appuntamenti creati: {$result['appointmentsCreated']}", __METHOD__);
                
                return $response;

            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Yii::error("Errore creazione pattern: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Crea un singolo appuntamento
     * 
     * @return array
     */
    public function actionCreateAppointment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = Yii::$app->request->post();
            $this->validateSingleAppointmentFields($data);

            // Verifica entità correlate
            $planTherapy = $this->findPlanTherapy($data['planTherapyId']);
            $this->validateTherapeuticPlan($planTherapy->therapeuticPlan);
            $therapist = $this->findTherapist($data['therapistId']);

            // Verifica conflitti
            $conflict = $this->checkTherapistConflict(
                $data['therapistId'], 
                $data['appointmentDateTime'], 
                $data['durationMinutes']
            );

            if ($conflict) {
                return [
                    'success' => false,
                    'error' => 'Conflitto terapista rilevato',
                    'conflict' => $this->formatConflictInfo($conflict)
                ];
            }

            // Crea appuntamento
            $appointment = $this->createSingleAppointment($data, $planTherapy);

            // Verifica limite settimanale
            $weeklyLimitInfo = $this->checkWeeklyLimit($therapist, $data['appointmentDateTime'], $data['durationMinutes']);

            Yii::info("Appuntamento singolo creato: ID {$appointment->id}", __METHOD__);

            return [
                'success' => true,
                'message' => 'Appuntamento creato con successo',
                'data' => [
                    'appointmentId' => $appointment->id,
                    'weeklyLimitExceeded' => $weeklyLimitInfo ? [$weeklyLimitInfo] : []
                ]
            ];

        } catch (Exception $e) {
            Yii::error("Errore creazione appuntamento: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene la lista dei terapisti disponibili
     * 
     * @return array
     */
    public function actionGetTherapists()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $therapists = Therapist::find()
                ->where(['is_active' => 1])
                ->with(['user.profile', 'specialization'])
                ->orderBy(['user_profiles.last_name' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($therapists as $therapist) {
                $profile = $therapist->user->profile;
                $result[] = [
                    'id' => $therapist->id,
                    'name' => $profile->getFullName(),
                    'email' => $therapist->user->email,
                    'specialization' => $therapist->specialization->name ?? 'Non specificata',
                    'weeklyHours' => $therapist->weekly_hours_contract
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero terapisti: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene la lista dei terapisti filtrati per tipo di trattamento
     * 
     * @return array
     */
    public function actionGetTherapistsByTreatment($treatmentTypeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $therapists = Therapist::find()
                ->alias('t')
                ->innerJoin('therapist_treatment_type tt', 'tt.therapist_id = t.id')
                ->where(['t.is_active' => 1, 'tt.treatment_type_id' => $treatmentTypeId])
                ->with(['user.profile'])
                ->orderBy(['user_profiles.last_name' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($therapists as $therapist) {
                $profile = $therapist->user->profile;
                $result[] = [
                    'id' => $therapist->id,
                    'name' => $profile->getFullName(),
                    'email' => $therapist->user->email
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero terapisti per trattamento: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene i dati anagrafici di un paziente
     * 
     * @return array
     */
    public function actionGetPatient($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $patient = Patient::find()
                ->with(['user.profile'])
                ->where(['id' => $id])
                ->one();

            if (!$patient) {
                throw new NotFoundHttpException('Paziente non trovato');
            }

            $profile = $patient->user->profile;
            return [
                'success' => true,
                'data' => [
                    'id' => $patient->id,
                    'name' => $profile->getFullName(),
                    'birthDate' => $profile->birth_date,
                    'fiscalCode' => $profile->fiscal_code,
                    'email' => $patient->user->email
                ]
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero paziente: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene gli appuntamenti di un terapista per un mese specifico
     * 
     * @return array
     */
    public function actionGetTherapistAppointments($therapistId, $month, $year)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $startDate = new DateTime("$year-$month-01");
            $endDate = (clone $startDate)->modify('last day of this month');

            $appointments = Appointment::find()
                ->alias('a')
                ->innerJoin('plan_therapy pt', 'pt.id = a.plan_therapy_id')
                ->with(['planTherapy.patient.user.profile'])
                ->where([
                    'a.therapist_id' => $therapistId,
                    'a.deleted_at' => null
                ])
                ->andWhere(['between', 'a.appointment_datetime', 
                    $startDate->format('Y-m-d 00:00:00'),
                    $endDate->format('Y-m-d 23:59:59')
                ])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($appointments as $appointment) {
                $patient = $appointment->planTherapy->patient;
                $profile = $patient->user->profile;
                
                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $profile->getFullName()
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero appuntamenti terapista: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene gli appuntamenti di un paziente per un mese specifico
     * 
     * @return array
     */
    public function actionGetPatientAppointments($patientId, $month, $year)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $startDate = new DateTime("$year-$month-01");
            $endDate = (clone $startDate)->modify('last day of this month');

            $appointments = Appointment::find()
                ->alias('a')
                ->innerJoin('plan_therapy pt', 'pt.id = a.plan_therapy_id')
                ->innerJoin('therapist t', 't.id = a.therapist_id')
                ->innerJoin('user u', 'u.id = t.user_id')
                ->innerJoin('user_profile up', 'up.user_id = u.id')
                ->with(['planTherapy.treatmentType'])
                ->where([
                    'pt.patient_id' => $patientId,
                    'a.deleted_at' => null
                ])
                ->andWhere(['between', 'a.appointment_datetime', 
                    $startDate->format('Y-m-d 00:00:00'),
                    $endDate->format('Y-m-d 23:59:59')
                ])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($appointments as $appointment) {
                $therapist = $appointment->therapist;
                $profile = $therapist->user->profile;
                
                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'treatmentType' => $appointment->planTherapy->treatmentType->name,
                    'therapist' => [
                        'id' => $therapist->id,
                        'name' => $profile->getFullName()
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero appuntamenti paziente: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Aggiorna un singolo appuntamento
     * 
     * @return array
     */
    public function actionUpdateAppointment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = Yii::$app->request->post();
            $this->validateSingleAppointmentFields($data);

            $appointment = Appointment::findOne($data['appointmentId']);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            if ($appointment->status === 'completed') {
                throw new BadRequestHttpException('Non è possibile modificare un appuntamento completato');
            }

            // Verifica conflitti se cambiano data/ora/terapista
            if ($data['appointmentDateTime'] != $appointment->appointment_datetime || 
                $data['therapistId'] != $appointment->therapist_id ||
                $data['durationMinutes'] != $appointment->duration_minutes) {
                
                $conflict = $this->checkTherapistConflict(
                    $data['therapistId'], 
                    $data['appointmentDateTime'], 
                    $data['durationMinutes'],
                    $appointment->id
                );

                if ($conflict) {
                    return [
                        'success' => false,
                        'error' => 'Conflitto terapista rilevato',
                        'conflict' => $this->formatConflictInfo($conflict)
                    ];
                }
            }

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $oldValues = $appointment->getAttributes();

                $appointment->therapist_id = $data['therapistId'];
                $appointment->appointment_datetime = $data['appointmentDateTime'];
                $appointment->duration_minutes = $data['durationMinutes'];
                $appointment->notes = $data['notes'] ?? null;

                if (!$appointment->save()) {
                    throw new Exception('Errore salvataggio appuntamento');
                }

                // Traccia modifiche
                Yii::$app->activityLog->record(
                    'update_appointment',
                    'Appuntamento modificato',
                    $appointment->id,
                    $oldValues,
                    $appointment->getAttributes()
                );

                $transaction->commit();

                return [
                    'success' => true,
                    'message' => 'Appuntamento aggiornato con successo',
                    'data' => ['appointmentId' => $appointment->id]
                ];

            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Yii::error("Errore aggiornamento appuntamento: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Aggiorna gli appuntamenti futuri di un pattern
     * 
     * @return array
     */
    public function actionUpdatePatternAppointments()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = Yii::$app->request->post();
            $this->validateRequiredFields($data);

            if (!isset($data['patternId'])) {
                throw new BadRequestHttpException('ID pattern mancante');
            }

            if (!isset($data['fromDate'])) {
                throw new BadRequestHttpException('Data di inizio modifica mancante');
            }

            $pattern = AppointmentPattern::findOne($data['patternId']);
            if (!$pattern) {
                throw new NotFoundHttpException('Pattern non trovato');
            }

            // Trova appuntamenti futuri del pattern
            $appointments = Appointment::find()
                ->where([
                    'pattern_id' => $pattern->id,
                    'status' => 'scheduled',
                    'deleted_at' => null
                ])
                ->andWhere(['>=', 'appointment_datetime', $data['fromDate']])
                ->all();

            if (empty($appointments)) {
                return [
                    'success' => true,
                    'message' => 'Nessun appuntamento da aggiornare',
                    'data' => ['updatedCount' => 0]
                ];
            }

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $updatedCount = 0;
                $errors = [];

                foreach ($appointments as $appointment) {
                    // Calcola nuova data/ora mantenendo stesso giorno e ora
                    $appointmentDate = new DateTime($appointment->appointment_datetime);
                    $newDateTime = $appointmentDate->format('Y-m-d ') . $data['startTime'];

                    // Verifica conflitti
                    $conflict = $this->checkTherapistConflict(
                        $data['therapistId'],
                        $newDateTime,
                        $data['durationMinutes'],
                        $appointment->id
                    );

                    if ($conflict) {
                        $errors[] = $this->formatConflictInfo(
                            $conflict,
                            $appointmentDate->format('Y-m-d'),
                            $data['startTime'],
                            $data['therapistId']
                        );
                        continue;
                    }

                    $oldValues = $appointment->getAttributes();

                    $appointment->therapist_id = $data['therapistId'];
                    $appointment->appointment_datetime = $newDateTime;
                    $appointment->duration_minutes = $data['durationMinutes'];

                    if (!$appointment->save()) {
                        throw new Exception('Errore aggiornamento appuntamento ID: ' . $appointment->id);
                    }

                    // Traccia modifiche
                    Yii::$app->activityLog->record(
                        'update_appointment',
                        'Appuntamento modificato (aggiornamento pattern)',
                        $appointment->id,
                        $oldValues,
                        $appointment->getAttributes()
                    );

                    $updatedCount++;
                }

                if ($updatedCount === 0) {
                    $transaction->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Impossibile aggiornare gli appuntamenti a causa di conflitti',
                        'conflicts' => $errors
                    ];
                }

                // Aggiorna pattern
                $pattern->therapist_id = $data['therapistId'];
                $pattern->start_time = $data['startTime'];
                $pattern->duration_minutes = $data['durationMinutes'];
                
                if (!$pattern->save()) {
                    throw new Exception('Errore aggiornamento pattern');
                }

                $transaction->commit();

                return [
                    'success' => true,
                    'message' => 'Appuntamenti aggiornati con successo',
                    'data' => [
                        'updatedCount' => $updatedCount,
                        'conflicts' => $errors
                    ]
                ];

            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Yii::error("Errore aggiornamento appuntamenti pattern: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Cancella logicamente un appuntamento
     * 
     * @return array
     */
    public function actionDeleteAppointment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $appointmentId = Yii::$app->request->post('appointmentId');
            if (!$appointmentId) {
                throw new BadRequestHttpException('ID appuntamento mancante');
            }

            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            if ($appointment->status === 'completed') {
                throw new BadRequestHttpException('Non è possibile cancellare un appuntamento completato');
            }

            $appointment->deleted_at = date('Y-m-d H:i:s');
            if (!$appointment->save()) {
                throw new Exception('Errore cancellazione appuntamento');
            }

            // Traccia cancellazione
            Yii::$app->activityLog->record(
                'delete_appointment',
                'Appuntamento cancellato',
                $appointment->id
            );

            return [
                'success' => true,
                'message' => 'Appuntamento cancellato con successo'
            ];

        } catch (Exception $e) {
            Yii::error("Errore cancellazione appuntamento: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Inizializza la struttura di risposta standard
     * 
     * @return array
     */
    private function initializeResponse()
    {
        return [
            'success' => false,
            'appointmentsCreated' => 0,
            'conflicts' => [],
            'weeklyLimitExceeded' => [],
            'data' => []
        ];
    }

    /**
     * Valida i campi obbligatori per la creazione del pattern
     * 
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validateRequiredFields($data)
    {
        $requiredFields = ['planTherapyId', 'therapistId', 'dayOfWeek', 'startTime', 'durationMinutes', 'validFrom', 'validTo'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new BadRequestHttpException("Campo obbligatorio mancante: {$field}");
            }
        }

        // Validazioni specifiche
        if (!preg_match('/^\d{2}:\d{2}$/', $data['startTime'])) {
            throw new BadRequestHttpException('Formato orario non valido. Utilizzare HH:mm');
        }

        if ($data['dayOfWeek'] < 1 || $data['dayOfWeek'] > 7) {
            throw new BadRequestHttpException('Giorno della settimana non valido (1-7)');
        }

        if ($data['durationMinutes'] < 15 || $data['durationMinutes'] > 180) {
            throw new BadRequestHttpException('Durata non valida (15-180 minuti)');
        }
    }

    /**
     * Valida i campi per la creazione di un singolo appuntamento
     * 
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validateSingleAppointmentFields($data)
    {
        $requiredFields = ['planTherapyId', 'therapistId', 'appointmentDateTime', 'durationMinutes'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new BadRequestHttpException("Campo obbligatorio mancante: {$field}");
            }
        }

        if ($data['durationMinutes'] < 15 || $data['durationMinutes'] > 180) {
            throw new BadRequestHttpException('Durata non valida (15-180 minuti)');
        }
    }

    /**
     * Trova e valida PlanTherapy
     * 
     * @param int $id
     * @return PlanTherapy
     * @throws NotFoundHttpException
     */
    private function findPlanTherapy($id)
    {
        $planTherapy = PlanTherapy::find()
            ->where(['id' => $id])
            ->with(['therapeuticPlan'])
            ->one();

        if (!$planTherapy) {
            throw new NotFoundHttpException('Piano terapia non trovato');
        }

        return $planTherapy;
    }

    /**
     * Trova e valida Therapist
     * 
     * @param int $id
     * @return Therapist
     * @throws NotFoundHttpException
     */
    private function findTherapist($id)
    {
        $therapist = Therapist::find()
            ->where(['id' => $id, 'is_active' => 1])
            ->one();

        if (!$therapist) {
            throw new NotFoundHttpException('Terapista non trovato o non attivo');
        }

        return $therapist;
    }

    /**
     * Valida il piano terapeutico
     * 
     * @param TherapeuticPlan $plan
     * @throws BadRequestHttpException
     */
    private function validateTherapeuticPlan($plan)
    {
        if (!$plan) {
            throw new BadRequestHttpException('Piano terapeutico non trovato');
        }

        if ($plan->status !== 'active') {
            throw new BadRequestHttpException('Piano terapeutico non attivo');
        }

        if ($plan->isExpired()) {
            throw new BadRequestHttpException('Piano terapeutico scaduto');
        }
    }

    /**
     * Valida le date del pattern
     * 
     * @param string $validFrom
     * @param string $validTo
     * @param TherapeuticPlan $plan
     * @throws BadRequestHttpException
     */
    private function validateDates($validFrom, $validTo, $plan)
    {
        $fromDate = new DateTime($validFrom);
        $toDate = new DateTime($validTo);
        $planStart = new DateTime($plan->start_date);
        $planEnd = new DateTime($plan->getCalculatedEndDate());

        if ($fromDate > $toDate) {
            throw new BadRequestHttpException('Data inizio non può essere successiva alla data fine');
        }

        if ($fromDate < $planStart || $toDate > $planEnd) {
            throw new BadRequestHttpException('Le date del pattern devono essere comprese nel periodo del piano terapeutico');
        }
    }

    /**
     * Crea un nuovo AppointmentPattern
     * 
     * @param array $data
     * @return AppointmentPattern
     * @throws Exception
     */
    private function createAppointmentPattern($data)
    {
        $pattern = new AppointmentPattern();
        $pattern->plan_therapy_id = $data['planTherapyId'];
        $pattern->therapist_id = $data['therapistId'];
        $pattern->day_of_week = $data['dayOfWeek'];
        $pattern->start_time = $data['startTime'];
        $pattern->duration_minutes = $data['durationMinutes'];
        $pattern->valid_from = $data['validFrom'];
        $pattern->valid_to = $data['validTo'];
        $pattern->created_by = Yii::$app->user->id;

        if (!$pattern->save()) {
            throw new Exception('Errore nel salvataggio del pattern: ' . json_encode($pattern->errors));
        }

        return $pattern;
    }

    /**
     * Genera gli appuntamenti per un pattern
     * 
     * @param AppointmentPattern $pattern
     * @param Therapist $therapist
     * @param PlanTherapy $planTherapy
     * @return array
     */
    private function generateAppointments($pattern, $therapist, $planTherapy)
    {
        $result = [
            'appointmentsCreated' => 0,
            'conflicts' => [],
            'weeklyLimitExceeded' => []
        ];

        $currentDate = new DateTime($pattern->valid_from);
        $endDate = new DateTime($pattern->valid_to);

        while ($currentDate <= $endDate) {
            if ($currentDate->format('N') == $pattern->day_of_week) {
                $appointmentDateTime = $currentDate->format('Y-m-d') . ' ' . $pattern->start_time;
                
                // Verifica conflitti
                $conflict = $this->checkTherapistConflict($pattern->therapist_id, $appointmentDateTime, $pattern->duration_minutes);
                
                if ($conflict) {
                    $result['conflicts'][] = $this->formatConflictInfo($conflict, $currentDate->format('Y-m-d'), $pattern->start_time, $pattern->therapist_id);
                    $currentDate->modify('+1 day');
                    continue;
                }

                // Verifica limite settimanale
                $weeklyLimitInfo = $this->checkWeeklyLimit($therapist, $appointmentDateTime, $pattern->duration_minutes);
                if ($weeklyLimitInfo) {
                    $result['weeklyLimitExceeded'][] = $weeklyLimitInfo;
                }

                // Crea appuntamento
                $appointment = $this->createAppointmentFromPattern($pattern, $appointmentDateTime, $planTherapy);
                $result['appointmentsCreated']++;
            }

            $currentDate->modify('+1 day');
        }

        return $result;
    }

    /**
     * Crea un appuntamento dal pattern
     * 
     * @param AppointmentPattern $pattern
     * @param string $appointmentDateTime
     * @param PlanTherapy $planTherapy
     * @return Appointment
     * @throws Exception
     */
    private function createAppointmentFromPattern($pattern, $appointmentDateTime, $planTherapy)
    {
        $appointment = new Appointment();
        $appointment->pattern_id = $pattern->id;
        $appointment->plan_therapy_id = $pattern->plan_therapy_id;
        $appointment->therapist_id = $pattern->therapist_id;
        $appointment->appointment_datetime = $appointmentDateTime;
        $appointment->duration_minutes = $pattern->duration_minutes;
        $appointment->created_by = Yii::$app->user->id;

        if (!$appointment->save()) {
            throw new Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($appointment->errors));
        }

        return $appointment;
    }

    /**
     * Crea un singolo appuntamento
     * 
     * @param array $data
     * @param PlanTherapy $planTherapy
     * @return Appointment
     * @throws Exception
     */
    private function createSingleAppointment($data, $planTherapy)
    {
        $appointment = new Appointment();
        $appointment->plan_therapy_id = $data['planTherapyId'];
        $appointment->therapist_id = $data['therapistId'];
        $appointment->appointment_datetime = $data['appointmentDateTime'];
        $appointment->duration_minutes = $data['durationMinutes'];
        $appointment->notes = $data['notes'] ?? null;
        $appointment->created_by = Yii::$app->user->id;

        if (!$appointment->save()) {
            throw new Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($appointment->errors));
        }

        return $appointment;
    }

    /**
     * Controlla conflitti terapista
     * 
     * @param int $therapistId
     * @param string $appointmentDateTime
     * @param int $durationMinutes
     * @return Appointment|null
     */
    private function checkTherapistConflict($therapistId, $appointmentDateTime, $durationMinutes)
    {
        $startTime = new DateTime($appointmentDateTime);
        $endTime = clone $startTime;
        $endTime->modify("+{$durationMinutes} minutes");

        return Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->andWhere(['or',
                ['and',
                    ['<=', 'appointment_datetime', $appointmentDateTime],
                    ['>', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $appointmentDateTime]
                ],
                ['and',
                    ['<', 'appointment_datetime', $endTime->format('Y-m-d H:i:s')],
                    ['>=', 'appointment_datetime', $appointmentDateTime]
                ]
            ])
            ->with(['planTherapy.therapeuticPlan.patient'])
            ->one();
    }

    /**
     * Formatta le informazioni del conflitto
     * 
     * @param Appointment $conflict
     * @param string $date
     * @param string $time
     * @param int $therapistId
     * @return array
     */
    private function formatConflictInfo($conflict, $date = null, $time = null, $therapistId = null)
    {
        $conflictInfo = [
            'existingAppointmentId' => $conflict->id,
            'existingAppointmentInfo' => [
                'patientName' => $conflict->planTherapy->therapeuticPlan->patient->getFullName(),
                'startTime' => $conflict->getStartTime(),
                'endTime' => $conflict->getEndTime()
            ]
        ];

        if ($date && $time && $therapistId) {
            $conflictInfo['date'] = $date;
            $conflictInfo['time'] = $time;
            $conflictInfo['therapistId'] = $therapistId;
        }

        return $conflictInfo;
    }

    /**
     * Verifica limite settimanale terapista
     * 
     * @param Therapist $therapist
     * @param string $appointmentDateTime
     * @param int $durationMinutes
     * @return array|null
     */
    private function checkWeeklyLimit($therapist, $appointmentDateTime, $durationMinutes)
    {
        $appointmentDate = new DateTime($appointmentDateTime);
        $weekStart = clone $appointmentDate;
        $weekStart->modify('monday this week');

        $currentWeeklyHours = $this->calculateWeeklyHours($therapist->id, $weekStart->format('Y-m-d'));
        $newTotal = $currentWeeklyHours + ($durationMinutes / 60);

        if ($newTotal > $therapist->weekly_hours_contract) {
            return [
                'weekStartDate' => $weekStart->format('Y-m-d'),
                'currentHours' => $currentWeeklyHours,
                'limitHours' => $therapist->weekly_hours_contract,
                'newTotal' => $newTotal
            ];
        }

        return null;
    }

    /**
     * Calcola le ore settimanali del terapista
     * 
     * @param int $therapistId
     * @param string $weekStartDate
     * @return float
     */
    private function calculateWeeklyHours($therapistId, $weekStartDate)
    {
        $weekStart = new DateTime($weekStartDate);
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days 23:59:59');

        $totalMinutes = Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['in', 'status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED]])
            ->andWhere(['between', 'appointment_datetime', $weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')])
            ->sum('duration_minutes') ?: 0;

        return $totalMinutes / 60;
    }

    /**
     * Formatta la risposta di errore
     * 
     * @param string $message
     * @param string $code
     * @return array
     */
    private function errorResponse($message, $code = 'GENERIC_ERROR')
    {
        return [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
    }
} 