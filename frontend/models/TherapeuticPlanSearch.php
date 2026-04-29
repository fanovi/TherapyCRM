<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use common\models\TherapeuticPlan;

/**
 * TherapeuticPlanSearch represents the model behind the search form of `common\models\TherapeuticPlan`.
 */
class TherapeuticPlanSearch extends TherapeuticPlan
{
    /**
     * @var string Search term for patient name
     */
    public $patientName;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'patient_id', 'regime_id', 'district_id', 'duration_days', 'created_by'], 'integer'],
            [['start_date', 'end_date', 'approval_date', 'protocol_number', 'notes', 'status', 'patientName'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = TherapeuticPlan::find()
            ->with(['patient', 'regime', 'district', 'createdBy'])
            ->joinWith(['patient']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'status_priority' => SORT_DESC,
                    'created_at' => SORT_DESC,
                ],
                'attributes' => [
                    'id',
                    'protocol_number',
                    'start_date',
                    'end_date',
                    'duration_days',
                    'regime_id',
                    'district_id',
                    'status',
                    'created_at',
                    'patientName' => [
                        'asc' => ['patients.last_name' => SORT_ASC, 'patients.first_name' => SORT_ASC],
                        'desc' => ['patients.last_name' => SORT_DESC, 'patients.first_name' => SORT_DESC],
                    ],
                    // Pseudo-attributo: 1 se status='active', altrimenti 0. Default order DESC mostra prima gli attivi.
                    'status_priority' => [
                        'asc' => [new Expression("(therapeutic_plans.status = 'active') ASC")],
                        'desc' => [new Expression("(therapeutic_plans.status = 'active') DESC")],
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'therapeutic_plans.id' => $this->id,
            'therapeutic_plans.patient_id' => $this->patient_id,
            'therapeutic_plans.regime_id' => $this->regime_id,
            'therapeutic_plans.district_id' => $this->district_id,
            'therapeutic_plans.duration_days' => $this->duration_days,
            'therapeutic_plans.created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'therapeutic_plans.protocol_number', $this->protocol_number])
            ->andFilterWhere(['like', 'therapeutic_plans.notes', $this->notes])
            ->andFilterWhere(['therapeutic_plans.status' => $this->status]);

        // Date filters
        if (!empty($this->start_date)) {
            $query->andFilterWhere(['therapeutic_plans.start_date' => $this->start_date]);
        }

        if (!empty($this->end_date)) {
            $query->andFilterWhere(['therapeutic_plans.end_date' => $this->end_date]);
        }

        // Patient name filter (search in both first_name and last_name)
        if (!empty($this->patientName)) {
            $query->andWhere([
                'or',
                ['like', 'patients.first_name', $this->patientName],
                ['like', 'patients.last_name', $this->patientName],
                ['like', "CONCAT(patients.first_name, ' ', patients.last_name)", $this->patientName],
                ['like', "CONCAT(patients.last_name, ' ', patients.first_name)", $this->patientName],
            ]);
        }

        return $dataProvider;
    }
}
