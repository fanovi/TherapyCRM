<?php

namespace frontend\models;

use yii\base\Model;
use yii\db\Query;
use common\models\TherapeuticPlan;

/**
 * PlanStatisticsSearch rappresenta il modello per i filtri delle statistiche dei piani terapeutici
 */
class PlanStatisticsSearch extends Model
{
    public $status = 'active';
    public $minDuration;
    public $maxDuration;
    public $dateFrom;
    public $dateTo;
    public $therapistId;
    public $patientId;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'in', 'range' => array_keys(self::getStatusOptions())],
            [['minDuration', 'maxDuration', 'therapistId', 'patientId'], 'integer', 'min' => 0],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['dateFrom'], 'compare', 'compareAttribute' => 'dateTo', 'operator' => '<=', 'when' => function ($model) {
                return !empty($model->dateTo);
            }],
            [['minDuration'], 'compare', 'compareAttribute' => 'maxDuration', 'operator' => '<=', 'when' => function ($model) {
                return $model->maxDuration !== null && $model->maxDuration !== '';
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'status' => 'Stato piano',
            'minDuration' => 'Durata minima (giorni)',
            'maxDuration' => 'Durata massima (giorni)',
            'dateFrom' => 'Data inizio',
            'dateTo' => 'Data fine',
            'therapistId' => 'Terapista',
            'patientId' => 'Paziente',
        ];
    }

    public static function getStatusOptions()
    {
        return [
            'active' => 'Attivi (validi oggi)',
            'all' => 'Tutti gli stati',
            TherapeuticPlan::STATUS_DRAFT => 'Bozza',
            TherapeuticPlan::STATUS_PENDING => 'In attesa',
            TherapeuticPlan::STATUS_SUSPENDED => 'Sospesi',
            TherapeuticPlan::STATUS_COMPLETED => 'Completati',
            TherapeuticPlan::STATUS_TERMINATED => 'Interrotti',
            TherapeuticPlan::STATUS_EXPIRED => 'Scaduti',
        ];
    }

    public static function getStatusLabels()
    {
        return [
            TherapeuticPlan::STATUS_DRAFT => 'Bozza',
            TherapeuticPlan::STATUS_PENDING => 'In attesa',
            TherapeuticPlan::STATUS_ACTIVE => 'Attivo',
            TherapeuticPlan::STATUS_SUSPENDED => 'Sospeso',
            TherapeuticPlan::STATUS_COMPLETED => 'Completato',
            TherapeuticPlan::STATUS_TERMINATED => 'Interrotto',
            TherapeuticPlan::STATUS_EXPIRED => 'Scaduto',
        ];
    }

    /**
     * Filtri condivisi su query con alias `tp`.
     *
     * @param Query $query
     * @param bool $applyStatus
     */
    public function applyCommonFilters($query, $applyStatus = true)
    {
        if ($applyStatus) {
            $this->applyStatusFilter($query);
        }

        if ($this->minDuration !== null && $this->minDuration !== '') {
            $query->andWhere(['>=', 'tp.duration_days', $this->minDuration]);
        }
        if ($this->maxDuration !== null && $this->maxDuration !== '') {
            $query->andWhere(['<=', 'tp.duration_days', $this->maxDuration]);
        }

        if ($this->dateFrom) {
            $query->andWhere(['>=', 'tp.end_date', $this->dateFrom]);
        }
        if ($this->dateTo) {
            $query->andWhere(['<=', 'tp.start_date', $this->dateTo]);
        }

        if ($this->patientId) {
            $query->andWhere(['tp.patient_id' => $this->patientId]);
        }

        if ($this->therapistId) {
            $subQuery = (new Query())
                ->select('pt_filter.therapeutic_plan_id')
                ->distinct()
                ->from(['pt_filter' => 'plan_therapies'])
                ->innerJoin(['a_filter' => 'appointments'], 'pt_filter.id = a_filter.plan_therapy_id')
                ->where(['a_filter.therapist_id' => $this->therapistId]);
            $query->andWhere(['in', 'tp.id', $subQuery]);
        }
    }

    public function applyStatusFilter($query)
    {
        if ($this->status === 'all' || $this->status === '' || $this->status === null) {
            return;
        }

        if ($this->status === 'active') {
            $today = date('Y-m-d');
            $query->andWhere(['tp.status' => TherapeuticPlan::STATUS_ACTIVE])
                ->andWhere(['<=', 'tp.start_date', $today])
                ->andWhere(['>=', 'tp.end_date', $today]);
            return;
        }

        $query->andWhere(['tp.status' => $this->status]);
    }

    /**
     * {@inheritdoc}
     */
    public function load($params, $formName = null)
    {
        $loaded = parent::load($params, $formName);

        if ($this->status === '') {
            $this->status = 'all';
        }
        if ($this->dateFrom === '') {
            $this->dateFrom = null;
        }
        if ($this->dateTo === '') {
            $this->dateTo = null;
        }
        if ($this->minDuration === '') {
            $this->minDuration = null;
        }
        if ($this->maxDuration === '') {
            $this->maxDuration = null;
        }
        if ($this->therapistId === '') {
            $this->therapistId = null;
        }
        if ($this->patientId === '') {
            $this->patientId = null;
        }

        return $loaded;
    }
}
