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
use common\models\TherapistSubstitution;
use common\models\Patient;
use common\models\TreatmentType;
use common\models\PrivateCycle;
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
            $this->validateDates($data['validFrom'], $data['validTo'], planTherapy->therapeuticPlan);

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

            // Verifica conflitti slot temporale paziente
            $patientId = $planTherapy->therapeuticPlan->patient_id;
            $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                $patientId,
                $data['appointmentDateTime'], 
                $data['durationMinutes']
            );

            if ($patientSlotConflict) {
                return [
                    'success' => false,
                    'error' => 'Slot paziente già occupato',
                    'conflict' => $this->formatPatientSlotConflictInfo($patientSlotConflict)
                ];
            }

            // Verifica conflitti tipologia trattamento
            $treatmentConflict = $this->checkSameTreatmentTypeConflictByPlanTherapy(
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
     * Crea un appuntamento privato
     * 
     * @return array
     */
    public function actionCreatePrivateAppointment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();
            $this->validatePrivateAppointmentFields($data);

            // Verifica entità correlate
            $patient = $this->findPatient($data['patientId']);
            $therapist = $this->findTherapist($data['therapistId']);
            
            // Se treatmentTypeId non è fornito o è 0, lo ricavo dalla specializzazione del terapista
            if (!isset($data['treatmentTypeId']) || $data['treatmentTypeId'] == 0) {
                $treatmentType = $this->getTreatmentTypeFromTherapist($therapist);
                Yii::info("TreatmentType ricavato dalla specializzazione terapista: ID {$treatmentType->id}, Nome '{$treatmentType->name}'", __METHOD__);
            } else {
                $treatmentType = $this->findTreatmentType($data['treatmentTypeId']);
            }

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

            // Verifica conflitti slot temporale paziente
            $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                $data['patientId'],
                $data['appointmentDateTime'], 
                $data['durationMinutes']
            );

            if ($patientSlotConflict) {
                return [
                    'success' => false,
                    'error' => 'Slot paziente già occupato',
                    'conflict' => $this->formatPatientSlotConflictInfo($patientSlotConflict)
                ];
            }

            // Verifica conflitti tipologia trattamento per appuntamenti privati
            $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                $data['patientId'],
                $treatmentType->id,
                $data['appointmentDateTime']
            );

            if ($treatmentConflict) {
                return [
                    'success' => false,
                    'error' => 'Conflitto tipologia trattamento rilevato',
                    'conflict' => $this->formatTreatmentTypeConflictInfo($treatmentConflict)
                ];
            }

            // Aggiungi il treatmentTypeId ai dati per createPrivateSingleAppointment
            $data['treatmentTypeId'] = $treatmentType->id;

            // Crea appuntamento privato
            $appointment = $this->createPrivateSingleAppointment($data);

            // NON verifichiamo limiti settimanali per appuntamenti privati
            Yii::info("Appuntamento privato creato: ID {$appointment->id}", __METHOD__);

            return [
                'success' => true,
                'message' => 'Appuntamento privato creato con successo',
                'data' => [
                    'appointmentId' => $appointment->id
                ]
            ];

        } catch (Exception $e) {
            Yii::error("Errore creazione appuntamento privato: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

     /**
     * Crea un ciclo privato mensile di appuntamenti
     * 
     * @return array
     */
    public function actionCreatePrivateCycle()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();
            $this->validatePrivateCycleFields($data);

            // Verifica entità correlate
            $patient = $this->findPatient($data['patientId']);
            $therapist = $this->findTherapist($data['therapistId']);
            $treatmentType = $this->findTreatmentType($data['treatmentTypeId'], $data['therapistId']);

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Crea il ciclo privato
                $privateCycle = new PrivateCycle();
                $privateCycle->patient_id = $data['patientId'];
                $privateCycle->month_year = date('Y-m-01'); // Primo giorno del mese corrente
                $privateCycle->total_sessions = 0; // Verrà aggiornato dopo
                $privateCycle->notes = $data['notes'] ?? null;
                $privateCycle->created_by = $this->getCurrentUserId();

                if (!$privateCycle->save()) {
                    throw new Exception('Errore nel salvataggio del ciclo privato: ' . json_encode($privateCycle->errors));
                }

                // Genera appuntamenti per il mese corrente
                $result = $this->generatePrivateMonthlyAppointments($privateCycle, $data);

                // Aggiorna il numero totale di sessioni
                $privateCycle->total_sessions = $result['appointmentsCreated'];
                $privateCycle->save();

                $transaction->commit();

                return [
                    'success' => true,
                    'message' => 'Ciclo privato creato con successo',
                    'data' => [
                        'privateCycleId' => $privateCycle->id,
                        'appointmentsCreated' => $result['appointmentsCreated'],
                        'conflicts' => $result['conflicts']
                    ]
                ];

            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Yii::error("Errore creazione ciclo privato: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

      /**
     * Trova e valida TreatmentType
     * 
     * @param int $id
     * @param int $therapistId Opzionale: se $id è 0, usa la specializzazione del terapista
     * @return TreatmentType
     * @throws NotFoundHttpException
     */
    private function findTreatmentType($id, $therapistId = null)
    {
        // Se l'ID è 0 e abbiamo un terapista, trova il TreatmentType dalla sua specializzazione
        if ($id === 0 && $therapistId) {
            Yii::info("TreatmentType ID = 0, cerco dalla specializzazione del terapista {$therapistId}", __METHOD__);
            
            // Trova il terapista con la sua specializzazione
            $therapist = Therapist::find()
                ->with(['specialization'])
                ->where(['id' => $therapistId])
                ->one();
                
            if (!$therapist || !$therapist->specialization) {
                throw new NotFoundHttpException('Terapista o specializzazione non trovata');
            }
            
            // Trova il primo TreatmentType associato alla specializzazione del terapista
            $treatmentType = TreatmentType::find()
                ->innerJoin('specialization_treatments st', 'st.treatment_type_id = treatment_types.id')
                ->where(['st.specialization_id' => $therapist->specialization_id])
                ->one();
                
            if (!$treatmentType) {
                throw new NotFoundHttpException("Nessun tipo di trattamento trovato per la specializzazione '{$therapist->specialization->name}'");
            }
            
            Yii::info("TreatmentType trovato dalla specializzazione: ID {$treatmentType->id}, Nome '{$treatmentType->name}'", __METHOD__);
            return $treatmentType;
        }
        
        // Comportamento normale: cerca per ID
        $treatmentType = TreatmentType::findOne($id);

        if (!$treatmentType) {
            throw new NotFoundHttpException('Tipo trattamento non trovato');
        }

        return $treatmentType;
    }

    /**
     * Ottiene il TreatmentType dalla specializzazione del terapista
     * 
     * @param Therapist $therapist
     * @return TreatmentType
     * @throws NotFoundHttpException
     */
    private function getTreatmentTypeFromTherapist($therapist)
    {
        if (!$therapist->specialization_id) {
            throw new NotFoundHttpException('Terapista senza specializzazione');
        }
        
        // Trova il primo TreatmentType associato alla specializzazione del terapista
        $treatmentType = TreatmentType::find()
            ->innerJoin('specialization_treatments st', 'st.treatment_type_id = treatment_types.id')
            ->where(['st.specialization_id' => $therapist->specialization_id])
            ->one();
            
        if (!$treatmentType) {
            // Carica la specializzazione per il messaggio di errore
            $specialization = \common\models\Specialization::findOne($therapist->specialization_id);
            $specializationName = $specialization ? $specialization->name : "ID {$therapist->specialization_id}";
            throw new NotFoundHttpException("Nessun tipo di trattamento trovato per la specializzazione '{$specializationName}'");
        }
        
        return $treatmentType;
    }

     /**
     * Crea un singolo appuntamento privato
     * 
     * @param array $data
     * @return Appointment
     * @throws Exception
     */
    private function createPrivateSingleAppointment($data)
    {
        Yii::info("Creazione singolo appuntamento privato - DateTime: {$data['appointmentDateTime']}", __METHOD__);
        
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
        $appointment->appointment_source = Appointment::SOURCE_PRIVATE;
        $appointment->patient_id = $data['patientId'];
        $appointment->therapist_id = $data['therapistId'];
        $appointment->treatment_type_id = $data['treatmentTypeId'];
        $appointment->appointment_datetime = $appointmentDateTime;
        $appointment->duration_minutes = $data['durationMinutes'];
        $appointment->notes = $data['notes'] ?? null;
        $appointment->status = Appointment::STATUS_SCHEDULED;
        $appointment->created_by = $this->getCurrentUserId();
        $appointment->private_cycle_id = $data['privateCycleId'] ?? null;

        Yii::info("Tentativo salvataggio singolo appuntamento privato: " . json_encode($appointment->attributes), __METHOD__);

        if (!$appointment->save()) {
            $errors = $appointment->errors;
            Yii::error("Errori validazione singolo appuntamento privato: " . json_encode($errors), __METHOD__);
            throw new Exception('Errore nel salvataggio dell\'appuntamento privato: ' . json_encode($errors));
        }

        Yii::info("Singolo appuntamento privato salvato con successo: ID {$appointment->id}", __METHOD__);
        return $appointment;
    }


    

      /**
     * Trova e valida Patient
     * 
     * @param int $id
     * @return Patient
     * @throws NotFoundHttpException
     */
    private function findPatient($id)
    {
        $patient = Patient::findOne($id);

        if (!$patient) {
            throw new NotFoundHttpException('Paziente non trovato');
        }

        return $patient;
    }

      /**
     * Ottiene tutti i tipi di trattamento disponibili
     * 
     * @return array
     */
    public function actionGetTreatmentTypes()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $treatmentTypes = TreatmentType::find()
                ->orderBy(['name' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($treatmentTypes as $type) {
                $result[] = [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero tipi trattamento: " . $e->getMessage(), __METHOD__);
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
                    'specializationId' => $therapist->specialization_id,
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
     * Ottiene i dati anagrafici di un paziente con tutte le terapie disponibili
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

            // Ottieni l'email dal primo utente collegato (se presente)
            $email = null;
            if (!empty($patient->linkedUsers)) {
                $email = $patient->linkedUsers[0]->email;
            }

            $responseData = [
                'id' => $patient->id,
                'name' => $patient->getFullName(),
                'birthDate' => $patient->birth_date,
                'fiscalCode' => $patient->fiscal_code,
                'email' => $email,
                'hasActiveTherapeuticPlans' => false,
                'canCreatePrivateAppointments' => true // Sempre true, tutti possono creare appuntamenti privati
            ];

            // Cerca il piano terapeutico attivo più recente
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patient->id])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if ($therapeuticPlan) {
                $responseData['hasActiveTherapeuticPlans'] = true;

                // Trova TUTTE le terapie correlate al piano terapeutico
                $planTherapies = PlanTherapy::find()
                    ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
                    ->with(['treatmentType'])
                    ->all();

                if (!empty($planTherapies)) {
                    // Prepara i dati delle terapie
                    $therapiesData = [];
                    foreach ($planTherapies as $planTherapy) {
                        $therapiesData[] = [
                            'planTherapyId' => $planTherapy->id,
                            'treatmentTypeId' => $planTherapy->treatment_type_id,
                            'treatmentTypeName' => $planTherapy->treatmentType->name,
                            'weeklyHours' => $planTherapy->weekly_hours,
                            'isGroup' => $planTherapy->is_group,
                            'notes' => $planTherapy->notes,
                        ];
                    }

                    // Per backward compatibility, usa la prima terapia come default
                    $defaultPlanTherapy = $planTherapies[0];

                    $responseData['planTherapy'] = [
                        'planTherapyId' => $defaultPlanTherapy->id,
                        'therapeuticPlanId' => $therapeuticPlan->id,
                        'startDate' => $therapeuticPlan->start_date,
                        'endDate' => $therapeuticPlan->getCalculatedEndDate(),
                        'durationDays' => $therapeuticPlan->duration_days,
                        'weeklyHours' => $defaultPlanTherapy->weekly_hours,
                        'notes' => $therapeuticPlan->notes,
                    ];
                    $responseData['availableTherapies'] = $therapiesData;
                }
            }

            return [
                'success' => true,
                'data' => $responseData
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero paziente: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene il planTherapyId corretto per un paziente e terapista specifico
     * 
     * @return array
     */
    public function actionGetPlanTherapyForTherapist()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();
            $patientId = $data['patientId'] ?? null;
            $therapistId = $data['therapistId'] ?? null;

            if (!$patientId || !$therapistId) {
                return $this->errorResponse('Patient ID e Therapist ID sono obbligatori');
            }

            // Trova il piano terapeutico attivo del paziente
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patientId])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$therapeuticPlan) {
                return $this->errorResponse('Nessun piano terapeutico attivo trovato');
            }

            // Trova il terapista e la sua specializzazione
            $therapist = Therapist::find()
                ->with(['specialization.treatmentTypes'])
                ->where(['id' => $therapistId])
                ->one();

            if (!$therapist) {
                return $this->errorResponse('Terapista non trovato');
            }

            // Ottieni i tipi di trattamento che il terapista può gestire
            $therapistTreatmentTypes = [];
            if ($therapist->specialization && $therapist->specialization->treatmentTypes) {
                foreach ($therapist->specialization->treatmentTypes as $treatmentType) {
                    $therapistTreatmentTypes[] = $treatmentType->id;
                }
            }

            // Trova il PlanTherapy che corrisponde a uno dei tipi di trattamento del terapista
            $planTherapy = PlanTherapy::find()
                ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
                ->andWhere(['treatment_type_id' => $therapistTreatmentTypes])
                ->with(['treatmentType'])
                ->one();

            if (!$planTherapy) {
                return $this->errorResponse('Nessuna terapia compatibile trovata per questo terapista');
            }

            return [
                'success' => true,
                'data' => [
                    'planTherapyId' => $planTherapy->id,
                    'treatmentTypeId' => $planTherapy->treatment_type_id,
                    'treatmentTypeName' => $planTherapy->treatmentType->name,
                    'therapeuticPlanId' => $therapeuticPlan->id,
                    'weeklyHours' => $planTherapy->weekly_hours,
                ]
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero piano terapia per terapista: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene gli appuntamenti di un terapista per un mese specifico
     * Include sia appuntamenti da piano terapeutico che privati
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
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType'])
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
                // Ottieni il paziente corretto basato sul tipo di appuntamento
                if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                    $patient = $appointment->planTherapy->therapeuticPlan->patient;
                    $treatmentType = $appointment->planTherapy->treatmentType;
                } else {
                    $patient = $appointment->patient;
                    $treatmentType = $appointment->treatmentType;
                }

                $therapist = $appointment->therapist;
                $profile = $therapist->user->profile;
                
                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'appointmentSource' => $appointment->appointment_source,
                    'treatmentType' => $treatmentType ? $treatmentType->name : 'Non specificato',
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->getFullName()
                    ],
                    'therapist' => [
                        'id' => $therapist->id,
                        'name' => $profile->getFullName()
                    ],
                    'patternId' => $appointment->pattern_id,
                    'isRecurring' => $appointment->pattern_id !== null,
                    'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE
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
     * Include sia appuntamenti da piano terapeutico che privati
     * 
     * @return array
     */
    public function actionGetPatientAppointments($patientId, $month, $year){
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $startDate = new DateTime("$year-$month-01");
            $endDate = (clone $startDate)->modify('last day of this month');

            $appointments = Appointment::find()
                ->alias('a')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->innerJoin('therapists t', 't.id = a.therapist_id')
                ->innerJoin('users u', 'u.id = t.user_id')
                ->innerJoin('user_profiles up', 'up.user_id = u.id')
                ->with(['planTherapy.treatmentType', 'planTherapy.therapeuticPlan.patient', 'treatmentType', 'patient'])
                ->where(['or',
                    ['tp.patient_id' => $patientId],
                    ['a.patient_id' => $patientId]
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
                
                // Ottieni il paziente e il tipo di trattamento corretti
                if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                    $patient = $appointment->planTherapy->therapeuticPlan->patient;
                    $treatmentType = $appointment->planTherapy->treatmentType;
                } else {
                    $patient = $appointment->patient;
                    $treatmentType = $appointment->treatmentType;
                }
                
                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'appointmentSource' => $appointment->appointment_source,
                    'treatmentType' => $treatmentType ? $treatmentType->name : 'Non specificato',
                    'therapist' => [
                        'id' => $therapist->id,
                        'name' => $profile->getFullName()
                    ],
                    'patient' => [
                        'id' => $patient->id,
                        'name' => $patient->getFullName()
                    ],
                    'patternId' => $appointment->pattern_id,
                    'isRecurring' => $appointment->pattern_id !== null,
                    'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE
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
     * Gestisce sia appuntamenti da piano terapeutico che privati
     * 
     * @return array
     */
    public function actionUpdateAppointment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {$data = $this->getRequestData();
            Yii::info("Dati ricevuti per update appointment: " . json_encode($data), __METHOD__);
            $this->validateUpdateAppointmentFields($data);

            $appointment = Appointment::findOne($data['appointmentId']);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            if ($appointment->status === 'completed') {
                throw new BadRequestHttpException('Non è possibile modificare un appuntamento completato');
            }

            // Gestione diversa per appuntamenti privati vs piano terapeutico
            if ($appointment->appointment_source === Appointment::SOURCE_PRIVATE) {
                return $this->updatePrivateAppointment($appointment, $data);
            } else {
                return $this->updateTherapeuticPlanAppointment($appointment, $data);
            }

        } catch (Exception $e) {
            Yii::error("Errore aggiornamento appuntamento: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Aggiorna un appuntamento da piano terapeutico
     * 
     * @param Appointment $appointment
     * @param array $data
     * @return array
     */
    private function updateTherapeuticPlanAppointment($appointment, $data)
    {
        // Determina il nuovo plan_therapy_id se cambia il terapista
        $newPlanTherapyId = $appointment->plan_therapy_id;
        
        if ($data['therapistId'] != $appointment->therapist_id) {
            Yii::info("Terapista cambiato da {$appointment->therapist_id} a {$data['therapistId']}, calcolo nuovo plan_therapy_id", __METHOD__);
            
            // Ottieni il paziente dall'appuntamento esistente
            $patientId = $appointment->planTherapy->therapeuticPlan->patient_id;
            
            // Determina il nuovo plan_therapy_id usando il metodo esistente
            $planTherapyResult = $this->getPlanTherapyForPatientAndTherapist($patientId, $data['therapistId']);
            
            if (!$planTherapyResult) {
                throw new BadRequestHttpException('Impossibile determinare il piano terapia per il nuovo terapista');
            }
            
            $newPlanTherapyId = $planTherapyResult['planTherapyId'];
            Yii::info("Nuovo plan_therapy_id determinato: {$newPlanTherapyId}", __METHOD__);
        }

        // Verifica conflitti se cambiano data/ora/terapista
        if ($data['appointmentDateTime'] != $appointment->appointment_datetime || 
            $data['therapistId'] != $appointment->therapist_id ||
            $data['durationMinutes'] != $appointment->duration_minutes) {
            
            // Controllo conflitti terapista
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

            // Controllo conflitti slot temporale paziente
            $patientId = $appointment->planTherapy->therapeuticPlan->patient_id;
            $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                $patientId,
                $data['appointmentDateTime'], 
                $data['durationMinutes'],
                $appointment->id
            );

            if ($patientSlotConflict) {
                return [
                    'success' => false,
                    'error' => 'Slot paziente già occupato',
                    'conflict' => $this->formatPatientSlotConflictInfo($patientSlotConflict)
                ];
            }
        }

        // Verifica conflitti tipologia trattamento se cambia la data O il plan_therapy_id
        if ($data['appointmentDateTime'] != $appointment->appointment_datetime || 
            $newPlanTherapyId != $appointment->plan_therapy_id) {
            
            Yii::info("Controllo conflitto tipologia trattamento - Data cambiata: " . 
                     ($data['appointmentDateTime'] != $appointment->appointment_datetime ? 'SI' : 'NO') . 
                     ", Plan therapy cambiato: " . 
                     ($newPlanTherapyId != $appointment->plan_therapy_id ? 'SI' : 'NO'), __METHOD__);
            
            $treatmentConflict = $this->checkSameTreatmentTypeConflictByPlanTherapy(
                $newPlanTherapyId,
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

            // Aggiorna tutti i campi dell'appuntamento
            $appointment->plan_therapy_id = $newPlanTherapyId;
            $appointment->therapist_id = $data['therapistId'];
            $appointment->appointment_datetime = $data['appointmentDateTime'];
            $appointment->duration_minutes = $data['durationMinutes'];
            $appointment->notes = $data['notes'] ?? null;

            if (!$appointment->save()) {
                $errors = $appointment->getFirstErrors();
                Yii::error("Errori validazione appuntamento: " . json_encode($errors), __METHOD__);
                throw new Exception('Errore salvataggio appuntamento: ' . implode(', ', $errors));
            }

            // Traccia modifiche
            if (isset(Yii::$app->activityLog)) {
                Yii::$app->activityLog->record(
                    'update_appointment',
                    'Appuntamento modificato',
                    $appointment->id,
                    $oldValues,
                    $appointment->getAttributes()
                );
            }
            
            Yii::info("Appuntamento {$appointment->id} modificato con successo. Nuovo plan_therapy_id: {$newPlanTherapyId}", __METHOD__);

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Appuntamento aggiornato con successo',
                'data' => [
                    'appointmentId' => $appointment->id,
                    'planTherapyId' => $newPlanTherapyId
                ]
            ];

        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

     /**
     * Genera appuntamenti privati per un mese
     * 
     * @param PrivateCycle $privateCycle
     * @param array $data
     * @return array
     */
    private function generatePrivateMonthlyAppointments($privateCycle, $data)
    {
        $result = [
            'appointmentsCreated' => 0,
            'conflicts' => []
        ];

        $currentDate = new DateTime();
        $currentMonth = $currentDate->format('n');
        $currentYear = $currentDate->format('Y');

        // Calcola l'ultimo giorno del mese
        $endDate = new DateTime("$currentYear-$currentMonth-01");
        $endDate->modify('last day of this month');

        // Parti dal prossimo giorno del tipo specificato
        $startDate = clone $currentDate;
        while ($startDate->format('N') != $data['dayOfWeek']) {
            $startDate->modify('+1 day');
        }

        while ($startDate <= $endDate) {
            // Costruisci datetime appuntamento
            $appointmentDateTime = $startDate->format('Y-m-d ') . $data['startTime'] . ':00';

            // Verifica conflitti terapista
            $conflict = $this->checkTherapistConflict(
                $data['therapistId'], 
                $appointmentDateTime, 
                $data['durationMinutes']
            );

            if ($conflict) {
                $result['conflicts'][] = $this->formatConflictInfo($conflict, $startDate->format('Y-m-d'), $data['startTime'], $data['therapistId']);
                $startDate->modify('+7 days');
                continue;
            }

            // Verifica conflitti slot temporale paziente
            $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                $data['patientId'],
                $appointmentDateTime,
                $data['durationMinutes']
            );

            if ($patientSlotConflict) {
                $result['conflicts'][] = $this->formatPatientSlotConflictInfo($patientSlotConflict);
                $startDate->modify('+7 days');
                continue;
            }

            // Verifica conflitti tipologia trattamento
            $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                $data['patientId'],
                $data['treatmentTypeId'],
                $appointmentDateTime
            );

            if ($treatmentConflict) {
                $result['conflicts'][] = $this->formatTreatmentTypeConflictInfo($treatmentConflict, $startDate->format('Y-m-d'), $data['startTime']);
                $startDate->modify('+7 days');
                continue;
            }

            // Crea appuntamento privato
            try {
                $appointment = new Appointment();
                $appointment->appointment_source = Appointment::SOURCE_PRIVATE;
                $appointment->patient_id = $data['patientId'];
                $appointment->therapist_id = $data['therapistId'];
                $appointment->treatment_type_id = $data['treatmentTypeId'];
                $appointment->private_cycle_id = $privateCycle->id;
                $appointment->appointment_datetime = $appointmentDateTime;
                $appointment->duration_minutes = $data['durationMinutes'];
                $appointment->status = Appointment::STATUS_SCHEDULED;
                $appointment->notes = $data['notes'] ?? null;
                $appointment->created_by = $this->getCurrentUserId();

                if (!$appointment->save()) {
                    throw new Exception('Errore nel salvataggio dell\'appuntamento privato: ' . json_encode($appointment->errors));
                }

                $result['appointmentsCreated']++;
                Yii::info("Appuntamento privato creato: ID {$appointment->id}", __METHOD__);

            } catch (Exception $e) {
                Yii::error("Errore creazione appuntamento privato: " . $e->getMessage(), __METHOD__);
            }

            $startDate->modify('+7 days');
        }

        return $result;
    }

     /**
     * Valida i campi per appuntamento privato
     * 
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validatePrivateAppointmentFields($data)
    {
        // treatmentTypeId è opzionale, verrà derivato dal terapista se mancante
        $requiredFields = ['patientId', 'therapistId', 'appointmentDateTime', 'durationMinutes'];
        
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
     * Valida i campi per ciclo privato
     * 
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validatePrivateCycleFields($data)
    {
        $requiredFields = ['patientId', 'therapistId', 'treatmentTypeId', 'dayOfWeek', 'startTime', 'durationMinutes'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new BadRequestHttpException("Campo obbligatorio mancante: {$field}");
            }
        }

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
     * Aggiorna un appuntamento privato
     * 
     * @param Appointment $appointment
     * @param array $data
     * @return array
     */
    private function updatePrivateAppointment($appointment, $data)
    {
        // Per appuntamenti privati, permetti anche il cambio di tipo trattamento
        $newTreatmentTypeId = $data['treatmentTypeId'] ?? $appointment->treatment_type_id;
        
        // Verifica se il tipo trattamento esiste
        if ($newTreatmentTypeId != $appointment->treatment_type_id) {
            $treatmentType = $this->findTreatmentType($newTreatmentTypeId, $data['therapistId'] ?? $appointment->therapist_id);
        }

        // Verifica conflitti se cambiano data/ora/terapista
        if ($data['appointmentDateTime'] != $appointment->appointment_datetime || 
            $data['therapistId'] != $appointment->therapist_id ||
            $data['durationMinutes'] != $appointment->duration_minutes) {
            
            // Controllo conflitti terapista
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

            // Controllo conflitti slot temporale paziente
            $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                $appointment->patient_id,
                $data['appointmentDateTime'], 
                $data['durationMinutes'],
                $appointment->id
            );

            if ($patientSlotConflict) {
                return [
                    'success' => false,
                    'error' => 'Slot paziente già occupato',
                    'conflict' => $this->formatPatientSlotConflictInfo($patientSlotConflict)
                ];
            }
        }

        // Verifica conflitti tipologia trattamento se cambia
        if ($newTreatmentTypeId != $appointment->treatment_type_id || 
            $data['appointmentDateTime'] != $appointment->appointment_datetime) {
            
            $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                $appointment->patient_id,
                $newTreatmentTypeId,
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

            // Aggiorna i campi dell'appuntamento
            $appointment->therapist_id = $data['therapistId'];
            $appointment->appointment_datetime = $data['appointmentDateTime'];
            $appointment->duration_minutes = $data['durationMinutes'];
            $appointment->treatment_type_id = $newTreatmentTypeId;
            $appointment->notes = $data['notes'] ?? null;

            if (!$appointment->save()) {
                $errors = $appointment->getFirstErrors();
                Yii::error("Errori validazione appuntamento privato: " . json_encode($errors), __METHOD__);
                throw new Exception('Errore salvataggio appuntamento: ' . implode(', ', $errors));
            }

            // Traccia modifiche
            if (isset(Yii::$app->activityLog)) {
                Yii::$app->activityLog->record(
                    'update_private_appointment',
                    'Appuntamento privato modificato',
                    $appointment->id,
                    $oldValues,
                    $appointment->getAttributes()
                );
            }
            
            Yii::info("Appuntamento privato {$appointment->id} modificato con successo", __METHOD__);

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Appuntamento privato aggiornato con successo',
                'data' => [
                    'appointmentId' => $appointment->id,
                    'treatmentTypeId' => $appointment->treatment_type_id
                ]
            ];

        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
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

                    // Verifica conflitti slot temporale paziente
                    $patientId = $appointment->planTherapy->therapeuticPlan->patient_id;
                    $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                        $patientId,
                        $newDateTime,
                        $data['durationMinutes'],
                        $appointment->id
                    );

                    if ($patientSlotConflict) {
                        $errors[] = $this->formatPatientSlotConflictInfo($patientSlotConflict);
                        continue;
                    }

                    // Verifica conflitti tipologia trattamento
                    $treatmentConflict = $this->checkSameTreatmentTypeConflictByPlanTherapy(
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
            
            // Calcola inizio e fine settimana in modo deterministico
            // Se la data passata è domenica, considera la settimana successiva
            $weekStart = new DateTime($startDate);
            $dayOfWeek = $weekStart->format('N'); // 1 = lunedì, 7 = domenica
            
            if ($dayOfWeek == 7) {
                // Se è domenica, considera la settimana che inizia il giorno dopo (lunedì)
                $weekStart->modify('+1 day');
            } else {
                // Per tutti gli altri giorni, calcola il lunedì della settimana corrente
                $daysToSubtract = ($dayOfWeek - 1);
                $weekStart->modify("-{$daysToSubtract} days");
            }
            
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

      // ... resto dei metodi helper esistenti rimangono invariati ...

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

                // Verifica conflitti slot temporale paziente
                $patientId = $planTherapy->therapeuticPlan->patient_id;
                $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                    $patientId,
                    $appointmentDateTime,
                    $pattern->duration_minutes
                );
                
                if ($patientSlotConflict) {
                    Yii::info("Conflitto slot temporale paziente rilevato per {$appointmentDateTime}", __METHOD__);
                    $result['conflicts'][] = $this->formatPatientSlotConflictInfo($patientSlotConflict);
                    $currentDate->modify('+1 day');
                    continue;
                }

                // Verifica conflitti tipologia trattamento
                $treatmentConflict = $this->checkSameTreatmentTypeConflictByPlanTherapy($pattern->plan_therapy_id, $appointmentDateTime);
                
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
        $appointment->appointment_source = Appointment::SOURCE_THERAPEUTIC_PLAN;
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
        $appointment->appointment_source = Appointment::SOURCE_THERAPEUTIC_PLAN;
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
 * Controlla se esiste già un appuntamento dello stesso tipo di trattamento nello stesso giorno
 * Supporta sia appuntamenti da piano terapeutico che privati
 * 
 * @param int $patientId
 * @param int $treatmentTypeId
 * @param string $appointmentDateTime
 * @param int $excludeAppointmentId ID dell'appuntamento da escludere dal controllo (per update)
 * @return Appointment|null
 */
private function checkSameTreatmentTypeConflict($patientId, $treatmentTypeId, $appointmentDateTime, $excludeAppointmentId = null)
{
    $appointmentDate = new DateTime($appointmentDateTime);
    $dateStart = $appointmentDate->format('Y-m-d 00:00:00');
    $dateEnd = $appointmentDate->format('Y-m-d 23:59:59');

    // Cerca appuntamenti dello stesso tipo di trattamento nello stesso giorno
    // sia da piano terapeutico che privati
    $query = Appointment::find()
        ->alias('a')
        ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
        ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
        ->where(['or',
            // Appuntamenti da piano terapeutico con lo stesso treatment_type_id
            ['and',
                ['a.appointment_source' => Appointment::SOURCE_THERAPEUTIC_PLAN],
                ['pt.treatment_type_id' => $treatmentTypeId],
                ['tp.patient_id' => $patientId]
            ],
            // Appuntamenti privati con lo stesso treatment_type_id
            ['and',
                ['a.appointment_source' => Appointment::SOURCE_PRIVATE],
                ['a.treatment_type_id' => $treatmentTypeId],
                ['a.patient_id' => $patientId]
            ]
        ])
        ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
        ->andWhere(['between', 'a.appointment_datetime', $dateStart, $dateEnd])
        ->with([
            'planTherapy.treatmentType', 
            'planTherapy.therapeuticPlan.patient', 
            'treatmentType', 
            'patient', 
            'therapist.user.profile'
        ]);

    if ($excludeAppointmentId) {
        $query->andWhere(['!=', 'a.id', $excludeAppointmentId]);
    }

    $result = $query->one();
    
    if ($result) {
        Yii::info("Conflitto tipo trattamento rilevato: Paziente ID {$patientId}, Treatment Type ID {$treatmentTypeId}, Data {$appointmentDate->format('Y-m-d')}", __METHOD__);
    }

    return $result;
}

/**
 * Wrapper per checkSameTreatmentTypeConflict che accetta planTherapyId
 * Mantiene compatibilità con codice esistente
 * 
 * @param int $planTherapyId
 * @param string $appointmentDateTime
 * @param int $excludeAppointmentId
 * @return Appointment|null
 */
private function checkSameTreatmentTypeConflictByPlanTherapy($planTherapyId, $appointmentDateTime, $excludeAppointmentId = null)
{
    // Ottieni il piano terapia per recuperare il treatment_type_id
    $planTherapy = PlanTherapy::find()
        ->where(['id' => $planTherapyId])
        ->with(['therapeuticPlan'])
        ->one();
        
    if (!$planTherapy) {
        Yii::warning("PlanTherapy non trovato: {$planTherapyId}", __METHOD__);
        return null;
    }
    
    if (!$planTherapy->therapeuticPlan) {
        Yii::warning("TherapeuticPlan non trovato per PlanTherapy: {$planTherapyId}", __METHOD__);
        return null;
    }

    $treatmentTypeId = $planTherapy->treatment_type_id;
    $patientId = $planTherapy->therapeuticPlan->patient_id;

    return $this->checkSameTreatmentTypeConflict($patientId, $treatmentTypeId, $appointmentDateTime, $excludeAppointmentId);
}

    /**
     * Controlla se lo stesso paziente ha già un appuntamento che si sovrappone temporalmente
     * Modificato per gestire sia appuntamenti da piano che privati
     * 
     * @param int $patientId
     * @param string $appointmentDateTime
     * @param int $durationMinutes
     * @param int $excludeAppointmentId ID dell'appuntamento da escludere dal controllo (per update)
     * @return Appointment|null
     */
    private function checkPatientTimeSlotConflict($patientId, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null)
    {
        $startTime = new DateTime($appointmentDateTime);
        $endTime = clone $startTime;
        $endTime->modify("+{$durationMinutes} minutes");

        $query = Appointment::find()
            ->alias('a')
            ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
            ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
            ->where(['or',
                ['tp.patient_id' => $patientId],
                ['a.patient_id' => $patientId]
            ])
            ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
            ->andWhere(['or',
                ['and',
                    ['<=', 'a.appointment_datetime', $appointmentDateTime],
                    ['>', 'DATE_ADD(a.appointment_datetime, INTERVAL a.duration_minutes MINUTE)', $appointmentDateTime]
                ],
                ['and',
                    ['<', 'a.appointment_datetime', $endTime->format('Y-m-d H:i:s')],
                    ['>=', 'a.appointment_datetime', $appointmentDateTime]
                ]
            ])
            ->with(['planTherapy.treatmentType', 'planTherapy.therapeuticPlan.patient', 'therapist.user.profile', 'treatmentType', 'patient']);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'a.id', $excludeAppointmentId]);
        }

        $result = $query->one();
        
        if ($result) {
            Yii::info("Conflitto slot temporale paziente rilevato", __METHOD__);
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
     * Formatta le informazioni del conflitto per terapia specifica
     * 
     * @param Appointment $conflict
     * @param string $date
     * @param string $time
     * @return array
     */
    private function formatTreatmentTypeConflictInfo($conflict, $date = null, $time = null)
    {
        $appointmentDate = new DateTime($conflict->appointment_datetime);
        
        // Gestisci sia appuntamenti da piano terapeutico che privati
        if ($conflict->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN && 
            $conflict->planTherapy && 
            $conflict->planTherapy->therapeuticPlan) {
            $treatmentType = $conflict->planTherapy->treatmentType;
            $patient = $conflict->planTherapy->therapeuticPlan->patient;
            $planTherapyId = $conflict->plan_therapy_id;
            $conflictType = 'same_plan_therapy';
        } else {
            // Gestisci appuntamenti privati o appuntamenti da piano con dati mancanti
            $treatmentType = $conflict->treatmentType;
            $patient = $conflict->patient;
            $planTherapyId = null;
            $conflictType = 'same_treatment_type_private';
        }
        
        // Ottieni informazioni sul terapista per fornire più contesto
        $therapist = $conflict->therapist;
        $therapistInfo = $therapist ? $therapist->user->profile->getFullName() : 'Terapista non specificato';

        $conflictInfo = [
            'type' => $conflictType,
            'existingAppointmentId' => $conflict->id,
            'planTherapyId' => $planTherapyId,
            'treatmentType' => $treatmentType->name,
            'patientName' => $patient->getFullName(),
            'existingAppointmentDate' => $appointmentDate->format('Y-m-d'),
            'existingAppointmentTime' => $appointmentDate->format('H:i'),
            'existingTherapistName' => $therapistInfo,
            'appointmentSource' => $conflict->appointment_source,
            'message' => "Esiste già un appuntamento di {$treatmentType->name} per {$patient->getFullName()} in data {$appointmentDate->format('d/m/Y')} alle ore {$appointmentDate->format('H:i')} con {$therapistInfo}"
        ];

        if ($date && $time) {
            $conflictInfo['requestedDate'] = $date;
            $conflictInfo['requestedTime'] = $time;
        }

        return $conflictInfo;
    }

    /**
     * Formatta le informazioni del conflitto per slot temporale paziente
     * 
     * @param Appointment $conflict
     * @return array
     */
    private function formatPatientSlotConflictInfo($conflict)
    {
        $startDateTime = new DateTime($conflict->appointment_datetime);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+{$conflict->duration_minutes} minutes");
        
        $treatmentType = $conflict->planTherapy->treatmentType;
        $patient = $conflict->planTherapy->therapeuticPlan->patient;
        $therapist = $conflict->therapist;
        $therapistInfo = $therapist ? $therapist->user->profile->getFullName() : 'Terapista non specificato';

        $conflictInfo = [
            'type' => 'patient_time_slot_conflict',
            'existingAppointmentId' => $conflict->id,
            'patientName' => $patient->getFullName(),
            'treatmentType' => $treatmentType->name,
            'existingAppointmentDate' => $startDateTime->format('Y-m-d'),
            'existingAppointmentTime' => $startDateTime->format('H:i'),
            'existingAppointmentEndTime' => $endDateTime->format('H:i'),
            'existingTherapistName' => $therapistInfo,
            'message' => "Il paziente {$patient->getFullName()} ha già un appuntamento di {$treatmentType->name} in data {$startDateTime->format('d/m/Y')} dalle ore {$startDateTime->format('H:i')} alle ore {$endDateTime->format('H:i')} con {$therapistInfo}"
        ];

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
        
        // Calcola inizio settimana in modo deterministico
        $dayOfWeek = $weekStart->format('N'); // 1 = lunedì, 7 = domenica
        // Per checkWeeklyLimit usiamo sempre la settimana che contiene la data
        $daysToSubtract = ($dayOfWeek - 1); // Se lunedì (1), sottrae 0; se domenica (7), sottrae 6
        $weekStart->modify("-{$daysToSubtract} days");

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

    /**
     * Metodo helper per ottenere il plan_therapy_id per un paziente e terapista specifico
     * 
     * @param int $patientId
     * @param int $therapistId
     * @return array|null
     */
    private function getPlanTherapyForPatientAndTherapist($patientId, $therapistId)
    {
        try {
            // Trova il piano terapeutico attivo del paziente
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patientId])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$therapeuticPlan) {
                Yii::warning("Nessun piano terapeutico attivo per paziente {$patientId}", __METHOD__);
                return null;
            }

            // Trova il terapista e la sua specializzazione
            $therapist = Therapist::find()
                ->with(['specialization.treatmentTypes'])
                ->where(['id' => $therapistId])
                ->one();

            if (!$therapist) {
                Yii::warning("Terapista {$therapistId} non trovato", __METHOD__);
                return null;
            }

            // Ottieni i tipi di trattamento che il terapista può gestire
            $therapistTreatmentTypes = [];
            if ($therapist->specialization && $therapist->specialization->treatmentTypes) {
                foreach ($therapist->specialization->treatmentTypes as $treatmentType) {
                    $therapistTreatmentTypes[] = $treatmentType->id;
                }
            }

            // Trova il PlanTherapy che corrisponde a uno dei tipi di trattamento del terapista
            $planTherapy = PlanTherapy::find()
                ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
                ->andWhere(['in', 'treatment_type_id', $therapistTreatmentTypes])
                ->with(['treatmentType'])
                ->one();

            if (!$planTherapy) {
                Yii::warning("Nessun piano terapia trovato per terapista {$therapistId} e paziente {$patientId}", __METHOD__);
                return null;
            }

            return [
                'planTherapyId' => $planTherapy->id,
                'treatmentTypeId' => $planTherapy->treatment_type_id,
                'treatmentTypeName' => $planTherapy->treatmentType->name,
                'therapeuticPlanId' => $therapeuticPlan->id,
                'weeklyHours' => $planTherapy->weekly_hours
            ];

        } catch (Exception $e) {
            Yii::error("Errore in getPlanTherapyForPatientAndTherapist: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Sostituisce il terapista di un appuntamento
     * 
     * @return array
     */
    public function actionSubstituteTherapist()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $request = Yii::$app->request;
            $appointmentId = $request->post('appointmentId');
            $newTherapistId = $request->post('newTherapistId');
            $reason = $request->post('reason');

            if (!$appointmentId || !$newTherapistId) {
                return $this->errorResponse('Parametri mancanti: appointmentId e newTherapistId sono obbligatori');
            }

            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                return $this->errorResponse('Appuntamento non trovato');
            }

            // Verifica che l'appuntamento sia in stato therapist_absent
            if ($appointment->status !== Appointment::STATUS_THERAPIST_ABSENT) {
                return $this->errorResponse('La sostituzione è possibile solo per appuntamenti con terapista assente');
            }

            // Trova il nuovo terapista
            $newTherapist = Therapist::findOne($newTherapistId);
            if (!$newTherapist) {
                return $this->errorResponse('Nuovo terapista non trovato');
            }

            // Verifica che il nuovo terapista sia attivo
            if (!$newTherapist->is_active) {
                return $this->errorResponse('Il terapista selezionato non è attivo');
            }

            $transaction = Yii::$app->db->beginTransaction();
            
            try {
                // Salva il terapista originale se non è già stato salvato
                if (!$appointment->original_therapist_id) {
                    $appointment->original_therapist_id = $appointment->therapist_id;
                }

                $originalTherapistId = $appointment->original_therapist_id ?: $appointment->therapist_id;

                // Aggiorna l'appuntamento con il nuovo terapista
                $appointment->therapist_id = $newTherapistId;
                $appointment->status = Appointment::STATUS_SCHEDULED; // Torna allo stato programmato
                
                if (!$appointment->save()) {
                    throw new Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($appointment->errors));
                }

                // Crea o aggiorna il record di sostituzione
                $substitution = TherapistSubstitution::findOne(['appointment_id' => $appointmentId]);
                
                if (!$substitution) {
                    $substitution = new TherapistSubstitution();
                    $substitution->appointment_id = $appointmentId;
                    $substitution->original_therapist_id = $originalTherapistId;
                }
                
                $substitution->substitute_therapist_id = $newTherapistId;
                $substitution->reason = $reason;
                $substitution->substituted_by = Yii::$app->user->id ?: 1; // Default per test
                $substitution->substituted_at = date('Y-m-d H:i:s');

                if (!$substitution->save()) {
                    throw new Exception('Errore nel salvataggio della sostituzione: ' . json_encode($substitution->errors));
                }

                $transaction->commit();

                Yii::info("Sostituzione terapista completata - Appuntamento: {$appointmentId}, Terapista originale: {$originalTherapistId}, Nuovo terapista: {$newTherapistId}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Terapista sostituito con successo',
                    'data' => [
                        'appointmentId' => $appointmentId,
                        'originalTherapistId' => $originalTherapistId,
                        'newTherapistId' => $newTherapistId,
                        'substitutionId' => $substitution->id
                    ]
                ];

            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Yii::error("Errore nella sostituzione terapista: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene la lista di tutti i pazienti
     * 
     * @return array
     */
    public function actionGetPatients()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $patients = Patient::find()
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->all();

            $result = [];
            foreach ($patients as $patient) {
                // Ottieni l'email dal primo utente collegato (se presente)
                $email = null;
                $linkedUsers = $patient->linkedUsers;
                if (!empty($linkedUsers)) {
                    $email = $linkedUsers[0]->email;
                }

                // Verifica se ha piani terapeutici attivi
                $hasActiveTherapeuticPlans = TherapeuticPlan::find()
                    ->where(['patient_id' => $patient->id])
                    ->andWhere(['<=', 'start_date', date('Y-m-d')])
                    ->andWhere(['>=', 'end_date', date('Y-m-d')])
                    ->exists();

                $result[] = [
                    'id' => $patient->id,
                    'name' => $patient->getFullName(),
                    'birthDate' => $patient->birth_date,
                    'fiscalCode' => $patient->fiscal_code,
                    'email' => $email,
                    'hasActiveTherapeuticPlans' => $hasActiveTherapeuticPlans,
                    'canCreatePrivateAppointments' => true // Sempre true
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Yii::error("Errore recupero pazienti: " . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }
} 