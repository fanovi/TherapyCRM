<?php

namespace frontend\models;

use yii\base\Model;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use common\models\Patient;
use Yii;

/**
 * PatientStatisticsSearch rappresenta il modello per i filtri delle statistiche pazienti
 */
class PatientStatisticsSearch extends Model
{
    public $gender = 'all';
    public $ageFrom;
    public $ageTo;
    public $districtId;
    public $regimeId;
    public $treatmentTypeIds = [];
    public $hasMultipleTreatments;
    public $status = 'active';
    public $dateFrom;
    public $dateTo;
    public $activePlanOnly = 1;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gender'], 'in', 'range' => ['M', 'F', 'N', 'all']],
            [['ageFrom', 'ageTo', 'districtId', 'regimeId'], 'integer', 'min' => 0],
            [['treatmentTypeIds'], 'each', 'rule' => ['integer']],
            [['hasMultipleTreatments', 'activePlanOnly'], 'boolean'],
            [['status'], 'in', 'range' => ['active', 'inactive', 'all']],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['ageFrom'], 'compare', 'compareAttribute' => 'ageTo', 'operator' => '<=', 'when' => function ($model) {
                return !empty($model->ageTo);
            }],
            [['dateFrom'], 'compare', 'compareAttribute' => 'dateTo', 'operator' => '<=', 'when' => function ($model) {
                return !empty($model->dateTo);
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'gender' => 'Genere',
            'ageFrom' => 'Età da',
            'ageTo' => 'Età a',
            'districtId' => 'Distretto',
            'regimeId' => 'Regime',
            'treatmentTypeIds' => 'Tipi di Trattamento',
            'hasMultipleTreatments' => 'Ha Trattamenti Multipli',
            'activePlanOnly' => 'Solo pazienti con piano terapeutico attivo',
            'status' => 'Stato',
            'dateFrom' => 'Data Creazione da',
            'dateTo' => 'Data Creazione a',
        ];
    }

    /**
     * Crea la query base per le statistiche pazienti con filtri applicati
     *
     * @return Query
     */
    public function getStatisticsQuery()
    {
        $query = (new Query())
            ->from('statistics_patients_current_v sp');

        $this->applyCommonFilters($query);

        return $query;
    }

    /**
     * Applica i filtri condivisi a una query che espone l'alias `sp`.
     */
    public function applyCommonFilters($query)
    {
        if ($this->resolvePlanStatus() === 'active') {
            $query->andWhere(['sp.piano_terapeutico_attivo' => 'SI']);
        } elseif ($this->resolvePlanStatus() === 'inactive') {
            $query->andWhere(['sp.piano_terapeutico_attivo' => 'NO']);
        }

        if ($this->gender && $this->gender !== 'all') {
            $query->andWhere(['sp.gender' => $this->gender]);
        }

        if ($this->ageFrom !== null && $this->ageFrom !== '') {
            $query->andWhere(['>=', 'sp.age', $this->ageFrom]);
        }

        if ($this->ageTo !== null && $this->ageTo !== '') {
            $query->andWhere(['<=', 'sp.age', $this->ageTo]);
        }

        if ($this->dateFrom) {
            $query->andWhere(['>=', 'DATE(sp.created_at)', $this->dateFrom]);
        }

        if ($this->dateTo) {
            $query->andWhere(['<=', 'DATE(sp.created_at)', $this->dateTo]);
        }

        if ($this->hasMultipleTreatments !== null && $this->hasMultipleTreatments !== '') {
            if ($this->hasMultipleTreatments) {
                $query->andWhere(['>', 'sp.trattamenti_count_no_aba', 1]);
            } else {
                $query->andWhere(['<=', 'sp.trattamenti_count_no_aba', 1]);
            }
        }

        if (!empty($this->treatmentTypeIds) && is_array($this->treatmentTypeIds)) {
            $subQuery = (new Query())
                ->select('tp.patient_id')
                ->distinct()
                ->from('plan_therapies pt')
                ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
                ->where(['in', 'pt.treatment_type_id', $this->treatmentTypeIds])
                ->andWhere(['tp.status' => 'active'])
                ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
                ->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);

            $query->andWhere(['in', 'sp.id', $subQuery]);
        }

        if ($this->regimeId) {
            $regimePatients = (new Query())
                ->select('tp.patient_id')
                ->distinct()
                ->from('therapeutic_plans tp')
                ->where(['tp.regime_id' => $this->regimeId])
                ->andWhere(['tp.status' => 'active'])
                ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
                ->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);
            $query->andWhere(['in', 'sp.id', $regimePatients]);
        }

        if ($this->districtId) {
            $districtPatients = (new Query())
                ->select('p_district.id')
                ->from(['p_district' => 'patients'])
                ->where(['p_district.district_id' => $this->districtId]);
            $query->andWhere(['in', 'sp.id', $districtPatients]);
        }
    }

    /**
     * Stato piano da usare nelle query: active|inactive|all.
     */
    public function resolvePlanStatus()
    {
        if ($this->status === 'inactive' || $this->status === 'all' || $this->status === 'active') {
            return $this->status;
        }

        // Retrocompatibilità con vecchi URL che usavano solo la checkbox
        return $this->activePlanOnly ? 'active' : 'all';
    }

    /**
     * Ottiene le opzioni per il filtro genere
     *
     * @return array
     */
    public static function getGenderOptions()
    {
        return [
            'all' => 'Tutti',
            'M' => 'Maschio',
            'F' => 'Femmina',
            'N' => 'Non specificato',
        ];
    }

    /**
     * Ottiene le opzioni per il filtro stato
     *
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            'all' => 'Tutti',
            'active' => 'Con piano attivo',
            'inactive' => 'Senza piano attivo',
        ];
    }

    /**
     * Ottiene la distribuzione per età in gruppi
     *
     * @return array
     */
    public function getAgeGroupDistribution()
    {
        $query = $this->getStatisticsQuery();
        
        return $query->select([
            'age_group' => "CASE 
                WHEN age < 18 THEN '0-17'
                WHEN age < 30 THEN '18-29'
                WHEN age < 50 THEN '30-49'
                WHEN age < 65 THEN '50-64'
                ELSE '65+'
            END",
            'count' => 'COUNT(*)',
            'avg_age' => 'ROUND(AVG(age), 1)'
        ])
        ->groupBy('age_group')
        ->orderBy('age_group')
        ->all();
    }

    /**
     * Ottiene la distribuzione per genere
     *
     * @return array
     */
    public function getGenderDistribution()
    {
        $query = $this->getStatisticsQuery();

        $results = $query->select([
            'sp.gender',
            'COUNT(*) as count'
        ])
        ->groupBy('sp.gender')
        ->all();
        
        // Aggiungi label per il genere
        foreach ($results as &$result) {
            switch ($result['gender']) {
                case 'M':
                    $result['gender_label'] = 'Maschio';
                    break;
                case 'F':
                    $result['gender_label'] = 'Femmina';
                    break;
                case 'N':
                    $result['gender_label'] = 'Non specificato';
                    break;
                default:
                    $result['gender_label'] = 'N/D';
            }
        }
        
        return $results;
    }

    /**
     * Ottiene pazienti con trattamenti multipli (escludendo ABA)
     *
     * @return array
     */
    public function getMultiTreatmentPatients()
    {
        $query = $this->getStatisticsQuery();

        return $query->select([
            'sp.id',
            'sp.first_name',
            'sp.last_name',
            'sp.trattamenti_count_no_aba as treatment_count'
        ])
        ->andWhere(['>', 'sp.trattamenti_count_no_aba', 1])
        ->orderBy(['sp.trattamenti_count_no_aba' => SORT_DESC, 'sp.last_name' => SORT_ASC])
        ->all();
    }

    /**
     * Valida e pulisce i parametri di ricerca
     *
     * @param array $params
     * @return bool
     */
    public function load($params, $formName = null)
    {
        $loaded = parent::load($params, $formName);

        if ($this->gender === '') {
            $this->gender = 'all';
        }
        if ($this->status === '') {
            $this->status = 'all';
        }
        if ($this->dateFrom === '') {
            $this->dateFrom = null;
        }
        if ($this->dateTo === '') {
            $this->dateTo = null;
        }
        if ($this->ageFrom === '') {
            $this->ageFrom = null;
        }
        if ($this->ageTo === '') {
            $this->ageTo = null;
        }
        if (empty($this->treatmentTypeIds)) {
            $this->treatmentTypeIds = [];
        }

        return $loaded;
    }

    /**
     * Crea un DataProvider per la lista dei pazienti con i filtri applicati
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        // Usa direttamente la query dalla materialized view invece del modello Patient
        $query = (new Query())
            ->select([
                'sp.id',
                'sp.first_name', 
                'sp.last_name',
                'sp.birth_date',
                'sp.gender',
                'sp.created_at',
                'p.district_id',
                'sp.age',
                'sp.piano_terapeutico_attivo',
                'sp.trattamenti_count_no_aba',
                "CASE WHEN d.asl_reference IS NOT NULL AND d.asl_reference != '' AND LOCATE(d.asl_reference, d.name) = 0 THEN CONCAT(d.asl_reference, ' - ', d.name) ELSE d.name END AS district_name"
            ])
            ->from('statistics_patients_current_v sp')
            ->leftJoin('patients p', 'sp.id = p.id')
            ->leftJoin('districts d', 'p.district_id = d.id');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'last_name' => SORT_ASC,
                    'first_name' => SORT_ASC,
                ],
                'attributes' => [
                    'id' => [
                        'asc' => ['sp.id' => SORT_ASC],
                        'desc' => ['sp.id' => SORT_DESC],
                    ],
                    'first_name' => [
                        'asc' => ['sp.first_name' => SORT_ASC],
                        'desc' => ['sp.first_name' => SORT_DESC],
                    ],
                    'last_name' => [
                        'asc' => ['sp.last_name' => SORT_ASC],
                        'desc' => ['sp.last_name' => SORT_DESC],
                    ],
                    'age' => [
                        'asc' => ['sp.age' => SORT_ASC],
                        'desc' => ['sp.age' => SORT_DESC],
                    ],
                    'gender' => [
                        'asc' => ['sp.gender' => SORT_ASC],
                        'desc' => ['sp.gender' => SORT_DESC],
                    ],
                    'created_at' => [
                        'asc' => ['sp.created_at' => SORT_ASC],
                        'desc' => ['sp.created_at' => SORT_DESC],
                    ],
                    'piano_terapeutico_attivo' => [
                        'asc' => ['sp.piano_terapeutico_attivo' => SORT_ASC],
                        'desc' => ['sp.piano_terapeutico_attivo' => SORT_DESC],
                    ],
                    'trattamenti_count_no_aba' => [
                        'asc' => ['sp.trattamenti_count_no_aba' => SORT_ASC],
                        'desc' => ['sp.trattamenti_count_no_aba' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->andWhere('0=1');
            return $dataProvider;
        }

        $this->applyCommonFilters($query);

        return $dataProvider;
    }
}