<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\AccountPatient;

/**
 * AccountPatientSearch represents the model behind the search form of `common\models\AccountPatient`.
 */
class AccountPatientSearch extends AccountPatient
{
    public $patient_name;
    public $user_email;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'patient_id', 'has_parental_authority', 'can_view_appointments', 'can_cancel_appointments'], 'integer'],
            [['relationship_type', 'created_at', 'patient_name', 'user_email'], 'safe'],
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
        $query = AccountPatient::find()
            ->joinWith(['patient', 'user', 'user.profile'])
            ->orderBy(['account_patients.created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'attributes' => [
                    'id',
                    'user_id',
                    'patient_id',
                    'relationship_type',
                    'has_parental_authority',
                    'created_at',
                    'patient_name' => [
                        'asc' => ['patients.last_name' => SORT_ASC, 'patients.first_name' => SORT_ASC],
                        'desc' => ['patients.last_name' => SORT_DESC, 'patients.first_name' => SORT_DESC],
                    ],
                    'user_email' => [
                        'asc' => ['user.email' => SORT_ASC],
                        'desc' => ['user.email' => SORT_DESC],
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
            'account_patients.id' => $this->id,
            'account_patients.user_id' => $this->user_id,
            'account_patients.patient_id' => $this->patient_id,
            'account_patients.has_parental_authority' => $this->has_parental_authority,
            'account_patients.can_view_appointments' => $this->can_view_appointments,
            'account_patients.can_cancel_appointments' => $this->can_cancel_appointments,
        ]);

        $query->andFilterWhere(['like', 'account_patients.relationship_type', $this->relationship_type]);

        // Filter by patient name
        if (!empty($this->patient_name)) {
            $query->andFilterWhere(['or',
                ['like', 'patients.first_name', $this->patient_name],
                ['like', 'patients.last_name', $this->patient_name],
            ]);
        }

        // Filter by user email
        if (!empty($this->user_email)) {
            $query->andFilterWhere(['like', 'user.email', $this->user_email]);
        }

        return $dataProvider;
    }
}
