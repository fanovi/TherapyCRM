<?php

namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\CoordinatorGroup;

/**
 * CoordinatorGroupSearch represents the model behind the search form of `common\models\CoordinatorGroup`.
 */
class CoordinatorGroupSearch extends CoordinatorGroup
{
    public $coordinator_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'coordinator_user_id'], 'integer'],
            [['is_active'], 'boolean'],
            [['name', 'coordinator_name'], 'safe'],
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
        $query = CoordinatorGroup::find()->with(['coordinator.profile']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC],
                'attributes' => [
                    'name',
                    'coordinator_name' => [
                        'asc' => ['user_profiles.last_name' => SORT_ASC, 'user_profiles.first_name' => SORT_ASC],
                        'desc' => ['user_profiles.last_name' => SORT_DESC, 'user_profiles.first_name' => SORT_DESC],
                    ],
                    'created_at',
                ],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Join con user_profiles per la ricerca per nome coordinatore
        $query->joinWith(['coordinator.profile']);

        // grid filtering conditions
        $query->andFilterWhere([
            'coordinator_groups.id' => $this->id,
            'coordinator_groups.coordinator_user_id' => $this->coordinator_user_id,
            'coordinator_groups.is_active' => $this->is_active,
        ]);

        $query->andFilterWhere(['like', 'coordinator_groups.name', $this->name]);

        // Filtro per nome coordinatore
        if ($this->coordinator_name) {
            $query->andFilterWhere(['or',
                ['like', 'user_profiles.first_name', $this->coordinator_name],
                ['like', 'user_profiles.last_name', $this->coordinator_name],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $this->coordinator_name],
            ]);
        }

        return $dataProvider;
    }
} 