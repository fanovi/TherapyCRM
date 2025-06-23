<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\User;

/**
 * UserSearch represents the model behind the search form of `common\models\User`.
 */
class UserSearch extends User
{
    /**
     * @var string Nome dall'UserProfile
     */
    public $first_name;
    
    /**
     * @var string Cognome dall'UserProfile
     */
    public $last_name;
    
    /**
     * @var string Telefono dall'UserProfile
     */
    public $phone;
    
    /**
     * @var string Username dell'User
     */
    public $username;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['status'], 'string'],
            [['email', 'first_name', 'last_name', 'phone', 'username'], 'safe'],
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
     * @param string $role Ruolo da filtrare (default: 'admin')
     *
     * @return ActiveDataProvider
     */
    public function search($params, $role = 'admin')
    {
        $query = User::find()
            ->joinWith(['authAssignments', 'profile'])
            ->where(['auth_assignment.item_name' => $role]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'attributes' => [
                    'id',
                    'username',
                    'email',
                    'status',
                    'first_name' => [
                        'asc' => ['user_profiles.first_name' => SORT_ASC],
                        'desc' => ['user_profiles.first_name' => SORT_DESC],
                    ],
                    'last_name' => [
                        'asc' => ['user_profiles.last_name' => SORT_ASC],
                        'desc' => ['user_profiles.last_name' => SORT_DESC],
                    ],
                    'phone' => [
                        'asc' => ['user_profiles.phone' => SORT_ASC],
                        'desc' => ['user_profiles.phone' => SORT_DESC],
                    ],
                ],
                'defaultOrder' => [
                    'last_name' => SORT_ASC,
                    'first_name' => SORT_ASC,
                ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'users.id' => $this->id,
        ]);

        // Status filter - handle both string and integer values
        if (!empty($this->status)) {
            if ($this->status === 'active') {
                $query->andWhere(['users.status' => 10]); // STATUS_ACTIVE
            } elseif ($this->status === 'inactive') {
                $query->andWhere(['users.status' => 9]); // STATUS_INACTIVE
            }
        }

        $query->andFilterWhere(['like', 'users.username', $this->username])
            ->andFilterWhere(['like', 'users.email', $this->email])
            ->andFilterWhere(['like', 'user_profiles.first_name', $this->first_name])
            ->andFilterWhere(['like', 'user_profiles.last_name', $this->last_name])
            ->andFilterWhere(['like', 'user_profiles.phone', $this->phone]);

        return $dataProvider;
    }
} 