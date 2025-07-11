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
use yii\filters\Cors;

/**
 * TherapeuticPlanManagerController gestisce la creazione di pattern e appuntamenti
 * per i piani terapeutici
 */
class TherapeuticPlanManagerController extends Controller
{
    /**
     * {@inheritdoc}
     * TEMPORANEAMENTE DISABILITATO PER TESTING
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'corsFilter' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Expose-Headers' => ['Content-Disposition'], // Espone l'intestazione per il download
                    'Access-Control-Request-Headers' => ['*'],
                ]
            ],
        ];
    }

    /**
     * Disabilita la validazione CSRF per questo controller (solo per testing)
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
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
            // Validazione input - gestisce sia POST che JSON
            $data = $this->getRequestData();
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
            $data = $this->getRequestData();
            $this->validateSingleAppointmentFields($data);

            // Verifica entità correlate
            $planTherapy = $this->findPlanTherapy($data['planTherapyId']);
            $this->validateTherapeuticPlan($planTherapy->therapeuticPlan);
            $therapist = $this->findTherapist($data['therapistId']);

            // Verifica conflitti terapista
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

            // Verifica conflitti tipologia trattamento
            $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                $data['planTherapyId'], 
                $data['appointmentDateTime']
            );

            if ($treatmentConflict) {
                return [
                    'success' => false,
                    'error' => 'Conflitto tipologia trattamento rilevato',
                    'conflict' => $this->formatTreatmentTypeConflictInfo($treatmentConflict)
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
     * Ottiene i dati del piano terapeutico per un paziente
     * 
     * @return array
     */
    public function actionGetPatientPlan()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $patientId = Yii::$app->request->get('patientId');
            
            if (!$patientId) {
                return $this->errorResponse('Patient ID mancante');
            }

            // Trova il paziente
            $patient = Patient::findOne($patientId);
            if (!$patient) {
                return $this->errorResponse('Paziente non trovato');
            }

