<?php

namespace frontend\models;

use common\models\Specialization;
use common\models\Therapist;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * TherapistSearch represents the model behind the search form of `common\models\Therapist`.
 */
class TherapistSearch extends Therapist
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
     * @var string Email dall'User
     */
    public $email;

    /**
     * @var string Nome specializzazione
     */
    public $specialization_name;

    /**
     * @var array Array of therapist IDs to filter by (for coordinator group filtering)
     */
    public $therapist_ids;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'specialization_id', 'weekly_hours_contract'], 'integer'],
            [['therapist_ids'], 'safe'],
            [['is_active', 'is_internal'], 'boolean'],
            [['first_name', 'last_name', 'email', 'specialization_name', 'calendar_color', 'created_at', 'updated_at'], 'safe'],
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
        $query = Therapist::find()
            ->joinWith(['user', 'user.profile', 'specialization']);
        // Remove default filter - let the user choose active/inactive

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'attributes' => [
                    'id',
                    'weekly_hours_contract',
                    'calendar_color',
                    'is_active',
                    'is_internal',
                    'created_at',
                    'first_name' => [
                        'asc' => ['user_profiles.first_name' => SORT_ASC],
                        'desc' => ['user_profiles.first_name' => SORT_DESC],
                    ],
                    'last_name' => [
                        'asc' => ['user_profiles.last_name' => SORT_ASC],
                        'desc' => ['user_profiles.last_name' => SORT_DESC],
                    ],
                    'email' => [
                        'asc' => ['users.email' => SORT_ASC],
                        'desc' => ['users.email' => SORT_DESC],
                    ],
                    'specialization_name' => [
                        'asc' => ['specializations.name' => SORT_ASC],
                        'desc' => ['specializations.name' => SORT_DESC],
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

        // Filter by therapist IDs if provided (for coordinator group filtering)
        if (!empty($this->therapist_ids)) {
            $query->andWhere(['therapists.id' => $this->therapist_ids]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'therapists.id' => $this->id,
            'therapists.user_id' => $this->user_id,
            'therapists.specialization_id' => $this->specialization_id,
            'therapists.weekly_hours_contract' => $this->weekly_hours_contract,
            'therapists.is_active' => $this->is_active,
            'therapists.is_internal' => $this->is_internal,
        ]);

        $query
            ->andFilterWhere(['like', 'therapists.calendar_color', $this->calendar_color])
            ->andFilterWhere(['like', 'user_profiles.first_name', $this->first_name])
            ->andFilterWhere(['like', 'user_profiles.last_name', $this->last_name])
            ->andFilterWhere(['like', 'users.email', $this->email])
            ->andFilterWhere(['like', 'specializations.name', $this->specialization_name]);

        return $dataProvider;
    }

    /**
     * Ottiene la lista delle specializzazioni per il filtro dropdown
     * @return array
     */
    public static function getSpecializationsList()
    {
        return ArrayHelper::map(Specialization::find()->all(), 'name', 'name');
    }

    /**
     * Ottiene la lista degli stati per il filtro dropdown
     * @return array
     */
    public static function getStatusList()
    {
        return [
            1 => 'Attivo',
            0 => 'Inattivo',
        ];
    }

    /**
     * Ottiene la lista delle ore settimanali più comuni per il filtro dropdown
     * @return array
     */
    public static function getHoursList()
    {
        return [
            10 => '10 ore',
            15 => '15 ore',
            20 => '20 ore',
            25 => '25 ore',
            30 => '30 ore',
            35 => '35 ore',
            40 => '40 ore',
        ];
    }

    /**
     * Ottiene la lista dei tipi di terapista per il filtro dropdown
     * @return array
     */
    public static function getInternalList()
    {
        return [
            1 => 'Terapista Interno',
            0 => 'Consulente Esterno',
        ];
    }
}
