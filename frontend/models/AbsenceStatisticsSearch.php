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
            [['therapistId', 'patientId', 'treatmentTypeId', 'settingId'], 'integer', 'min' => 1],
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
        ];
    }

    /**
     * Crea query per statistiche con la nuova logica
     */
    public function getStatisticsQuery()
    {
        $service = new \common\services\statistics\AbsenceStatisticsService();
        
        $filters = [];
        if ($this->dateFrom) $filters['dateFrom'] = $this->dateFrom;
        if ($this->dateTo) $filters['dateTo'] = $this->dateTo;
        if ($this->therapistId) $filters['therapistId'] = $this->therapistId;
        if ($this->patientId) $filters['patientId'] = $this->patientId;
        if ($this->treatmentTypeId) $filters['treatmentTypeId'] = $this->treatmentTypeId;
        if ($this->settingId) $filters['settingId'] = $this->settingId;
        if ($this->absenceSource) $filters['absenceSource'] = $this->absenceSource;
        if ($this->absenceTypeFlag) $filters['absenceTypeFlag'] = $this->absenceTypeFlag;
        if ($this->isJustified !== null) $filters['isJustified'] = $this->isJustified;
        
        return $service->getBaseAbsencesQuery($filters);
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
            $this->dateFrom = date('Y-m-d', strtotime('-30 days'));
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