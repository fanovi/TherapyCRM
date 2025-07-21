<?php

namespace frontend\controllers;

use Yii;
use common\models\Absence;
use common\models\Appointment;
use common\models\Therapist;
use common\models\AbsenceSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

/**
 * AbsenceController implements the CRUD actions for Absence model.
 */
class AbsenceController extends Controller
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
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['view_absence'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete', 'check-appointments'],
                        'allow' => true,
                        'roles' => ['create_absence'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Absence models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AbsenceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Absence model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Absence model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Absence();
        $model->status = Absence::STATUS_APPROVED;
        $model->created_by = Yii::$app->user->id;
        $model->approved_by = Yii::$app->user->id;
        $model->approved_at = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            
            try {
                if ($model->save()) {
                    // Check if we need to update appointments
                    $updateAppointments = Yii::$app->request->post('update_appointments', false);
                    
                    if ($updateAppointments) {
                        $appointments = $this->getAppointmentsInRange(
                            $model->therapist_id,
                            $model->start_date,
                            $model->end_date
                        );
                        
                        foreach ($appointments as $appointment) {
                            $appointment->status = Appointment::STATUS_THERAPIST_ABSENT;
                            $appointment->save(false);
                        }
                        
                        Yii::$app->session->setFlash('info', 
                            'Assenza creata con successo. ' . count($appointments) . ' appuntamenti sono stati aggiornati.'
                        );
                    } else {
                        Yii::$app->session->setFlash('success', 'Assenza creata con successo.');
                    }
                    
                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Errore durante la creazione dell\'assenza: ' . $e->getMessage());
            }
        }

        $therapists = ArrayHelper::map(
            Therapist::find()
                ->joinWith('user.profile')
                ->where(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function($model) {
                return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
            }
        );

        return $this->render('create', [
            'model' => $model,
            'therapists' => $therapists,
        ]);
    }

    /**
     * Updates an existing Absence model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Assenza aggiornata con successo.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $therapists = ArrayHelper::map(
            Therapist::find()
                ->joinWith('user.profile')
                ->where(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function($model) {
                return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
            }
        );

        return $this->render('update', [
            'model' => $model,
            'therapists' => $therapists,
        ]);
    }

    /**
     * Deletes an existing Absence model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Assenza eliminata con successo.');

        return $this->redirect(['index']);
    }

    /**
     * AJAX action to check appointments in date range
     * @return mixed
     */
    public function actionCheckAppointments()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $therapistId = Yii::$app->request->post('therapist_id');
        $startDate = Yii::$app->request->post('start_date');
        $endDate = Yii::$app->request->post('end_date');
        
        if (!$therapistId || !$startDate || !$endDate) {
            return ['count' => 0, 'appointments' => []];
        }
        
        $appointments = $this->getAppointmentsInRange($therapistId, $startDate, $endDate);
        
        $appointmentDetails = [];
        foreach ($appointments as $appointment) {
            $patient = $appointment->getActualPatient();
            $appointmentDetails[] = [
                'date' => Yii::$app->formatter->asDate($appointment->appointment_datetime),
                'time' => Yii::$app->formatter->asTime($appointment->appointment_datetime, 'short'),
                'patient' => $patient ? $patient->getFullName() : 'N/A',
                'type' => $appointment->getAppointmentTypeLabel(),
            ];
        }
        
        return [
            'count' => count($appointments),
            'appointments' => $appointmentDetails
        ];
    }

    /**
     * Get appointments in date range for a therapist
     * @param int $therapistId
     * @param string $startDate
     * @param string $endDate
     * @return Appointment[]
     */
    protected function getAppointmentsInRange($therapistId, $startDate, $endDate)
    {
        return Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['>=', 'appointment_datetime', $startDate . ' 00:00:00'])
            ->andWhere(['<=', 'appointment_datetime', $endDate . ' 23:59:59'])
            ->andWhere(['status' => Appointment::STATUS_SCHEDULED])
            ->orderBy('appointment_datetime')
            ->all();
    }

    /**
     * Finds the Absence model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Absence the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Absence::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }
}