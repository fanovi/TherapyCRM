<?php

namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Complaint;

/**
 * ComplaintSearch represents the model behind the search form of `common\models\Complaint`.
 */
class ComplaintSearch extends Complaint
{
    // Attributi aggiuntivi per la ricerca
    public $patient_name;
    public $account_name;
    public $date_from;
    public $date_to;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'account_id', 'patient_id'], 'integer'],
            [['title', 'description', 'created_at', 'patient_name', 'account_name', 'date_from', 'date_to'], 'safe'],
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
        $query = Complaint::find()
            ->joinWith([
                'patient',
                'account.profile'
            ]);

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'attributes' => [
                    'id',
                    'title',
                    'created_at',
                    'patient_name' => [
                        'asc' => ['patients.last_name' => SORT_ASC, 'patients.first_name' => SORT_ASC],
                        'desc' => ['patients.last_name' => SORT_DESC, 'patients.first_name' => SORT_DESC],
                    ],
                    'account_name' => [
                        'asc' => ['user_profiles.last_name' => SORT_ASC, 'user_profiles.first_name' => SORT_ASC],
                        'desc' => ['user_profiles.last_name' => SORT_DESC, 'user_profiles.first_name' => SORT_DESC],
                    ],
                ],
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
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
            'complaint.id' => $this->id,
            'complaint.account_id' => $this->account_id,
            'complaint.patient_id' => $this->patient_id,
        ]);

        $query->andFilterWhere(['like', 'complaint.title', $this->title])
            ->andFilterWhere(['like', 'complaint.description', $this->description]);

        // Filtro per nome paziente
        if ($this->patient_name) {
            $query->andWhere([
                'or',
                ['like', 'patients.first_name', $this->patient_name],
                ['like', 'patients.last_name', $this->patient_name],
                ['like', "CONCAT(patients.first_name, ' ', patients.last_name)", $this->patient_name],
            ]);
        }

        // Filtro per nome account
        if ($this->account_name) {
            $query->andWhere([
                'or',
                ['like', 'user_profiles.first_name', $this->account_name],
                ['like', 'user_profiles.last_name', $this->account_name],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $this->account_name],
            ]);
        }

        // Filtro per data di creazione
        if ($this->date_from) {
            $query->andFilterWhere(['>=', 'complaint.created_at', $this->date_from . ' 00:00:00']);
        }
        
        if ($this->date_to) {
            $query->andFilterWhere(['<=', 'complaint.created_at', $this->date_to . ' 23:59:59']);
        }

        return $dataProvider;
    }
}
