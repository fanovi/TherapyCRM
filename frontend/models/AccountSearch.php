<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\User;

/**
 * AccountSearch represents the model behind the search form for patient family accounts.
 */
class AccountSearch extends User
{
    public $profile_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status', 'created_at'], 'integer'],
            [['email', 'username', 'profile_name'], 'safe'],
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
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = User::find()
            ->joinWith(['profile', 'authAssignments', 'accountPatients.patient'])
            ->where(['auth_assignment.item_name' => 'patient_family'])
            ->groupBy('users.id')
            ->orderBy(['users.created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'attributes' => [
                    'id',
                    'email',
                    'created_at',
                    'profile_name' => [
                        'asc' => ['user_profile.last_name' => SORT_ASC, 'user_profile.first_name' => SORT_ASC],
                        'desc' => ['user_profile.last_name' => SORT_DESC, 'user_profile.first_name' => SORT_DESC],
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
            'users.id' => $this->id,
            'users.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'users.email', $this->email]);

        // Filter by profile name
        if (!empty($this->profile_name)) {
            $query->andFilterWhere(['or',
                ['like', 'user_profile.first_name', $this->profile_name],
                ['like', 'user_profile.last_name', $this->profile_name],
            ]);
        }

        return $dataProvider;
    }
}
