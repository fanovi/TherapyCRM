<?php

namespace frontend\models;

use yii\base\Model;
use yii\db\Query;
use Yii;

/**
 * PatientStatisticsSearch rappresenta il modello per i filtri delle statistiche pazienti
 */
class PatientStatisticsSearch extends Model
{
    public $gender;
    public $ageFrom;
    public $ageTo;
    public $districtId;
    public $regimeId;
    public $treatmentTypeIds = [];
    public $hasMultipleTreatments;
    public $status;
    public $dateFrom;
    public $dateTo;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gender'], 'in', 'range' => ['M', 'F', 'N', 'all']],
            [['ageFrom', 'ageTo', 'districtId', 'regimeId'], 'integer', 'min' => 0],
            [['treatmentTypeIds'], 'each', 'rule' => ['integer']],
            [['hasMultipleTreatments'], 'boolean'],
            [['status'], 'in', 'range' => ['active', 'dismissed', 'all']],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['ageFrom'], 'compare', 'compareAttribute' => 'ageTo', 'operator' => '<=', 'when' => function ($model) {
                return !empty($model->ageTo);
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
            ->from('statistics_patients_mv sp');

        // Applica filtri
        if ($this->gender && $this->gender !== 'all') {
            $query->andWhere(['sp.gender' => $this->gender]);
        }

        if ($this->ageFrom !== null && $this->ageFrom !== '') {
            $query->andWhere(['>=', 'sp.age', $this->ageFrom]);
        }

        if ($this->ageTo !== null && $this->ageTo !== '') {
            $query->andWhere(['<=', 'sp.age', $this->ageTo]);
        }

        if ($this->status && $this->status !== 'all') {
            if ($this->status === 'active') {
                $query->andWhere(['sp.piano_terapeutico_attivo' => 'SI']);
            } elseif ($this->status === 'dismissed') {
                $query->andWhere(['sp.dismesso' => 'SI']);
            }
        }

        if ($this->dateFrom) {
            $query->andWhere(['>=', 'DATE(sp.created_at)', $this->dateFrom]);
        }

        if ($this->dateTo) {
            $query->andWhere(['<=', 'DATE(sp.created_at)', $this->dateTo]);
        }

        // Filtro per trattamenti multipli
        if ($this->hasMultipleTreatments !== null) {
            if ($this->hasMultipleTreatments) {
                $query->andWhere(['>', 'sp.trattamenti_count_no_aba', 1]);
            } else {
                $query->andWhere(['<=', 'sp.trattamenti_count_no_aba', 1]);
            }
        }

        // Filtro per tipi di trattamento specifici
        if (!empty($this->treatmentTypeIds) && is_array($this->treatmentTypeIds)) {
            $subQuery = (new Query())
                ->select('tp.patient_id')
                ->distinct()
                ->from('plan_therapies pt')
                ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
                ->where(['in', 'pt.treatment_type_id', $this->treatmentTypeIds]);

            $query->andWhere(['in', 'sp.id', $subQuery]);
        }

        return $query;
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
            'active' => 'Pazienti Attivi',
            'dismissed' => 'Pazienti Dimessi',
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
        ->where(['>', 'sp.trattamenti_count_no_aba', 1])
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

        // Pulizia parametri stringa vuota
        if ($this->gender === '') $this->gender = 'all';
        if ($this->status === '') $this->status = 'all';
        if ($this->dateFrom === '') $this->dateFrom = null;
        if ($this->dateTo === '') $this->dateTo = null;
        if ($this->ageFrom === '') $this->ageFrom = null;
        if ($this->ageTo === '') $this->ageTo = null;
        if (empty($this->treatmentTypeIds)) $this->treatmentTypeIds = [];

        return $loaded;
    }
}