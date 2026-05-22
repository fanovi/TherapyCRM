<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Patient;

/**
 * PatientSearch represents the model behind the search form of `common\models\Patient`.
 */
class PatientSearch extends Patient
{
    /**
     * Filtra i pazienti agli "appartenenti" a uno o piu' terapisti.
     * Un paziente appartiene a un terapista se ha almeno un appuntamento
     * (da piano terapeutico oppure privato) con quel terapista.
     *
     * Non e' un attributo persistito: viene impostato dal controller
     * (es. PatientController::actionMyGroup per il coordinatore).
     *
     * @var int[]|null
     */
    public $therapist_ids;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'district_id', 'born_in_italy'], 'integer'],
            [[
                'first_name', 'last_name', 'fiscal_code', 'birth_date', 'notes', 'created_at',
                'birth_city', 'birth_province_name', 'birth_province_code',
                'residence_address', 'residence_city', 'residence_province_name', 'residence_province_code', 'residence_postal_code',
                'phone_number'
            ], 'safe'],
            [['therapist_ids'], 'safe'],
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
    public function searchDataProvider($params)
    {
        $query = Patient::find()
            ->with('district')
            ->orderBy('last_name, first_name');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'attributes' => [
                    'id',
                    'first_name',
                    'last_name',
                    'fiscal_code',
                    'birth_date',
                    'district_id',
                    'created_at',
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Restrizione "pazienti dei miei terapisti" (coordinator).
        // therapist_ids non e' filtrabile dall'utente via querystring: e'
        // popolata solo da actionMyGroup. Se vuota -> nessun risultato (fail-closed).
        if ($this->therapist_ids !== null) {
            $allowedPatientIds = self::patientIdsForTherapists($this->therapist_ids);
            if (empty($allowedPatientIds)) {
                $query->andWhere('0=1');
            } else {
                $query->andWhere(['id' => $allowedPatientIds]);
            }
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'district_id' => $this->district_id,
            'birth_date' => $this->birth_date,
            'born_in_italy' => $this->born_in_italy,
        ]);

        // first_name e last_name: match per prefisso (LIKE 'q%') anziche' contains.
        if (!empty($this->first_name)) {
            $query->andWhere(['like', 'first_name', addcslashes($this->first_name, '%_\\') . '%', false]);
        }
        if (!empty($this->last_name)) {
            $query->andWhere(['like', 'last_name', addcslashes($this->last_name, '%_\\') . '%', false]);
        }

        $query->andFilterWhere(['like', 'fiscal_code', $this->fiscal_code])
            ->andFilterWhere(['like', 'notes', $this->notes])
            ->andFilterWhere(['like', 'birth_city', $this->birth_city])
            ->andFilterWhere(['like', 'birth_province_name', $this->birth_province_name])
            ->andFilterWhere(['like', 'birth_province_code', $this->birth_province_code])
            ->andFilterWhere(['like', 'residence_address', $this->residence_address])
            ->andFilterWhere(['like', 'residence_city', $this->residence_city])
            ->andFilterWhere(['like', 'residence_province_name', $this->residence_province_name])
            ->andFilterWhere(['like', 'residence_province_code', $this->residence_province_code])
            ->andFilterWhere(['like', 'residence_postal_code', $this->residence_postal_code])
            ->andFilterWhere(['like', 'phone_number', $this->phone_number]);

        return $dataProvider;
    }

    /**
     * Restituisce gli id dei pazienti "assegnati" agli `$therapistIds` indicati,
     * cioe' che hanno almeno un appuntamento non cancellato (da piano terapeutico
     * o privato) con uno di quei terapisti.
     *
     * Replica il pattern gia' usato in `api/controllers/CalendarController::actionTherapistPatients`
     * (LEFT JOIN su plan_therapies + therapeutic_plans, OR su patient_id) per
     * mantenere coerente la nozione di "paziente del terapista" in tutta l'app.
     *
     * Utilizzata da:
     *  - PatientController::actionMyGroup (coordinatore -> "I Miei Pazienti")
     *  - SearchController::searchPatients (limitazione searchbar globale)
     *
     * @param int[] $therapistIds
     * @return int[]
     */
    public static function patientIdsForTherapists(array $therapistIds)
    {
        $therapistIds = array_values(array_filter(array_map('intval', $therapistIds)));
        if (empty($therapistIds)) {
            return [];
        }

        $rows = (new \yii\db\Query())
            ->select(new \yii\db\Expression('COALESCE(a.patient_id, tp.patient_id) AS patient_id'))
            ->distinct()
            ->from(['a' => '{{%appointments}}'])
            ->leftJoin(['pt' => '{{%plan_therapies}}'], 'pt.id = a.plan_therapy_id')
            ->leftJoin(['tp' => '{{%therapeutic_plans}}'], 'tp.id = pt.therapeutic_plan_id')
            ->where(['a.therapist_id' => $therapistIds])
            ->andWhere(['!=', 'a.status', \common\models\Appointment::STATUS_CANCELLED])
            ->andWhere(['or',
                ['IS NOT', 'a.patient_id', null],
                ['IS NOT', 'tp.patient_id', null],
            ])
            ->column();

        return array_values(array_unique(array_filter(array_map('intval', $rows))));
    }
} 