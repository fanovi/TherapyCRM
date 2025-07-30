<?php

namespace frontend\models;

use yii\base\Model;

/**
 * PlanStatisticsSearch rappresenta il modello per i filtri delle statistiche dei piani terapeutici
 */
class PlanStatisticsSearch extends Model
{
    public $status;
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
            [['status'], 'string'],
            [['minDuration', 'maxDuration', 'therapistId', 'patientId'], 'integer', 'min' => 0],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => ['', 'active', 'completed']],
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

    /**
     * Ottiene la lista degli stati disponibili
     */
    public function getStatusList()
    {
        return [
            '' => 'Tutti gli stati',
            'active' => 'Attivi',
            'completed' => 'Completati'
        ];
    }

    /**
     * Ottiene la lista dei terapisti disponibili
     */
    public function getTherapistList()
    {
        return (new \yii\db\Query())
            ->select(['id', 'CONCAT(first_name, " ", last_name) as name'])
            ->from('therapist')
            ->where(['status' => 'active'])
            ->orderBy('first_name, last_name')
            ->all();
    }

    /**
     * Ottiene la lista dei pazienti disponibili
     */
    public function getPatientList()
    {
        return (new \yii\db\Query())
            ->select(['id', 'CONCAT(first_name, " ", last_name) as name'])
            ->from('patient')
            ->where(['status' => 'active'])
            ->orderBy('first_name, last_name')
            ->all();
    }

    /**
     * Prepara i filtri per le query delle statistiche
     */
    public function getFilters()
    {
        $filters = [];
        
        if (!empty($this->status)) {
            $filters['status'] = $this->status;
        }
        
        if (!empty($this->minDuration)) {
            $filters['minDuration'] = $this->minDuration;
        }
        
        if (!empty($this->maxDuration)) {
            $filters['maxDuration'] = $this->maxDuration;
        }
        
        if (!empty($this->dateFrom)) {
            $filters['dateFrom'] = $this->dateFrom;
        }
        
        if (!empty($this->dateTo)) {
            $filters['dateTo'] = $this->dateTo;
        }
        
        if (!empty($this->therapistId)) {
            $filters['therapistId'] = $this->therapistId;
        }
        
        if (!empty($this->patientId)) {
            $filters['patientId'] = $this->patientId;
        }
        
        return $filters;
    }

    /**
     * Valida il range di date
     */
    public function validateDateRange()
    {
        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            if (strtotime($this->dateFrom) > strtotime($this->dateTo)) {
                $this->addError('dateTo', 'La data di fine deve essere successiva alla data di inizio');
                return false;
            }
        }
        return true;
    }

    /**
     * Valida il range di durata
     */
    public function validateDurationRange()
    {
        if (!empty($this->minDuration) && !empty($this->maxDuration)) {
            if ($this->minDuration > $this->maxDuration) {
                $this->addError('maxDuration', 'La durata massima deve essere maggiore della durata minima');
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        return $this->validateDateRange() && $this->validateDurationRange();
    }
} 