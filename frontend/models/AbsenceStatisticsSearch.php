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
            [['therapistId', 'patientId', 'treatmentTypeId', 'settingId'], 'integer'],
            [['absenceSource', 'absenceTypeFlag'], 'string'],
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
        if ($this->isJustified !== null) $filters['isJustified'] = $this->isJustified;
        
        return $service->getBaseAbsencesQuery($filters);
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($params)
    {
        $query = $this->getStatisticsQuery();

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

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        return $dataProvider;
    }
}