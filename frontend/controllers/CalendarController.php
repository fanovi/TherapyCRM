<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use common\models\CoordinatorGroup;
use common\models\GroupTherapist;

class CalendarController extends Controller
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
                        'roles' => ['view_calendar'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Renderizza l'app calendario
     *
     * @param int|null $id_patient ID del paziente
     * @param int|null $id_therapist ID del terapista
     * @return string
     */
    public function actionIndex($id_patient = null, $id_therapist = null)
    {
        // Validazione: solo uno dei due parametri può essere presente
        if ($id_patient && $id_therapist) {
            throw new \yii\web\BadRequestHttpException('Non è possibile specificare sia id_patient che id_therapist');
        }

        // Coordinator can only view calendars of therapists in their group
        if ($id_therapist && $this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array((int)$id_therapist, $therapistIds)) {
                throw new ForbiddenHttpException('Non hai i permessi per visualizzare il calendario di questo terapista.');
            }
        }

        return $this->render('index', [
            'idPatient' => $id_patient,
            'idTherapist' => $id_therapist,
        ]);
    }

    /**
     * Check if the current user is a coordinator (not manager/admin).
     */
    private function isCoordinatorOnly()
    {
        return Yii::$app->user->can('coordinator')
            && !Yii::$app->user->can('manager')
            && !Yii::$app->user->can('admin');
    }

    /**
     * Get therapist IDs in the current coordinator's group.
     */
    private function getCoordinatorTherapistIds()
    {
        $coordinatorGroup = CoordinatorGroup::find()
            ->where(['coordinator_user_id' => Yii::$app->user->id])
            ->one();

        if ($coordinatorGroup) {
            $ids = GroupTherapist::find()
                ->select('therapist_id')
                ->where(['group_id' => $coordinatorGroup->id])
                ->andWhere(['assigned_to' => null])
                ->column();
            return !empty($ids) ? array_map('intval', $ids) : [0];
        }
        return [0];
    }
} 