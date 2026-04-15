<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Absence;
use yii\helpers\ArrayHelper;

/**
 * AbsenceSearch represents the model behind the search form of `common\models\Absence`.
 */
class AbsenceSearch extends Absence
{
    public $therapist_name;
    public $date_range;
    public $therapistIds;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'therapist_id', 'approved_by', 'created_by'], 'integer'],
            [['start_date', 'end_date', 'type', 'reason', 'status', 'approved_at', 'notes', 'created_at', 'updated_at'], 'safe'],
            [['therapist_name', 'date_range'], 'safe'],
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
        $query = Absence::find()
            ->joinWith(['therapist.user.profile']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['start_date' => SORT_DESC]
            ],
        ]);

        // Add sortable attributes
        $dataProvider->sort->attributes['therapist_name'] = [
            'asc' => ['user_profiles.last_name' => SORT_ASC, 'user_profiles.first_name' => SORT_ASC],
            'desc' => ['user_profiles.last_name' => SORT_DESC, 'user_profiles.first_name' => SORT_DESC],
        ];

        // Filter by coordinator's group therapist IDs if set
        if (!empty($this->therapistIds)) {
            $query->andWhere(['absences.therapist_id' => $this->therapistIds]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'absences.id' => $this->id,
            'absences.therapist_id' => $this->therapist_id,
            'absences.type' => $this->type,
            'absences.status' => $this->status,
            'absences.approved_by' => $this->approved_by,
            'absences.created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'absences.reason', $this->reason])
            ->andFilterWhere(['like', 'absences.notes', $this->notes]);

        // Filter by therapist name
        if (!empty($this->therapist_name)) {
            $query->andWhere([
                'or',
                ['like', 'user_profiles.first_name', $this->therapist_name],
                ['like', 'user_profiles.last_name', $this->therapist_name],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $this->therapist_name],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $this->therapist_name],
            ]);
        }

        // Date range filter
        if (!empty($this->start_date)) {
            $query->andWhere(['>=', 'absences.start_date', $this->start_date]);
        }
        if (!empty($this->end_date)) {
            $query->andWhere(['<=', 'absences.end_date', $this->end_date]);
        }

        return $dataProvider;
    }

    /**
     * Get list of therapists for dropdown
     * @return array
     */
    public static function getTherapistsList()
    {
        return ArrayHelper::map(
            \common\models\Therapist::find()
                ->joinWith('user.profile')
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function($model) {
                return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
            }
        );
    }

    /**
     * Get list of absence types for dropdown
     * @return array
     */
    public static function getTypesList()
    {
        return Absence::getTypeLabels();
    }

    /**
     * Get list of statuses for dropdown
     * @return array
     */
    public static function getStatusList()
    {
        return Absence::getStatusLabels();
    }
}