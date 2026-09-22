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
    public $combinationMode = 'any';
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
            'treatmentIds' => 'Trattamenti',
            'combinationMode' => 'Modalità Combinazione',
            'dateFrom' => 'Data da',
            'dateTo' => 'Data a',
            'includeInactive' => 'Includi Piani Inattivi',
            'regimeId' => 'Regime',
        ];
    }

    /**
     * Lista pazienti che corrispondono a trattamenti + modalità combinazione.
     *
     * @return array
     */
    public function getStatistics()
    {
        if (empty($this->treatmentIds)) {
            return [];
        }

        $patientIdsQuery = $this->getMatchingPatientIdQuery();
        $patientIds = $patientIdsQuery->column();
        if (empty($patientIds)) {
            return [];
        }

        $query = (new Query())
            ->select([
                'tp.patient_id',
                'p.first_name',
                'p.last_name',
                'treatment_count' => 'COUNT(DISTINCT pt.treatment_type_id)',
                'treatments' => new Expression('GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name)'),
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['in', 'tp.patient_id', $patientIds])
            ->groupBy(['tp.patient_id', 'p.first_name', 'p.last_name'])
            ->orderBy(['treatment_count' => SORT_DESC, 'p.last_name' => SORT_ASC]);

        $this->applyCommonFilters($query);

        return $query->all();
    }

    /**
     * Filtri condivisi su query con alias `tp` (e `pt` se presente).
     *
     * @param Query $query
     */
    public function applyCommonFilters($query)
    {
        if (!$this->includeInactive) {
            $today = date('Y-m-d');
            $query->andWhere(['tp.status' => 'active'])
                ->andWhere(['<=', 'tp.start_date', $today])
                ->andWhere(['>=', 'tp.end_date', $today]);
        }

        if ($this->dateFrom || $this->dateTo) {
            $this->applyPeriodOverlap($query);
        }

        if ($this->regimeId) {
            $query->andWhere(['tp.regime_id' => $this->regimeId]);
        }
    }

    /**
     * Piani la cui validità si sovrappone al periodo [dateFrom, dateTo].
     *
     * @param Query $query
     */
    public function applyPeriodOverlap($query)
    {
        if ($this->dateFrom) {
            $query->andWhere(['>=', 'tp.end_date', $this->dateFrom]);
        }
        if ($this->dateTo) {
            $query->andWhere(['<=', 'tp.start_date', $this->dateTo]);
        }
    }

    /**
     * Pazienti che soddisfano trattamenti selezionati e combinationMode.
     * Null se non c'è filtro tipi.
     *
     * @return Query|null
     */
    public function getMatchingPatientIdQuery()
    {
        if (empty($this->treatmentIds) || !is_array($this->treatmentIds)) {
            return null;
        }

        $ids = array_values(array_unique(array_map('intval', $this->treatmentIds)));
        if (empty($ids)) {
            return null;
        }

        $query = (new Query())
            ->select('tp.patient_id')
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id');

        $this->applyCommonFilters($query);

        $mode = $this->combinationMode ?: 'any';
        $n = count($ids);
        $inList = implode(',', $ids);

        if ($mode === 'any') {
            $query->andWhere(['in', 'pt.treatment_type_id', $ids])
                ->distinct();
            return $query;
        }

        $selectedCount = "COUNT(DISTINCT CASE WHEN pt.treatment_type_id IN ($inList) THEN pt.treatment_type_id END)";
        $query->groupBy('tp.patient_id')
            ->having(new Expression("$selectedCount = $n"));

        if ($mode === 'exact') {
            $query->andHaving(new Expression('COUNT(DISTINCT pt.treatment_type_id) = ' . $n));
        }

        return $query;
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
     * {@inheritdoc}
     */
    public function load($params, $formName = null)
    {
        $loaded = parent::load($params, $formName);

        if (empty($this->treatmentIds)) {
            $this->treatmentIds = [];
        }
        if ($this->dateFrom === '') {
            $this->dateFrom = null;
        }
        if ($this->dateTo === '') {
            $this->dateTo = null;
        }
        if ($this->combinationMode === '') {
            $this->combinationMode = 'any';
        }
        $this->includeInactive = filter_var($this->includeInactive, FILTER_VALIDATE_BOOLEAN);

        return $loaded;
    }
}
