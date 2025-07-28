<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "private_cycles".
 *
 * @property int $id
 * @property int $patient_id
 * @property string $month_year
 * @property int $total_sessions
 * @property string|null $notes
 * @property string $created_at
 * @property int $created_by
 * @property string $updated_at
 *
 * @property Patient $patient
 * @property User $createdBy
 * @property Appointment[] $appointments
 */
class PrivateCycle extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%private_cycles}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function($model) {
                    return 'Ciclo Privato';
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['patient_id', 'month_year', 'total_sessions', 'created_by'], 'required'],
            [['patient_id', 'total_sessions', 'created_by'], 'integer'],
            [['month_year'], 'date', 'format' => 'php:Y-m-d'],
            [['notes'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['total_sessions'], 'integer', 'min' => 1, 'max' => 50],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'patient_id' => 'Paziente',
            'month_year' => 'Mese e Anno',
            'total_sessions' => 'Sessioni Totali',
            'notes' => 'Note',
            'created_at' => 'Creato il',
            'created_by' => 'Creato da',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Patient]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatient()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Appointments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAppointments()
    {
        return $this->hasMany(Appointment::class, ['private_cycle_id' => 'id']);
    }

    /**
     * Gets the formatted month and year
     *
     * @return string
     */
    public function getFormattedMonthYear()
    {
        if (!$this->month_year) {
            return '';
        }
        
        $date = new \DateTime($this->month_year);
        return $date->format('F Y'); // e.g., "January 2024"
    }

    /**
     * Gets the formatted month and year in Italian
     *
     * @return string
     */
    public function getFormattedMonthYearItalian()
    {
        if (!$this->month_year) {
            return '';
        }
        
        $date = new \DateTime($this->month_year);
        $months = [
            '01' => 'Gennaio', '02' => 'Febbraio', '03' => 'Marzo', '04' => 'Aprile',
            '05' => 'Maggio', '06' => 'Giugno', '07' => 'Luglio', '08' => 'Agosto',
            '09' => 'Settembre', '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];
        
        return $months[$date->format('m')] . ' ' . $date->format('Y');
    }

    /**
     * Gets the number of scheduled appointments
     *
     * @return int
     */
    public function getScheduledAppointmentsCount()
    {
        return $this->getAppointments()
            ->where(['appointment_source' => Appointment::SOURCE_PRIVATE])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->count();
    }

    /**
     * Gets the number of completed appointments
     *
     * @return int
     */
    public function getCompletedAppointmentsCount()
    {
        return $this->getAppointments()
            ->where(['appointment_source' => Appointment::SOURCE_PRIVATE])
            ->andWhere(['status' => Appointment::STATUS_COMPLETED])
            ->count();
    }

    /**
     * Gets the number of remaining sessions
     *
     * @return int
     */
    public function getRemainingSessions()
    {
        $scheduled = $this->getScheduledAppointmentsCount();
        return max(0, $this->total_sessions - $scheduled);
    }

    /**
     * Checks if all sessions are scheduled
     *
     * @return bool
     */
    public function isFullyScheduled()
    {
        return $this->getScheduledAppointmentsCount() >= $this->total_sessions;
    }

    /**
     * Checks if the cycle is completed (all sessions done)
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->getCompletedAppointmentsCount() >= $this->total_sessions;
    }

    /**
     * Gets progress percentage
     *
     * @return float
     */
    public function getProgressPercentage()
    {
        if ($this->total_sessions == 0) {
            return 0;
        }
        
        return min(100, ($this->getCompletedAppointmentsCount() / $this->total_sessions) * 100);
    }

    /**
     * Gets the cycle status
     *
     * @return string
     */
    public function getStatus()
    {
        if ($this->isCompleted()) {
            return 'completed';
        } elseif ($this->isFullyScheduled()) {
            return 'scheduled';
        } else {
            return 'active';
        }
    }

    /**
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            'active' => 'Attivo',
            'scheduled' => 'Programmato',
            'completed' => 'Completato',
        ];
    }

    /**
     * Gets status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = static::getStatusLabels();
        return $labels[$this->getStatus()] ?? 'Sconosciuto';
    }

    /**
     * Creates a new private cycle for a patient and month
     *
     * @param int $patientId
     * @param string $monthYear Format: Y-m-01
     * @param int $totalSessions
     * @param string|null $notes
     * @param int|null $createdBy
     * @return PrivateCycle|null
     */
    public static function createCycle($patientId, $monthYear, $totalSessions, $notes = null, $createdBy = null)
    {
        $cycle = new static();
        $cycle->patient_id = $patientId;
        $cycle->month_year = $monthYear;
        $cycle->total_sessions = $totalSessions;
        $cycle->notes = $notes;
        $cycle->created_by = $createdBy ?: Yii::$app->user->id;
        
        if ($cycle->save()) {
            return $cycle;
        }
        
        return null;
    }

    /**
     * Finds cycles for a patient
     *
     * @param int $patientId
     * @return \yii\db\ActiveQuery
     */
    public static function findByPatient($patientId)
    {
        return static::find()
            ->where(['patient_id' => $patientId])
            ->orderBy(['month_year' => SORT_DESC]);
    }

    /**
     * Finds active cycles (not fully completed)
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()
            ->where(['<', 'completed_sessions', 'total_sessions'])
            ->orWhere(['completed_sessions' => null]);
    }

    /**
     * Gets cycles for a specific month/year
     *
     * @param string $monthYear Format: Y-m-01
     * @return \yii\db\ActiveQuery
     */
    public static function findByMonth($monthYear)
    {
        return static::find()
            ->where(['month_year' => $monthYear])
            ->with(['patient']);
    }
} 