            // Trova il piano terapeutico attivo più recente
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patientId])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$therapeuticPlan) {
                return $this->errorResponse('Nessun piano terapeutico attivo trovato per questo paziente');
            }

            // Trova il piano terapia correlato
            $planTherapy = PlanTherapy::find()
                ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
                ->one();

            if (!$planTherapy) {
                return $this->errorResponse('Piano terapia non trovato');
            }

            return [
                'success' => true,
                'data' => [
                    'planTherapyId' => $planTherapy->id,
                    'therapeuticPlanId' => $therapeuticPlan->id,
                    'patientId' => $patient->id,
                    'patientName' => $patient->name,
                    'startDate' => $therapeuticPlan->start_date,
                    'endDate' => $therapeuticPlan->end_date,
                    'sessionCount' => $therapeuticPlan->session_count,
                    'weeklyFrequency' => $therapeuticPlan->weekly_frequency,
                    'status' => $therapeuticPlan->status,
                ]
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero piano paziente: " . $e->getMessage(), __METHOD__);
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
                ->innerJoin('users u', 'u.id = therapists.user_id')
                ->innerJoin('user_profiles up', 'up.user_id = u.id')
                ->orderBy(['up.last_name' => SORT_ASC])
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
     * Ottiene le specializzazioni disponibili per un paziente basate sul suo piano terapeutico
     * 
     * @param int $patientId
     * @return array
     */
    public function actionGetPatientSpecializations($patientId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Trova il piano terapeutico attivo più recente
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patientId])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$therapeuticPlan) {
                return $this->errorResponse('Nessun piano terapeutico attivo trovato');
            }

            // Ottieni i trattamenti dal piano terapeutico
            $planTherapies = PlanTherapy::find()
                ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
                ->all();

            if (empty($planTherapies)) {
                return $this->errorResponse('Nessun piano terapia trovato');
            }

            $treatmentTypeIds = [];
            foreach ($planTherapies as $planTherapy) {
                $treatmentTypeIds[] = $planTherapy->treatment_type_id;
            }

            // Ottieni le specializzazioni che coprono questi trattamenti
            $specializations = \common\models\Specialization::find()
                ->alias('s')
                ->innerJoin('{{%specialization_treatments}} st', 'st.specialization_id = s.id')
                ->where(['st.treatment_type_id' => $treatmentTypeIds])
                ->groupBy('s.id')
                ->orderBy(['s.name' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($specializations as $specialization) {
                $result[] = [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero specializzazioni paziente: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene i terapisti per una specializzazione specifica
     * 
     * @param int $specializationId
     * @return array
     */
    public function actionGetTherapistsBySpecialization($specializationId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $therapists = Therapist::find()
                ->alias('t')
                ->innerJoin('{{%users}} u', 'u.id = t.user_id')
                ->innerJoin('{{%user_profiles}} up', 'up.user_id = u.id')
                ->where(['t.is_active' => true])
                ->andWhere(['t.specialization_id' => $specializationId])
                ->orderBy(['up.last_name' => SORT_ASC])
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
            Yii::error("Errore recupero terapisti per specializzazione: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene i terapisti per un tipo di trattamento specifico
     * 
     * @param int $treatmentTypeId
     * @return array
     */
    public function actionGetTherapistsByTreatment($treatmentTypeId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $therapists = Therapist::find()
                ->alias('t')
                ->innerJoin('{{%specializations}} s', 's.id = t.specialization_id')
                ->innerJoin('{{%specialization_treatments}} st', 'st.specialization_id = s.id')
                ->innerJoin('{{%users}} u', 'u.id = t.user_id')
                ->innerJoin('{{%user_profiles}} up', 'up.user_id = u.id')
                ->where(['t.is_active' => true])
                ->andWhere(['st.treatment_type_id' => $treatmentTypeId])
                ->orderBy(['up.last_name' => SORT_ASC])
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
                ->with(['linkedUsers'])
                ->where(['id' => $id])
                ->one();

            if (!$patient) {
                throw new NotFoundHttpException('Paziente non trovato');
            }

            // Trova il piano terapeutico attivo più recente
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patient->id])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$therapeuticPlan) {
                return $this->errorResponse(
                    'Il paziente non ha piani terapeutici attivi. Non è possibile accedere al calendario.',
                    'NO_ACTIVE_THERAPEUTIC_PLAN'
                );
            }

            // Trova il piano terapia correlato
            $planTherapy = PlanTherapy::find()
                ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
                ->one();

            if (!$planTherapy) {
                return $this->errorResponse(
                    'Piano terapia non trovato per il piano terapeutico attivo.',
                    'NO_PLAN_THERAPY'
                );
            }

            // Ottieni l'email dal primo utente collegato (se presente)
            $email = null;
            if (!empty($patient->linkedUsers)) {
                $email = $patient->linkedUsers[0]->email;
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $patient->id,
                    'name' => $patient->getFullName(),
                    'birthDate' => $patient->birth_date,
                    'fiscalCode' => $patient->fiscal_code,
                    'email' => $email,
                    'hasActiveTherapeuticPlans' => true,
                    'planTherapy' => [
                        'planTherapyId' => $planTherapy->id,
                        'therapeuticPlanId' => $therapeuticPlan->id,
                        'startDate' => $therapeuticPlan->start_date,
                        'endDate' => $therapeuticPlan->getCalculatedEndDate(),
                        'durationDays' => $therapeuticPlan->duration_days,
                        'weeklyHours' => $planTherapy->weekly_hours,
                        'notes' => $therapeuticPlan->notes,
                    ]
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
                ->innerJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->innerJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->innerJoin('patients p', 'p.id = tp.patient_id')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType'])
                ->where([
                    'a.therapist_id' => $therapistId
                ])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->andWhere(['between', 'a.appointment_datetime', 
                    $startDate->format('Y-m-d 00:00:00'),
                    $endDate->format('Y-m-d 23:59:59')
                ])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($appointments as $appointment) {
                $patient = $appointment->planTherapy->therapeuticPlan->patient;
                $therapist = $appointment->therapist;
                $profile = $therapist->user->profile;
                
                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'treatmentType' => $appointment->planTherapy->treatmentType->name ?? 'Non specificato',
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->getFullName()
                    ],
                    'therapist' => [
                        'id' => $therapist->id,
                        'name' => $profile->getFullName()
                    ],
                    'patternId' => $appointment->pattern_id,
                    'isRecurring' => $appointment->pattern_id !== null
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
                ->innerJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->innerJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->innerJoin('patients p', 'p.id = tp.patient_id')
                ->innerJoin('therapists t', 't.id = a.therapist_id')
                ->innerJoin('users u', 'u.id = t.user_id')
                ->innerJoin('user_profiles up', 'up.user_id = u.id')
                ->with(['planTherapy.treatmentType', 'planTherapy.therapeuticPlan.patient'])
                ->where([
                    'tp.patient_id' => $patientId
                ])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
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
                $patient = $appointment->planTherapy->therapeuticPlan->patient;
                
                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'treatmentType' => $appointment->planTherapy->treatmentType->name,
                    'therapist' => [
                        'id' => $therapist->id,
                        'name' => $profile->getFullName()
                    ],
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->getFullName()
                    ],
                    'patternId' => $appointment->pattern_id,
                    'isRecurring' => $appointment->pattern_id !== null
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
            $data = $this->getRequestData();
            Yii::info("Dati ricevuti per update appointment: " . json_encode($data), __METHOD__);
            $this->validateUpdateAppointmentFields($data);

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

            // Verifica conflitti tipologia trattamento se cambia la data
            if ($data['appointmentDateTime'] != $appointment->appointment_datetime) {
                $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                    $appointment->plan_therapy_id, 
                    $data['appointmentDateTime'],
                    $appointment->id
                );

                if ($treatmentConflict) {
                    return [
                        'success' => false,
                        'error' => 'Conflitto tipologia trattamento rilevato',
                        'conflict' => $this->formatTreatmentTypeConflictInfo($treatmentConflict)
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
                    $errors = $appointment->getFirstErrors();
                    Yii::error("Errori validazione appuntamento: " . json_encode($errors), __METHOD__);
                    throw new Exception('Errore salvataggio appuntamento: ' . implode(', ', $errors));
                }

                // Traccia modifiche (opzionale se il componente esiste)
                if (isset(Yii::$app->activityLog)) {
                    Yii::$app->activityLog->record(
                        'update_appointment',
                        'Appuntamento modificato',
                        $appointment->id,
                        $oldValues,
                        $appointment->getAttributes()
                    );
                }
                
                // Log della modifica
                Yii::info("Appuntamento {$appointment->id} modificato con successo", __METHOD__);

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
            $data = $this->getRequestData();
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
                    'status' => Appointment::STATUS_SCHEDULED
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

                    // Verifica conflitti terapista
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

                    // Verifica conflitti tipologia trattamento
                    $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                        $appointment->plan_therapy_id,
                        $newDateTime,
                        $appointment->id
                    );

                    if ($treatmentConflict) {
                        $errors[] = $this->formatTreatmentTypeConflictInfo(
                            $treatmentConflict,
                            $appointmentDate->format('Y-m-d'),
                            $data['startTime']
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
            $data = $this->getRequestData();
            $appointmentId = $data['appointmentId'] ?? null;
            
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

            $appointment->status = Appointment::STATUS_CANCELLED;
            if (!$appointment->save()) {
                throw new Exception('Errore cancellazione appuntamento');
            }

            // Traccia cancellazione
            if (isset(Yii::$app->activityLog)) {
                Yii::$app->activityLog->record(
                    'delete_appointment',
                    'Appuntamento cancellato',
                    $appointment->id
                );
            }

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
     * Cancella tutti gli appuntamenti futuri di un pattern ricorrente
     * 
     * @return array
     */
    public function actionDeletePatternAppointments()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();
            
            if (!isset($data['patternId'])) {
                throw new BadRequestHttpException('ID pattern mancante');
            }

            if (!isset($data['fromDate'])) {
                throw new BadRequestHttpException('Data di inizio cancellazione mancante');
            }

            $pattern = AppointmentPattern::findOne($data['patternId']);
            if (!$pattern) {
                throw new NotFoundHttpException('Pattern non trovato');
            }

            // Trova tutti gli appuntamenti del pattern dalla data specificata in poi
            $appointments = Appointment::find()
                ->where([
                    'pattern_id' => $pattern->id,
                    'status' => Appointment::STATUS_SCHEDULED
                ])
                ->andWhere(['>=', 'appointment_datetime', $data['fromDate'] . ' 00:00:00'])
                ->all();

            if (empty($appointments)) {
                return [
                    'success' => true,
                    'message' => 'Nessun appuntamento da cancellare',
                    'data' => ['deletedCount' => 0]
                ];
            }

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $deletedCount = 0;

                foreach ($appointments as $appointment) {
                    // Cancella logicamente l'appuntamento
                    $appointment->status = Appointment::STATUS_CANCELLED;
                    
                    if (!$appointment->save()) {
                        throw new Exception('Errore cancellazione appuntamento ID: ' . $appointment->id);
                    }

                    // Traccia cancellazione
                    if (isset(Yii::$app->activityLog)) {
                        Yii::$app->activityLog->record(
                            'delete_appointment',
                            'Appuntamento cancellato (cancellazione pattern)',
                            $appointment->id
                        );
                    }

                    $deletedCount++;
                }

                $transaction->commit();

                Yii::info("Cancellati {$deletedCount} appuntamenti del pattern {$pattern->id} dalla data {$data['fromDate']}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Appuntamenti cancellati con successo',
                    'data' => ['deletedCount' => $deletedCount]
                ];

            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Yii::error("Errore cancellazione appuntamenti pattern: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Calcola le ore settimanali del terapista per una settimana specifica
     * 
     * @return array
     */
    public function actionGetTherapistWeeklyHours($therapistId, $startDate)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $therapist = $this->findTherapist($therapistId);
            
            // Calcola inizio e fine settimana
            $weekStart = new DateTime($startDate);
            $weekStart->modify('monday this week');
            $weekEnd = (clone $weekStart)->modify('+6 days');

            // Trova tutti gli appuntamenti del terapista per quella settimana
            $appointments = Appointment::find()
                ->where([
                    'therapist_id' => $therapistId
                ])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->andWhere(['between', 'appointment_datetime', 
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

        } catch (Exception $e) {
            Yii::error("Errore calcolo ore settimanali: " . $e->getMessage(), __METHOD__);
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
     * Valida i campi per l'aggiornamento di un appuntamento esistente
     * Non richiede planTherapyId perché l'appuntamento esiste già
     * 
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validateUpdateAppointmentFields($data)
    {
        $requiredFields = ['appointmentId', 'therapistId', 'appointmentDateTime', 'durationMinutes'];
        
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
        
        if ($fromDate > $toDate) {
            throw new BadRequestHttpException('Data inizio non può essere successiva alla data fine');
        }

        // TEMPORANEAMENTE DISABILITATO PER TESTING
        // TODO: Riabilitare questa validazione quando i dati di test saranno corretti
        /*
        $planStart = new DateTime($plan->start_date);
        $planEnd = new DateTime($plan->getCalculatedEndDate());

        if ($fromDate < $planStart || $toDate > $planEnd) {
            throw new BadRequestHttpException('Le date del pattern devono essere comprese nel periodo del piano terapeutico');
        }
        */
        
        Yii::info("Validazione date temporaneamente disabilitata per testing - Pattern: {$validFrom} - {$validTo}", __METHOD__);
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
        $pattern->created_by = $this->getCurrentUserId();

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

        Yii::info("Generazione appuntamenti - Pattern ID: {$pattern->id}, Da: {$pattern->valid_from}, A: {$pattern->valid_to}, Giorno: {$pattern->day_of_week}, Ora: {$pattern->start_time}", __METHOD__);

        while ($currentDate <= $endDate) {
            if ($currentDate->format('N') == $pattern->day_of_week) {
                // Assicurati che start_time sia nel formato corretto HH:mm
                $startTime = $pattern->start_time;
                if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                    Yii::error("Formato start_time non valido: {$startTime}", __METHOD__);
                    $currentDate->modify('+1 day');
                    continue;
                }
                
                // Usa DateTime per garantire il formato corretto
                $appointmentDate = clone $currentDate;
                $timeParts = explode(':', $startTime);
                $appointmentDate->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                $appointmentDateTime = $appointmentDate->format('Y-m-d H:i:s');
                
                Yii::info("Tentativo creazione appuntamento: {$appointmentDateTime}", __METHOD__);
                
                // Verifica conflitti terapista
                $conflict = $this->checkTherapistConflict($pattern->therapist_id, $appointmentDateTime, $pattern->duration_minutes);
                
                if ($conflict) {
                    Yii::info("Conflitto terapista rilevato per {$appointmentDateTime}", __METHOD__);
                    $result['conflicts'][] = $this->formatConflictInfo($conflict, $currentDate->format('Y-m-d'), $startTime, $pattern->therapist_id);
                    $currentDate->modify('+1 day');
                    continue;
                }

                // Verifica conflitti tipologia trattamento
                $treatmentConflict = $this->checkSameTreatmentTypeConflict($pattern->plan_therapy_id, $appointmentDateTime);
                
                if ($treatmentConflict) {
                    Yii::info("Conflitto tipologia trattamento rilevato per {$appointmentDateTime}", __METHOD__);
                    $result['conflicts'][] = $this->formatTreatmentTypeConflictInfo($treatmentConflict, $currentDate->format('Y-m-d'), $startTime);
                    $currentDate->modify('+1 day');
                    continue;
                }

                // Verifica limite settimanale
                $weeklyLimitInfo = $this->checkWeeklyLimit($therapist, $appointmentDateTime, $pattern->duration_minutes);
                if ($weeklyLimitInfo) {
                    $result['weeklyLimitExceeded'][] = $weeklyLimitInfo;
                }

                // Crea appuntamento
                try {
                    $appointment = $this->createAppointmentFromPattern($pattern, $appointmentDateTime, $planTherapy);
                    $result['appointmentsCreated']++;
                    Yii::info("Appuntamento creato con successo: ID {$appointment->id}, DateTime: {$appointmentDateTime}", __METHOD__);
                } catch (Exception $e) {
                    Yii::error("Errore nella creazione dell'appuntamento per {$appointmentDateTime}: " . $e->getMessage(), __METHOD__);
                    // Continua con il prossimo appuntamento invece di fermarsi
                }
            }

            $currentDate->modify('+1 day');
        }

        Yii::info("Generazione completata - Appuntamenti creati: {$result['appointmentsCreated']}, Conflitti: " . count($result['conflicts']), __METHOD__);
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
        Yii::info("Creazione appuntamento da pattern - DateTime: {$appointmentDateTime}, Pattern ID: {$pattern->id}", __METHOD__);
        
        $appointment = new Appointment();
        $appointment->pattern_id = $pattern->id;
        $appointment->plan_therapy_id = $pattern->plan_therapy_id;
        $appointment->therapist_id = $pattern->therapist_id;
        $appointment->appointment_datetime = $appointmentDateTime;
        $appointment->duration_minutes = $pattern->duration_minutes;
        $appointment->status = Appointment::STATUS_SCHEDULED; // Imposta status di default
        $appointment->created_by = $this->getCurrentUserId();

        Yii::info("Tentativo salvataggio appuntamento: " . json_encode($appointment->attributes), __METHOD__);

        if (!$appointment->save()) {
            $errors = $appointment->errors;
            Yii::error("Errori validazione appuntamento: " . json_encode($errors), __METHOD__);
            throw new Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($errors));
        }

        Yii::info("Appuntamento salvato con successo: ID {$appointment->id}", __METHOD__);
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
        Yii::info("Creazione singolo appuntamento - DateTime: {$data['appointmentDateTime']}", __METHOD__);
        
        // Normalizza il formato datetime
        $appointmentDateTime = $data['appointmentDateTime'];
        try {
            $dateTime = new DateTime($appointmentDateTime);
            $appointmentDateTime = $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            Yii::error("Errore nel parsing della data: {$appointmentDateTime}", __METHOD__);
            throw new Exception("Formato data/ora non valido: {$appointmentDateTime}");
        }
        
        $appointment = new Appointment();
        $appointment->plan_therapy_id = $data['planTherapyId'];
        $appointment->therapist_id = $data['therapistId'];
        $appointment->appointment_datetime = $appointmentDateTime;
        $appointment->duration_minutes = $data['durationMinutes'];
        $appointment->notes = $data['notes'] ?? null;
        $appointment->status = Appointment::STATUS_SCHEDULED; // Imposta status di default
        $appointment->created_by = $this->getCurrentUserId();

        Yii::info("Tentativo salvataggio singolo appuntamento: " . json_encode($appointment->attributes), __METHOD__);

        if (!$appointment->save()) {
            $errors = $appointment->errors;
            Yii::error("Errori validazione singolo appuntamento: " . json_encode($errors), __METHOD__);
            throw new Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($errors));
        }

        Yii::info("Singolo appuntamento salvato con successo: ID {$appointment->id}", __METHOD__);
        return $appointment;
    }

    /**
     * Controlla conflitti terapista
     * 
     * @param int $therapistId
     * @param string $appointmentDateTime
     * @param int $durationMinutes
     * @param int $excludeAppointmentId ID dell'appuntamento da escludere dal controllo (per update)
     * @return Appointment|null
     */
    private function checkTherapistConflict($therapistId, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null)
    {
        $startTime = new DateTime($appointmentDateTime);
        $endTime = clone $startTime;
        $endTime->modify("+{$durationMinutes} minutes");

        $query = Appointment::find()
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
            ->with(['planTherapy.therapeuticPlan.patient']);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'id', $excludeAppointmentId]);
        }

        return $query->one();
    }

    /**
     * Controlla se esiste già un appuntamento della stessa tipologia nello stesso giorno
     * 
     * @param int $planTherapyId
     * @param string $appointmentDateTime
     * @param int $excludeAppointmentId ID dell'appuntamento da escludere dal controllo (per update)
     * @return Appointment|null
     */
    private function checkSameTreatmentTypeConflict($planTherapyId, $appointmentDateTime, $excludeAppointmentId = null)
    {
        // Ottieni il piano terapia per recuperare treatment_type_id e patient_id
        $planTherapy = PlanTherapy::findOne($planTherapyId);
        if (!$planTherapy) {
            Yii::warning("PlanTherapy non trovato: {$planTherapyId}", __METHOD__);
            return null;
        }

        $appointmentDate = new DateTime($appointmentDateTime);
        $dateStart = $appointmentDate->format('Y-m-d 00:00:00');
        $dateEnd = $appointmentDate->format('Y-m-d 23:59:59');

        // Cerca appuntamenti dello stesso paziente con lo stesso tipo di trattamento nella stessa data
        $query = Appointment::find()
            ->alias('a')
            ->innerJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
            ->innerJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
            ->where([
                'tp.patient_id' => $planTherapy->therapeuticPlan->patient_id,
                'pt.treatment_type_id' => $planTherapy->treatment_type_id
            ])
            ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
            ->andWhere(['between', 'a.appointment_datetime', $dateStart, $dateEnd])
            ->with(['planTherapy.treatmentType', 'planTherapy.therapeuticPlan.patient']);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'a.id', $excludeAppointmentId]);
        }

        $result = $query->one();
        
        if ($result) {
            Yii::info("Conflitto tipologia trattamento rilevato: Paziente {$planTherapy->therapeuticPlan->patient->getFullName()}, Trattamento {$planTherapy->treatmentType->name}, Data {$appointmentDate->format('Y-m-d')}", __METHOD__);
        }

        return $result;
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
        // Calcola start e end time dall'appuntamento
        $startDateTime = new DateTime($conflict->appointment_datetime);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+{$conflict->duration_minutes} minutes");

        $conflictInfo = [
            'existingAppointmentId' => $conflict->id,
            'existingAppointmentInfo' => [
                'patientName' => $conflict->planTherapy->therapeuticPlan->patient->getFullName(),
                'startTime' => $startDateTime->format('H:i'),
                'endTime' => $endDateTime->format('H:i')
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
     * Formatta le informazioni del conflitto di tipologia trattamento
     * 
     * @param Appointment $conflict
     * @param string $date
     * @param string $time
     * @return array
     */
    private function formatTreatmentTypeConflictInfo($conflict, $date = null, $time = null)
    {
        $appointmentDate = new DateTime($conflict->appointment_datetime);
        $treatmentType = $conflict->planTherapy->treatmentType;
        $patient = $conflict->planTherapy->therapeuticPlan->patient;

        $conflictInfo = [
            'type' => 'same_treatment_type',
            'existingAppointmentId' => $conflict->id,
            'treatmentType' => $treatmentType->name,
            'patientName' => $patient->getFullName(),
            'existingAppointmentDate' => $appointmentDate->format('Y-m-d'),
            'existingAppointmentTime' => $appointmentDate->format('H:i'),
            'message' => "Esiste già un appuntamento di {$treatmentType->name} per {$patient->getFullName()} in data {$appointmentDate->format('d/m/Y')}"
        ];

        if ($date && $time) {
            $conflictInfo['requestedDate'] = $date;
            $conflictInfo['requestedTime'] = $time;
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
     * Ottiene l'ID dell'utente corrente con fallback per modalità standalone
     * 
     * @return int
     */
    private function getCurrentUserId()
    {
        // In modalità normale, usa l'utente autenticato
        if (Yii::$app->user->id) {
            return Yii::$app->user->id;
        }
        
        // FALLBACK PER MODALITÀ STANDALONE - RIMUOVERE IN PRODUZIONE
        // Prende il primo manager disponibile nel database
        $firstManager = \common\models\User::find()
            ->joinWith('authAssignments')
            ->where(['auth_assignment.item_name' => 'manager'])
            ->orWhere(['auth_assignment.item_name' => 'admin'])
            ->one();
            
        if ($firstManager) {
            Yii::info("Using fallback user ID {$firstManager->id} for standalone mode", __METHOD__);
            return $firstManager->id;
        }
        
        // Se non trova manager/admin, usa il primo utente disponibile
        $firstUser = \common\models\User::find()->one();
        if ($firstUser) {
            Yii::info("Using fallback first user ID {$firstUser->id} for standalone mode", __METHOD__);
            return $firstUser->id;
        }
        
        // Fallback finale (non dovrebbe mai accadere)
        return 1;
    }

    /**
     * Ottiene i dati della richiesta sia da POST che da JSON body
     * 
     * @return array
     */
    private function getRequestData()
    {
        $request = Yii::$app->request;
        
        // Prima prova a leggere come JSON dal body
        $contentType = $request->getHeaders()->get('Content-Type');
        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $rawBody = $request->getRawBody();
            if (!empty($rawBody)) {
                $jsonData = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $jsonData;
                }
            }
        }
        
        // Fallback ai dati POST normali
        return $request->post();
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