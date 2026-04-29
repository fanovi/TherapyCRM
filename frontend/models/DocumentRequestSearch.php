<?php

namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\DocumentRequest;
use common\models\RequestStatus;

/**
 * DocumentRequestSearch represents the model behind the search form of `common\models\DocumentRequest`.
 */
class DocumentRequestSearch extends DocumentRequest
{
    // Attributi aggiuntivi per la ricerca
    public $account_patient_name;
    public $patient_name;
    public $request_type_name;
    public $status_name;
    public $created_by_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'account_patient_id', 'patient_id', 'request_type_id', 'therapeutic_plan_id', 'therapy_id', 'status'], 'integer'],
            [['notes', 'created_at', 'account_patient_name', 'patient_name', 'request_type_name', 'status_name', 'created_by_name'], 'safe'],
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
        $query = DocumentRequest::find()
            ->joinWith([
                'accountPatient.user.profile',
                'patient',
                'requestType',
                'requestStatus'
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
                    'status',
                    'created_at',
                    'account_patient_name' => [
                        'asc' => ['user_profiles.last_name' => SORT_ASC, 'user_profiles.first_name' => SORT_ASC],
                        'desc' => ['user_profiles.last_name' => SORT_DESC, 'user_profiles.first_name' => SORT_DESC],
                    ],
                    'patient_name' => [
                        'asc' => ['patients.last_name' => SORT_ASC, 'patients.first_name' => SORT_ASC],
                        'desc' => ['patients.last_name' => SORT_DESC, 'patients.first_name' => SORT_DESC],
                    ],
                    'request_type_name' => [
                        'asc' => ['request_types.name' => SORT_ASC],
                        'desc' => ['request_types.name' => SORT_DESC],
                    ],
                    'status_name' => [
                        'asc' => ['request_statuses.name' => SORT_ASC],
                        'desc' => ['request_statuses.name' => SORT_DESC],
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
            'document_requests.id' => $this->id,
            'document_requests.account_patient_id' => $this->account_patient_id,
            'document_requests.patient_id' => $this->patient_id,
            'document_requests.request_type_id' => $this->request_type_id,
            'document_requests.therapeutic_plan_id' => $this->therapeutic_plan_id,
            'document_requests.therapy_id' => $this->therapy_id,
            'document_requests.status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'document_requests.notes', $this->notes])
            ->andFilterWhere(['like', 'document_requests.created_at', $this->created_at]);

        // Filtro per nome account paziente (richiedente)
        if ($this->account_patient_name) {
            $query->andWhere([
                'or',
                ['like', 'user_profiles.first_name', $this->account_patient_name],
                ['like', 'user_profiles.last_name', $this->account_patient_name],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $this->account_patient_name],
            ]);
        }

        // Filtro per nome paziente
        if ($this->patient_name) {
            $query->andWhere([
                'or',
                ['like', 'patients.first_name', $this->patient_name],
                ['like', 'patients.last_name', $this->patient_name],
                ['like', "CONCAT(patients.first_name, ' ', patients.last_name)", $this->patient_name],
            ]);
        }

        // Filtro per tipo richiesta
        if ($this->request_type_name) {
            $query->andFilterWhere(['like', 'request_types.name', $this->request_type_name]);
        }

        // Filtro per stato
        if ($this->status_name) {
            $query->andFilterWhere(['like', 'request_statuses.name', $this->status_name]);
        }

        return $dataProvider;
    }

    /**
     * Restituisce le opzioni per il filtro degli stati
     * @return array
     */
    public function getStatusOptions()
    {
        return RequestStatus::getDropdownOptions();
    }

    /**
     * Restituisce solo le richieste non lette (stato = 1)
     * @param array $params
     * @return ActiveDataProvider
     */
    public function searchUnread($params)
    {
        $dataProvider = $this->search($params);
        $dataProvider->query->andWhere(['document_requests.status' => RequestStatus::STATUS_INVIATA]);
        
        return $dataProvider;
    }
} 