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
     * Nuova logica:
     * 1. Parte da tabella absences (periodi di non disponibilità terapisti)
     * 2. Conta assenze quando:
     *    - Appuntamenti con therapist_id del terapista e status='therapist_absent'
     *    - Sostituzioni con original_therapist_id del terapista
     * 3. Raggruppa per group_session_id (stesso ID = 1 assenza, NULL = singole)
     *
     * @return Query
     */
    public function getStatisticsQuery()
    {
        // Query principale che gestisce entrambi i casi: appuntamenti diretti + sostituzioni
        $query = (new Query())
            ->select([
                'abs.id as absence_id',
                'abs.therapist_id',
                'abs.start_date',
                'abs.end_date',
                'abs.type as absence_type',
                'abs.reason as absence_reason',
                // Priorità agli appuntamenti diretti, poi sostituzioni
                'COALESCE(a_direct.id, a_subst.id) as appointment_id',
                'COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime) as appointment_datetime',
                'COALESCE(a_direct.patient_id, a_subst.patient_id) as patient_id',
                'COALESCE(a_direct.group_session_id, a_subst.group_session_id) as group_session_id',
                'COALESCE(a_direct.status, a_subst.status) as appointment_status',
                'DATE(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime)) as absence_date',
                'HOUR(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime)) as absence_hour',
                'DAYNAME(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime)) as absence_day_name',
                'DAYOFWEEK(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime)) as absence_day_number',
                // Dati paziente
                'p.first_name as patient_name',
                'p.last_name as patient_surname',
                // Dati terapista
                'up_th.first_name as therapist_name',
                'up_th.last_name as therapist_surname',
                // Dati trattamento
                'tt.id as treatment_type_id',
                'tt.name as treatment_name',
                'tt.code as treatment_code',
                // Tipo di assenza generata - includi alias direttamente nella Expression
                new \yii\db\Expression('"therapist" as generated_by'),
                new \yii\db\Expression('1 as is_justified'), // Assenze terapisti sono sempre giustificate
                new \yii\db\Expression('"NO" as has_recovery'), // Per ora non gestiamo recuperi terapisti
                // Identificatore per distinguere il tipo
                new \yii\db\Expression('CASE 
                    WHEN a_direct.id IS NOT NULL THEN "direct"
                    WHEN a_subst.id IS NOT NULL THEN "substitution"
                    ELSE "none"
                END as absence_type_flag'),
                // Identificatore univoco per raggruppare
                new \yii\db\Expression('CASE 
                    WHEN COALESCE(a_direct.group_session_id, a_subst.group_session_id) IS NULL 
                    THEN CONCAT("single_", COALESCE(a_direct.id, a_subst.id))
                    ELSE COALESCE(a_direct.group_session_id, a_subst.group_session_id)
                END as absence_group_key')
            ])
            ->from('absences abs')
            
            // LEFT JOIN per appuntamenti diretti con status therapist_absent
            ->leftJoin(['a_direct' => 'appointments'], 
                'a_direct.therapist_id = abs.therapist_id 
                AND a_direct.status = "therapist_absent"
                AND DATE(a_direct.appointment_datetime) BETWEEN abs.start_date AND abs.end_date'
            )
            
            // LEFT JOIN per sostituzioni
            ->leftJoin('therapist_substitutions ts', 'ts.original_therapist_id = abs.therapist_id')
            ->leftJoin(['a_subst' => 'appointments'],
                'a_subst.id = ts.appointment_id 
                AND DATE(a_subst.appointment_datetime) BETWEEN abs.start_date AND abs.end_date'
            )
            
            // JOIN per dati correlati (usa COALESCE per priorità)
            ->leftJoin('patients p', 'p.id = COALESCE(a_direct.patient_id, a_subst.patient_id)')
            ->leftJoin('therapists th', 'th.id = abs.therapist_id')
            ->leftJoin('users u_th', 'u_th.id = th.user_id')
            ->leftJoin('user_profiles up_th', 'up_th.user_id = u_th.id')
            ->leftJoin('plan_therapies pt', 'pt.id = COALESCE(a_direct.plan_therapy_id, a_subst.plan_therapy_id)')
            ->leftJoin('treatment_types tt', 'tt.id = pt.treatment_type_id')
            
            // Solo assenze che hanno effettivamente causato perdita di appuntamenti
            ->where('(a_direct.id IS NOT NULL OR a_subst.id IS NOT NULL)');

        // Applica filtri
        if ($this->dateFrom) {
            $query->andWhere(['>=', 'DATE(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime))', $this->dateFrom]);
        }
        
        if ($this->dateTo) {
            $query->andWhere(['<=', 'DATE(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime))', $this->dateTo]);
        }
        
        if ($this->therapistId) {
            $query->andWhere(['abs.therapist_id' => $this->therapistId]);
        }
        
        if ($this->patientId) {
            $query->andWhere(['COALESCE(a_direct.patient_id, a_subst.patient_id)' => $this->patientId]);
        }
        
        if ($this->treatmentTypeId) {
            $query->andWhere(['tt.id' => $this->treatmentTypeId]);
        }
        
        if ($this->dayOfWeek) {
            $query->andWhere(['DAYOFWEEK(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime))' => $this->dayOfWeek]);
        }
        
        if ($this->hourFrom !== null) {
            $query->andWhere(['>=', 'HOUR(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime))', $this->hourFrom]);
        }
        
        if ($this->hourTo !== null) {
            $query->andWhere(['<=', 'HOUR(COALESCE(a_direct.appointment_datetime, a_subst.appointment_datetime))', $this->hourTo]);
        }
        
        if ($this->reason) {
            $query->andWhere(['like', 'abs.reason', $this->reason]);
        }
        
        if ($this->generatedBy && $this->generatedBy !== 'all') {
            // Per ora tutte le assenze terapisti sono 'therapist'
            if ($this->generatedBy !== 'therapist') {
                $query->andWhere('1=0'); // Nessun risultato se non therapist
            }
        }
        
        if ($this->isJustified !== null) {
            // Assenze terapisti sono sempre giustificate, ma filtriamo se richiesto
            if (!$this->isJustified) {
                $query->andWhere('1=0'); // Nessun risultato se si cercano non giustificate
            }
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