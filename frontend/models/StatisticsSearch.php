<?php

namespace frontend\models;

use yii\base\Model;
use yii\db\Query;

/**
 * StatisticsSearch rappresenta il modello per i filtri delle statistiche
 */
class StatisticsSearch extends Model
{
    public $date_from;
    public $date_to;
    public $age_from;
    public $age_to;
    public $gender;
    public $treatments = [];
    public $regimes = [];
    public $hour_breakdown;
    public $active_only;
    public $dismissed_only;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            [['age_from', 'age_to'], 'integer', 'min' => 0, 'max' => 120],
            [['gender'], 'in', 'range' => ['all', 'M', 'F']],
            [['treatments', 'regimes'], 'each', 'rule' => ['integer']],
            [['hour_breakdown'], 'in', 'range' => ['all', 'breakdown']],
            [['active_only', 'dismissed_only'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'date_from' => 'Data da',
            'date_to' => 'Data a',
            'age_from' => 'Età da',
            'age_to' => 'Età a',
            'gender' => 'Genere',
            'treatments' => 'Trattamenti',
            'regimes' => 'Regimi',
            'hour_breakdown' => 'Dettaglio orario',
            'active_only' => 'Solo piani attivi',
            'dismissed_only' => 'Solo dismessi',
        ];
    }

    /**
     * Ottiene la lista dei trattamenti disponibili
     */
    public function getTreatmentsList()
    {
        return (new Query())
            ->select(['id', 'name', 'code'])
            ->from('treatment_types')
            ->orderBy('name')
            ->all();
    }

    /**
     * Ottiene la lista dei regimi disponibili
     */
    public function getRegimesList()
    {
        return (new Query())
            ->select(['id', 'nome as name'])
            ->from('regime')
            ->orderBy('nome')
            ->all();
    }

    /**
     * Prepara i filtri per le query delle statistiche
     */
    public function getFilters()
    {
        $filters = [];
        
        if (!empty($this->date_from)) {
            $filters['date_from'] = $this->date_from;
        }
        
        if (!empty($this->date_to)) {
            $filters['date_to'] = $this->date_to;
        }
        
        if (!empty($this->age_from)) {
            $filters['age_from'] = $this->age_from;
        }
        
        if (!empty($this->age_to)) {
            $filters['age_to'] = $this->age_to;
        }
        
        if (!empty($this->gender) && $this->gender !== 'all') {
            $filters['gender'] = $this->gender;
        }
        
        if (!empty($this->treatments)) {
            $filters['treatments'] = $this->treatments;
        }
        
        if (!empty($this->regimes)) {
            $filters['regimes'] = $this->regimes;
        }
        
        if (!empty($this->hour_breakdown)) {
            $filters['hour_breakdown'] = $this->hour_breakdown;
        }
        
        if ($this->active_only) {
            $filters['active_only'] = true;
        }
        
        if ($this->dismissed_only) {
            $filters['dismissed_only'] = true;
        }
        
        return $filters;
    }

    /**
     * Valida i filtri delle date
     */
    public function validateDateRange()
    {
        if (!empty($this->date_from) && !empty($this->date_to)) {
            if (strtotime($this->date_from) > strtotime($this->date_to)) {
                $this->addError('date_to', 'La data finale deve essere successiva alla data iniziale');
                return false;
            }
        }
        return true;
    }

    /**
     * Valida i filtri dell'età
     */
    public function validateAgeRange()
    {
        if (!empty($this->age_from) && !empty($this->age_to)) {
            if ($this->age_from > $this->age_to) {
                $this->addError('age_to', 'L\'età finale deve essere maggiore dell\'età iniziale');
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
        if (!$this->validateDateRange()) {
            return false;
        }
        
        if (!$this->validateAgeRange()) {
            return false;
        }
        
        return parent::beforeValidate();
    }
} 