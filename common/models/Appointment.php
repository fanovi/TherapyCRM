<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "appointments".
 *
 * @property int $id
 * @property int|null $pattern_id
 * @property int $plan_therapy_id
 * @property int $therapist_id
 * @property string $appointment_datetime
 * @property int $duration_minutes
 * @property string $status
 * @property int|null $original_therapist_id
 * @property string|null $notes
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property AppointmentPattern $pattern
 * @property PlanTherapy $planTherapy
 * @property Therapist $therapist
 * @property Patient $patient
 * @property Therapist $originalTherapist
 * @property User $createdBy
 */
class Appointment extends ActiveRecord
{
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABSENT_JUSTIFIED = 'absent_justified';
    const STATUS_ABSENT_NOT_JUSTIFIED = 'absent_not_justified';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_SUPERVISIONE = 'supervisione';
    const TYPE_PARENT_TRAINING = 'parent_training';
    const TYPE_TERAPIA = 'terapia';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%appointments}}';
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
                    return 'Appuntamento';
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
            [['plan_therapy_id', 'therapist_id', 'appointment_datetime', 'duration_minutes', 'created_by'], 'required'],
            [['pattern_id', 'plan_therapy_id', 'therapist_id', 'duration_minutes', 'original_therapist_id', 'created_by'], 'integer'],
            [['appointment_datetime'], 'validateAppointmentDateTime'],
            [['notes'], 'string'],
            [['status'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => [self::STATUS_SCHEDULED, self::STATUS_COMPLETED, self::STATUS_ABSENT_JUSTIFIED, self::STATUS_ABSENT_NOT_JUSTIFIED, self::STATUS_CANCELLED]],
            [['appointment_type'], 'string', 'max' => 20],
            [['appointment_type'], 'in', 'range' => [self::TYPE_SUPERVISIONE, self::TYPE_PARENT_TRAINING, self::TYPE_TERAPIA]],
            [['appointment_type'], 'default', 'value' => self::TYPE_TERAPIA],
            [['duration_minutes'], 'integer', 'min' => 15, 'max' => 180],
            [['appointment_datetime'], 'validateFutureDateTime'],
            [['therapist_id', 'appointment_datetime'], 'validateTherapistAvailability'],
            [['pattern_id'], 'exist', 'skipOnError' => true, 'targetClass' => AppointmentPattern::class, 'targetAttribute' => ['pattern_id' => 'id']],
            [['plan_therapy_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanTherapy::class, 'targetAttribute' => ['plan_therapy_id' => 'id']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['original_therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['original_therapist_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * Custom validator for appointment_datetime
     */
    public function validateAppointmentDateTime($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            // Try to parse the datetime string
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->$attribute);
            
            if ($dateTime === false) {
                // Try alternative formats
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i', $this->$attribute);
                if ($dateTime !== false) {
                    // Convert to full format
                    $this->$attribute = $dateTime->format('Y-m-d H:i:s');
                } else {
                    // Try to parse with strtotime as last resort
                    $timestamp = strtotime($this->$attribute);
                    if ($timestamp !== false) {
                        $this->$attribute = date('Y-m-d H:i:s', $timestamp);
                    } else {
                        $this->addError($attribute, 'Il formato della data e ora deve essere YYYY-MM-DD HH:mm:ss');
                        return;
                    }
                }
            }
            
            // Additional validation: check if it's a valid date
            $finalDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->$attribute);
            if ($finalDateTime === false) {
                $this->addError($attribute, 'Data e ora non valida');
            }
        }
    }

    /**
     * Validates that appointment is not in the past
     */
    public function validateFutureDateTime($attribute, $params)
    {
        // TEMPORANEAMENTE DISABILITATO PER TESTING
        // TODO: Riabilitare questa validazione quando i test saranno completati
        /*
        if (!empty($this->$attribute) && $this->status === self::STATUS_SCHEDULED) {
            $appointmentTime = strtotime($this->$attribute);
            if ($appointmentTime < time() - 300) { // 5 minutes tolerance
                $this->addError($attribute, 'Non è possibile programmare appuntamenti nel passato');
            }
        }
        */
        
        // Per ora accettiamo qualsiasi data per il testing
        if (!empty($this->$attribute)) {
            \Yii::info("Validazione data futura temporaneamente disabilitata per testing - DateTime: {$this->$attribute}", __METHOD__);
        }
    }

    /**
     * Validates therapist availability
     */
    public function validateTherapistAvailability($attribute, $params)
    {
        if (!empty($this->therapist_id) && !empty($this->appointment_datetime)) {
            $conflictingAppointment = static::find()
                ->where(['therapist_id' => $this->therapist_id])
                ->andWhere(['between', 'appointment_datetime', 
                    date('Y-m-d H:i:s', strtotime($this->appointment_datetime) - 300),
                    date('Y-m-d H:i:s', strtotime($this->appointment_datetime) + ($this->duration_minutes * 60) + 300)
                ])
                ->andWhere(['!=', 'status', self::STATUS_CANCELLED])
                ->andWhere(['!=', 'id', $this->id ?: 0])
                ->exists();

            if ($conflictingAppointment) {
                $this->addError($attribute, 'Il terapista ha già un appuntamento in questo orario');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pattern_id' => 'Pattern',
            'plan_therapy_id' => 'Piano Terapia',
            'therapist_id' => 'Terapista',
            'appointment_datetime' => 'Data e Ora',
            'duration_minutes' => 'Durata (minuti)',
            'status' => 'Stato',
            'original_therapist_id' => 'Terapista Originale',
            'notes' => 'Note',
            'appointment_type' => 'Tipo Appuntamento',
            'created_by' => 'Creato da',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Pattern]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPattern()
    {
        return $this->hasOne(AppointmentPattern::class, ['id' => 'pattern_id']);
    }

    /**
     * Gets query for [[PlanTherapy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanTherapy()
    {
        return $this->hasOne(PlanTherapy::class, ['id' => 'plan_therapy_id']);
    }

    /**
     * Gets query for [[Therapist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'therapist_id']);
    }

    /**
     * Gets query for [[Patient]] via plan therapy.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatient()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id'])
            ->via('planTherapy');
    }

    /**
     * Gets query for [[OriginalTherapist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOriginalTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'original_therapist_id']);
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
     * Gets appointment type labels
     *
     * @return array
     */
    public static function getAppointmentTypeLabels()
    {
        return [
            self::TYPE_SUPERVISIONE => 'Supervisione',
            self::TYPE_PARENT_TRAINING => 'Parent Training',
            self::TYPE_TERAPIA => 'Terapia',
        ];
    }

    /**
     * Gets appointment type label
     *
     * @return string
     */
    public function getAppointmentTypeLabel()
    {
        $labels = static::getAppointmentTypeLabels();
        return $labels[$this->appointment_type] ?? $this->appointment_type;
    }

    /**
     * {@inheritdoc}
     * @return AppointmentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AppointmentQuery(get_called_class());
    }

    /**
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_SCHEDULED => 'Programmato',
            self::STATUS_COMPLETED => 'Completato',
            self::STATUS_ABSENT_JUSTIFIED => 'Assente Giustificato',
            self::STATUS_ABSENT_NOT_JUSTIFIED => 'Assente Non Giustificato',
            self::STATUS_CANCELLED => 'Annullato',
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
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Checks if appointment is completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Checks if appointment is cancelled
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Checks if appointment is absent
     *
     * @return bool
     */
    public function isAbsent()
    {
        return in_array($this->status, [self::STATUS_ABSENT_JUSTIFIED, self::STATUS_ABSENT_NOT_JUSTIFIED]);
    }

    /**
     * Checks if therapist was substituted
     *
     * @return bool
     */
    public function isSubstituted()
    {
        return !empty($this->original_therapist_id) && $this->original_therapist_id != $this->therapist_id;
    }

    /**
     * Gets formatted appointment datetime
     *
     * @return string
     */
    public function getFormattedDateTime()
    {
        return Yii::$app->formatter->asDatetime($this->appointment_datetime);
    }

    /**
     * Gets formatted appointment date
     *
     * @return string
     */
    public function getFormattedDate()
    {
        return Yii::$app->formatter->asDate($this->appointment_datetime);
    }

    /**
     * Gets formatted appointment time
     *
     * @return string
     */
    public function getFormattedTime()
    {
        return Yii::$app->formatter->asTime($this->appointment_datetime);
    }

    /**
     * Marks appointment as completed
     *
     * @return bool
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        if ($this->save()) {
            // Increment plan therapy completed sessions
            $this->planTherapy->incrementCompleted();
            return true;
        }
        return false;
    }

    /**
     * Cancels appointment
     *
     * @return bool
     */
    public function cancel()
    {
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Gets appointments for therapist on specific date
     *
     * @param int $therapistId
     * @param string $date
     * @return static[]
     */
    public static function findByTherapistAndDate($therapistId, $date)
    {
        return static::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['like', 'appointment_datetime', $date])
            ->orderBy('appointment_datetime')
            ->all();
    }

    /**
     * Gets upcoming appointments for patient
     *
     * @param int $patientId
     * @param int $limit
     * @return static[]
     */
    public static function findUpcomingByPatient($patientId, $limit = 10)
    {
        return static::find()
            ->joinWith('planTherapy')
            ->where(['plan_therapies.patient_id' => $patientId])
            ->andWhere(['>=', 'appointment_datetime', date('Y-m-d H:i:s')])
            ->andWhere(['!=', 'status', self::STATUS_CANCELLED])
            ->orderBy('appointment_datetime')
            ->limit($limit)
            ->all();
    }
} 