<?php

namespace frontend\controllers;

use common\models\Notification;
use Yii;
use common\models\TherapeuticPlan;
use common\models\Patient;
use common\models\Regime;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use common\helpers\NotificationHelper;

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
                            'actions' => ['search-patients'],
                            'allow' => true,
                            'matchCallback' => function ($rule, $action) {
                                return Yii::$app->user->can('view_therapeutic_plan') || Yii::$app->user->can('create_therapeutic_plan');
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
        $dataProvider = new ActiveDataProvider([
            'query' => TherapeuticPlan::find()->with(['patient', 'regime', 'createdBy']),
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
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
            
            $isValid = true;
            $error = null;
            
            try {
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

                // Se il modello principale è valido, procedi con il salvataggio
                if ($model->validate()) {
                    Yii::info('Validazione modello principale superata', __METHOD__);
                    
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        if (!$model->save()) {
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
                            foreach ($therapies as $therapy) {
                                if (!empty($therapy['treatment_type_id'])) {
                                    $treatmentType = \common\models\TreatmentType::findOne($therapy['treatment_type_id']);
                                    $setting = \common\models\Setting::findOne($therapy['setting_id']);
                                    $htmlMessage .= "- {$treatmentType->name}: {$therapy['weekly_hours']} ore/settimana";
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
        $therapyModel = new \common\models\PlanTherapy();

        if ($this->request->isPost) {
            $isValid = true;
            $transaction = Yii::$app->db->beginTransaction();
            
            try {
                if ($model->load($this->request->post()) && $model->save()) {
                    // Valida le terapie
                    $therapies = Yii::$app->request->post('PlanTherapy', []);
                    if (empty($therapies)) {
                        throw new \Exception('È necessario inserire almeno una terapia.');
                    }

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

                    // Verifica se ci sono appuntamenti per le terapie da rimuovere
                    $currentTherapies = \common\models\PlanTherapy::find()
                        ->where(['therapeutic_plan_id' => $model->id])
                        ->all();
                    
                    $currentTherapyIds = array_map(function($therapy) {
                        return $therapy->treatment_type_id;
                    }, $currentTherapies);
                    
                    $newTherapyIds = array_map(function($therapy) {
                        return $therapy['treatment_type_id'];
                    }, array_filter($therapies, function($therapy) {
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

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Piano terapeutico aggiornato con successo.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
                $isValid = false;
            }
            
            if (!$isValid) {
                return $this->render('update', [
                    'model' => $model,
                    'therapyModel' => $therapyModel,
                    'patients' => $this->getPatientsList(),
                    'regimes' => $this->getRegimesList(),
                    'treatmentTypes' => $this->getTreatmentTypesList(),
                    'settings' => $this->getSettingsList(),
                    'postedTherapies' => $therapies ?? [] // Aggiungi $postedTherapies anche in caso di errore
                ]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'therapyModel' => $therapyModel,
            'patients' => $this->getPatientsList(),
            'regimes' => $this->getRegimesList(),
            'treatmentTypes' => $this->getTreatmentTypesList(),
            'settings' => $this->getSettingsList(),
            'postedTherapies' => [] // Aggiungi $postedTherapies anche per il primo caricamento
        ]);
    }

    /**
     * Deletes an existing TherapeuticPlan model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        try {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Piano terapeutico eliminato con successo.');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Errore durante l\'eliminazione del piano terapeutico.');
            Yii::error('Errore eliminazione piano terapeutico: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
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
            function($model) {
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
                    'text' => $patient->fullName . ' (' . $patient->fiscal_code . ')',
                    'fiscal_code' => $patient->fiscal_code,
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
                ->where(['or',
                    ['like', 'first_name', $q],
                    ['like', 'last_name', $q],
                    ['like', 'fiscal_code', $q],
                    ['like', "CONCAT(first_name, ' ', last_name)", $q]
                ])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->limit(20)
                ->all();
        }
        
        $results = [];
        foreach ($patients as $patient) {
            $results[] = [
                'id' => $patient->id,
                'text' => $patient->fullName . ' (' . $patient->fiscal_code . ')',
                'fiscal_code' => $patient->fiscal_code,
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
} 