<?php

namespace frontend\models;

use yii\base\Model;
use yii\db\Query;
use yii\db\Expression;
use Yii;

/**
 * TreatmentStatisticsSearch rappresenta il modello per i filtri delle statistiche trattamenti
 */
class TreatmentStatisticsSearch extends Model
{
    public $treatmentIds = [];
    public $combinationMode = 'any'; // any, all, exact
    public $dateFrom;
    public $dateTo;
    public $includeInactive = false;
    public $regimeId;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['treatmentIds'], 'each', 'rule' => ['integer']],
            [['combinationMode'], 'in', 'range' => ['any', 'all', 'exact']],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['includeInactive'], 'boolean'],
            [['regimeId'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'treatmentIds' => 'Trattamenti',
            'combinationMode' => 'Modalità Combinazione',
            'dateFrom' => 'Data da',
            'dateTo' => 'Data a',
            'includeInactive' => 'Includi Piani Inattivi',
            'regimeId' => 'Regime',
        ];
    }

    /**
     * Ottiene statistiche dei trattamenti con i filtri applicati
     *
     * @return array
     */
    public function getStatistics()
    {
        if (empty($this->treatmentIds)) {
            return $this->getAllTreatmentStatistics();
        }

        return $this->getCombinationStatistics();
    }

    /**
     * Ottiene statistiche per tutti i trattamenti
     *
     * @return array
     */
    protected function getAllTreatmentStatistics()
    {
        $query = (new Query())
            ->select([
                'tt.id',
                'tt.name',
                'tt.code',
                'COUNT(DISTINCT tp.patient_id) as patient_count',
                'COUNT(pt.id) as therapy_count',
                'SUM(pt.weekly_hours) as total_hours',
                'AVG(pt.weekly_hours) as avg_hours'
            ])
            ->from('treatment_types tt')
            ->leftJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->leftJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->groupBy(['tt.id', 'tt.name', 'tt.code'])
            ->orderBy(['patient_count' => SORT_DESC]);

        // Applica filtri
        $this->applyDateFilters($query);
        $this->applyStatusFilters($query);

        return $query->all();
    }

    /**
     * Ottiene statistiche per combinazioni di trattamenti
     *
     * @return array
     */
    protected function getCombinationStatistics()
    {
        switch ($this->combinationMode) {
            case 'any':
                return $this->getAnyTreatmentStats();
            case 'all':
                return $this->getAllTreatmentStats();
            case 'exact':
                return $this->getExactTreatmentStats();
            default:
                return [];
        }
    }

    /**
     * Pazienti che hanno almeno uno dei trattamenti selezionati
     *
     * @return array
     */
    protected function getAnyTreatmentStats()
    {
        $query = (new Query())
            ->select([
                'tp.patient_id',
                'p.first_name',
                'p.last_name',
                'treatment_count' => 'COUNT(DISTINCT pt.treatment_type_id)',
                'treatments' => new Expression('GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name)')
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['in', 'pt.treatment_type_id', $this->treatmentIds])
            ->groupBy(['tp.patient_id', 'p.first_name', 'p.last_name'])
            ->orderBy(['treatment_count' => SORT_DESC, 'p.last_name' => SORT_ASC]);

        $this->applyDateFilters($query);
        $this->applyStatusFilters($query);

        return $query->all();
    }

    /**
     * Pazienti che hanno tutti i trattamenti selezionati
     *
     * @return array
     */
    protected function getAllTreatmentStats()
    {
        $treatmentCount = count($this->treatmentIds);
        
        $query = (new Query())
            ->select([
                'tp.patient_id',
                'p.first_name',
                'p.last_name',
                'treatment_count' => 'COUNT(DISTINCT pt.treatment_type_id)',
                'treatments' => new Expression('GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name)')
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['in', 'pt.treatment_type_id', $this->treatmentIds])
            ->groupBy(['tp.patient_id', 'p.first_name', 'p.last_name'])
            ->having(['=', new Expression('COUNT(DISTINCT pt.treatment_type_id)'), $treatmentCount])
            ->orderBy(['p.last_name' => SORT_ASC]);

        $this->applyDateFilters($query);
        $this->applyStatusFilters($query);

        return $query->all();
    }

    /**
     * Pazienti che hanno esattamente i trattamenti selezionati (niente di più, niente di meno)
     *
     * @return array
     */
    protected function getExactTreatmentStats()
    {
        $treatmentCount = count($this->treatmentIds);
        
        // Prima trova pazienti con tutti i trattamenti richiesti
        $patientsWithRequired = (new Query())
            ->select('tp.patient_id')
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->where(['in', 'pt.treatment_type_id', $this->treatmentIds])
            ->groupBy('tp.patient_id')
            ->having(['=', new Expression('COUNT(DISTINCT pt.treatment_type_id)'), $treatmentCount]);

        // Applica filtri
        $this->applyDateFilters($patientsWithRequired);
        $this->applyStatusFilters($patientsWithRequired);

        $patientIds = array_column($patientsWithRequired->all(), 'patient_id');
        
        if (empty($patientIds)) {
            return [];
        }

        // Ora verifica che questi pazienti non abbiano altri trattamenti
        $query = (new Query())
            ->select([
                'tp.patient_id',
                'p.first_name',
                'p.last_name',
                'total_treatments' => 'COUNT(DISTINCT pt.treatment_type_id)',
                'treatments' => new Expression('GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name)')
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['in', 'tp.patient_id', $patientIds])
            ->groupBy(['tp.patient_id', 'p.first_name', 'p.last_name'])
            ->having(['=', 'total_treatments', $treatmentCount])
            ->orderBy(['p.last_name' => SORT_ASC]);

        return $query->all();
    }

    /**
     * Applica filtri di data alla query
     *
     * @param Query $query
     */
    protected function applyDateFilters($query)
    {
        if ($this->dateFrom) {
            $query->andWhere(['>=', 'tp.start_date', $this->dateFrom]);
        }
        
        if ($this->dateTo) {
            $query->andWhere(['<=', 'tp.start_date', $this->dateTo]);
        }
    }

    /**
     * Applica filtri di stato alla query
     *
     * @param Query $query
     */
    protected function applyStatusFilters($query)
    {
        if (!$this->includeInactive) {
            $query->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);
        }

        if ($this->regimeId) {
            $query->andWhere(['tp.regime_id' => $this->regimeId]);
        }
    }

    /**
     * Ottiene le opzioni per la modalità di combinazione
     *
     * @return array
     */
    public static function getCombinationModeOptions()
    {
        return [
            'any' => 'Almeno uno',
            'all' => 'Tutti',
            'exact' => 'Esattamente questi',
        ];
    }

    /**
     * Ottiene il ranking dei trattamenti per numero di pazienti
     *
     * @param int $limit
     * @return array
     */
    public function getTreatmentRanking($limit = 10)
    {
        $query = (new Query())
            ->select([
                'tt.id',
                'tt.name',
                'tt.code',
                'COUNT(DISTINCT tp.patient_id) as patient_count'
            ])
            ->from('treatment_types tt')
            ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->groupBy(['tt.id', 'tt.name', 'tt.code'])
            ->orderBy(['patient_count' => SORT_DESC])
            ->limit($limit);

        $this->applyDateFilters($query);
        $this->applyStatusFilters($query);

        return $query->all();
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
        
        // Pulizia parametri
        if (empty($this->treatmentIds)) $this->treatmentIds = [];
        if ($this->dateFrom === '') $this->dateFrom = null;
        if ($this->dateTo === '') $this->dateTo = null;
        if ($this->combinationMode === '') $this->combinationMode = 'any';
        
        return $loaded;
    }
}