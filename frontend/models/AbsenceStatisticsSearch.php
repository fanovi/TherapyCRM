<?php

namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\db\Expression;

/**
 * AbsenceStatisticsSearch per filtrare le statistiche assenze
 */
class AbsenceStatisticsSearch extends Model
{
    public $dateFrom;
    public $dateTo;
    public $therapistId;
    public $patientId;
    public $treatmentTypeId;
    public $settingId;
    public $absenceSource; // 'therapist' o 'patient'
    public $isJustified;
    public $absenceTypeFlag; // 'direct', 'substitution', 'patient'
    /** @var int 1 = solo assenze su piani in corso oggi (più i cicli privati); default attivo */
    public $onlyActivePlans = 1;
    /** @var int|null Gruppo di coordinamento (coordinator_groups.id) */
    public $coordinationGroupId;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['dateFrom'], 'compare', 'compareAttribute' => 'dateTo', 'operator' => '<=', 'when' => function ($model) {
                return !empty($model->dateTo);
            }],
            [['therapistId', 'patientId', 'treatmentTypeId', 'settingId', 'coordinationGroupId'], 'integer', 'min' => 1],
            [['onlyActivePlans'], 'boolean'],
            [['absenceSource'], 'in', 'range' => ['therapist', 'patient']],
            [['absenceTypeFlag'], 'in', 'range' => ['direct', 'substitution', 'patient']],
            [['absenceTypeFlag'], 'validateSourceAndType'],
            [['isJustified'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'dateFrom' => 'Data inizio',
            'dateTo' => 'Data fine',
            'therapistId' => 'Terapista',
            'patientId' => 'Paziente',
            'treatmentTypeId' => 'Tipo trattamento',
            'settingId' => 'Setting',
            'absenceSource' => 'Chi è assente',
            'isJustified' => 'Giustificata',
            'absenceTypeFlag' => 'Tipo assenza',
            'onlyActivePlans' => 'Solo piani in corso',
            'coordinationGroupId' => 'Coordinamento',
        ];
    }

    /**
     * Filtri per AbsenceStatisticsService, nel formato di applyFilters().
     * Unica mappatura: usata da controller, service ed export, così un filtro
     * nuovo va aggiunto solo qui.
     *
     * @return array
     */
    public function toFilters()
    {
        $filters = [];
        foreach (['dateFrom', 'dateTo', 'therapistId', 'patientId', 'treatmentTypeId', 'settingId', 'absenceSource', 'absenceTypeFlag', 'coordinationGroupId'] as $attribute) {
            if (!empty($this->$attribute)) {
                $filters[$attribute] = $this->$attribute;
            }
        }
        if ($this->isJustified !== null && $this->isJustified !== '') {
            $filters['isJustified'] = $this->isJustified;
        }
        if (!empty($this->onlyActivePlans)) {
            $filters['onlyActivePlans'] = 1;
        }

        return $filters;
    }

    /**
     * Crea query per statistiche con la nuova logica
     */
    public function getStatisticsQuery()
    {
        return (new \common\services\statistics\AbsenceStatisticsService())->getBaseAbsencesQuery($this->toFilters());
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($params)
    {
        $this->load($params);

        if (!$this->validate()) {
            $query = (new \common\services\statistics\AbsenceStatisticsService())
                ->getBaseAbsencesQuery()
                ->andWhere('0=1');
        } else {
            $query = $this->getStatisticsQuery();
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'absence_date' => SORT_DESC,
                    'absence_hour' => SORT_DESC,
                ],
            ],
        ]);

        return $dataProvider;
    }

    /**
     * Imposta il periodo standard della pagina quando non specificato.
     */
    public function applyDefaultPeriod()
    {
        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $this->dateFrom = date('Y-m-01');
            $this->dateTo = date('Y-m-d');
        }
    }

    public function validateSourceAndType($attribute)
    {
        if (
            ($this->absenceSource === 'therapist' && $this->absenceTypeFlag === 'patient')
            || (
                $this->absenceSource === 'patient'
                && in_array($this->absenceTypeFlag, ['direct', 'substitution'], true)
            )
        ) {
            $this->addError($attribute, 'Il tipo evento non è compatibile con la sorgente selezionata.');
        }
    }
}