<?php

namespace frontend\controllers;

use common\components\PlanHelper;
use common\helpers\NotificationHelper;
use common\models\Absence;
use common\models\Appointment;
use common\models\AppointmentPattern;
use common\models\CoordinatorGroup;
use common\models\GroupTherapist;
use common\models\Notification;
use common\models\Patient;
use common\models\PlanTherapy;
use common\models\PrivateCycle;
use common\models\Regime;
use common\models\Setting;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\TherapistSubstitution;
use common\models\TreatmentType;
use yii\filters\AccessControl;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use DateTime;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;

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
                    // Origin specifici (no wildcard) richiesto per inviare credentials.
                    // Lista produzione (gruppovitolo) + stage (cgm.badil.it) + dev locale.
                    'Origin' => [
                        // Produzione
                        'http://app.gruppovitolo.local',
                        'https://app.gruppovitolo.local',
                        'http://calendar.gruppovitolo.local',
                        'https://calendar.gruppovitolo.local',
                        // Stage
                        'https://calendar-cgm.badil.it',
                        'https://app-cgm.badil.it',
                        // Dev locale
                        'http://localhost',
                        'http://localhost:8080',
                        'http://localhost:5173',
                    ],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Expose-Headers' => ['Content-Disposition'],
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
            $patient = $this->findPatient($data['patientId']);  // AGGIUNGI QUESTA RIGA

            // Se validTo non è fornito, usa la data fine del piano terapeutico
            if (!isset($data['validTo']) || empty($data['validTo'])) {
                $data['validTo'] = $planTherapy->therapeuticPlan->getCalculatedEndDate();
                Yii::info("ValidTo non fornito, usata data fine piano terapeutico: {$data['validTo']}", __METHOD__);
            }

            // Verifica date
            $this->validateDates($data['validFrom'], $data['validTo'], $planTherapy->therapeuticPlan);

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Crea pattern
                $pattern = $this->createAppointmentPattern($data);

                // Genera appuntamenti (passa anche $data per weekInterval)
                $result = $this->generateAppointments($pattern, $therapist, $planTherapy, $patient, $data);

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
            Yii::error('Errore creazione pattern: ' . $e->getMessage(), __METHOD__);
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
            $this->validateStartDate($data['appointmentDateTime'], $data['planTherapyId']);
            $therapist = $this->findTherapist($data['therapistId']);

            $this->validateTherapist($data);

            // Verifica conflitti terapista
            $conflict = $this->checkTherapistConflict(
                $data['therapistId'],
                $data['appointmentDateTime'],
                $data['durationMinutes'],
                null,
                $data['groupSessionId'] ?? null
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

            // Verifica limite ore per tipologia trattamento
            $hoursLimitCheck = $this->checkPlanTherapyHoursLimit(
                Appointment::SOURCE_THERAPEUTIC_PLAN,
                $data['planTherapyId'],
                $data['appointmentDateTime'],
                $data['durationMinutes']
            );

            if ($hoursLimitCheck) {
                return [
                    'success' => false,
                    'error' => $hoursLimitCheck['message'],
                    'code' => $hoursLimitCheck['code']
                ];
            }

            // Crea appuntamento
            $appointment = $this->createSingleAppointment($data, $planTherapy, $patientId);

            // Verifica limite settimanale
            $weeklyLimitInfo = $this->checkWeeklyLimit($therapist, $data['appointmentDateTime'], $data['durationMinutes']);

            Yii::info("Appuntamento singolo creato: ID {$appointment->id}", __METHOD__);

            return [
                'success' => true,
                'message' => 'Appuntamento creato con successo',
                'data' => [
                    'appointmentId' => $appointment->id,
                    'groupSessionId' => $appointment->group_session_id,
                    'weeklyLimitExceeded' => $weeklyLimitInfo ? [$weeklyLimitInfo] : []
                ]
            ];
        } catch (Exception $e) {
            Yii::error('Errore creazione appuntamento: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), __METHOD__);
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
                $data['durationMinutes'],
                null,
                null
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
            Yii::error('Errore creazione appuntamento privato: ' . $e->getMessage(), __METHOD__);
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

            // Aggiorna i dati con il treatmentTypeId risolto
            $data['treatmentTypeId'] = $treatmentType->id;

            Yii::info("TreatmentType risolto: ID {$treatmentType->id}, Nome: {$treatmentType->name}", __METHOD__);

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Crea il ciclo privato
                $privateCycle = new PrivateCycle();
                $privateCycle->patient_id = $data['patientId'];
                $privateCycle->month_year = date('Y-m-01');  // Primo giorno del mese corrente
                $privateCycle->total_sessions = 1;  // Valore temporaneo per passare la validazione
                $privateCycle->notes = $data['notes'] ?? null;
                $privateCycle->created_by = $this->getCurrentUserId();

                if (!$privateCycle->save()) {
                    throw new Exception('Errore nel salvataggio del ciclo privato: ' . json_encode($privateCycle->errors));
                }

                // Genera appuntamenti per il mese corrente
                $result = $this->generatePrivateMonthlyAppointments($privateCycle, $data);

                // Se non sono stati creati appuntamenti, rollback
                if ($result['appointmentsCreated'] === 0) {
                    throw new Exception('Nessun appuntamento è stato creato per questo ciclo. Verifica le date e gli orari disponibili.');
                }

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
            Yii::error('Errore creazione ciclo privato: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Cancella tutti gli appuntamenti di un ciclo privato
     *
     * @return array
     */
    public function actionDeletePrivateCycleAppointments()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();

            if (!isset($data['privateCycleId']) || empty($data['privateCycleId'])) {
                throw new BadRequestHttpException('privateCycleId è obbligatorio');
            }

            $privateCycleId = (int) $data['privateCycleId'];

            // Verifica che il ciclo privato esista
            $privateCycle = PrivateCycle::findOne($privateCycleId);
            if (!$privateCycle) {
                throw new NotFoundHttpException('Ciclo privato non trovato');
            }

            // Inizia transazione
            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Trova tutti gli appuntamenti del ciclo privato
                $appointments = Appointment::find()
                    ->where(['private_cycle_id' => $privateCycleId])
                    ->andWhere(['status' => Appointment::STATUS_SCHEDULED])
                    ->all();

                $deletedCount = 0;
                foreach ($appointments as $appointment) {
                    if ($appointment->delete()) {
                        $deletedCount++;
                        Yii::info("Appuntamento privato eliminato: ID {$appointment->id}", __METHOD__);
                    }
                }

                // Elimina anche il ciclo privato
                if ($privateCycle->delete()) {
                    Yii::info("Ciclo privato eliminato: ID {$privateCycle->id}", __METHOD__);
                }

                $transaction->commit();

                return [
                    'success' => true,
                    'message' => "Eliminati {$deletedCount} appuntamenti e il ciclo privato",
                    'data' => [
                        'deletedCount' => $deletedCount,
                        'privateCycleDeleted' => true
                    ]
                ];
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Yii::error('Errore eliminazione ciclo privato: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Aggiorna la nota di un appuntamento
     *
     * @return array
     */
    public function actionSetAppointmentNote()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $appointmentId = Yii::$app->request->get('appointmentId');
            $note = Yii::$app->request->get('note');

            if (!$appointmentId || !$note) {
                throw new BadRequestHttpException('appointmentId e note sono obbligatori');
            }

            // Verifica che l'appuntamento esista
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }
            $appointment->notes = $note;

            if ($appointment->save()) {
                return [
                    'success' => true,
                    'message' => 'Appuntamento aggiornato con successo',
                    'data' => [
                        'appointmentId' => $appointment->id,
                        'note' => $note
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Errore nell'aggiornamento dell'appuntamento",
                    'data' => $appointment->errors
                ];
            }
        } catch (Exception $e) {
            Yii::error('Errore aggiornamento appuntamento: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Elimina la nota di un appuntamento
     *
     * @return array
     */
    public function actionDeleteAppointmentNote()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $data = $this->getRequestData();

            if (Yii::$app->request->isGet)
                throw new BadRequestHttpException('Metodo non supportato');

            if (!isset($data['appointmentId'])) {
                throw new BadRequestHttpException('appointmentId è obbligatorio');
            }

            // Verifica che l'appuntamento esista
            $appointment = Appointment::findOne($data['appointmentId']);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            $appointment->notes = null;

            if ($appointment->save()) {
                return [
                    'success' => true,
                    'message' => 'Appuntamento aggiornato con successo',
                    'data' => [
                        'appointmentId' => $appointment->id,
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Errore nell'aggiornamento dell'appuntamento",
                    'data' => $appointment->errors
                ];
            }
        } catch (Exception $e) {
            Yii::error('Errore aggiornamento appuntamento: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    public function actionGetTherapistAbsences()
    {
        $data = $this->getRequestData();

        // Validazione parametri
        $therapistId = Yii::$app->request->get('therapistId');
        if (!$therapistId) {
            return [
                'success' => false,
                'message' => 'ID terapista obbligatorio',
                'data' => null
            ];
        }

        if (Therapist::findOne($therapistId) === null) {
            return [
                'success' => false,
                'message' => 'Terapista non trovato',
                'data' => null
            ];
        }

        // Parametri opzionali
        $startDate = Yii::$app->request->get('startDate');
        $endDate = Yii::$app->request->get('endDate');

        // Query di base
        $query = Absence::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['status' => 'approved'])
            ->orderBy(['start_date' => SORT_DESC]);

        // Filtri date opzionali
        if ($startDate && $endDate) {
            // Assenze che si sovrappongono al periodo specificato
            $query->andWhere([
                'or',
                ['between', 'start_date', $startDate, $endDate],
                ['between', 'end_date', $startDate, $endDate],
                [
                    'and',
                    ['<=', 'start_date', $startDate],
                    ['>=', 'end_date', $endDate]
                ]
            ]);
        } elseif ($startDate) {
            // Assenze che terminano dopo la data di inizio
            $query->andWhere(['>=', 'end_date', $startDate]);
        } elseif ($endDate) {
            // Assenze che iniziano prima della data di fine
            $query->andWhere(['<=', 'start_date', $endDate]);
        }

        $absences = $query->all();

        return [
            'success' => true,
            'message' => 'Assenze recuperate con successo',
            'data' => [
                'therapist_id' => $therapistId,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'total' => count($absences),
                'absences' => $absences
            ]
        ];
    }

    /**
     * Aggiorna l'appuntamento con un ID di sessione di gruppo
     *
     * @return array
     */
    public function actionSetGroupAppointment()
    {
        try {
            if (Yii::$app->request->isGet)
                throw new BadRequestHttpException('Metodo non supportato');

            $data = $this->getRequestData();

            if (!isset($data['appointmentId'])) {
                throw new BadRequestHttpException('appointmentId è obbligatorio');
            }

            $appointment = Appointment::findOne($data['appointmentId']);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            if ($appointment->group_session_id) {
                throw new BadRequestHttpException('Appuntamento già associato a un gruppo');
            }

            $appointment->group_session_id = Appointment::generateGroupSessionId();

            if (!$appointment->save()) {
                throw new Exception("Errore nel salvataggio dell'appuntamento di gruppo: " . json_encode($appointment->errors));
            }

            return [
                'success' => true,
                'message' => 'Appuntamento aggiornato con successo',
                'data' => [
                    'appointmentId' => $appointment->id,
                    'groupSessionId' => $appointment->group_session_id
                ]
            ];
        } catch (Exception $e) {
            Yii::error('Errore creazione appuntamento di gruppo: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    public function actionGetSettingsList($regimeId = 0)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if ($regimeId > 0) {
            // Query SQL diretta - massima velocita
            $settings = Yii::$app->db->createCommand('
                SELECT s.id, s.nome 
                FROM {{%setting}} s 
                INNER JOIN {{%regime_setting}} rs ON rs.setting_id = s.id 
                WHERE rs.regime_id = :regimeId 
                ORDER BY s.nome ASC
            ', [':regimeId' => $regimeId])->queryAll();

            $data = ArrayHelper::map($settings, 'id', 'nome');
        } else {
            $settings = Setting::find()
                ->select(['id', 'nome'])
                ->orderBy(['nome' => SORT_ASC])
                ->asArray()
                ->all();

            $data = ArrayHelper::map($settings, 'id', 'nome');
        }

        return [
            'success' => true,
            'data' => $data
        ];
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

        // Aggiorna id_setting - mantieni esistente se non specificato
        if (isset($data['id_setting']) && $data['id_setting'] !== null) {
            $appointment->id_setting = $data['id_setting'];
        } elseif ($appointment->id_setting === null) {
            $setting = PlanHelper::getPlanTherapySettingFromAppointment($appointment);
            $appointment->id_setting = $setting->id;
        }

        Yii::info('Tentativo salvataggio singolo appuntamento privato: ' . json_encode($appointment->attributes), __METHOD__);

        if (!$appointment->save()) {
            $errors = $appointment->errors;
            Yii::error('Errori validazione singolo appuntamento privato: ' . json_encode($errors), __METHOD__);
            throw new Exception("Errore nel salvataggio dell'appuntamento privato: " . json_encode($errors));
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
            $query = TreatmentType::find()
                ->orderBy(['name' => SORT_ASC]);

            $allowedTypeIds = $this->getCoordinatorTreatmentTypeFilter();
            if ($allowedTypeIds !== null) {
                $query->andWhere(['id' => $allowedTypeIds]);
            }

            $treatmentTypes = $query->all();

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
            Yii::error('Errore recupero tipi trattamento: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene tutti i settings disponibili
     * Se viene fornito regimeId, restituisce solo i settings associati a quel regime
     *
     * @return array
     */
    public function actionGetSettings()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $regimeId = Yii::$app->request->get('regimeId');

            if ($regimeId) {
                // Filtra per regime specifico
                $settings = \common\models\Setting::getByRegime($regimeId);
            } else {
                // Tutti i settings
                $settings = \common\models\Setting::find()
                    ->orderBy(['nome' => SORT_ASC])
                    ->all();
            }

            $result = [];
            foreach ($settings as $setting) {
                $result[] = [
                    'id' => $setting->id,
                    'nome' => $setting->nome
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero settings: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    public function actionGetPlanTreatments($planId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            if (!$planId) {
                return $this->errorResponse('ID piano terapeutico mancante');
            }

            // Valida il piano terapeutico
            $plan = TherapeuticPlan::findOne($planId);

            $this->validateTherapeuticPlan($plan);

            // Recupera i trattamenti con eager loading. Filtra per coordinator
            // se applicabile: il dropdown specializzazione mostra solo quelle
            // coperte da terapisti del gruppo.
            $planTherapyQuery = PlanTherapy::find()
                ->select(['id', 'treatment_type_id'])
                ->with(['treatmentType' => function ($query) {
                    $query
                        ->select(['id', 'name'])
                        ->with(['specializationTreatments' => function ($q) {
                            $q->select(['treatment_type_id', 'specialization_id']);
                        }]);
                }])
                ->where(['therapeutic_plan_id' => $planId]);

            $allowedTypeIds = $this->getCoordinatorTreatmentTypeFilter();
            if ($allowedTypeIds !== null) {
                $planTherapyQuery->andWhere(['treatment_type_id' => $allowedTypeIds]);
            }

            $planTherapies = $planTherapyQuery->all();

            $result = [];
            foreach ($planTherapies as $therapy) {
                // Prendi il primo specialization_id disponibile per questo treatment_type
                $specializationId = null;
                if (!empty($therapy->treatmentType->specializationTreatments)) {
                    $specializationId = $therapy->treatmentType->specializationTreatments[0]->specialization_id;
                }

                $result[] = [
                    'id' => $therapy->id,
                    'treatment_type_id' => $therapy->treatment_type_id,
                    'name' => $therapy->treatmentType->name,
                    'specialization_id' => $specializationId
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero trattamenti piano: ' . $e->getMessage(), __METHOD__);
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

            // Trova il piano terapeutico attivo più recente.
            // Solo i piani con status='active' sono utilizzabili dal calendario:
            // suspended/draft/pending/terminated/expired/completed non permettono
            // la creazione o la modifica di appuntamenti collegati al piano.
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patientId])
                ->andWhere(['<=', 'start_date', date('Y-m-d')])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->andWhere(['status' => 'active'])
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
            Yii::error('Errore recupero piano paziente: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene la lista dei terapisti disponibili
     *
     * @return array
     */
    /**
     * Se l'utente loggato e' coordinator (e NON manager/admin), restituisce
     * la lista degli ID terapisti del suo gruppo. Altrimenti null = nessun
     * filtro (manager/admin vedono tutti i terapisti).
     *
     * Coerente con CalendarController::isCoordinatorOnly e
     * PatientController::getCoordinatorTherapistIds.
     *
     * @return int[]|null
     */
    private function getCoordinatorTherapistFilter()
    {
        $userId = Yii::$app->user->id;
        if (!$userId) {
            return null;
        }

        // Check ruoli direttamente da auth_assignment (piu affidabile di can()
        // su role nominali, indipendente dalla risoluzione gerarchica).
        $assignedRoles = (new \yii\db\Query())
            ->select('item_name')
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => $userId])
            ->column();

        $hasCoord = in_array('coordinator', $assignedRoles, true);
        $hasMan = in_array('manager', $assignedRoles, true);
        $hasAdmin = in_array('admin', $assignedRoles, true)
            || in_array('super_admin', $assignedRoles, true);

        if (!$hasCoord || $hasMan || $hasAdmin) {
            return null;
        }

        $group = CoordinatorGroup::find()
            ->where(['coordinator_user_id' => $userId])
            ->one();

        if (!$group) {
            return [0]; // fail-closed
        }

        $ids = GroupTherapist::find()
            ->select('therapist_id')
            ->where(['group_id' => $group->id])
            ->andWhere(['assigned_to' => null])
            ->column();

        return !empty($ids) ? array_map('intval', $ids) : [0];
    }

    /**
     * Treatment type ID coperti dai terapisti del gruppo del coordinator.
     * Null se non si applica filtro (manager/admin).
     *
     * @return int[]|null
     */
    private function getCoordinatorTreatmentTypeFilter()
    {
        $therapistIds = $this->getCoordinatorTherapistFilter();
        if ($therapistIds === null) {
            return null;
        }
        if ($therapistIds === [0]) {
            return [0];
        }

        $ids = (new \yii\db\Query())
            ->select('st.treatment_type_id')
            ->distinct()
            ->from(['st' => '{{%specialization_treatments}}'])
            ->innerJoin(['t' => '{{%therapists}}'], 't.specialization_id = st.specialization_id')
            ->where(['t.id' => $therapistIds])
            ->andWhere(['t.is_active' => 1])
            ->column();

        return !empty($ids) ? array_map('intval', $ids) : [0];
    }

    public function actionGetTherapists()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $query = Therapist::find()
                ->where(['is_active' => 1])
                ->with(['user.profile', 'specialization'])
                ->innerJoin('users u', 'u.id = therapists.user_id')
                ->innerJoin('user_profiles up', 'up.user_id = u.id')
                ->orderBy(['up.last_name' => SORT_ASC]);

            // Filtro ABA: se il paziente ha un piano ABA attivo, mostra solo
            // i terapisti abilitati (is_aba=1). Richiede patientId esplicito.
            $patientId = (int) Yii::$app->request->get('patientId', 0);
            if ($this->patientHasActiveABAPlan($patientId)) {
                $query->andWhere(['therapists.is_aba' => 1]);
            }

            $allowedIds = $this->getCoordinatorTherapistFilter();
            if ($allowedIds !== null) {
                $query->andWhere(['therapists.id' => $allowedIds]);
            }

            $therapists = $query->all();

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
            Yii::error('Errore recupero terapisti: ' . $e->getMessage(), __METHOD__);
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
            Yii::error('Errore recupero specializzazioni paziente: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene i terapisti per una specializzazione specifica con controlli di disponibilit�
     *
     * @param int $specializationId
     * @param string $date Data dell'appuntamento (Y-m-d) - opzionale
     * @param string $time Orario dell'appuntamento (H:i) - opzionale
     * @param int $duration Durata in minuti - opzionale
     * @param int $appointmentId ID appuntamento per escludere il terapista originale - opzionale
     * @param bool $force Se true, restituisce tutti i terapisti ignorando il filtro specializzazione
     * @return array
     */
    public function actionGetTherapistsBySpecialization($specializationId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // Parametri opzionali per controllo disponibilità
            $request = Yii::$app->request;
            $checkDate = $request->get('date');  // Y-m-d
            $checkTime = $request->get('time');  // H:i
            $checkDuration = (int) $request->get('duration', 60);  // minuti
            $appointmentId = (int) $request->get('appointmentId');  // ID appuntamento per escludere terapista originale
            $force = $request->get('force', false);

            $therapists = Therapist::find()
                ->alias('t')
                ->innerJoin('{{%users}} u', 'u.id = t.user_id')
                ->innerJoin('{{%user_profiles}} up', 'up.user_id = u.id')
                ->where(['t.is_active' => true]);

            if (!$force) {
                $therapists = $therapists->andWhere(['t.specialization_id' => $specializationId]);
            }

            // Filtro ABA: richiede patientId esplicito in query.
            $patientId = (int) $request->get('patientId', 0);
            if ($this->patientHasActiveABAPlan($patientId)) {
                $therapists->andWhere(['t.is_aba' => 1]);
            }

            // Se è fornito appointmentId, escludi il terapista originale dell'appuntamento
            if ($appointmentId > 0) {
                $appointment = Appointment::findOne($appointmentId);
                if ($appointment) {
                    $therapists->andWhere(['!=', 't.id', $appointment->therapist_id]);
                }
            }

            $allowedIds = $this->getCoordinatorTherapistFilter();
            if ($allowedIds !== null) {
                $therapists->andWhere(['t.id' => $allowedIds]);
            }

            $therapists = $therapists->orderBy(['up.last_name' => SORT_ASC])->all();

            $result = [];
            foreach ($therapists as $therapist) {
                $profile = $therapist->user->profile;

                $therapistData = [
                    'id' => $therapist->id,
                    'name' => $profile->getFullName(),
                    'email' => $therapist->user->email,
                    'specialization' => $therapist->specialization->name ?? 'Non specificata',
                    'weeklyHours' => $therapist->weekly_hours_contract,
                    'isAvailable' => true,
                    'unavailabilityReason' => null
                ];

                // Se sono forniti data/ora, controlla disponibilità
                if ($checkDate && $checkTime) {
                    $availability = $this->checkTherapistAvailabilityForSubstitution(
                        $therapist->id,
                        $checkDate,
                        $checkTime,
                        $checkDuration
                    );

                    $therapistData['isAvailable'] = $availability['isAvailable'];
                    $therapistData['unavailabilityReason'] = $availability['reason'];
                }

                $result[] = $therapistData;
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero terapisti per specializzazione: ' . $e->getMessage(), __METHOD__);
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
            // Get treatment type code for SUP/PT filtering
            $treatmentType = TreatmentType::findOne($treatmentTypeId);

            $query = Therapist::find()
                ->alias('t')
                ->innerJoin('{{%specializations}} s', 's.id = t.specialization_id')
                ->innerJoin('{{%specialization_treatments}} st', 'st.specialization_id = s.id')
                ->innerJoin('{{%users}} u', 'u.id = t.user_id')
                ->innerJoin('{{%user_profiles}} up', 'up.user_id = u.id')
                ->where(['t.is_active' => true])
                ->andWhere(['st.treatment_type_id' => $treatmentTypeId])
                ->orderBy(['up.last_name' => SORT_ASC]);

            // Filter by capability flags for supervision and parental training
            if ($treatmentType && $treatmentType->code === 'SUP') {
                $query->andWhere(['t.can_supervise' => 1]);
            } elseif ($treatmentType && $treatmentType->code === 'PT') {
                $query->andWhere(['t.can_parental_training' => 1]);
            }

            // Filtro ABA: richiede patientId esplicito in query.
            $patientId = (int) Yii::$app->request->get('patientId', 0);
            if ($this->patientHasActiveABAPlan($patientId)) {
                $query->andWhere(['t.is_aba' => 1]);
            }

            $allowedIds = $this->getCoordinatorTherapistFilter();
            if ($allowedIds !== null) {
                $query->andWhere(['t.id' => $allowedIds]);
            }

            $therapists = $query->all();

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
            Yii::error('Errore recupero terapisti per trattamento: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene i dettagli di un singolo appuntamento
     *
     * @return array
     */
    // public function actionGetAppointmentDetails($appointmentId)
    // {
    //     Yii::$app->response->format = Response::FORMAT_JSON;
    //     try {
    //         if (!$appointmentId) {
    //             return $this->errorResponse('ID appuntamento mancante');
    //         }
    //         $appointment = Appointment::find()
    //             ->alias('a')
    //             ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
    //             ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
    //             ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
    //             ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
    //             ->leftJoin('therapists t', 't.id = a.therapist_id')
    //             ->leftJoin('users u', 'u.id = t.user_id')
    //             ->leftJoin('user_profiles up', 'up.user_id = u.id')
    //             ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType', 'therapist.user.profile'])
    //             ->where(['a.id' => $appointmentId])
    //             ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
    //             ->one();
    //         if (!$appointment) {
    //             return $this->errorResponse('Appuntamento non trovato');
    //         }
    //         // Ottieni il paziente e il tipo di trattamento corretti basato sul tipo di appuntamento
    //         if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
    //             $patient = $appointment->planTherapy->therapeuticPlan->patient;
    //             $treatmentType = $appointment->planTherapy->treatmentType;
    //         } else {
    //             $patient = $appointment->patient;
    //             $treatmentType = $appointment->treatmentType;
    //         }
    //         $therapist = $appointment->therapist;
    //         $profile = $therapist->user->profile;
    //         $result = [
    //             'id' => $appointment->id,
    //             'datetime' => $appointment->appointment_datetime,
    //             'duration' => $appointment->duration_minutes,
    //             'status' => $appointment->status,
    //             'notes' => $appointment->notes,
    //             'appointmentSource' => $appointment->appointment_source,
    //             'treatmentType' => $treatmentType ? $treatmentType->name : 'Non specificato',
    //             'patient' => [
    //                 'id' => $patient->id,
    //                 'name' => $patient->getFullName()
    //             ],
    //             'therapist' => [
    //                 'id' => $therapist->id,
    //                 'name' => $profile->getFullName()
    //             ],
    //             'patternId' => $appointment->pattern_id,
    //             'isRecurring' => $appointment->pattern_id !== null,
    //             'privateCycleId' => $appointment->private_cycle_id,
    //             'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE,
    //             'groupSessionId' => $appointment->group_session_id\
    //         ];
    //         return [
    //             'success' => true,
    //             'data' => $result
    //         ];
    //     } catch (Exception $e) {
    //         Yii::error('Errore recupero dettagli appuntamento: ' . $e->getMessage(), __METHOD__);
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }
    public function actionGetAppointmentDetails($appointmentId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            if (!$appointmentId) {
                return $this->errorResponse('ID appuntamento mancante');
            }

            $appointment = Appointment::find()
                ->alias('a')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->leftJoin('therapists t', 't.id = a.therapist_id')
                ->leftJoin('users u', 'u.id = t.user_id')
                ->leftJoin('user_profiles up', 'up.user_id = u.id')
                ->leftJoin('setting s', 's.id = a.id_setting')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType', 'therapist.user.profile', 'setting'])
                ->where(['a.id' => $appointmentId])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->one();

            if (!$appointment) {
                return $this->errorResponse('Appuntamento non trovato');
            }

            // Ottieni il paziente e il tipo di trattamento corretti basato sul tipo di appuntamento
            if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
                $patient = $appointment->planTherapy->therapeuticPlan->patient;
                $treatmentType = $appointment->planTherapy->treatmentType;
            } else {
                $patient = $appointment->patient;
                $treatmentType = $appointment->treatmentType;
            }

            $therapist = $appointment->therapist;
            $profile = $therapist->user->profile;

            // Recupera tutti i pazienti del gruppo se è un appuntamento di gruppo
            $groupPatients = [];
            if ($appointment->group_session_id !== null) {
                $groupAppointments = Appointment::find()
                    ->alias('a')
                    ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                    ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                    ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                    ->where(['a.group_session_id' => $appointment->group_session_id])
                    ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                    ->all();

                foreach ($groupAppointments as $groupAppt) {
                    $groupPatient = $groupAppt->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN
                        ? $groupAppt->planTherapy->therapeuticPlan->patient
                        : $groupAppt->patient;

                    $groupPatients[] = [
                        'id' => $groupPatient->id,
                        'name' => $groupPatient->getFullName(),
                        'appointmentId' => $groupAppt->id
                    ];
                }
            }

            $result = [
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
                'privateCycleId' => $appointment->private_cycle_id,
                'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE,
                'groupSessionId' => $appointment->group_session_id,
                'groupPatients' => $groupPatients,  // AGGIUNTO: ora include i pazienti del gruppo
                'settingName' => $appointment->setting ? $appointment->setting->nome : null,
                'id_setting' => $appointment->id_setting,
            ];

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero dettagli appuntamento: ' . $e->getMessage(), __METHOD__);
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
                'canCreatePrivateAppointments' => true  // Sempre true, tutti possono creare appuntamenti privati
            ];

            // Cerca il piano terapeutico attivo più recente con il regime
            $therapeuticPlan = TherapeuticPlan::find()
                ->with(['regime'])  // Aggiungi questa riga
                ->where(['patient_id' => $patient->id])
                // ->andWhere(['<=', 'start_date', date('Y-m-d')]) //TODO: rimuovere questa riga
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if ($therapeuticPlan) {
                $responseData['hasActiveTherapeuticPlans'] = true;

                // Aggiungi questa sezione per includere i dati del regime
                $responseData['therapeuticPlan'] = [
                    'id' => $therapeuticPlan->id,
                    'status' => $therapeuticPlan->status,
                    'startDate' => $therapeuticPlan->start_date,
                    'endDate' => $therapeuticPlan->getCalculatedEndDate(),
                    'durationDays' => $therapeuticPlan->duration_days,
                    'regime' => null,
                ];

                // Includi i dati del regime se presente
                if (
                    $therapeuticPlan->regime &&
                    isset($therapeuticPlan->regime->id) &&
                    isset($therapeuticPlan->regime->nome)
                ) {
                    $responseData['therapeuticPlan']['regime'] = [
                        'id' => $therapeuticPlan->regime->id,
                        'nome' => $therapeuticPlan->regime->nome,
                        'descrizione' => $therapeuticPlan->regime->descrizione ?? '',
                        'conteggio_ore' => $therapeuticPlan->regime->conteggio_ore ?? ''
                    ];
                }

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
            Yii::error('Errore recupero paziente: ' . $e->getMessage(), __METHOD__);
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

            // Trova il piano terapeutico del paziente (include anche quelli che iniziano nel futuro)
            $therapeuticPlan = TherapeuticPlan::find()
                ->where(['patient_id' => $patientId])
                // Rimosso controllo start_date per permettere piani futuri
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();

            if (!$therapeuticPlan) {
                return $this->errorResponse('Nessun piano terapeutico trovato per questo paziente');
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
            Yii::error('Errore recupero piano terapia per terapista: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene gli appuntamenti di un terapista per un mese specifico
     * Include sia appuntamenti da piano terapeutico che privati
     *
     * @return array
     */
    // public function actionGetTherapistAppointments($therapistId, $month, $year)
    // {
    //     Yii::$app->response->format = Response::FORMAT_JSON;
    //     try {
    //         $startDate = new DateTime("$year-$month-01");
    //         $endDate = (clone $startDate)
    //             ->modify('first day of next month')
    //             ->modify('sunday');
    //         $appointments = Appointment::find()
    //             ->alias('a')
    //             ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
    //             ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
    //             ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
    //             ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
    //             ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType'])
    //             ->where([
    //                 'a.therapist_id' => $therapistId
    //             ])
    //             ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
    //             ->andWhere([
    //                 'between',
    //                 'a.appointment_datetime',
    //                 $startDate->format('Y-m-d 00:00:00'),
    //                 $endDate->format('Y-m-d 23:59:59')
    //             ])
    //             ->orderBy(['a.appointment_datetime' => SORT_ASC])
    //             ->all();
    //         $result = [];
    //         foreach ($appointments as $appointment) {
    //             // Ottieni il paziente corretto basato sul tipo di appuntamento
    //             if ($appointment->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN) {
    //                 $patient = $appointment->planTherapy->therapeuticPlan->patient;
    //                 $treatmentType = $appointment->planTherapy->treatmentType;
    //             } else {
    //                 $patient = $appointment->patient;
    //                 $treatmentType = $appointment->treatmentType;
    //             }
    //             $therapist = $appointment->therapist;
    //             $profile = $therapist->user->profile;
    //             // Recupera tutti i pazienti del gruppo se è un appuntamento di gruppo
    //             $groupPatients = [];
    //             if ($appointment->group_session_id !== null) {
    //                 $groupAppointments = Appointment::find()
    //                     ->alias('a')
    //                     ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
    //                     ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
    //                     ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
    //                     ->where(['a.group_session_id' => $appointment->group_session_id])
    //                     ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
    //                     ->all();
    //                 foreach ($groupAppointments as $groupAppt) {
    //                     $groupPatient = $groupAppt->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN
    //                         ? $groupAppt->planTherapy->therapeuticPlan->patient
    //                         : $groupAppt->patient;
    //                     $groupPatients[] = [
    //                         'id' => $groupPatient->id,
    //                         'name' => $groupPatient->getFullName(),
    //                         'appointmentId' => $groupAppt->id  // per poter eliminare il paziente dal gruppo
    //                     ];
    //                 }
    //             }
    //             $result[] = [
    //                 'id' => $appointment->id,
    //                 'datetime' => $appointment->appointment_datetime,
    //                 'duration' => $appointment->duration_minutes,
    //                 'status' => $appointment->status,
    //                 'notes' => $appointment->notes,
    //                 'appointmentSource' => $appointment->appointment_source,
    //                 'treatmentType' => $treatmentType ? $treatmentType->name : 'Non specificato',
    //                 'patient' => [
    //                     'id' => $patient->id,
    //                     'name' => $patient->getFullName()
    //                 ],
    //                 'therapist' => [
    //                     'id' => $therapist->id,
    //                     'name' => $profile->getFullName()
    //                 ],
    //                 'patternId' => $appointment->pattern_id,
    //                 'isRecurring' => $appointment->pattern_id !== null,
    //                 'privateCycleId' => $appointment->private_cycle_id,
    //                 'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE,
    //                 'groupSessionId' => $appointment->group_session_id,
    //                 'groupPatients' => $groupPatients,  // NUOVO CAMPO
    //             ];
    //         }
    //         return [
    //             'success' => true,
    //             'data' => $result
    //         ];
    //     } catch (Exception $e) {
    //         Yii::error('Errore recupero appuntamenti terapista: ' . $e->getMessage(), __METHOD__);
    //         return $this->errorResponse($e->getMessage());
    //     }
    // }
    public function actionGetTherapistAppointments($therapistId, $month, $year)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $startDate = new DateTime("$year-$month-01");
            $endDate = (clone $startDate)
                ->modify('first day of next month')
                ->modify('sunday');

            $appointments = Appointment::find()
                ->alias('a')
                ->leftJoin('setting s', 's.id = a.id_setting')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('patients p', 'p.id = COALESCE(tp.patient_id, a.patient_id)')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->with(['planTherapy.therapeuticPlan.patient', 'planTherapy.treatmentType', 'patient', 'treatmentType', 'setting'])
                ->where([
                    'a.therapist_id' => $therapistId
                ])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->andWhere([
                    'between',
                    'a.appointment_datetime',
                    $startDate->format('Y-m-d 00:00:00'),
                    $endDate->format('Y-m-d 23:59:59')
                ])
                ->orderBy(['a.appointment_datetime' => SORT_ASC])
                ->all();

            // Raggruppa gli appuntamenti
            $groupedAppointments = [];
            foreach ($appointments as $appointment) {
                if ($appointment->group_session_id !== null) {
                    // Appuntamenti di gruppo: raggruppa per group_session_id
                    $groupKey = $appointment->group_session_id;
                } else {
                    // Appuntamenti singoli: usa un ID univoco per ognuno
                    $groupKey = 'single_' . $appointment->id;
                }

                if (!isset($groupedAppointments[$groupKey])) {
                    $groupedAppointments[$groupKey] = [];
                }
                $groupedAppointments[$groupKey][] = $appointment;
            }

            $result = [];
            foreach ($groupedAppointments as $groupKey => $appointmentGroup) {
                // Prendi il primo appuntamento del gruppo come "principale"
                $appointment = $appointmentGroup[0];

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

                // Se è un gruppo, raccogli tutti i pazienti
                $groupPatients = [];
                if ($appointment->group_session_id !== null) {
                    foreach ($appointmentGroup as $groupAppt) {
                        $groupPatient = $groupAppt->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN
                            ? $groupAppt->planTherapy->therapeuticPlan->patient
                            : $groupAppt->patient;

                        $groupPatients[] = [
                            'id' => $groupPatient->id,
                            'name' => $groupPatient->getFullName(),
                            'appointmentId' => $groupAppt->id
                        ];
                    }
                }

                $result[] = [
                    'id' => $appointment->id,
                    'datetime' => $appointment->appointment_datetime,
                    'setting_id' => $appointment->id_setting,
                    'setting_name' => $appointment->setting->nome,
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
                    'privateCycleId' => $appointment->private_cycle_id,
                    'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE,
                    'groupSessionId' => $appointment->group_session_id,
                    'groupPatients' => $groupPatients,
                    'category' => $appointment->appointment_category ?? NULL,
                    'appointmentType' => $appointment->appointment_type,
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero appuntamenti terapista: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene gli appuntamenti di un paziente per un mese specifico
     * Include sia appuntamenti da piano terapeutico che privati
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
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
                ->leftJoin('setting s', 's.id = a.id_setting')
                ->innerJoin('therapists t', 't.id = a.therapist_id')
                ->innerJoin('users u', 'u.id = t.user_id')
                ->innerJoin('user_profiles up', 'up.user_id = u.id')
                ->with(['planTherapy.treatmentType', 'planTherapy.therapeuticPlan.patient', 'treatmentType', 'patient', 'setting'])
                ->where([
                    'or',
                    ['tp.patient_id' => $patientId],
                    ['a.patient_id' => $patientId]
                ])
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->andWhere([
                    'between',
                    'a.appointment_datetime',
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
                    'privateCycleId' => $appointment->private_cycle_id,
                    'isPrivate' => $appointment->appointment_source === Appointment::SOURCE_PRIVATE,
                    'settingName' => $appointment->setting ? $appointment->setting->nome : null,
                    'id_setting' => $appointment->id_setting,
                    'groupSessionId' => $appointment->group_session_id,
                    'appointmentType' => $appointment->appointment_type,
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero appuntamenti paziente: ' . $e->getMessage(), __METHOD__);
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

        try {
            $data = $this->getRequestData();
            Yii::info('Dati ricevuti per update appointment: ' . json_encode($data), __METHOD__);
            $this->validateUpdateAppointmentFields($data);
            $this->validateTherapist($data);

            $appointment = Appointment::findOne($data['appointmentId']);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            $immutableStatuses = [
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_ABSENT_JUSTIFIED,
                Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                Appointment::STATUS_THERAPIST_ABSENT,
            ];
            if (in_array($appointment->status, $immutableStatuses, true)) {
                throw new BadRequestHttpException('Non è possibile modificare un appuntamento con stato "' . $appointment->status . '"');
            }

            // Gestione diversa per appuntamenti privati vs piano terapeutico
            if ($appointment->appointment_source === Appointment::SOURCE_PRIVATE) {
                return $this->updatePrivateAppointment($appointment, $data);
            } else {
                return $this->updateTherapeuticPlanAppointment($appointment, $data);
            }
        } catch (Exception $e) {
            Yii::error('Errore aggiornamento appuntamento: ' . $e->getMessage(), __METHOD__);
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
        $is_regular_appointment_category = !isset($data['appointmentCategory']) || $data['appointmentCategory'] == 'regular';

        if ($data['therapistId'] != $appointment->therapist_id) {
            Yii::info("Terapista cambiato da {$appointment->therapist_id} a {$data['therapistId']}, calcolo nuovo plan_therapy_id", __METHOD__);

            // Ottieni il paziente dall'appuntamento esistente con fallback sul
            // campo diretto se la relazione planTherapy o therapeuticPlan
            // dovesse essere nulla (es. dati legacy / piano cancellato).
            $patientId = $appointment->planTherapy && $appointment->planTherapy->therapeuticPlan
                ? $appointment->planTherapy->therapeuticPlan->patient_id
                : $appointment->patient_id;
            if (!$patientId) {
                throw new BadRequestHttpException('Impossibile determinare il paziente associato all\'appuntamento.');
            }

            // Determina il nuovo plan_therapy_id usando il metodo esistente
            $planTherapyResult = $this->getPlanTherapyForPatientAndTherapist($patientId, $data['therapistId']);

            if (!$planTherapyResult) {
                throw new BadRequestHttpException('Impossibile determinare il piano terapia per il nuovo terapista');
            }

            $newPlanTherapyId = $planTherapyResult['planTherapyId'];
            Yii::info("Nuovo plan_therapy_id determinato: {$newPlanTherapyId}", __METHOD__);
        }

        // Verifica validità della data rispetto al piano terapeutico se cambia la data
        if (($is_regular_appointment_category) && $data['appointmentDateTime'] != $appointment->appointment_datetime) {
            $this->validateStartDate($data['appointmentDateTime'], $newPlanTherapyId);
        }
        // Verifica conflitti se cambiano data/ora/terapista
        if (
            $data['appointmentDateTime'] != $appointment->appointment_datetime ||
            $data['therapistId'] != $appointment->therapist_id ||
            $data['durationMinutes'] != $appointment->duration_minutes
        ) {
            // Controllo conflitti terapista
            $conflict = $this->checkTherapistConflict(
                $data['therapistId'],
                $data['appointmentDateTime'],
                $data['durationMinutes'],
                $appointment->id,
                $data['groupSessionId'] ?? null
            );

            if ($conflict) {
                return [
                    'success' => false,
                    'error' => 'Conflitto terapista rilevato',
                    'conflict' => $this->formatConflictInfo($conflict)
                ];
            }

            // Controllo conflitti slot temporale paziente.
            // Fallback su campo diretto se la relazione planTherapy non e'
            // disponibile (es. piano cancellato o dati legacy).
            $therapeuticPlan = $appointment->planTherapy
                ? $appointment->planTherapy->therapeuticPlan
                : null;
            $patientId = $therapeuticPlan
                ? $therapeuticPlan->patient_id
                : $appointment->patient_id;
            if (!$patientId) {
                throw new BadRequestHttpException('Impossibile determinare il paziente associato all\'appuntamento.');
            }

            if ($therapeuticPlan && $this->isABARegime($therapeuticPlan)) {
                // Per piani ABA: usa checkABAConflicts che permette coesistenza terapia/supervisione
                $abaConflict = $this->checkABAConflicts(
                    $patientId,
                    $data['therapistId'],
                    $data['appointmentDateTime'],
                    $appointment->appointment_type,
                    $appointment->planTherapy->treatment_type_id,
                    $appointment->id,
                    $appointment->group_session_id
                );

                if ($abaConflict) {
                    return [
                        'success' => false,
                        'error' => 'Conflitto rilevato',
                        'conflict' => $this->formatABAConflictInfo($abaConflict)
                    ];
                }
            } else {
                // Per piani non-ABA: controllo generico sovrapposizione slot
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
        }

        // Verifica conflitti tipologia trattamento se cambia la data O il plan_therapy_id
        // Per piani ABA questo check è già gestito da checkABAConflicts sopra
        $appointmentPlan = $appointment->planTherapy
            ? $appointment->planTherapy->therapeuticPlan
            : null;
        $isABA = $appointmentPlan ? $this->isABARegime($appointmentPlan) : false;
        if (
            !$isABA &&
            ($is_regular_appointment_category) &&
            ($data['appointmentDateTime'] != $appointment->appointment_datetime ||
                $newPlanTherapyId != $appointment->plan_therapy_id)
        ) {
            Yii::info('Controllo conflitto tipologia trattamento - Data cambiata: '
                . ($data['appointmentDateTime'] != $appointment->appointment_datetime ? 'SI' : 'NO')
                . ', Plan therapy cambiato: '
                . ($newPlanTherapyId != $appointment->plan_therapy_id ? 'SI' : 'NO'), __METHOD__);

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

        // Verifica limite ore per tipologia trattamento se cambia la data O la durata O il plan_therapy_id
        // (vale sia per ABA che non-ABA)
        if (
            ($is_regular_appointment_category) &&
            ($data['appointmentDateTime'] != $appointment->appointment_datetime ||
                $data['durationMinutes'] != $appointment->duration_minutes ||
                $newPlanTherapyId != $appointment->plan_therapy_id)
        ) {
            $hoursLimitCheck = $this->checkPlanTherapyHoursLimit(
                Appointment::SOURCE_THERAPEUTIC_PLAN,
                $newPlanTherapyId,
                $data['appointmentDateTime'],
                $data['durationMinutes'],
                $appointment->id
            );

            if ($hoursLimitCheck) {
                return [
                    'success' => false,
                    'error' => $hoursLimitCheck['message'],
                    'code' => $hoursLimitCheck['code']
                ];
            }
        }

        // Verifica limite ore settimanali del terapista se cambiano data/ora o durata
        if (
            $data['appointmentDateTime'] != $appointment->appointment_datetime ||
            $data['durationMinutes'] != $appointment->duration_minutes ||
            $data['therapistId'] != $appointment->therapist_id
        ) {
            $therapist = Therapist::findOne($data['therapistId']);
            if ($therapist && $therapist->weekly_hours_contract > 0) {
                $weeklyLimitInfo = $this->checkWeeklyLimit(
                    $therapist,
                    $data['appointmentDateTime'],
                    $data['durationMinutes'],
                    $appointment->id // Escludi l'appuntamento corrente dal calcolo
                );

                if ($weeklyLimitInfo) {
                    return [
                        'success' => false,
                        'error' => "Superato il limite ore settimanali del terapista ({$weeklyLimitInfo['limitHours']}h). " .
                            "Ore già assegnate: {$weeklyLimitInfo['currentHours']}h, " .
                            "Ore con modifica: " . number_format($weeklyLimitInfo['newTotal'], 1) . "h",
                        'code' => 'THERAPIST_WEEKLY_LIMIT_EXCEEDED',
                        'weeklyLimitExceeded' => $weeklyLimitInfo
                    ];
                }
            }
        }

        // Gestione aggiornamento gruppo se applicabile
        $applyToGroup = $data['applyToGroup'] ?? false;
        $appointmentsToUpdate = [$appointment];

        // Controllo per rimuovere groupSessionId se applyToGroup è false e la data/ora è cambiata
        $shouldRemoveGroupSessionId = false;
        if (!$applyToGroup && $appointment->group_session_id !== null) {
            $dateTimeChanged = $data['appointmentDateTime'] != $appointment->appointment_datetime;
            if ($dateTimeChanged) {
                $shouldRemoveGroupSessionId = true;
                Yii::info("Rimozione groupSessionId - applyToGroup: false, groupSessionId esistente: {$appointment->group_session_id}, data/ora cambiata", __METHOD__);
            }
        }

        if ($appointment->group_session_id !== null && $applyToGroup) {
            // Trova tutti gli appuntamenti con lo stesso group_session_id
            $groupAppointments = Appointment::find()
                ->where(['group_session_id' => $appointment->group_session_id])
                ->andWhere(['!=', 'id', $appointment->id])  // Escludi l'appuntamento corrente
                ->all();

            $appointmentsToUpdate = array_merge([$appointment], $groupAppointments);
            Yii::info("Aggiornamento di gruppo rilevato - Group Session ID: {$appointment->group_session_id}, Appuntamenti da aggiornare: " . count($appointmentsToUpdate), __METHOD__);
        }

        // Inizia transazione
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $updatedAppointments = [];

            foreach ($appointmentsToUpdate as $appointmentToUpdate) {
                $oldValues = $appointmentToUpdate->getAttributes();

                // Aggiorna tutti i campi dell'appuntamento
                $appointmentToUpdate->plan_therapy_id = $newPlanTherapyId;
                $appointmentToUpdate->therapist_id = $data['therapistId'];
                $appointmentToUpdate->appointment_datetime = $data['appointmentDateTime'];
                $appointmentToUpdate->duration_minutes = $data['durationMinutes'];
                $appointmentToUpdate->notes = $data['notes'] ?? null;

                // Rimuovi groupSessionId se necessario (solo per l'appuntamento principale)
                if ($shouldRemoveGroupSessionId && $appointmentToUpdate->id === $appointment->id) {
                    $appointmentToUpdate->group_session_id = null;
                    Yii::info("GroupSessionId rimosso dall'appuntamento {$appointmentToUpdate->id}", __METHOD__);
                }

                // Aggiorna id_setting - mantieni esistente se non specificato
                if (isset($data['id_setting']) && $data['id_setting'] !== null) {
                    $appointmentToUpdate->id_setting = $data['id_setting'];
                } elseif ($appointmentToUpdate->id_setting === null) {
                    $setting = PlanHelper::getPlanTherapySettingFromAppointment($appointmentToUpdate);
                    $appointmentToUpdate->id_setting = $setting->id;
                }
                // altrimenti mantieni il valore esistente

                if (!$appointmentToUpdate->save()) {
                    $errors = $appointmentToUpdate->getFirstErrors();
                    Yii::error('Errori validazione appuntamento: ' . json_encode($errors), __METHOD__);
                    throw new Exception('Errore salvataggio appuntamento: ' . implode(', ', $errors));
                }

                $updatedAppointments[] = $appointmentToUpdate->id;

                // Traccia modifiche per ogni appuntamento
                if (isset(Yii::$app->activityLog)) {
                    Yii::$app->activityLog->record(
                        'update_appointment',
                        'Appuntamento modificato',
                        $appointmentToUpdate->id,
                        $oldValues,
                        $appointmentToUpdate->getAttributes()
                    );
                }
            }

            $transaction->commit();

            $message = count($updatedAppointments) > 1
                ? 'Appuntamenti di gruppo aggiornati con successo'
                : 'Appuntamento aggiornato con successo';

            Yii::info('Appuntamenti aggiornati: ' . implode(', ', $updatedAppointments) . ". Nuovo plan_therapy_id: {$newPlanTherapyId}", __METHOD__);

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'appointmentIds' => $updatedAppointments,
                    'planTherapyId' => $newPlanTherapyId,
                    'wasGroupUpdate' => count($updatedAppointments) > 1
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
        Yii::info('Generazione appuntamenti privati - parametri: ' . json_encode($data), __METHOD__);

        $result = [
            'appointmentsCreated' => 0,
            'conflicts' => []
        ];

        $currentDate = new DateTime();
        $weekInterval = $data['weekInterval'] ?? 1; // 1 = settimanale (default), 2 = ogni 2 settimane
        $daysToAdd = 7 * $weekInterval;

        // Usa la data fornita in $data['appointmentDateTime'] come punto di partenza
        $startDate = new DateTime($data['appointmentDateTime']);
        $currentMonth = $startDate->format('n');
        $currentYear = $startDate->format('Y');

        // Calcola l'ultimo giorno del mese della data di inizio
        $endDate = new DateTime("$currentYear-$currentMonth-01");
        $endDate->modify('last day of this month');

        Yii::info("Date calcolate - corrente: {$currentDate->format('Y-m-d H:i:s')}, inizio: {$startDate->format('Y-m-d H:i:s')}, fine: {$endDate->format('Y-m-d')}, intervallo: ogni {$weekInterval} settimana/e", __METHOD__);

        while ($startDate <= $endDate) {
            // Usa il datetime completo dalla data corrente del ciclo
            $appointmentDateTime = $startDate->format('Y-m-d H:i:s');
            Yii::info("Tentativo creazione appuntamento: {$appointmentDateTime}", __METHOD__);

            // Verifica che l'appuntamento non sia nel passato
            if ($startDate <= $currentDate) {
                Yii::info("Saltato appuntamento nel passato: {$appointmentDateTime}", __METHOD__);
                $startDate->modify("+{$daysToAdd} days");
                continue;
            }

            // Verifica conflitti terapista
            $conflict = $this->checkTherapistConflict(
                $data['therapistId'],
                $appointmentDateTime,
                $data['durationMinutes'],
                null,
                null
            );

            if ($conflict) {
                Yii::info("Conflitto terapista trovato per {$appointmentDateTime}: " . json_encode($conflict), __METHOD__);
                $result['conflicts'][] = $this->formatConflictInfo(
                    $conflict,
                    $startDate->format('Y-m-d'),
                    $startDate->format('H:i'),
                    $data['therapistId']
                );
                $startDate->modify("+{$daysToAdd} days");
                continue;
            } else {
                Yii::info("Nessun conflitto terapista per {$appointmentDateTime}", __METHOD__);
            }

            // Verifica conflitti slot temporale paziente
            $patientSlotConflict = $this->checkPatientTimeSlotConflict(
                $data['patientId'],
                $appointmentDateTime,
                $data['durationMinutes']
            );

            if ($patientSlotConflict) {
                Yii::info("Conflitto slot temporale paziente rilevato per {$appointmentDateTime}: " . json_encode($patientSlotConflict), __METHOD__);
                $result['conflicts'][] = $this->formatPatientSlotConflictInfo($patientSlotConflict);
                $startDate->modify("+{$daysToAdd} days");
                continue;
            } else {
                Yii::info("Nessun conflitto slot paziente per {$appointmentDateTime}", __METHOD__);
            }

            // Verifica conflitti tipologia trattamento
            $treatmentConflict = $this->checkSameTreatmentTypeConflict(
                $data['patientId'],
                $data['treatmentTypeId'],
                $appointmentDateTime
            );

            if ($treatmentConflict) {
                Yii::info("Conflitto trattamento trovato per {$appointmentDateTime}: " . json_encode($treatmentConflict), __METHOD__);
                $result['conflicts'][] = $this->formatTreatmentTypeConflictInfo(
                    $treatmentConflict,
                    $startDate->format('Y-m-d'),
                    $startDate->format('H:i')
                );
                $startDate->modify("+{$daysToAdd} days");
                continue;
            } else {
                Yii::info("Nessun conflitto trattamento per {$appointmentDateTime}", __METHOD__);
            }

            // Crea appuntamento privato
            Yii::info("Tentativo creazione appuntamento privato per {$appointmentDateTime} - tutti i controlli passati", __METHOD__);
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

                // Aggiorna id_setting - mantieni esistente se non specificato
                if (isset($data['id_setting']) && $data['id_setting'] !== null) {
                    $appointment->id_setting = $data['id_setting'];
                } elseif ($appointment->id_setting === null) {
                    $setting = PlanHelper::getPlanTherapySettingFromAppointment($appointment);
                    $appointment->id_setting = $setting->id;
                }

                Yii::info('Dati appuntamento da salvare: ' . json_encode($appointment->attributes), __METHOD__);

                if (!$appointment->save()) {
                    Yii::error('Errore validazione appuntamento: ' . json_encode($appointment->errors), __METHOD__);
                    throw new Exception("Errore nel salvataggio dell'appuntamento privato: " . json_encode($appointment->errors));
                }

                $result['appointmentsCreated']++;
                Yii::info("Appuntamento privato creato: ID {$appointment->id}", __METHOD__);
            } catch (Exception $e) {
                Yii::error('Errore creazione appuntamento privato: ' . $e->getMessage(), __METHOD__);
            }

            // Passa alla settimana successiva (o ogni 2 settimane se weekInterval = 2)
            $startDate->modify("+{$daysToAdd} days");
        }

        Yii::info("Risultato generazione appuntamenti privati: {$result['appointmentsCreated']} creati, " . count($result['conflicts']) . ' conflitti', __METHOD__);

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
        // treatmentTypeId è opzionale, verrà derivato dal terapista se mancante o 0
        $requiredFields = ['patientId', 'therapistId', 'dayOfWeek', 'startTime', 'durationMinutes'];

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
        if (
            $data['appointmentDateTime'] != $appointment->appointment_datetime ||
            $data['therapistId'] != $appointment->therapist_id ||
            $data['durationMinutes'] != $appointment->duration_minutes
        ) {
            // Controllo conflitti terapista
            $conflict = $this->checkTherapistConflict(
                $data['therapistId'],
                $data['appointmentDateTime'],
                $data['durationMinutes'],
                $appointment->id,
                null
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
        if (
            $newTreatmentTypeId != $appointment->treatment_type_id ||
            $data['appointmentDateTime'] != $appointment->appointment_datetime
        ) {
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

            // Aggiorna id_setting - mantieni esistente se non specificato
            if (isset($data['id_setting']) && $data['id_setting'] !== null) {
                $appointment->id_setting = $data['id_setting'];
            } elseif ($appointment->id_setting === null) {
                $setting = PlanHelper::getPlanTherapySettingFromAppointment($appointment);
                $appointment->id_setting = $setting->id;
            }
            // altrimenti mantieni il valore esistente

            if (!$appointment->save()) {
                $errors = $appointment->getFirstErrors();
                Yii::error('Errori validazione appuntamento privato: ' . json_encode($errors), __METHOD__);
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
                        $appointment->id,
                        $data['groupSessionId'] ?? null
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

                    // Verifica limite ore per tipologia trattamento
                    $hoursLimitCheck = $this->checkPlanTherapyHoursLimit(
                        Appointment::SOURCE_THERAPEUTIC_PLAN,
                        $appointment->plan_therapy_id,
                        $newDateTime,
                        $data['durationMinutes'],
                        $appointment->id
                    );

                    if ($hoursLimitCheck) {
                        $errors[] = [
                            'message' => $hoursLimitCheck['message'],
                            'code' => $hoursLimitCheck['code'],
                            'date' => $appointmentDate->format('Y-m-d'),
                            'time' => $data['startTime']
                        ];
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
            Yii::error('Errore aggiornamento appuntamenti pattern: ' . $e->getMessage(), __METHOD__);
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
            $applyToGroup = $data['applyToGroup'] ?? false;  // NUOVO: parametro per gestire il gruppo

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

            // Se è un appuntamento di gruppo E applyToGroup è true, trova tutti gli appuntamenti del gruppo
            $appointmentsToDelete = [];
            $isGroupDeletion = false;

            if ($appointment->group_session_id !== null && $applyToGroup) {
                // Cancella tutto il gruppo
                $isGroupDeletion = true;
                $appointmentsToDelete = Appointment::find()
                    ->where(['group_session_id' => $appointment->group_session_id])
                    ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                    ->andWhere(['!=', 'status', 'completed'])
                    ->all();

                Yii::info("Cancellazione di gruppo rilevata - Group Session ID: {$appointment->group_session_id}, Appuntamenti da cancellare: " . count($appointmentsToDelete), __METHOD__);
            } else {
                // Cancella solo l'appuntamento singolo
                $appointmentsToDelete = [$appointment];
                Yii::info("Cancellazione singolo appuntamento - ID: {$appointmentId}", __METHOD__);
            }

            $deletedCount = 0;
            $deletedAppointmentIds = [];

            // Cancella gli appuntamenti selezionati
            foreach ($appointmentsToDelete as $apt) {
                $apt->status = Appointment::STATUS_CANCELLED;
                if ($apt->save()) {
                    $deletedCount++;
                    $deletedAppointmentIds[] = $apt->id;

                    // Traccia cancellazione
                    if (isset(Yii::$app->activityLog)) {
                        $activityMessage = $isGroupDeletion
                            ? 'Appuntamento cancellato (gruppo)'
                            : ($appointment->group_session_id !== null ? 'Appuntamento cancellato (singolo da gruppo)' : 'Appuntamento cancellato');

                        Yii::$app->activityLog->record(
                            'delete_appointment',
                            $activityMessage,
                            $apt->id
                        );
                    }
                } else {
                    Yii::warning("Errore nella cancellazione dell'appuntamento ID {$apt->id}", __METHOD__);
                }
            }

            $message = $isGroupDeletion
                ? "Cancellati {$deletedCount} appuntamenti del gruppo con successo"
                : ($appointment->group_session_id !== null
                    ? 'Cancellato appuntamento singolo dal gruppo'
                    : 'Appuntamento cancellato con successo');

            Yii::info("Cancellazione completata - Appuntamenti cancellati: {$deletedCount}, IDs: " . implode(',', $deletedAppointmentIds), __METHOD__);

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'deletedCount' => $deletedCount,
                    'deletedAppointmentIds' => $deletedAppointmentIds,
                    'isGroupDeletion' => $isGroupDeletion,
                    'wasGroupAppointment' => $appointment->group_session_id !== null
                ]
            ];
        } catch (Exception $e) {
            Yii::error('Errore cancellazione appuntamento: ' . $e->getMessage(), __METHOD__);
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
            Yii::error('Errore cancellazione appuntamenti pattern: ' . $e->getMessage(), __METHOD__);
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
            $dayOfWeek = $weekStart->format('N');  // 1 = lunedì, 7 = domenica

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
        } catch (Exception $e) {
            Yii::error('Errore calcolo ore settimanali: ' . $e->getMessage(), __METHOD__);
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

    private function validateTherapist($data)
    {
        $absences = Absence::find()
            ->where(['therapist_id' => $data['therapistId']])
            ->andWhere(['<=', 'start_date', $data['appointmentDateTime']])
            ->andWhere(['>=', 'end_date', $data['appointmentDateTime']])
            ->andWhere(['status' => 'approved'])
            ->exists();

        if ($absences) {
            throw new BadRequestHttpException('Terapista non disponibile per le date selezionate');
        }
    }

    /**
     * Valida i campi obbligatori per la creazione del pattern
     *
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validateRequiredFields($data)
    {
        // validTo è opzionale, verrà ricavato dal piano terapeutico se non fornito
        $requiredFields = ['planTherapyId', 'therapistId', 'dayOfWeek', 'startTime', 'durationMinutes', 'validFrom'];

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
     * Non richiede planTherapyId perché l'appuntamento esiste gi�
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

        // Solo i piani con status='active' sono operativi: bozza, in attesa,
        // sospeso, interrotto, completato, scaduto non permettono operazioni.
        if ($plan->status !== 'active') {
            throw new BadRequestHttpException(
                'Piano terapeutico non attivo (stato: ' . $plan->status . ')'
            );
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
        // Validazione range date del pattern rispetto al piano terapeutico
        $planStart = new DateTime($plan->start_date);
        $planEnd = new DateTime($plan->getCalculatedEndDate());

        if ($fromDate < $planStart || $toDate > $planEnd) {
            throw new BadRequestHttpException('Le date del pattern devono essere comprese nel periodo del piano terapeutico ('
                . $plan->start_date . ' - ' . $plan->getCalculatedEndDate() . ')');
        }

        Yii::info("Validazione date pattern completata - Pattern: {$validFrom} - {$validTo}, Piano: {$plan->start_date} - {$plan->getCalculatedEndDate()}", __METHOD__);
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
        $pattern->id_setting = $data['id_setting'] ?? 1;

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
     * @param Patient $patient
     * @param array $data Dati della richiesta (contiene weekInterval)
     * @return array
     */
    private function generateAppointments($pattern, $therapist, $planTherapy, $patient, $data = [])
    {
        $result = [
            'appointmentsCreated' => 0,
            'conflicts' => [],
            'weeklyLimitExceeded' => []
        ];

        $currentDate = new DateTime($pattern->valid_from);
        $endDate = new DateTime($pattern->valid_to);
        $weekInterval = isset($data['weekInterval']) ? (int)$data['weekInterval'] : 1; // 1 = settimanale, 2 = ogni 2 settimane
        $weekCounter = 0; // Contatore per tracciare le settimane

        Yii::info("Generazione appuntamenti - Pattern ID: {$pattern->id}, Da: {$pattern->valid_from}, A: {$pattern->valid_to}, Giorno: {$pattern->day_of_week}, Ora: {$pattern->start_time}, Intervallo: ogni {$weekInterval} settimana/e", __METHOD__);

        while ($currentDate <= $endDate) {
            if ($currentDate->format('N') == $pattern->day_of_week) {
                // Verifica se dobbiamo creare l'appuntamento in questa settimana in base all'intervallo
                if ($weekCounter % $weekInterval !== 0) {
                    Yii::info("Settimana saltata per intervallo bi-settimanale: {$currentDate->format('Y-m-d')}", __METHOD__);
                    $weekCounter++;
                    $currentDate->modify('+1 day');
                    continue;
                }
                $weekCounter++;

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
                $appointmentDate->setTime((int) $timeParts[0], (int) $timeParts[1], 0);
                $appointmentDateTime = $appointmentDate->format('Y-m-d H:i:s');

                Yii::info("Tentativo creazione appuntamento: {$appointmentDateTime}", __METHOD__);

                // Verifica conflitti terapista
                $conflict = $this->checkTherapistConflict(
                    $pattern->therapist_id,
                    $appointmentDateTime,
                    $pattern->duration_minutes,
                    null,
                    $data['groupSessionId'] ?? null
                );

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

                // Verifica limite ore per tipologia trattamento
                $hoursLimitCheck = $this->checkPlanTherapyHoursLimit(
                    Appointment::SOURCE_THERAPEUTIC_PLAN,
                    $pattern->plan_therapy_id,
                    $appointmentDateTime,
                    $pattern->duration_minutes
                );

                if ($hoursLimitCheck) {
                    Yii::info("Limite ore superato per {$appointmentDateTime}: {$hoursLimitCheck['message']}", __METHOD__);
                    $result['conflicts'][] = [
                        'message' => $hoursLimitCheck['message'],
                        'code' => $hoursLimitCheck['code'],
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $startTime
                    ];
                    $currentDate->modify('+1 day');
                    continue;
                }

                // Verifica limite settimanale
                $weeklyLimitInfo = $this->checkWeeklyLimit($therapist, $appointmentDateTime, $pattern->duration_minutes);
                if ($weeklyLimitInfo) {
                    $result['weeklyLimitExceeded'][] = $weeklyLimitInfo;
                }

                // Crea appuntamento (nuovo group_session_id per ciascuna occorrenza se isGroup)
                $occurrenceGroupSessionId = !empty($data['isGroup'])
                    ? Appointment::generateGroupSessionId()
                    : null;
                try {
                    $appointment = $this->createAppointmentFromPattern($pattern, $appointmentDateTime, $planTherapy, $patient, $occurrenceGroupSessionId);
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
    private function createAppointmentFromPattern($pattern, $appointmentDateTime, $planTherapy, $patient, $groupSessionId = null)
    {
        Yii::info("Creazione appuntamento da pattern - DateTime: {$appointmentDateTime}, Pattern ID: {$pattern->id}", __METHOD__);
        $this->validateTherapist(['therapistId' => $pattern->therapist_id, 'appointmentDateTime' => $appointmentDateTime]);

        // Validazione data rispetto al piano terapeutico
        $this->validateStartDate($appointmentDateTime, $pattern->plan_therapy_id);

        $appointment = new Appointment();
        $appointment->pattern_id = $pattern->id;
        $appointment->appointment_source = Appointment::SOURCE_THERAPEUTIC_PLAN;
        $appointment->plan_therapy_id = $pattern->plan_therapy_id;
        $appointment->therapist_id = $pattern->therapist_id;
        $appointment->patient_id = $patient->id;
        $appointment->appointment_datetime = $appointmentDateTime;
        $appointment->duration_minutes = $pattern->duration_minutes;
        $appointment->status = Appointment::STATUS_SCHEDULED;  // Imposta status di default
        $appointment->created_by = $this->getCurrentUserId();
        $appointment->group_session_id = $groupSessionId;

        if ($pattern->id_setting == null && $appointment->id_setting == null) {
            $setting = PlanHelper::getPlanTherapySettingFromAppointment($appointment);
            $appointment->id_setting = $setting->id;
        } else {
            $appointment->id_setting = $pattern->id_setting;
        }

        Yii::info('Tentativo salvataggio appuntamento: ' . json_encode($appointment->attributes), __METHOD__);

        if (!$appointment->save()) {
            $errors = $appointment->errors;
            Yii::error('Errori validazione appuntamento: ' . json_encode($errors), __METHOD__);
            throw new Exception("Errore nel salvataggio dell'appuntamento: " . json_encode($errors));
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
    private function createSingleAppointment($data, $planTherapy, $patientId)
    {
        if (!$patientId) {
            throw new Exception('Paziente non trovato');
        }
        // Normalizza il formato datetime
        $appointmentDateTime = $data['appointmentDateTime'];
        try {
            $dateTime = new DateTime($appointmentDateTime);
            $appointmentDateTime = $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            Yii::error("Errore nel parsing della data: {$appointmentDateTime}", __METHOD__);
            throw new Exception("Formato data/ora non valido: {$appointmentDateTime}");
        }

        // Validazione data rispetto al piano terapeutico
        $this->validateStartDate($appointmentDateTime, $data['planTherapyId']);

        $appointment = new Appointment();
        $appointment->plan_therapy_id = $data['planTherapyId'];
        $appointment->appointment_source = Appointment::SOURCE_THERAPEUTIC_PLAN;
        $appointment->therapist_id = $data['therapistId'];
        $appointment->patient_id = $patientId;
        $appointment->appointment_datetime = $appointmentDateTime;
        $appointment->duration_minutes = $data['durationMinutes'];
        $appointment->notes = $data['notes'] ?? null;
        $appointment->status = Appointment::STATUS_SCHEDULED;  // Imposta status di default
        $appointment->created_by = $this->getCurrentUserId();
        $appointment->group_session_id = (isset($data['isGroup']) && $data['isGroup']) ? $this->getGroupSessionId($data) : null;

        // Aggiorna id_setting - mantieni esistente se non specificato
        if (isset($data['id_setting']) && $data['id_setting'] !== null) {
            $appointment->id_setting = $data['id_setting'];
        } elseif ($appointment->id_setting === null) {
            $setting = PlanHelper::getPlanTherapySettingFromAppointment($appointment);
            $appointment->id_setting = $setting->id;
        }

        Yii::info('Tentativo salvataggio singolo appuntamento: ' . json_encode($appointment->attributes), __METHOD__);

        if (!$appointment->save()) {
            $errors = $appointment->errors;
            Yii::error('Errori validazione singolo appuntamento: ' . json_encode($errors), __METHOD__);
            throw new Exception("Errore nel salvataggio dell'appuntamento: " . json_encode($errors));
        }

        Yii::info("Singolo appuntamento salvato con successo: ID {$appointment->id}", __METHOD__);
        return $appointment;
    }

    /**
     * Genera un ID di sessione di gruppo se non specificato
     *
     * @param array $data
     * @return string
     */
    private function getGroupSessionId($data)
    {
        $groupSessionId = $data['groupSessionId'] ?? Appointment::generateGroupSessionId();
        return $groupSessionId;
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
    private function checkTherapistConflict($therapistId, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null, $groupSessionId = null)
    {
        $startTime = new DateTime($appointmentDateTime);
        $endTime = clone $startTime;
        $endTime->modify("+{$durationMinutes} minutes");

        $query = Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['not in', 'status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_ABSENT_JUSTIFIED,
                Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                Appointment::STATUS_THERAPIST_ABSENT,
            ]])
            ->andWhere([
                'or',
                [
                    'and',
                    ['<=', 'appointment_datetime', $appointmentDateTime],
                    ['>', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $appointmentDateTime]
                ],
                [
                    'and',
                    ['<', 'appointment_datetime', $endTime->format('Y-m-d H:i:s')],
                    ['>=', 'appointment_datetime', $appointmentDateTime]
                ]
            ])
            ->with(['planTherapy.therapeuticPlan.patient']);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'id', $excludeAppointmentId]);
        }
        // Se è specificato un group_session_id, escludi gli appuntamenti con lo stesso group_session_id
        if ($groupSessionId !== null) {
            $query->andWhere([
                'or',
                ['group_session_id' => null],
                ['!=', 'group_session_id', $groupSessionId]
            ]);
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

        // Query per trovare appuntamenti con LO STESSO treatment_type nello stesso giorno
        // (terapie diverse della stessa specializzazione sono ammesse)
        $query = Appointment::find()
            ->alias('a')
            ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
            ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
            ->where([
                'or',
                // Appuntamenti da piano terapeutico con lo stesso treatment_type
                [
                    'and',
                    ['a.appointment_source' => Appointment::SOURCE_THERAPEUTIC_PLAN],
                    ['pt.treatment_type_id' => $treatmentTypeId],
                    ['tp.patient_id' => $patientId]
                ],
                // Appuntamenti privati con lo stesso treatment_type
                [
                    'and',
                    ['a.appointment_source' => Appointment::SOURCE_PRIVATE],
                    ['a.treatment_type_id' => $treatmentTypeId],
                    ['a.patient_id' => $patientId]
                ]
            ])
            ->andWhere(['not in', 'a.status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_ABSENT_JUSTIFIED,
                Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                Appointment::STATUS_THERAPIST_ABSENT,
            ]])
            ->andWhere(['between', 'a.appointment_datetime', $dateStart, $dateEnd]);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'a.id', $excludeAppointmentId]);
        }

        // Eager loading per debug/log
        $query->with([
            'planTherapy.treatmentType',
            'planTherapy.therapeuticPlan.patient',
            'treatmentType',
            'patient',
            'therapist.user.profile'
        ]);

        $result = $query->one();

        if ($result) {
            $conflictTreatmentName = $result->appointment_source == Appointment::SOURCE_THERAPEUTIC_PLAN
                ? $result->planTherapy->treatmentType->name
                : $result->treatmentType->name;

            Yii::info(
                'Conflitto stessa terapia rilevato: '
                    . "Paziente ID {$patientId}, "
                    . "Treatment Type ID {$treatmentTypeId}, "
                    . "Data {$appointmentDate->format('Y-m-d')}, "
                    . "Appuntamento esistente: {$conflictTreatmentName} alle {$result->appointment_datetime}",
                __METHOD__
            );
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
        // Ottieni la terapia associata al piano per recuperare il treatment_type_id
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
            ->where([
                'or',
                ['tp.patient_id' => $patientId],
                ['a.patient_id' => $patientId]
            ])
            ->andWhere(['not in', 'a.status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_ABSENT_JUSTIFIED,
                Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                Appointment::STATUS_THERAPIST_ABSENT,
            ]])
            ->andWhere([
                'or',
                [
                    'and',
                    ['<=', 'a.appointment_datetime', $appointmentDateTime],
                    ['>', 'DATE_ADD(a.appointment_datetime, INTERVAL a.duration_minutes MINUTE)', $appointmentDateTime]
                ],
                [
                    'and',
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
            Yii::info('Conflitto slot temporale paziente rilevato', __METHOD__);
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

        // Recupera il paziente in modo difensivo: l'appuntamento confliggente
        // puo' essere privato (no planTherapy/therapeuticPlan) oppure avere
        // dati mancanti.
        $patientName = 'Paziente non disponibile';
        if ($conflict->planTherapy && $conflict->planTherapy->therapeuticPlan && $conflict->planTherapy->therapeuticPlan->patient) {
            $patientName = $conflict->planTherapy->therapeuticPlan->patient->getFullName();
        } elseif ($conflict->patient) {
            $patientName = $conflict->patient->getFullName();
        }

        $conflictInfo = [
            'existingAppointmentId' => $conflict->id,
            'existingAppointmentInfo' => [
                'patientName' => $patientName,
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
        if (
            $conflict->appointment_source === Appointment::SOURCE_THERAPEUTIC_PLAN &&
            $conflict->planTherapy &&
            $conflict->planTherapy->therapeuticPlan
        ) {
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

        // Difensivo: l'appuntamento confliggente puo' essere privato o avere
        // relazioni mancanti (piano cancellato, dati legacy).
        $treatmentType = null;
        $patient = null;
        if ($conflict->planTherapy) {
            $treatmentType = $conflict->planTherapy->treatmentType;
            if ($conflict->planTherapy->therapeuticPlan) {
                $patient = $conflict->planTherapy->therapeuticPlan->patient;
            }
        }
        if (!$treatmentType) {
            $treatmentType = $conflict->treatmentType;
        }
        if (!$patient) {
            $patient = $conflict->patient;
        }

        $treatmentName = $treatmentType ? $treatmentType->name : 'tipo non specificato';
        $patientName = $patient ? $patient->getFullName() : 'Paziente non disponibile';

        $therapist = $conflict->therapist;
        $therapistInfo = $therapist && $therapist->user && $therapist->user->profile
            ? $therapist->user->profile->getFullName()
            : 'Terapista non specificato';

        return [
            'type' => 'patient_time_slot_conflict',
            'existingAppointmentId' => $conflict->id,
            'patientName' => $patientName,
            'treatmentType' => $treatmentName,
            'existingAppointmentDate' => $startDateTime->format('Y-m-d'),
            'existingAppointmentTime' => $startDateTime->format('H:i'),
            'existingAppointmentEndTime' => $endDateTime->format('H:i'),
            'existingTherapistName' => $therapistInfo,
            'message' => "Il paziente {$patientName} ha gia' un appuntamento di {$treatmentName} in data {$startDateTime->format('d/m/Y')} dalle ore {$startDateTime->format('H:i')} alle ore {$endDateTime->format('H:i')} con {$therapistInfo}"
        ];
    }

    /**
     * Verifica limite settimanale terapista
     *
     * @param Therapist $therapist
     * @param string $appointmentDateTime
     * @param int $durationMinutes
     * @return array|null
     */
    private function checkWeeklyLimit($therapist, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null)
    {
        $appointmentDate = new DateTime($appointmentDateTime);
        $weekStart = clone $appointmentDate;

        // Calcola inizio settimana in modo deterministico
        $dayOfWeek = $weekStart->format('N');  // 1 = lunedì, 7 = domenica
        // Per checkWeeklyLimit usiamo sempre la settimana che contiene la data
        $daysToSubtract = ($dayOfWeek - 1);  // Se lunedì (1), sottrae 0; se domenica (7), sottrae 6
        $weekStart->modify("-{$daysToSubtract} days");

        $currentWeeklyHours = $this->calculateWeeklyHours($therapist->id, $weekStart->format('Y-m-d'), $excludeAppointmentId);
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
    private function calculateWeeklyHours($therapistId, $weekStartDate, $excludeAppointmentId = null)
    {
        $weekStart = new DateTime($weekStartDate);
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days 23:59:59');

        $query = Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['in', 'status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED]])
            ->andWhere(['between', 'appointment_datetime', $weekStart->format('Y-m-d H:i:s'), $weekEnd->format('Y-m-d H:i:s')]);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'id', $excludeAppointmentId]);
        }

        $totalMinutes = $query->sum('duration_minutes') ?: 0;

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
            Yii::error('Errore in getPlanTherapyForPatientAndTherapist: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Sostituzione terapista
     *
     * @return array
     */
    public function actionSubstituteTherapist()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();
            $appointmentId = $data['appointmentId'] ?? null;
            $newTherapistId = $data['newTherapistId'] ?? null;
            $reason = $data['reason'] ?? null;
            $dontRegisterAbsence = $data['dontRegisterAbsence'] ?? false;

            Yii::info('Dati ricevuti per sostituzione terapista: ' . json_encode($data), __METHOD__);

            if (!$appointmentId || !$newTherapistId) {
                return $this->errorResponse('Parametri mancanti: appointmentId e newTherapistId sono obbligatori');
            }

            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                return $this->errorResponse('Appuntamento non trovato');
            }

            // Se è un appuntamento di gruppo, trova tutti gli appuntamenti del gruppo
            $appointmentsToSubstitute = [];
            $isGroupSubstitution = false;

            if ($appointment->group_session_id !== null) {
                $isGroupSubstitution = true;
                $appointmentsToSubstitute = Appointment::find()
                    ->where(['group_session_id' => $appointment->group_session_id])
                    ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                    ->all();

                Yii::info("Sostituzione di gruppo rilevata - Group Session ID: {$appointment->group_session_id}, Appuntamenti da sostituire: " . count($appointmentsToSubstitute), __METHOD__);
            } else {
                $appointmentsToSubstitute = [$appointment];
            }

            // Verifica che tutti gli appuntamenti siano in uno stato che permette la sostituzione
            foreach ($appointmentsToSubstitute as $apt) {
                if (!in_array($apt->status, [Appointment::STATUS_SCHEDULED, Appointment::STATUS_THERAPIST_ABSENT])) {
                    return $this->errorResponse('La sostituzione è possibile solo per appuntamenti programmati o con terapista assente');
                }
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
                $substitutedCount = 0;
                $substitutedAppointmentIds = [];
                $originalTherapistId = $appointment->therapist_id;

                foreach ($appointmentsToSubstitute as $currentAppointment) {
                    // Se dontRegisterAbsence è true, fai solo il cambio semplice
                    if ($dontRegisterAbsence) {
                        // Aggiorna solo l'appuntamento con il nuovo terapista
                        $currentAppointment->therapist_id = $newTherapistId;
                        $currentAppointment->status = Appointment::STATUS_SCHEDULED;

                        if (!$currentAppointment->save()) {
                            throw new Exception("Errore nel salvataggio dell'appuntamento ID {$currentAppointment->id}: " . json_encode($currentAppointment->errors));
                        }

                        $substitutedCount++;
                        $substitutedAppointmentIds[] = $currentAppointment->id;
                        continue;
                    }

                    // LOGICA ORIGINALE: Solo se dontRegisterAbsence è false
                    // Se l'appuntamento era in status 'scheduled', crea un record Absence per tracciare l'assenza del terapista
                    if ($currentAppointment->status === Appointment::STATUS_SCHEDULED && $substitutedCount === 0) {
                        // Crea l'assenza solo per il primo appuntamento del gruppo
                        $absence = new Absence();
                        $absence->therapist_id = $currentAppointment->therapist_id;
                        $absence->start_date = date('Y-m-d', strtotime($currentAppointment->appointment_datetime));
                        $absence->end_date = $absence->start_date;
                        $absence->type = Absence::TYPE_OTHER;
                        $absence->reason = $reason ?: 'Assenza comunicata tramite sostituzione';
                        $absence->status = Absence::STATUS_APPROVED;
                        $absence->approved_by = Yii::$app->user->id ?: 1;
                        $absence->approved_at = date('Y-m-d H:i:s');
                        $absence->created_by = Yii::$app->user->id ?: 1;

                        if (!$absence->save()) {
                            Yii::warning("Errore nel salvataggio dell'assenza per il terapista {$currentAppointment->therapist_id}: " . json_encode($absence->errors), __METHOD__);
                        } else {
                            Yii::info("Creata assenza automatica per terapista {$currentAppointment->therapist_id} in data {$absence->start_date}", __METHOD__);
                        }
                    }

                    // Salva il terapista originale se non è già stato salvato
                    if (!$currentAppointment->original_therapist_id) {
                        $currentAppointment->original_therapist_id = $currentAppointment->therapist_id;
                    }

                    // Aggiorna l'appuntamento con il nuovo terapista
                    $currentAppointment->therapist_id = $newTherapistId;
                    $currentAppointment->status = Appointment::STATUS_SCHEDULED;

                    if (!$currentAppointment->save()) {
                        throw new Exception("Errore nel salvataggio dell'appuntamento ID {$currentAppointment->id}: " . json_encode($currentAppointment->errors));
                    }

                    // Crea o aggiorna il record di sostituzione
                    $substitution = TherapistSubstitution::findOne(['appointment_id' => $currentAppointment->id]);

                    if (!$substitution) {
                        $substitution = new TherapistSubstitution();
                        $substitution->appointment_id = $currentAppointment->id;
                        $substitution->original_therapist_id = $originalTherapistId;
                    }

                    $substitution->substitute_therapist_id = $newTherapistId;
                    $substitution->reason = $reason;
                    $substitution->substituted_by = Yii::$app->user->id ?: 1;
                    $substitution->substituted_at = date('Y-m-d H:i:s');

                    if (!$substitution->save()) {
                        throw new Exception('Errore nel salvataggio della sostituzione: ' . json_encode($substitution->errors));
                    }

                    $substitutedCount++;
                    $substitutedAppointmentIds[] = $currentAppointment->id;
                }

                $transaction->commit();

                try {
                    $this->sendSubstitutionNotifications(
                        $appointmentsToSubstitute,
                        $originalTherapistId,
                        $newTherapistId,
                        $reason,
                        $isGroupSubstitution,
                        (bool)$dontRegisterAbsence
                    );
                } catch (\Exception $e) {
                    Yii::error('Errore invio notifiche sostituzione: ' . $e->getMessage(), __METHOD__);
                }

                $message = $isGroupSubstitution
                    ? "Terapista sostituito con successo per {$substitutedCount} appuntamenti del gruppo"
                    : 'Terapista sostituito con successo';

                Yii::info("Sostituzione terapista completata - Appuntamenti sostituiti: {$substitutedCount}, IDs: " . implode(',', $substitutedAppointmentIds), __METHOD__);

                return [
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'appointmentIds' => $substitutedAppointmentIds,
                        'originalTherapistId' => $originalTherapistId,
                        'newTherapistId' => $newTherapistId,
                        'substitutedCount' => $substitutedCount,
                        'isGroupSubstitution' => $isGroupSubstitution,
                        'absenceRegistered' => !$dontRegisterAbsence
                    ]
                ];
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Yii::error('Errore nella sostituzione terapista: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Invia le notifiche al terapista sostituto e a quello sostituito.
     * Una sola notifica per gruppo (anche se include più appuntamenti).
     *
     * @param Appointment[] $appointments
     * @param int $originalTherapistId
     * @param int $newTherapistId
     * @param string|null $reason
     * @param bool $isGroupSubstitution
     * @param bool $dontRegisterAbsence
     * @return void
     */
    private function sendSubstitutionNotifications(
        array $appointments,
        $originalTherapistId,
        $newTherapistId,
        $reason,
        $isGroupSubstitution,
        $dontRegisterAbsence
    ) {
        if (empty($appointments)) {
            return;
        }

        $originalTherapist = Therapist::findOne($originalTherapistId);
        $newTherapist = Therapist::findOne($newTherapistId);

        if (!$originalTherapist || !$newTherapist) {
            Yii::warning("Notifiche sostituzione non inviate: terapista non trovato (originale={$originalTherapistId}, nuovo={$newTherapistId})", __METHOD__);
            return;
        }

        $originalUserId = $originalTherapist->user_id;
        $newUserId = $newTherapist->user_id;
        $newTherapistName = $this->getTherapistDisplayName($newTherapist);

        if ($isGroupSubstitution) {
            $count = count($appointments);
            $timestamps = array_map(function ($a) {
                return strtotime($a->appointment_datetime);
            }, $appointments);
            sort($timestamps);
            $firstDate = date('d/m/Y', $timestamps[0]);
            $lastDate = date('d/m/Y', end($timestamps));
            $rangeText = $firstDate === $lastDate
                ? "del {$firstDate}"
                : "dal {$firstDate} al {$lastDate}";
            $whatForSubstitute = "{$count} appuntamenti di gruppo {$rangeText}";
            $whatForOriginal = "I tuoi {$count} appuntamenti di gruppo {$rangeText}";
            $appointmentIds = array_map(function ($a) {
                return $a->id;
            }, $appointments);
        } else {
            $apt = $appointments[0];
            $dt = new DateTime($apt->appointment_datetime);
            $date = $dt->format('d/m/Y');
            $time = $dt->format('H:i');
            $patient = isset($apt->planTherapy->therapeuticPlan->patient)
                ? $apt->planTherapy->therapeuticPlan->patient
                : null;
            $patientName = $patient
                ? trim($patient->last_name . ' ' . $patient->first_name)
                : 'paziente';
            $whatForSubstitute = "l'appuntamento con {$patientName} del {$date} alle {$time}";
            $whatForOriginal = "Il tuo appuntamento con {$patientName} del {$date} alle {$time}";
            $appointmentIds = [$apt->id];
        }

        $payload = [
            'type' => 'therapist_substitution',
            'is_group' => $isGroupSubstitution,
            'appointment_ids' => $appointmentIds,
            'original_therapist_id' => $originalTherapistId,
            'substitute_therapist_id' => $newTherapistId,
            'reason' => $reason,
            'absence_registered' => !$dontRegisterAbsence,
        ];

        if ($newUserId) {
            if ($dontRegisterAbsence) {
                $titleSub = $isGroupSubstitution ? 'Appuntamenti assegnati' : 'Appuntamento assegnato';
                $verbSub = $isGroupSubstitution ? 'Ti sono stati assegnati' : 'Ti è stato assegnato';
                $messageSub = "{$verbSub} {$whatForSubstitute}.";
            } else {
                $titleSub = 'Sostituzione assegnata';
                $verbSub = $isGroupSubstitution ? 'Ti sono stati assegnati' : 'Ti è stata assegnata una sostituzione:';
                $messageSub = $isGroupSubstitution
                    ? "{$verbSub} {$whatForSubstitute} in sostituzione."
                    : "{$verbSub} {$whatForSubstitute}.";
            }
            if (!empty($reason)) {
                $messageSub .= "\nMotivo: {$reason}";
            }
            NotificationHelper::sendToUsers(
                [$newUserId],
                $titleSub,
                $messageSub,
                Notification::TYPE_INFO,
                $payload
            );
        }

        if ($originalUserId && $originalUserId !== $newUserId) {
            if ($dontRegisterAbsence) {
                $titleOrig = $isGroupSubstitution ? 'Appuntamenti riassegnati' : 'Appuntamento riassegnato';
                $verbOrig = $isGroupSubstitution ? 'sono stati riassegnati' : 'è stato riassegnato';
                $messageOrig = "{$whatForOriginal} {$verbOrig} a {$newTherapistName}.";
            } else {
                $titleOrig = 'Sostituzione registrata';
                $verbOrig = $isGroupSubstitution ? 'sono stati sostituiti' : 'è stato sostituito';
                $messageOrig = "{$whatForOriginal} {$verbOrig} da {$newTherapistName}.";
            }
            if (!empty($reason)) {
                $messageOrig .= "\nMotivo: {$reason}";
            }
            NotificationHelper::sendToUsers(
                [$originalUserId],
                $titleOrig,
                $messageOrig,
                Notification::TYPE_INFO,
                $payload
            );
        }
    }

    /**
     * Restituisce il nome visualizzabile di un terapista (Cognome Nome),
     * con fallback sull'username o sull'id se il profilo non è disponibile.
     */
    private function getTherapistDisplayName(Therapist $therapist)
    {
        $user = $therapist->user;
        if ($user && $user->profile) {
            $name = trim(($user->profile->last_name ?? '') . ' ' . ($user->profile->first_name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
        if ($user && !empty($user->username)) {
            return $user->username;
        }
        return "terapista #{$therapist->id}";
    }

    /**
     * Controlla la disponibilità di un terapista per una sostituzione
     *
     * @param int $therapistId
     * @param string $date Data in formato Y-m-d
     * @param string $time Orario in formato H:i
     * @param int $duration Durata in minuti
     * @return array
     */
    private function checkTherapistAvailabilityForSubstitution($therapistId, $date, $time, $duration)
    {
        try {
            // 1. Controlla se il terapista ha un'assenza approvata per quella data
            $absence = Absence::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['status' => Absence::STATUS_APPROVED])
                ->andWhere(['<=', 'start_date', $date])
                ->andWhere(['>=', 'end_date', $date])
                ->one();

            if ($absence) {
                $startDate = date('d/m', strtotime($absence->start_date));
                $endDate = date('d/m', strtotime($absence->end_date));
                $period = ($absence->start_date === $absence->end_date) ? $startDate : "{$startDate} - {$endDate}";

                return [
                    'isAvailable' => false,
                    'reason' => "Assenza: {$absence->getTypeLabel()} ({$period})"
                ];
            }

            // 2. Controlla conflitti di appuntamenti nello stesso slot temporale
            $appointmentDateTime = "{$date} {$time}:00";
            $endDateTime = date('Y-m-d H:i:s', strtotime($appointmentDateTime) + ($duration * 60));

            $conflictingAppointment = Appointment::find()
                ->where(['therapist_id' => $therapistId])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->andWhere(['!=', 'status', Appointment::STATUS_COMPLETED])
                ->andWhere([
                    'or',
                    // L'appuntamento inizia durante il nuovo slot
                    [
                        'and',
                        ['>=', 'appointment_datetime', $appointmentDateTime],
                        ['<', 'appointment_datetime', $endDateTime]
                    ],
                    // L'appuntamento finisce durante il nuovo slot
                    [
                        'and',
                        ['>', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $appointmentDateTime],
                        ['<=', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $endDateTime]
                    ],
                    // L'appuntamento contiene completamente il nuovo slot
                    [
                        'and',
                        ['<=', 'appointment_datetime', $appointmentDateTime],
                        ['>=', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $endDateTime]
                    ]
                ])
                ->with(['patient.user.profile'])
                ->one();

            if ($conflictingAppointment) {
                $conflictTime = date('H:i', strtotime($conflictingAppointment->appointment_datetime));

                // Gestisci il nome del paziente con controlli di sicurezza
                $patientName = 'Paziente';
                if (
                    $conflictingAppointment->patient &&
                    $conflictingAppointment->patient->user &&
                    $conflictingAppointment->patient->user->profile
                ) {
                    $patientName = $conflictingAppointment->patient->user->profile->getFullName();
                }

                return [
                    'isAvailable' => false,
                    'reason' => "Appuntamento: {$patientName} alle {$conflictTime}"
                ];
            }

            // Terapista disponibile
            return [
                'isAvailable' => true,
                'reason' => null
            ];
        } catch (Exception $e) {
            Yii::error("Errore controllo disponibilità terapista {$therapistId}: " . $e->getMessage(), __METHOD__);

            return [
                'isAvailable' => false,
                'reason' => 'Errore nel controllo disponibilità'
            ];
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
                    'canCreatePrivateAppointments' => true  // Sempre true
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero pazienti: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene i specialization_treatments disponibili per un terapista basati sulla sua specializzazione
     *
     * @param int $therapistId
     * @return array
     */
    public function actionGetTherapistSpecializationTreatments($therapistId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $therapist = Therapist::find()
                ->with(['specialization.specializationTreatments.treatmentType'])
                ->where(['id' => $therapistId])
                ->one();

            if (!$therapist) {
                return $this->errorResponse('Terapista non trovato');
            }

            if (!$therapist->specialization) {
                return $this->errorResponse('Specializzazione terapista non trovata');
            }

            $result = [];
            foreach ($therapist->specialization->specializationTreatments as $specializationTreatment) {
                $result[] = [
                    'id' => $specializationTreatment->id,
                    'specialization_id' => $specializationTreatment->specialization_id,
                    'treatment_type_id' => $specializationTreatment->treatment_type_id,
                    'treatment_type_name' => $specializationTreatment->treatmentType->name,
                    'specialization_name' => $therapist->specialization->name,
                    'full_name' => $specializationTreatment->getFullName()
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            Yii::error('Errore recupero specialization_treatments terapista: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    // ***** ABA  ********

    // In TherapeuticPlanManagerController.php

    /**
     * Crea un appuntamento in modalità ABA
     * Permette appointment_type diversi nello stesso slot
     */
    public function actionCreateAbaAppointment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $data = $this->getRequestData();
            $this->validateABAAppointmentFields($data);
            $this->validateTherapist($data);

            // Validazione data rispetto al piano terapeutico
            $this->validateStartDate($data['appointmentDateTime'], $data['planTherapyId']);

            // Verifica limite ore per tipologia trattamento TEST ABA
            $hoursLimitCheck = $this->checkPlanTherapyHoursLimit(
                Appointment::SOURCE_THERAPEUTIC_PLAN,
                $data['planTherapyId'],
                $data['appointmentDateTime'],
                $data['durationMinutes']
            );

            if ($hoursLimitCheck) {
                return [
                    'success' => false,
                    'error' => $hoursLimitCheck['message'],
                    'code' => $hoursLimitCheck['code']
                ];
            }

            $treatmentTypeId = $data['treatmentTypeId'];

            // Mappa treatmentTypeId → appointmentType
            switch ($treatmentTypeId) {
                case 25:
                    $appointmentType = 'supervisione';
                    break;
                case 24:
                    $appointmentType = 'parent_training';
                    break;
                default:
                    $appointmentType = 'terapia';
            }

            // Verifica che siamo in regime ABA
            $planTherapy = $this->findPlanTherapy($data['planTherapyId']);
            $therapeuticPlan = $planTherapy->therapeuticPlan;

            if (!$this->isABARegime($therapeuticPlan)) {
                throw new BadRequestHttpException('Questa funzionalità è disponibile solo per regime ABA');
            }

            // Crea appuntamento PRIMA di modificare plan_therapy_id
            $appointment = new Appointment();

            // Se è parent_training o supervisione, trova il plan_therapy_id corretto
            $actualPlanTherapyId = $data['planTherapyId'];

            if ($appointmentType === 'parent_training' || $appointmentType === 'supervisione') {
                $correctTreatmentTypeId = $appointmentType === 'parent_training' ? 24 : 25;

                $correctPlanTherapy = PlanTherapy::find()
                    ->where([
                        'therapeutic_plan_id' => $planTherapy->therapeutic_plan_id,
                        'treatment_type_id' => $correctTreatmentTypeId
                    ])
                    ->one();

                if ($correctPlanTherapy) {
                    $actualPlanTherapyId = $correctPlanTherapy->id;
                    Yii::info("Cambiato plan_therapy_id da {$data['planTherapyId']} a {$actualPlanTherapyId} per {$appointmentType}", __METHOD__);
                }
            }

            // Risolvi group_session_id (UUID esistente per add-to-group, nuovo per create-new-group)
            $groupSessionId = (isset($data['isGroup']) && $data['isGroup'])
                ? $this->getGroupSessionId($data)
                : null;

            // Verifica conflitti specifici ABA
            $conflict = $this->checkABAConflicts(
                $data['patientId'],
                $data['therapistId'],
                $data['appointmentDateTime'],
                $appointmentType,
                $planTherapy->treatment_type_id,
                null,
                $groupSessionId
            );

            if ($conflict) {
                return [
                    'success' => false,
                    'error' => 'Conflitto rilevato',
                    'conflict' => $this->formatABAConflictInfo($conflict)
                ];
            }

            // Assegna tutti i valori all'appuntamento
            $appointment->plan_therapy_id = $actualPlanTherapyId;
            $appointment->appointment_source = Appointment::SOURCE_THERAPEUTIC_PLAN;
            $appointment->therapist_id = $data['therapistId'];
            $appointment->appointment_datetime = $data['appointmentDateTime'];
            $appointment->duration_minutes = $data['durationMinutes'];
            $appointment->patient_id = $data['patientId'];
            $appointment->appointment_type = $appointmentType;
            $appointment->notes = $data['notes'] ?? null;
            $appointment->status = Appointment::STATUS_SCHEDULED;
            $appointment->created_by = $this->getCurrentUserId();
            $appointment->id_setting = $data['id_setting'] ?? 1;
            $appointment->group_session_id = $groupSessionId;

            if (!$appointment->save()) {
                throw new Exception('Errore nel salvataggio: ' . json_encode($appointment->errors));
            }

            return [
                'success' => true,
                'message' => 'Appuntamento ABA creato con successo',
                'data' => [
                    'appointmentId' => $appointment->id,
                    'groupSessionId' => $appointment->group_session_id
                ]
            ];
        } catch (Exception $e) {
            Yii::error('Errore creazione appuntamento ABA: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ottiene le ore assegnate per un piano terapeutico
     *
     * @return array
     */
    public function actionGetPlanTherapyUsedHours()
    {
        $therapyId = Yii::$app->request->get('therapyId');
        $startDate = Yii::$app->request->get('start_date');
        $patientId = Yii::$app->request->get('patient_id', null);

        // Validazione parametri
        if (!$therapyId || !$startDate) {
            return [
                'error' => true,
                'message' => 'Parametri mancanti: therapyId e start_date sono obbligatori'
            ];
        }

        // Determina se è privato (quando c'è patient_id)
        $isPrivate = !is_null($patientId);

        // Se privato, valida che il paziente esista
        if ($isPrivate) {
            $patient = Patient::findOne($patientId);
            if (!$patient) {
                return [
                    'error' => true,
                    'message' => 'Paziente non trovato'
                ];
            }
        }

        // Inizializza date
        $periodoInizio = new \DateTime($startDate);

        if ($isPrivate) {
            // PRIVATI: sempre conteggio settimanale, nessun limite
            // Aggiusta automaticamente al lunedì della settimana
            $periodoInizio->modify('monday this week')->setTime(0, 0, 0);
            $periodoFine = clone $periodoInizio;
            $periodoFine->modify('+6 days')->setTime(23, 59, 59);
            $tipoPeriodo = 'settimanale';

            // Verifica che il tipo di trattamento esista
            $treatmentType = TreatmentType::findOne($therapyId);
            if (!$treatmentType) {
                return [
                    'error' => true,
                    'message' => 'Tipo di trattamento non trovato'
                ];
            }

            // Query per appuntamenti privati
            $query = Appointment::find()
                ->where(['treatment_type_id' => $therapyId])
                ->andWhere(['patient_id' => $patientId])
                ->andWhere(['source' => Appointment::SOURCE_PRIVATE]);

            $treatmentName = $treatmentType->name;
            $oreLimite = null;  // Privati sono illimitati
        } else {
            // PIANO TERAPEUTICO: recupera info e determina periodo
            $planTherapy = PlanTherapy::find()
                ->where(['id' => $therapyId])
                ->with(['therapeuticPlan.regime', 'treatmentType'])
                ->one();

            if (!$planTherapy) {
                return [
                    'error' => true,
                    'message' => 'Piano terapia non trovato'
                ];
            }

            if (!$planTherapy->therapeuticPlan || !$planTherapy->therapeuticPlan->regime) {
                return [
                    'error' => true,
                    'message' => 'Piano terapeutico o regime non trovato'
                ];
            }

            $regime = $planTherapy->therapeuticPlan->regime;

            if ($regime->conteggio_ore === Regime::CONTEGGIO_ORE_WEEKLY) {
                // Aggiusta al lunedì della settimana
                $periodoInizio->modify('monday this week')->setTime(0, 0, 0);
                $periodoFine = clone $periodoInizio;
                $periodoFine->modify('+6 days')->setTime(23, 59, 59);
                $tipoPeriodo = 'settimanale';
            } else {  // monthly
                // Aggiusta al primo del mese
                $periodoInizio->modify('first day of this month')->setTime(0, 0, 0);
                $periodoFine = clone $periodoInizio;
                $periodoFine->modify('last day of this month')->setTime(23, 59, 59);
                $tipoPeriodo = 'mensile';
            }

            // Query per piano terapeutico
            $query = Appointment::find()
                ->where(['plan_therapy_id' => $therapyId]);

            $treatmentName = $planTherapy->treatmentType->name;
            $oreLimite = $planTherapy->weekly_hours;
        }

        // Completa la query comune
        $minutiAssegnati = $query
            ->andWhere([
                'between',
                'appointment_datetime',
                $periodoInizio->format('Y-m-d H:i:s'),
                $periodoFine->format('Y-m-d H:i:s')
            ])
            ->andWhere(['not in', 'status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_THERAPIST_ABSENT
            ]])
            ->sum('duration_minutes') ?: 0;

        // Prepara la risposta
        $oreAssegnate = $minutiAssegnati / 60;

        $response = [
            'success' => true,
            'data' => [
                'is_private' => $isPrivate,
                'treatment_type' => $treatmentName,
                'periodo_tipo' => $tipoPeriodo,
                'periodo_inizio' => $periodoInizio->format('Y-m-d'),
                'periodo_fine' => $periodoFine->format('Y-m-d'),
                'ore_assegnate' => round($oreAssegnate, 2),
                'minuti_assegnati' => $minutiAssegnati
            ]
        ];

        // Aggiungi info limite solo se non privato (piano terapeutico)
        if (!$isPrivate) {
            $oreRimanenti = $oreLimite - $oreAssegnate;
            $response['data']['ore_limite'] = $oreLimite;
            $response['data']['ore_rimanenti'] = round($oreRimanenti, 2);
            $response['data']['minuti_limite'] = $oreLimite * 60;
            $response['data']['minuti_rimanenti'] = ($oreLimite * 60) - $minutiAssegnati;
            $response['data']['plan_therapy_id'] = $therapyId;
        } else {
            // Info specifiche per privati
            $response['data']['patient_id'] = $patientId;
            $response['data']['treatment_type_id'] = $therapyId;
            $response['data']['ore_limite'] = 'illimitato';
        }

        return $response;
    }

    /**
     * Valida i campi per la creazione di un appuntamento ABA
     *
     * @param array $data
     * @throws BadRequestHttpException
     */
    private function validateABAAppointmentFields($data)
    {
        // Rimuovi 'appointmentType' dai campi obbligatori poiché lo mappiamo noi
        $requiredFields = ['planTherapyId', 'therapistId', 'patientId', 'appointmentDateTime', 'durationMinutes', 'treatmentTypeId'];

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
     * Verifica se il piano terapeutico è in regime ABA
     */
    private function isABARegime($therapeuticPlan)
    {
        return $therapeuticPlan->regime && stripos($therapeuticPlan->regime->nome, 'ABA') !== false;
    }

    /**
     * Verifica se il paziente ha un piano terapeutico ABA attualmente attivo.
     * "Attivo" = oggi compreso tra start_date ed end_date e status in
     * (active, pending, suspended). Regime ABA = nome regime contiene "ABA".
     *
     * @param int|null $patientId
     * @return bool
     */
    private function patientHasActiveABAPlan($patientId)
    {
        $patientId = (int) $patientId;
        if ($patientId <= 0) {
            return false;
        }
        $today = date('Y-m-d');
        $plan = TherapeuticPlan::find()
            ->alias('tp')
            ->innerJoin('{{%regime}} r', 'r.id = tp.regime_id')
            ->where(['tp.patient_id' => $patientId])
            ->andWhere(['tp.status' => ['active', 'pending', 'suspended']])
            ->andWhere(['<=', 'tp.start_date', $today])
            ->andWhere(['>=', 'tp.end_date', $today])
            ->andWhere(['like', 'r.nome', 'ABA'])
            ->one();
        return $plan !== null;
    }

    /**
     * Verifica conflitti specifici per modalità ABA
     */
    private function checkABAConflicts($patientId, $therapistId, $appointmentDateTime, $appointmentType, $treatmentTypeId, $excludeAppointmentId = null, $groupSessionId = null)
    {
        $appointmentDate = new DateTime($appointmentDateTime);
        $dateStart = $appointmentDate->format('Y-m-d 00:00:00');
        $dateEnd = $appointmentDate->format('Y-m-d 23:59:59');

        // 1. Verifica conflitti terapista nello stesso slot orario
        $therapistConflict = $this->checkTherapistConflict(
            $therapistId,
            $appointmentDateTime,
            60,  // assumiamo durata standard, puoi passarla come parametro
            $excludeAppointmentId,
            $groupSessionId
        );

        if ($therapistConflict) {
            return $therapistConflict;
        }

        // 2. Verifica stesso tipo appuntamento + stesso treatment_type nello stesso giorno
        // (terapie diverse della stessa specializzazione sono ammesse)
        $query = Appointment::find()
            ->alias('a')
            ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
            ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
            ->where([
                'a.appointment_type' => $appointmentType,
                'pt.treatment_type_id' => $treatmentTypeId,
                'tp.patient_id' => $patientId
            ])
            ->andWhere(['not in', 'a.status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_ABSENT_JUSTIFIED,
                Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                Appointment::STATUS_THERAPIST_ABSENT,
            ]])
            ->andWhere(['between', 'a.appointment_datetime', $dateStart, $dateEnd]);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'a.id', $excludeAppointmentId]);
        }

        // Esclude appuntamenti dello stesso gruppo (per add-patient-to-group ABA)
        if ($groupSessionId !== null) {
            $query->andWhere([
                'or',
                ['a.group_session_id' => null],
                ['!=', 'a.group_session_id', $groupSessionId]
            ]);
        }

        return $query->one();
    }

    /**
     * Formatta le informazioni del conflitto ABA
     *
     * @param Appointment $conflict
     * @return array
     */
    private function formatABAConflictInfo($conflict)
    {
        $appointmentDate = new DateTime($conflict->appointment_datetime);

        // Gestisci i dati del paziente e del trattamento
        if ($conflict->planTherapy && $conflict->planTherapy->therapeuticPlan) {
            $patient = $conflict->planTherapy->therapeuticPlan->patient;
            $treatmentType = $conflict->planTherapy->treatmentType;
        } else {
            $patient = $conflict->patient;
            $treatmentType = $conflict->treatmentType;
        }

        // Ottieni informazioni sul terapista
        $therapist = $conflict->therapist;
        $therapistInfo = $therapist ? $therapist->user->profile->getFullName() : 'Terapista non specificato';

        return [
            'type' => 'aba_appointment_type_conflict',
            'existingAppointmentId' => $conflict->id,
            'appointmentType' => $conflict->appointment_type,
            'treatmentType' => $treatmentType->name,
            'patientName' => $patient->getFullName(),
            'existingAppointmentDate' => $appointmentDate->format('Y-m-d'),
            'existingAppointmentTime' => $appointmentDate->format('H:i'),
            'existingTherapistName' => $therapistInfo,
            'message' => "Esiste già un appuntamento di tipo '{$conflict->appointment_type}' per {$patient->getFullName()} in data {$appointmentDate->format('d/m/Y')} alle ore {$appointmentDate->format('H:i')} con {$therapistInfo}"
        ];
    }

    /**
     * Controlla se l'appuntamento supererebbe il limite di ore previsto per il tipo di terapia nel periodo di riferimento
     *
     * @param int $planTherapyId
     * @param string $appointmentDateTime
     * @param int $durationMinutes
     * @param int $excludeAppointmentId ID dell'appuntamento da escludere dal calcolo (per modifiche)
     * @return array|null Array con errore se supera il limite, null se ok
     */
    private function checkPlanTherapyHoursLimit($source, $planTherapyId, $appointmentDateTime, $durationMinutes, $excludeAppointmentId = null)
    {
        // Se l'appuntamento è privato, non controlliamo il limite di ore
        if ($source === Appointment::SOURCE_PRIVATE)
            return null;

        // 1. Recupero informazioni base
        $planTherapy = PlanTherapy::find()
            ->where(['id' => $planTherapyId])
            ->with(['therapeuticPlan.regime', 'treatmentType'])
            ->one();

        if (!$planTherapy) {
            Yii::warning("PlanTherapy non trovato: {$planTherapyId}", __METHOD__);
            return [
                'error' => true,
                'message' => 'Piano terapia non trovato',
                'code' => 'PLAN_THERAPY_NOT_FOUND'
            ];
        }

        if (!$planTherapy->therapeuticPlan || !$planTherapy->therapeuticPlan->regime) {
            Yii::warning("TherapeuticPlan o Regime non trovato per PlanTherapy: {$planTherapyId}", __METHOD__);
            return [
                'error' => true,
                'message' => 'Piano terapeutico o regime non trovato',
                'code' => 'PLAN_THERAPY_NOT_FOUND_2'
            ];
        }

        // 2. Determina il periodo di riferimento
        $appointmentDate = new \DateTime($appointmentDateTime);
        $regime = $planTherapy->therapeuticPlan->regime;

        if ($regime->conteggio_ore === Regime::CONTEGGIO_ORE_WEEKLY) {
            $periodoInizio = clone $appointmentDate;
            $periodoInizio->modify('monday this week')->setTime(0, 0, 0);
            $periodoFine = clone $periodoInizio;
            $periodoFine->modify('sunday this week')->setTime(23, 59, 59);
            $tipoPeriodo = 'settimanale';
        } else {  // monthly
            $periodoInizio = clone $appointmentDate;
            $periodoInizio->modify('first day of this month')->setTime(0, 0, 0);
            $periodoFine = clone $periodoInizio;
            $periodoFine->modify('last day of this month')->setTime(23, 59, 59);
            $tipoPeriodo = 'mensile';
        }

        // 3. Calcola minuti già assegnati nel periodo per lo stesso tipo di terapia e paziente
        $query = Appointment::find()
            ->where(['plan_therapy_id' => $planTherapyId])
            // ->andWhere(['patient_id' => $planTherapy->therapeuticPlan->patient_id])
            ->andWhere(['between', 'appointment_datetime', $periodoInizio->format('Y-m-d H:i:s'), $periodoFine->format('Y-m-d H:i:s')])
            ->andWhere(['not in', 'status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_THERAPIST_ABSENT]]);

        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'id', $excludeAppointmentId]);
        }
        Yii::error('Query: ' . $query->createCommand()->rawSql, __METHOD__);
        $minutiAssegnati = $query->sum('duration_minutes') ?: 0;
        Yii::error("Minuti assegnati: {$minutiAssegnati}", __METHOD__);
        // 4. Converti weekly_hours in minuti per il confronto
        $minutiMassimi = $planTherapy->weekly_hours * 60;

        // 5. Verifica se con il nuovo appuntamento si supera il limite
        if (($minutiAssegnati + $durationMinutes) > $minutiMassimi) {
            $oreAssegnate = number_format($minutiAssegnati / 60, 1);
            $oreMassime = number_format($planTherapy->weekly_hours, 1);
            $nuoveOre = number_format($durationMinutes / 60, 1);

            return [
                'error' => true,
                'message' => "Superato il limite di ore {$tipoPeriodo} - {$oreMassime}h per il trattamento {$planTherapy->treatmentType->name}. "
                    . "Ore già assegnate: {$oreAssegnate}h, Ore da aggiungere: {$nuoveOre}h",
                'code' => 'HOURS_LIMIT_EXCEEDED'
            ];
        }

        return null;  // Nessun conflitto
    }

    public function actionCheckPatientInGroup($groupSessionId, $patientId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $exists = Appointment::find()
                ->alias('a')
                ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
                ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
                ->where(['a.group_session_id' => $groupSessionId])
                ->andWhere(
                    ['a.patient_id' => $patientId]
                )
                ->andWhere(['!=', 'a.status', Appointment::STATUS_CANCELLED])
                ->exists();

            return [
                'success' => true,
                'isInGroup' => $exists
            ];
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Restituisce candidati paziente per aggiunta a un gruppo, con flag eligibility
     * e lista motivazioni di blocco. Search su nome/cognome/CF/data_nascita, paginato.
     */
    public function actionGetGroupCandidatePatients()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $appointmentId = (int) Yii::$app->request->get('appointmentId');
            $search = trim((string) Yii::$app->request->get('search', ''));
            $page = max(1, (int) Yii::$app->request->get('page', 1));
            $pageSize = min(2000, max(1, (int) Yii::$app->request->get('pageSize', 20)));

            if (!$appointmentId) {
                return $this->errorResponse('appointmentId obbligatorio');
            }

            $appointment = Appointment::find()
                ->where(['id' => $appointmentId])
                ->with(['planTherapy.therapeuticPlan.regime', 'planTherapy.treatmentType'])
                ->one();

            if (!$appointment) {
                return $this->errorResponse('Appuntamento non trovato');
            }

            if (empty($appointment->group_session_id)) {
                return $this->errorResponse('Appuntamento non e\' di gruppo');
            }

            $planTherapy = $appointment->planTherapy;
            if (!$planTherapy || !$planTherapy->therapeuticPlan) {
                return $this->errorResponse('Piano terapeutico non trovato per l\'appuntamento');
            }

            $treatmentTypeId = $planTherapy->treatment_type_id;
            $appointmentType = $appointment->appointment_type ?: 'terapia';
            $datetime = $appointment->appointment_datetime;
            $duration = $appointment->duration_minutes;
            $therapistId = $appointment->therapist_id;
            $groupSessionId = $appointment->group_session_id;
            $isGroupABA = $this->isABARegime($planTherapy->therapeuticPlan);

            // Pazienti gia' nel gruppo (esclusi dalla lista candidati)
            $existingPatientIds = Appointment::find()
                ->select('patient_id')
                ->where(['group_session_id' => $groupSessionId])
                ->andWhere(['not in', 'status', [Appointment::STATUS_CANCELLED]])
                ->column();

            // Query base candidati con search
            $query = Patient::find()
                ->andWhere(['not in', 'id', $existingPatientIds ?: [0]])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC]);

            if ($search !== '') {
                $query->andWhere([
                    'or',
                    ['like', 'first_name', $search],
                    ['like', 'last_name', $search],
                    ['like', 'fiscal_code', $search],
                    ['like', 'CONCAT(first_name, " ", last_name)', $search],
                    ['like', 'CONCAT(last_name, " ", first_name)', $search],
                    ['like', 'birth_date', $search],
                ]);
            }

            $total = (int) $query->count();
            $offset = ($page - 1) * $pageSize;
            $patients = $query->offset($offset)->limit($pageSize)->all();

            $items = [];
            foreach ($patients as $patient) {
                $reasons = $this->evaluateGroupCandidateReasons(
                    $patient,
                    $treatmentTypeId,
                    $appointmentType,
                    $datetime,
                    $duration,
                    $therapistId,
                    $isGroupABA,
                    $groupSessionId
                );

                $items[] = [
                    'id' => $patient->id,
                    'name' => $patient->getFullName(),
                    'fiscalCode' => $patient->fiscal_code,
                    'birthDate' => $patient->birth_date,
                    'eligible' => empty($reasons),
                    'reasons' => $reasons,
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'items' => $items,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'groupContext' => [
                        'appointmentType' => $appointmentType,
                        'treatmentTypeId' => $treatmentTypeId,
                        'treatmentTypeName' => $planTherapy->treatmentType ? $planTherapy->treatmentType->name : null,
                        'datetime' => $datetime,
                        'durationMinutes' => $duration,
                        'isABA' => $isGroupABA,
                    ],
                ],
            ];
        } catch (Exception $e) {
            Yii::error('Errore actionGetGroupCandidatePatients: ' . $e->getMessage(), __METHOD__);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Valuta tutti i motivi per cui un paziente NON puo' essere aggiunto al gruppo.
     * Ritorna array di stringhe (vuoto = eleggibile).
     */
    private function evaluateGroupCandidateReasons(
        $patient,
        $treatmentTypeId,
        $appointmentType,
        $datetime,
        $duration,
        $therapistId,
        $isGroupABA,
        $groupSessionId
    ) {
        $reasons = [];
        $appointmentDate = (new DateTime($datetime))->format('Y-m-d');

        // 1. Piano terapeutico attivo nella data appuntamento
        $therapeuticPlan = TherapeuticPlan::find()
            ->where(['patient_id' => $patient->id])
            ->andWhere(['<=', 'start_date', $appointmentDate])
            ->andWhere(['>=', 'end_date', $appointmentDate])
            ->with('regime')
            ->one();

        if (!$therapeuticPlan) {
            $reasons[] = 'Nessun piano terapeutico attivo';
            return $reasons;
        }

        // 2. Plan therapy specifica per il treatment_type del gruppo
        $planTherapy = PlanTherapy::find()
            ->where(['therapeutic_plan_id' => $therapeuticPlan->id])
            ->andWhere(['treatment_type_id' => $treatmentTypeId])
            ->one();

        if (!$planTherapy) {
            $reasons[] = 'Nessuna terapia di questo tipo nel piano';
            return $reasons;
        }

        // 3. Regime ABA omogeneo
        $isPatientPlanABA = $this->isABARegime($therapeuticPlan);
        if ($isGroupABA !== $isPatientPlanABA) {
            $reasons[] = $isGroupABA
                ? 'Paziente non in regime ABA (gruppo ABA)'
                : 'Paziente in regime ABA (gruppo non ABA)';
        }

        // 4. Slot temporale paziente non occupato
        $slotConflict = $this->checkPatientTimeSlotConflict(
            $patient->id,
            $datetime,
            $duration
        );
        if ($slotConflict) {
            $reasons[] = 'Slot orario gia\' occupato da altro appuntamento';
        }

        // 5. ABA: conflitto stesso appointment_type+treatment_type stesso giorno
        if ($isPatientPlanABA && $isGroupABA) {
            $abaConflict = $this->checkABAConflicts(
                $patient->id,
                $therapistId,
                $datetime,
                $appointmentType,
                $treatmentTypeId,
                null,
                $groupSessionId
            );
            if ($abaConflict) {
                $reasons[] = 'Stesso tipo appuntamento gia\' presente nello stesso giorno';
            }
        } else {
            // Non-ABA: stessa tipologia trattamento stesso giorno
            $treatmentConflict = $this->checkSameTreatmentTypeConflictByPlanTherapy(
                $planTherapy->id,
                $datetime
            );
            if ($treatmentConflict) {
                $reasons[] = 'Tipologia trattamento gia\' presente nello stesso giorno';
            }
        }

        // 6. Limite ore plan_therapy
        $hoursLimit = $this->checkPlanTherapyHoursLimit(
            Appointment::SOURCE_THERAPEUTIC_PLAN,
            $planTherapy->id,
            $datetime,
            $duration
        );
        if ($hoursLimit) {
            $reasons[] = $hoursLimit['message'] ?? 'Limite ore terapia superato';
        }

        return $reasons;
    }

    private function validateStartDate($appointmentDateTime, $planTherapy)
    {
        $plan = TherapeuticPlan::find()
            ->leftJoin('plan_therapies', 'plan_therapies.therapeutic_plan_id = therapeutic_plans.id')
            ->where(['plan_therapies.id' => $planTherapy])
            ->one();
        if (!$plan) {
            return [
                'error' => true,
                'message' => 'Piano terapeutico non trovato.',
                'code' => 'PLAN_THERAPY_NOT_FOUND'
            ];
        }
        // Confronta solo la parte data: end_date è inclusiva, ma il datetime
        // dell'appuntamento porta un orario che lo farebbe risultare "dopo"
        // mezzanotte dell'ultimo giorno valido.
        $appointmentDay = (new DateTime($appointmentDateTime))->format('Y-m-d');
        if ($appointmentDay < $plan->start_date || $appointmentDay > $plan->end_date) {
            throw new BadRequestHttpException("La data dell'appuntamento non è valida per il piano terapeutico.");
        }

        return null;
    }
}
