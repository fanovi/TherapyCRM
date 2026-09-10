<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\Appointment;
use common\models\Holiday;
use frontend\models\HolidaySearch;

/**
 * HolidayController implementa il CRUD dell'anagrafica dei giorni di chiusura
 * della struttura (docs/PLAN_GIORNI_FESTIVI_2026-09-09.md).
 *
 * I permessi view/create/update/delete_holiday governano solo l'anagrafica:
 * il blocco degli appuntamenti nei giorni chiusi vale per chiunque.
 */
class HolidayController extends Controller
{
    /** Appuntamenti in conflitto elencati al massimo nel form */
    const MAX_CONFLICT_APPOINTMENTS = 50;

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
                        'roles' => ['@'],
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
     * Lists all Holiday models.
     * @return mixed
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('view_holiday')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i giorni festivi.');
        }

        $searchModel = new HolidaySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Holiday model.
     * @return mixed
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('create_holiday')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare giorni festivi.');
        }

        $model = new Holiday();
        $model->kind = Holiday::KIND_RECURRING;
        $model->is_active = 1;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Giorno di chiusura "' . $model->name . '" creato con successo.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'conflictAppointments' => $this->findConflictAppointments($model),
        ]);
    }

    /**
     * Updates an existing Holiday model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('update_holiday')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare giorni festivi.');
        }

        $model = $this->findModel($id);

        if (!$model->load(Yii::$app->request->post())) {
            $model->initFormFields();
        } elseif ($model->save()) {
            Yii::$app->session->setFlash('success', 'Giorno di chiusura "' . $model->name . '" aggiornato con successo.');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'conflictAppointments' => $this->findConflictAppointments($model),
        ]);
    }

    /**
     * Deletes an existing Holiday model.
     * Riaprire un giorno non puo' entrare in conflitto con nulla: nessun controllo.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->can('delete_holiday')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare giorni festivi.');
        }

        $model = $this->findModel($id);

        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Giorno di chiusura "' . $model->name . '" eliminato: da ora il giorno accetta di nuovo appuntamenti.');
        } else {
            Yii::$app->session->setFlash('error', 'Errore durante l\'eliminazione del giorno di chiusura.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Holiday model based on its primary key value.
     * @param int $id
     * @return Holiday the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Holiday::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }

    /**
     * Appuntamenti che hanno bloccato il salvataggio, da mostrare nel form
     * perche' l'operatore sappia cosa spostare o annullare.
     *
     * @param Holiday $model
     * @return Appointment[]
     */
    private function findConflictAppointments(Holiday $model): array
    {
        if (empty($model->appointmentConflicts)) {
            return [];
        }

        return Appointment::find()
            ->with(['therapist.user.profile', 'patient', 'planTherapy.therapeuticPlan.patient'])
            ->where(['status' => Appointment::STATUS_SCHEDULED])
            ->andWhere(['in', 'DATE(appointment_datetime)', array_keys($model->appointmentConflicts)])
            ->orderBy(['appointment_datetime' => SORT_ASC, 'therapist_id' => SORT_ASC])
            ->limit(self::MAX_CONFLICT_APPOINTMENTS)
            ->all();
    }
}
