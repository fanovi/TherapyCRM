<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * PatientAbsenceSearch represents the search model for patient absences
 * (appointments with status absent_justified / absent_not_justified).
 */
class PatientAbsenceSearch extends Appointment
{
    public $patient_name;
    public $therapist_name;
    public $date_from;
    public $date_to;
    public $therapistIds;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'therapist_id'], 'integer'],
            [['status', 'patient_name', 'therapist_name', 'date_from', 'date_to'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Appointment::find()
            ->alias('a')
            ->where(['a.status' => [
                Appointment::STATUS_ABSENT_JUSTIFIED,
                Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
            ]])
            ->joinWith(['therapist.user.profile'])
            ->leftJoin('plan_therapies pt', 'a.plan_therapy_id = pt.id')
            ->leftJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin('patients p_plan', 'tp.patient_id = p_plan.id')
            ->leftJoin('patients p_private', 'a.patient_id = p_private.id');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['appointment_datetime' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Coordinator group filter - applied before load to prevent bypass
        if (!empty($this->therapistIds)) {
            $query->andWhere(['a.therapist_id' => $this->therapistIds]);
        }

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['a.status' => $this->status]);
        $query->andFilterWhere(['a.therapist_id' => $this->therapist_id]);

        // Date range filter
        if (!empty($this->date_from)) {
            $query->andWhere(['>=', 'a.appointment_datetime', $this->date_from . ' 00:00:00']);
        }
        if (!empty($this->date_to)) {
            $query->andWhere(['<=', 'a.appointment_datetime', $this->date_to . ' 23:59:59']);
        }

        // Patient name filter (search across both plan-based and private patients)
        if (!empty($this->patient_name)) {
            $query->andWhere([
                'or',
                ['like', 'p_plan.first_name', $this->patient_name],
                ['like', 'p_plan.last_name', $this->patient_name],
                ['like', 'p_private.first_name', $this->patient_name],
                ['like', 'p_private.last_name', $this->patient_name],
            ]);
        }

        return $dataProvider;
    }

    /**
     * Get list of therapists for dropdown filter.
     * @return array
     */
    public static function getTherapistsList()
    {
        return ArrayHelper::map(
            Therapist::find()
                ->joinWith('user.profile')
                ->where(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function ($model) {
                return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
            }
        );
    }

    /**
     * Get filtered list of therapists for dropdown (coordinator group).
     * @param array $therapistIds
     * @return array
     */
    public static function getTherapistsListFiltered($therapistIds)
    {
        if (empty($therapistIds)) {
            return [];
        }
        return ArrayHelper::map(
            Therapist::find()
                ->joinWith('user.profile')
                ->where(['therapists.id' => $therapistIds])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function ($model) {
                return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
            }
        );
    }

    /**
     * Get list of patient absence statuses for dropdown filter.
     * @return array
     */
    public static function getStatusList()
    {
        return [
            Appointment::STATUS_ABSENT_JUSTIFIED => 'Giustificata',
            Appointment::STATUS_ABSENT_NOT_JUSTIFIED => 'Non giustificata',
        ];
    }
}
