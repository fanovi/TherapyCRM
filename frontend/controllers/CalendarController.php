<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use common\models\CoordinatorGroup;
use common\models\GroupTherapist;
use common\models\Therapist;

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
     * Hub calendario: scelta terapista in alto e iframe sotto.
     *
     * @param int|null $id_therapist
     * @return string
     */
    public function actionTherapists($id_therapist = null)
    {
        $idTherapist = $id_therapist !== null && $id_therapist !== ''
            ? (int) $id_therapist
            : null;
        $selectedTherapistName = null;
        $therapistNav = null;

        if ($idTherapist !== null) {
            if (!$this->isTherapistAllowed($idTherapist)) {
                throw new ForbiddenHttpException('Non hai i permessi per visualizzare il calendario di questo terapista.');
            }
            $selectedTherapistName = $this->getTherapistLabel($idTherapist);
            $therapistNav = $this->getTherapistNavigation($idTherapist);
        }

        return $this->render('therapists', [
            'idTherapist' => $idTherapist,
            'selectedTherapistName' => $selectedTherapistName,
            'therapistNav' => $therapistNav,
        ]);
    }

    /**
     * Terapista precedente e successivo nella lista della ricerca, in modo circolare
     * (dal primo si torna all'ultimo e viceversa), con la posizione del corrente.
     *
     * @param int $id
     * @return array|null ['prev' => [id, name], 'next' => [id, name], 'position' => int, 'total' => int]
     */
    private function getTherapistNavigation($id)
    {
        $therapists = $this->findAllowedTherapistsQuery()->all();
        $count = count($therapists);
        if ($count < 2) {
            return null;
        }

        $index = null;
        foreach ($therapists as $i => $therapist) {
            if ((int) $therapist->id === (int) $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return null;
        }

        $prev = $therapists[($index - 1 + $count) % $count];
        $next = $therapists[($index + 1) % $count];

        return [
            'prev' => ['id' => (int) $prev->id, 'name' => $this->formatTherapistName($prev)],
            'next' => ['id' => (int) $next->id, 'name' => $this->formatTherapistName($next)],
            'position' => $index + 1,
            'total' => $count,
        ];
    }

    /**
     * Terapisti attivi visibili all'utente corrente, ordinati per cognome e nome.
     *
     * @return \yii\db\ActiveQuery
     */
    private function findAllowedTherapistsQuery()
    {
        $query = Therapist::find()
            ->joinWith('user.profile')
            ->where(['therapists.is_active' => 1])
            ->orderBy([
                'user_profiles.last_name' => SORT_ASC,
                'user_profiles.first_name' => SORT_ASC,
                'therapists.id' => SORT_ASC,
            ]);

        $allowedIds = $this->getAllowedTherapistIds();
        if ($allowedIds !== null) {
            $query->andWhere(['therapists.id' => $allowedIds]);
        }

        return $query;
    }

    /**
     * Ricerca AJAX terapisti per Select2.
     */
    public function actionSearchTherapists($q = '', $page = 1)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim((string) $q);
        $page = max(1, (int) $page);
        $pageSize = 20;

        $query = $this->findAllowedTherapistsQuery();

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'user_profiles.first_name', $q],
                ['like', 'user_profiles.last_name', $q],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $q],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $q],
            ]);
        }

        $total = (int) $query->count();
        $models = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all();

        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => (int) $model->id,
                'text' => $this->formatTherapistName($model),
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($page * $pageSize) < $total,
            ],
        ];
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

    /**
     * Check if the current user is a therapist without broader calendar scope.
     */
    private function isTherapistOnly()
    {
        return Yii::$app->user->can('therapist')
            && !Yii::$app->user->can('coordinator')
            && !Yii::$app->user->can('manager')
            && !Yii::$app->user->can('admin');
    }

    /**
     * ID terapisti visibili all'utente corrente, o null per tutti gli attivi.
     *
     * @return int[]|null
     */
    private function getAllowedTherapistIds()
    {
        if ($this->isCoordinatorOnly()) {
            return $this->getCoordinatorTherapistIds();
        }

        if ($this->isTherapistOnly()) {
            $therapist = Therapist::findByUserId(Yii::$app->user->id);
            return $therapist ? [(int) $therapist->id] : [0];
        }

        return null;
    }

    /**
     * @param int $id
     * @return bool
     */
    private function isTherapistAllowed($id)
    {
        $query = Therapist::find()->where(['id' => (int) $id, 'is_active' => 1]);
        $allowedIds = $this->getAllowedTherapistIds();
        if ($allowedIds !== null) {
            $query->andWhere(['id' => $allowedIds]);
        }

        return $query->exists();
    }

    /**
     * @param int $id
     * @return string|null
     */
    private function getTherapistLabel($id)
    {
        $therapist = Therapist::find()
            ->joinWith('user.profile')
            ->where(['therapists.id' => (int) $id])
            ->one();

        return $therapist ? $this->formatTherapistName($therapist) : null;
    }

    /**
     * @param Therapist $model
     * @return string
     */
    private function formatTherapistName($model)
    {
        $profile = $model->user->profile ?? null;
        if ($profile) {
            $name = trim(($profile->last_name ?? '') . ' ' . ($profile->first_name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return 'Terapista #' . $model->id;
    }
} 