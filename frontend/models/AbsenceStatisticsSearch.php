<?php

namespace frontend\models;

use yii\base\Model;
use yii\db\Query;
use Yii;

/**
 * AbsenceStatisticsSearch rappresenta il modello per i filtri delle statistiche assenze
 */
class AbsenceStatisticsSearch extends Model
{
    public $dateFrom;
    public $dateTo;
    public $therapistId;
    public $patientId;
    public $treatmentTypeId;
    public $dayOfWeek;
    public $hourFrom;
    public $hourTo;
    public $reason;
    public $generatedBy;
    public $isJustified;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            [['therapistId', 'patientId', 'treatmentTypeId', 'dayOfWeek', 'hourFrom', 'hourTo'], 'integer'],
            [['reason'], 'string', 'max' => 255],
            [['generatedBy'], 'in', 'range' => ['patient', 'therapist', 'system', 'all']],
            [['isJustified'], 'boolean'],
            [['dayOfWeek'], 'in', 'range' => [1, 2, 3, 4, 5, 6, 7]], // 1=Monday, 7=Sunday
            [['hourFrom', 'hourTo'], 'in', 'range' => range(0, 23)],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'dateFrom' => 'Data da',
            'dateTo' => 'Data a',
            'therapistId' => 'Terapista',
            'patientId' => 'Paziente',
            'treatmentTypeId' => 'Tipo Trattamento',
            'dayOfWeek' => 'Giorno della Settimana',
            'hourFrom' => 'Ora da',
            'hourTo' => 'Ora a',
            'reason' => 'Motivo',
            'generatedBy' => 'Generata da',
            'isJustified' => 'Giustificata',
        ];
    }

    /**
     * Crea la query base per le statistiche assenze con filtri applicati
     *
     * @return Query
     */
    public function getStatisticsQuery()
    {
        $query = (new Query())
            ->from('statistics_absences_mv sa');

        // Applica filtri
        if ($this->dateFrom) {
            $query->andWhere(['>=', 'sa.absence_date', $this->dateFrom]);
        }
        
        if ($this->dateTo) {
            $query->andWhere(['<=', 'sa.absence_date', $this->dateTo]);
        }
        
        if ($this->therapistId) {
            $query->andWhere(['sa.therapist_id' => $this->therapistId]);
        }
        
        if ($this->patientId) {
            $query->andWhere(['sa.patient_id' => $this->patientId]);
        }
        
        if ($this->treatmentTypeId) {
            $query->andWhere(['sa.treatment_type_id' => $this->treatmentTypeId]);
        }
        
        if ($this->dayOfWeek) {
            $query->andWhere(['sa.absence_day_number' => $this->dayOfWeek]);
        }
        
        if ($this->hourFrom !== null) {
            $query->andWhere(['>=', 'sa.absence_hour', $this->hourFrom]);
        }
        
        if ($this->hourTo !== null) {
            $query->andWhere(['<=', 'sa.absence_hour', $this->hourTo]);
        }
        
        if ($this->reason) {
            $query->andWhere(['like', 'sa.reason', $this->reason]);
        }
        
        if ($this->generatedBy && $this->generatedBy !== 'all') {
            $query->andWhere(['sa.generated_by' => $this->generatedBy]);
        }
        
        if ($this->isJustified !== null) {
            $query->andWhere(['sa.is_justified' => $this->isJustified ? 1 : 0]);
        }

        return $query;
    }

    /**
     * Ottiene le opzioni per il filtro giorno della settimana
     *
     * @return array
     */
    public static function getDayOfWeekOptions()
    {
        return [
            '' => 'Tutti i giorni',
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica',
        ];
    }

    /**
     * Ottiene le opzioni per il filtro "generata da"
     *
     * @return array
     */
    public static function getGeneratedByOptions()
    {
        return [
            'all' => 'Tutti',
            'patient' => 'Paziente',
            'therapist' => 'Terapista',
            'system' => 'Sistema',
        ];
    }

    /**
     * Ottiene le opzioni per le ore
     *
     * @return array
     */
    public static function getHourOptions()
    {
        $options = ['' => 'Tutte le ore'];
        for ($hour = 0; $hour < 24; $hour++) {
            $options[$hour] = sprintf('%02d:00', $hour);
        }
        return $options;
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
        if ($this->dateFrom === '') $this->dateFrom = null;
        if ($this->dateTo === '') $this->dateTo = null;
        if ($this->reason === '') $this->reason = null;
        if ($this->generatedBy === '') $this->generatedBy = 'all';
        
        return $loaded;
    }
} 