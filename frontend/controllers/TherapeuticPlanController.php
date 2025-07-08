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

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
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
                    
                    \common\helpers\NotificationHelper::sendToRole(
                        'manager', // Ruolo come stringa
                        'Nuovo piano terapeutico',
                        $htmlMessage,
                        Notification::TYPE_INTERNAL_COMMUNICATION, // $type
                        ['sender_id' => Yii::$app->user->id] // $data come array
                    );
                    
                    Yii::info("Notifica piano terapeutico #{$model->id} inviata ai manager", __METHOD__);
                } catch (\Exception $e) {
                    // Log dell'errore ma non bloccare il flusso
                    Yii::error("Errore invio notifica piano terapeutico: " . $e->getMessage(), __METHOD__);
                }

                Yii::$app->session->setFlash('success', 'Piano terapeutico creato con successo.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Errore durante la creazione del piano terapeutico.');
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'patients' => $this->getPatientsList(),
            'regimes' => $this->getRegimesList(),
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

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Piano terapeutico aggiornato con successo.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'patients' => $this->getPatientsList(),
            'regimes' => $this->getRegimesList(),
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