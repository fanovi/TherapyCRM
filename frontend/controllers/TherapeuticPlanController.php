<?php

namespace frontend\controllers;

use common\models\Notification;
use Yii;
use common\models\TherapeuticPlan;
use common\models\Patient;
use common\models\Regime;
use common\models\District;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use common\helpers\NotificationHelper;
use common\models\Setting;
use frontend\models\TherapeuticPlanSearch;

/**
 * TherapeuticPlanController implements the CRUD actions for TherapeuticPlan model.
 */
class TherapeuticPlanController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                        'delete-confirm' => ['GET'], // Nuova azione per la conferma
                    ],
                ],
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'actions' => ['index'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('view_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['view'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('view_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['create'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('create_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['update'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('update_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['delete'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('delete_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['delete-confirm'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('delete_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['search-patients'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('view_therapeutic_plan') || Yii::$app->user->can('create_therapeutic_plan');
                            }
                        ],
                        [
                            'actions' => ['get-settings-list'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                // Permetti sia in create che in update
                                return Yii::$app->user->can('create_therapeutic_plan') || Yii::$app->user->can('update_therapeutic_plan');
                            }
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all TherapeuticPlan models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new TherapeuticPlanSearch();

        // Default: mostra solo piani attivi quando nessun filtro e' stato
        // applicato dall'utente. Settiamo sia in $params (per search()) sia
        // direttamente in $searchModel (per popolare il dropdown filtro).
        $params = $this->request->queryParams;
        if (!isset($params['TherapeuticPlanSearch'])) {
            $params['TherapeuticPlanSearch'] = ['status' => 'active'];
            $searchModel->status = 'active';
        }

        $dataProvider = $searchModel->search($params);

        // Get lists for filter dropdowns
        $regimes = ArrayHelper::map(Regime::find()->orderBy(['nome' => SORT_ASC])->all(), 'id', 'nome');
        $districts = District::getDropdownData();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'regimes' => $regimes,
            'districts' => $districts,
        ]);
    }

    /**
     * Get treatment types list for dropdown
     *
     * @return array
     */
    protected function getTreatmentTypesList()
    {
        return \yii\helpers\ArrayHelper::map(
            \common\models\TreatmentType::find()
                ->orderBy(['name' => SORT_ASC])
                ->all(),
            'id',
            'name'
        );
    }

    /**
     * Get settings list for dropdown
     *
     * @return array
     */
    protected function getSettingsList()
    {
        return \yii\helpers\ArrayHelper::map(
            \common\models\Setting::find()
                ->orderBy(['nome' => SORT_ASC])
                ->all(),
            'id',
            'nome'
        );
    }

    /**
     * Displays a single TherapeuticPlan model.
     *
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new TherapeuticPlan model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new TherapeuticPlan();
        $model->created_by = Yii::$app->user->id;
        $therapyModel = new \common\models\PlanTherapy();

        if ($this->request->isPost) {
            Yii::info('Inizio processo di creazione piano terapeutico', __METHOD__);

            // Carica i dati del form
            if ($model->load($this->request->post())) {
                Yii::info('Dati del modello caricati: ' . json_encode($model->attributes), __METHOD__);
            } else {
                Yii::error('Errore nel caricamento dei dati del modello', __METHOD__);
            }

            // Valida le terapie
            $therapies = Yii::$app->request->post('PlanTherapy', []);
            Yii::info('Terapie ricevute: ' . json_encode($therapies), __METHOD__);

            // Passa le terapie al modello per la validazione ABA
            $model->setTherapiesForValidation($therapies);

            $isValid = true;
            $error = null;

            try {

                // Verifica sovrapposizione piani PRIMA di tutto il resto
                if ($model->patient_id && $model->start_date && $model->duration_days) {
                    $overlapCheck = $this->checkPlanOverlap(
                        $model->patient_id,
                        $model->start_date,
                        $model->duration_days
                    );

                    if (!$overlapCheck['isValid']) {
                        $conflictingPlan = $overlapCheck['conflictingPlan'];
                        $error = sprintf(
                            'Esiste già un piano terapeutico per questo paziente dal %s al %s (Piano #%d - %s) che si sovrappone con le date selezionate.',
                            Yii::$app->formatter->asDate($conflictingPlan['start_date']),
                            Yii::$app->formatter->asDate($conflictingPlan['end_date']),
                            $conflictingPlan['id'],
                            $conflictingPlan['regime']
                        );
                        throw new \Exception($error);
                    }
                }

                if (empty($therapies)) {
                    Yii::error('Nessuna terapia ricevuta', __METHOD__);
                    throw new \Exception('È necessario inserire almeno una terapia.');
                }

                // Verifica duplicati
                $treatmentTypes = [];
                foreach ($therapies as $therapy) {
                    if (!empty($therapy['treatment_type_id'])) {
                        if (in_array($therapy['treatment_type_id'], $treatmentTypes)) {
                            Yii::error('Trovato tipo di trattamento duplicato: ' . $therapy['treatment_type_id'], __METHOD__);
                            throw new \Exception('Non è possibile assegnare lo stesso tipo di trattamento più volte.');
                        }
                        $treatmentTypes[] = $therapy['treatment_type_id'];
                    }
                }

                // Se il modello principale è valido (include validazione ABA), procedi con il salvataggio
                if ($model->validate()) {
                    Yii::info('Validazione modello principale superata', __METHOD__);

                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        if (!$model->save(false)) { // false per skip validation dato che l'abbiamo già fatta
                            Yii::error('Errore nel salvataggio del modello principale: ' . json_encode($model->errors), __METHOD__);
                            throw new \Exception('Errore nel salvataggio del piano terapeutico: ' . json_encode($model->errors));
                        }

                        Yii::info('Modello principale salvato con ID: ' . $model->id, __METHOD__);

                        // Salva le terapie
                        foreach ($therapies as $therapy) {
                            if (!empty($therapy['treatment_type_id']) && !empty($therapy['weekly_hours'])) {
                                $newTherapy = new \common\models\PlanTherapy();
                                $newTherapy->therapeutic_plan_id = $model->id;
                                $newTherapy->treatment_type_id = $therapy['treatment_type_id'];
                                $newTherapy->weekly_hours = $therapy['weekly_hours'];
                                $newTherapy->is_group = !empty($therapy['is_group']);
                                $newTherapy->setting_id = $therapy['setting_id'];
                                $newTherapy->notes = $therapy['notes'] ?? null;

                                if (!$newTherapy->save()) {
                                    Yii::error('Errore nel salvataggio della terapia: ' . json_encode($newTherapy->errors), __METHOD__);
                                    throw new \Exception('Errore nel salvataggio della terapia: ' . json_encode($newTherapy->errors));
                                }

                                Yii::info('Terapia salvata con successo: ' . json_encode($newTherapy->attributes), __METHOD__);
                            }
                        }

                        // Crea notifica per i manager
                        try {
                            $planLink = Yii::$app->urlManager->createAbsoluteUrl(['/therapeutic-plan/view', 'id' => $model->id]);
                            $patient = $model->patient;
                            $patientName = $patient->first_name . ' ' . $patient->last_name;

                            $htmlMessage = "<b>Nuovo piano terapeutico creato</b><br>";
                            $htmlMessage .= "Paziente: {$patientName}<br>";
                            $htmlMessage .= "Piano: <a href='{$planLink}'>#{$model->id}</a><br>";
                            $htmlMessage .= "Regime: {$model->regime->nome}<br>";
                            $htmlMessage .= "Data inizio: " . Yii::$app->formatter->asDate($model->start_date) . "<br>";
                            $htmlMessage .= "Durata: {$model->duration_days} giorni<br>";

                            // Aggiungi dettagli terapie alla notifica
                            $htmlMessage .= "<br><b>Terapie assegnate:</b><br>";

                            // Indica se è ABA e quindi le ore sono mensili
                            $hoursLabel = $model->isABARegime() ? 'ore/mese' : 'ore/settimana';

                            foreach ($therapies as $therapy) {
                                if (!empty($therapy['treatment_type_id'])) {
                                    $treatmentType = \common\models\TreatmentType::findOne($therapy['treatment_type_id']);
                                    $setting = \common\models\Setting::findOne($therapy['setting_id']);
                                    $htmlMessage .= "- {$treatmentType->name}: {$therapy['weekly_hours']} {$hoursLabel}";
                                    $htmlMessage .= " ({$setting->nome})";
                                    if (!empty($therapy['is_group'])) {
                                        $htmlMessage .= " [Gruppo]";
                                    }
                                    $htmlMessage .= "<br>";
                                }
                            }

                            NotificationHelper::sendToManagers(
                                'Nuovo piano terapeutico',
                                $htmlMessage,
                                Notification::TYPE_INFO,
                                [],
                                true // skipPush
                            );

                            Yii::info("Notifica piano terapeutico #{$model->id} inviata ai manager", __METHOD__);
                        } catch (\Exception $e) {
                            Yii::error("Errore invio notifica piano terapeutico: " . $e->getMessage(), __METHOD__);
                            // Non blocchiamo il salvataggio se la notifica fallisce
                        }

                        $transaction->commit();
                        Yii::info('Transazione completata con successo', __METHOD__);
                        Yii::$app->session->setFlash('success', 'Piano terapeutico creato con successo.');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        Yii::error('Errore durante la transazione: ' . $e->getMessage(), __METHOD__);
                        $error = $e->getMessage();
                        $isValid = false;
                    }
                } else {
                    Yii::error('Errori di validazione del modello principale: ' . json_encode($model->errors), __METHOD__);
                    $isValid = false;
                }
            } catch (\Exception $e) {
                Yii::error('Errore generale: ' . $e->getMessage(), __METHOD__);
                $error = $e->getMessage();
                $isValid = false;
            }

            if (!$isValid) {
                if ($error) {
                    Yii::$app->session->setFlash('error', $error);
                }

                Yii::info('Rendering del form con errori. Model errors: ' . json_encode($model->errors), __METHOD__);
                Yii::info('Terapie da ripopolare: ' . json_encode($therapies), __METHOD__);

                // Ricarica i dati per il form in caso di errore
                return $this->render('create', [
                    'model' => $model,
                    'therapyModel' => $therapyModel,
                    'patients' => $this->getPatientsList(),
                    'regimes' => $this->getRegimesList(),
                    'districts' => $this->getDistrictsList(),
                    'treatmentTypes' => $this->getTreatmentTypesList(),
                    'settings' => $this->getSettingsList(),
                    'postedTherapies' => $therapies // Passa i dati delle terapie al form
                ]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'therapyModel' => $therapyModel,
            'patients' => $this->getPatientsList(),
            'regimes' => $this->getRegimesList(),
            'districts' => $this->getDistrictsList(),
            'treatmentTypes' => $this->getTreatmentTypesList(),
            'settings' => $this->getSettingsList(),
            'postedTherapies' => [] // Inizializza l'array vuoto per il primo caricamento
        ]);
    }

    /**
     * Updates an existing TherapeuticPlan model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Piano interrotto: read-only, niente modifiche permesse.
        if ($model->status === 'terminated') {
            Yii::$app->session->setFlash('warning', 'Il piano terapeutico e\' stato interrotto e non puo\' essere modificato.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $therapyModel = new \common\models\PlanTherapy();

        // Carica le terapie esistenti per l'update
        $existingTherapies = [];
        if (!$this->request->isPost) {
            $planTherapies = \common\models\PlanTherapy::find()
                ->where(['therapeutic_plan_id' => $model->id])
                ->all();

            foreach ($planTherapies as $therapy) {
                $existingTherapies[] = [
                    'treatment_type_id' => $therapy->treatment_type_id,
                    'weekly_hours' => $therapy->weekly_hours,
                    'is_group' => $therapy->is_group,
                    'setting_id' => $therapy->setting_id,
                    'notes' => $therapy->notes,
                ];
            }
        }

        if ($this->request->isPost) {
            $isValid = true;
            $error = null;
            $transaction = null;

            // Stato precedente per rilevare transizione -> terminated
            $oldStatus = $model->status;

            try {
                // Carica i dati del form
                if (!$model->load($this->request->post())) {
                    throw new \Exception('Errore nel caricamento dei dati del form.');
                }

                // Transizione a "Interrotto": richiede conferma esplicita dal client
                // (swal). Senza flag confirmTerminate=1 il backend rifiuta.
                $isTerminatingNow = ($oldStatus !== 'terminated' && $model->status === 'terminated');
                if ($isTerminatingNow) {
                    $confirmed = (string) Yii::$app->request->post('confirmTerminate') === '1';
                    if (!$confirmed) {
                        throw new \Exception('Per interrompere il piano è necessario confermare l\'operazione. Riprova.');
                    }
                }

                // Verifica sovrapposizione piani PRIMA di tutto il resto
                if ($model->patient_id && $model->start_date && $model->duration_days) {
                    $overlapCheck = $this->checkPlanOverlap(
                        $model->patient_id,
                        $model->start_date,
                        $model->duration_days,
                        $model->id
                    );

                    if (!$overlapCheck['isValid']) {
                        $conflictingPlan = $overlapCheck['conflictingPlan'];
                        $error = sprintf(
                            'Esiste già un piano terapeutico per questo paziente dal %s al %s (Piano #%d - %s) che si sovrappone con le date selezionate.',
                            Yii::$app->formatter->asDate($conflictingPlan['start_date']),
                            Yii::$app->formatter->asDate($conflictingPlan['end_date']),
                            $conflictingPlan['id'],
                            $conflictingPlan['regime']
                        );
                        throw new \Exception($error);
                    }
                }

                // Valida le terapie
                $therapies = Yii::$app->request->post('PlanTherapy', []);
                if (empty($therapies)) {
                    throw new \Exception('È necessario inserire almeno una terapia.');
                }

                // Passa le terapie al modello per la validazione ABA
                $model->setTherapiesForValidation($therapies);

                // Verifica duplicati
                $treatmentTypes = [];
                foreach ($therapies as $therapy) {
                    if (!empty($therapy['treatment_type_id'])) {
                        if (in_array($therapy['treatment_type_id'], $treatmentTypes)) {
                            throw new \Exception('Non è possibile assegnare lo stesso tipo di trattamento più volte.');
                        }
                        $treatmentTypes[] = $therapy['treatment_type_id'];
                    }
                }

                // Valida il modello (include validazione ABA)
                if (!$model->validate()) {
                    $errors = [];
                    foreach ($model->errors as $field => $fieldErrors) {
                        $errors[] = implode(', ', $fieldErrors);
                    }
                    throw new \Exception('Errori di validazione: ' . implode('; ', $errors));
                }

                $transaction = Yii::$app->db->beginTransaction();

                // Salva il modello principale
                if (!$model->save(false)) { // false per skip validation dato che l'abbiamo già fatta
                    throw new \Exception('Errore nel salvataggio del piano terapeutico: ' . json_encode($model->errors));
                }

                // Verifica se ci sono appuntamenti per le terapie da rimuovere
                $currentTherapies = \common\models\PlanTherapy::find()
                    ->where(['therapeutic_plan_id' => $model->id])
                    ->all();

                $currentTherapyIds = array_map(function ($therapy) {
                    return $therapy->treatment_type_id;
                }, $currentTherapies);

                $newTherapyIds = array_map(function ($therapy) {
                    return $therapy['treatment_type_id'];
                }, array_filter($therapies, function ($therapy) {
                    return !empty($therapy['treatment_type_id']);
                }));

                $removedTherapyIds = array_diff($currentTherapyIds, $newTherapyIds);

                foreach ($removedTherapyIds as $treatmentTypeId) {
                    $therapy = \common\models\PlanTherapy::findOne([
                        'therapeutic_plan_id' => $model->id,
                        'treatment_type_id' => $treatmentTypeId
                    ]);

                    if ($therapy) {
                        $hasAppointments = \common\models\Appointment::find()
                            ->where(['plan_therapy_id' => $therapy->id])
                            ->exists();

                        if ($hasAppointments) {
                            throw new \Exception('Non è possibile rimuovere una terapia per cui esistono già degli appuntamenti.');
                        }
                    }
                }

                // Rimuovi le terapie esistenti che non hanno appuntamenti
                foreach ($currentTherapies as $therapy) {
                    if (!in_array($therapy->treatment_type_id, $newTherapyIds)) {
                        $therapy->delete();
                    }
                }

                // Aggiorna o crea nuove terapie
                foreach ($therapies as $therapy) {
                    if (!empty($therapy['treatment_type_id']) && !empty($therapy['weekly_hours'])) {
                        $existingTherapy = \common\models\PlanTherapy::findOne([
                            'therapeutic_plan_id' => $model->id,
                            'treatment_type_id' => $therapy['treatment_type_id']
                        ]);

                        if ($existingTherapy) {
                            $existingTherapy->weekly_hours = $therapy['weekly_hours'];
                            $existingTherapy->is_group = !empty($therapy['is_group']);
                            $existingTherapy->setting_id = $therapy['setting_id'];
                            $existingTherapy->notes = $therapy['notes'] ?? null;

                            if (!$existingTherapy->save()) {
                                throw new \Exception('Errore nell\'aggiornamento della terapia: ' . json_encode($existingTherapy->errors));
                            }
                        } else {
                            $newTherapy = new \common\models\PlanTherapy();
                            $newTherapy->therapeutic_plan_id = $model->id;
                            $newTherapy->treatment_type_id = $therapy['treatment_type_id'];
                            $newTherapy->weekly_hours = $therapy['weekly_hours'];
                            $newTherapy->is_group = !empty($therapy['is_group']);
                            $newTherapy->setting_id = $therapy['setting_id'];
                            $newTherapy->notes = $therapy['notes'] ?? null;

                            if (!$newTherapy->save()) {
                                throw new \Exception('Errore nel salvataggio della terapia: ' . json_encode($newTherapy->errors));
                            }
                        }
                    }
                }

                // Se il piano e' stato appena interrotto, cancella tutti gli
                // appuntamenti SCHEDULED successivi alla data di interruzione
                // (termination_date) appartenenti a qualunque plan_therapy di
                // questo piano. Gli appuntamenti gia' completed/absent/cancelled/
                // therapist_absent restano invariati; quelli precedenti o
                // coincidenti con termination_date restano scheduled (l'utente
                // potra' eventualmente segnarli come completed/absent in seguito).
                $cancelledCount = 0;
                if ($isTerminatingNow) {
                    // Cutoff: termination_date se valorizzata, altrimenti oggi.
                    $cutoffDate = !empty($model->termination_date)
                        ? $model->termination_date
                        : date('Y-m-d');
                    $cutoffDateTime = $cutoffDate . ' 23:59:59';

                    $planTherapyIds = \common\models\PlanTherapy::find()
                        ->select('id')
                        ->where(['therapeutic_plan_id' => $model->id])
                        ->column();

                    if (!empty($planTherapyIds)) {
                        $cancelledCount = \common\models\Appointment::updateAll(
                            ['status' => \common\models\Appointment::STATUS_CANCELLED],
                            [
                                'and',
                                ['plan_therapy_id' => $planTherapyIds],
                                ['status' => \common\models\Appointment::STATUS_SCHEDULED],
                                ['>', 'appointment_datetime', $cutoffDateTime],
                            ]
                        );

                        // Chiudi i pattern ricorrenti che si estendono oltre
                        // la data di interruzione: niente nuove generazioni dopo.
                        \common\models\AppointmentPattern::updateAll(
                            ['valid_to' => $cutoffDate],
                            [
                                'and',
                                ['plan_therapy_id' => $planTherapyIds],
                                ['>', 'valid_to', $cutoffDate],
                            ]
                        );
                    }
                }

                $transaction->commit();

                $msg = 'Piano terapeutico aggiornato con successo.';
                if ($isTerminatingNow) {
                    $msg .= ' ' . ($cancelledCount > 0
                        ? "Cancellati {$cancelledCount} appuntamenti futuri."
                        : 'Nessun appuntamento futuro da cancellare.');
                }
                Yii::$app->session->setFlash('success', $msg);
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                if ($transaction !== null && $transaction->isActive) {
                    $transaction->rollBack();
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
                $isValid = false;
            }

            if (!$isValid) {
                return $this->render('update', [
                    'model' => $model,
                    'therapyModel' => $therapyModel,
                    'patients' => $this->getPatientsList(),
                    'regimes' => $this->getRegimesList(),
                    'districts' => $this->getDistrictsList(),
                    'treatmentTypes' => $this->getTreatmentTypesList(),
                    'settings' => $this->getSettingsList(),
                    'postedTherapies' => $therapies ?? []
                ]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'therapyModel' => $therapyModel,
            'patients' => $this->getPatientsList(),
            'regimes' => $this->getRegimesList(),
            'districts' => $this->getDistrictsList(),
            'treatmentTypes' => $this->getTreatmentTypesList(),
            'settings' => $this->getSettingsList(),
            'postedTherapies' => $existingTherapies // Passa le terapie esistenti alla vista
        ]);
    }

    public function actionDeleteConfirm($id)
    {
        $model = $this->findModel($id);

        // Controlla se ci sono appuntamenti collegati tramite le plan_therapies
        $planTherapyIds = \common\models\PlanTherapy::find()
            ->where(['therapeutic_plan_id' => $id])
            ->select('id')
            ->column();

        $appointmentCount = 0;
        if (!empty($planTherapyIds)) {
            $appointmentCount = \common\models\Appointment::find()
                ->where(['plan_therapy_id' => $planTherapyIds])
                ->count();
        }

        return $this->render('delete-confirm', [
            'model' => $model,
            'appointmentCount' => $appointmentCount
        ]);
    }

    // Modifica l'azione delete per gestire solo POST
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Controlla se ci sono appuntamenti collegati tramite le plan_therapies
        $planTherapyIds = \common\models\PlanTherapy::find()
            ->where(['therapeutic_plan_id' => $id])
            ->select('id')
            ->column();

        $appointmentCount = 0;
        if (!empty($planTherapyIds)) {
            $appointmentCount = \common\models\Appointment::find()
                ->where(['plan_therapy_id' => $planTherapyIds])
                ->count();
        }

        if ($appointmentCount > 0) {
            // Se ci sono appuntamenti collegati, mostra un messaggio di conferma più dettagliato
            if (Yii::$app->request->post('confirm_delete') === 'yes') {
                // L'utente ha confermato l'eliminazione
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // Prima elimina tutti gli appuntamenti collegati alle plan_therapies di questo piano terapeutico
                    if (!empty($planTherapyIds)) {
                        \common\models\Appointment::deleteAll(['plan_therapy_id' => $planTherapyIds]);
                    }

                    // Poi elimina le plan_therapies
                    \common\models\PlanTherapy::deleteAll(['therapeutic_plan_id' => $id]);

                    // Infine elimina il piano terapeutico
                    $model->delete();

                    $transaction->commit();
                    Yii::$app->session->setFlash(
                        'success',
                        "Piano terapeutico e {$appointmentCount} appuntamenti collegati eliminati con successo."
                    );

                    return $this->redirect(['index']);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash(
                        'error',
                        'Errore durante l\'eliminazione del piano terapeutico e degli appuntamenti collegati.'
                    );
                    Yii::error('Errore eliminazione piano terapeutico: ' . $e->getMessage());

                    return $this->redirect(['index']);
                }
            } else {
                // Mostra la pagina di conferma
                return $this->render('delete-confirm', [
                    'model' => $model,
                    'appointmentCount' => $appointmentCount
                ]);
            }
        } else {
            // Nessun appuntamento collegato, eliminazione diretta
            try {
                $model->delete();
                Yii::$app->session->setFlash('success', 'Piano terapeutico eliminato con successo.');
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'Errore durante l\'eliminazione del piano terapeutico.');
                Yii::error('Errore eliminazione piano terapeutico: ' . $e->getMessage());
            }

            return $this->redirect(['index']);
        }
    }

    /**
     * Finds the TherapeuticPlan model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id ID
     * @return TherapeuticPlan the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TherapeuticPlan::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }

    /**
     * Get patients list for dropdown
     *
     * @return array
     */
    protected function getPatientsList()
    {
        return ArrayHelper::map(
            Patient::find()->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])->all(),
            'id',
            function ($model) {
                return $model->fullName . ' (' . $model->fiscal_code . ')';
            }
        );
    }

    /**
     * AJAX search for patients
     * 
     * @param string $q Search term
     * @param int $id Patient ID for loading specific patient
     * @return array JSON response
     */
    public function actionSearchPatients($q = '', $id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // If requesting specific patient by ID
        if ($id) {
            $patient = Patient::findOne($id);
            if ($patient) {
                return ['results' => [[
                    'id' => $patient->id,
                    'text' => $patient->fullName . ' (' . $patient->fiscal_code
                    . ($patient->birth_date ? ' - ' . date('d/m/Y', strtotime($patient->birth_date)) : '')
                    . ')',
                    'fiscal_code' => $patient->fiscal_code,
                    'birth_date_it' => $patient->birth_date ? date('d/m/Y', strtotime($patient->birth_date)) : '',
                    'full_name' => $patient->fullName
                ]]];
            }
            return ['results' => []];
        }

        // Return all patients if no search term (for initialization)
        if (empty($q)) {
            $patients = Patient::find()
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->limit(50)
                ->all();
        } else {
            // Search with minimum 2 characters
            if (strlen($q) < 2) {
                return ['results' => []];
            }

            $patients = Patient::find()
                ->where([
                    'or',
                    ['like', 'first_name', $q],
                    ['like', 'last_name', $q],
                    ['like', 'fiscal_code', $q],
                    ['like', "CONCAT(first_name, ' ', last_name)", $q],
                    ['like', "CONCAT(last_name, ' ', first_name)", $q]
                ])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->limit(20)
                ->all();
        }

        $results = [];
        foreach ($patients as $patient) {
            $results[] = [
                'id' => $patient->id,
                'text' => $patient->fullName . ' (' . $patient->fiscal_code
                    . ($patient->birth_date ? ' - ' . date('d/m/Y', strtotime($patient->birth_date)) : '')
                    . ')',
                'fiscal_code' => $patient->fiscal_code,
                'birth_date_it' => $patient->birth_date ? date('d/m/Y', strtotime($patient->birth_date)) : '',
                'full_name' => $patient->fullName
            ];
        }

        return ['results' => $results];
    }

    /**
     * Get regimes list for dropdown
     *
     * @return array
     */
    protected function getRegimesList()
    {
        return ArrayHelper::map(
            Regime::find()->orderBy(['nome' => SORT_ASC])->all(),
            'id',
            'nome'
        );
    }

    /**
     * Get districts list for dropdown
     *
     * @return array
     */
    protected function getDistrictsList()
    {
        return \common\models\District::getDropdownData(
        );
    }

    /**
     * Verifica se un piano terapeutico si sovrappone con altri piani dello stesso paziente
     * 
     * @param int $patientId ID del paziente
     * @param string $startDate Data di inizio del piano (formato Y-m-d)
     * @param int $durationDays Durata del piano in giorni
     * @param int|null $excludePlanId ID del piano da escludere dal controllo (per update)
     * @return array ['isValid' => bool, 'conflictingPlan' => array|null]
     */
    private function checkPlanOverlap($patientId, $startDate, $durationDays, $excludePlanId = null)
    {
        // Calcola la data di fine del nuovo piano
        $endDate = date('Y-m-d', strtotime($startDate . ' + ' . ($durationDays - 1) . ' days'));

        Yii::info("Verifica sovrapposizione piani per paziente {$patientId}: {$startDate} - {$endDate}", __METHOD__);

        // Query per trovare piani sovrapposti.
        // Escludi piani non bloccanti: draft (non avviato), completed/terminated/expired (conclusi).
        // Restano bloccanti: pending, active, suspended.
        $query = TherapeuticPlan::find()
            ->where(['patient_id' => $patientId])
            ->andWhere(['not in', 'status', ['draft', 'completed', 'terminated', 'expired']])
            ->andWhere([
                'or',
                // Il nuovo piano inizia durante un piano esistente
                [
                    'and',
                    ['<=', 'start_date', $startDate],
                    ['>=', 'end_date', $startDate]
                ],
                // Il nuovo piano finisce durante un piano esistente
                [
                    'and',
                    ['<=', 'start_date', $endDate],
                    ['>=', 'end_date', $endDate]
                ],
                // Il nuovo piano contiene completamente un piano esistente
                [
                    'and',
                    ['>=', 'start_date', $startDate],
                    ['<=', 'end_date', $endDate]
                ],
                // Un piano esistente contiene completamente il nuovo piano
                [
                    'and',
                    ['<=', 'start_date', $startDate],
                    ['>=', 'end_date', $endDate]
                ]
            ]);

        // Escludi il piano corrente se specificato (per update)
        if ($excludePlanId !== null) {
            $query->andWhere(['!=', 'id', $excludePlanId]);
        }

        $conflictingPlan = $query->one();

        if ($conflictingPlan) {
            Yii::warning("Trovato piano in conflitto: ID {$conflictingPlan->id}, date {$conflictingPlan->start_date} - {$conflictingPlan->end_date}", __METHOD__);

            return [
                'isValid' => false,
                'conflictingPlan' => [
                    'id' => $conflictingPlan->id,
                    'start_date' => $conflictingPlan->start_date,
                    'end_date' => $conflictingPlan->end_date,
                    'duration_days' => $conflictingPlan->duration_days,
                    'regime' => $conflictingPlan->regime->nome ?? null
                ]
            ];
        }

        return [
            'isValid' => true,
            'conflictingPlan' => null
        ];
    }

    /**
     * Ottiene la lista dei settings per un regime specifico
     * 
     * @param int $regimeId
     * @return array
     */
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
}
