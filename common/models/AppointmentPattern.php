<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "appointment_patterns".
 *
 * @property int $id
 * @property int $plan_therapy_id
 * @property int $therapist_id
 * @property int $day_of_week
 * @property string $start_time
 * @property int $duration_minutes
 * @property string $valid_from
 * @property string $valid_to
 * @property int $created_by
 * @property string $created_at
 * @property int $id_setting
 *
 * @property PlanTherapy $planTherapy
 * @property Therapist $therapist
 * @property User $createdBy
 * @property Appointment[] $appointments
 * @property Setting $setting
 */
class AppointmentPattern extends ActiveRecord
{
    const TYPE_SUPERVISIONE = 'supervisione';
    const TYPE_PARENT_TRAINING = 'parent_training';
    const TYPE_TERAPIA = 'terapia';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%appointment_patterns}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => function() {
                    return date('Y-m-d H:i:s');
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
            [['plan_therapy_id', 'therapist_id', 'day_of_week', 'start_time', 'duration_minutes', 'valid_from', 'valid_to', 'created_by'], 'required'],
            [['plan_therapy_id', 'therapist_id', 'day_of_week', 'duration_minutes', 'created_by', 'id_setting'], 'integer'],
            [['day_of_week'], 'integer', 'min' => 1, 'max' => 7],
            [['valid_from', 'valid_to'], 'date', 'format' => 'php:Y-m-d'],
            [['start_time'], 'time', 'format' => 'php:H:i'],
            [['duration_minutes'], 'integer', 'min' => 15, 'max' => 180],
            [['valid_to'], 'validateValidTo'],
            [['appointment_type'], 'string', 'max' => 20],
            [['appointment_type'], 'in', 'range' => [self::TYPE_SUPERVISIONE, self::TYPE_PARENT_TRAINING, self::TYPE_TERAPIA]],
            [['appointment_type'], 'default', 'value' => self::TYPE_TERAPIA],
            [['plan_therapy_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanTherapy::class, 'targetAttribute' => ['plan_therapy_id' => 'id']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['id_setting'], 'exist', 'skipOnError' => true, 'targetClass' => Setting::class, 'targetAttribute' => ['id_setting' => 'id']],
        ];
    }

    /**
     * Validates valid_to date
     */
    public function validateValidTo($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->valid_from)) {
            if ($this->$attribute < $this->valid_from) {
                $this->addError($attribute, 'La data di fine validità non può essere precedente alla data di inizio');
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
            'plan_therapy_id' => 'Piano Terapia',
            'therapist_id' => 'Terapista',
            'day_of_week' => 'Giorno della Settimana',
            'start_time' => 'Ora Inizio',
            'duration_minutes' => 'Durata (minuti)',
            'valid_from' => 'Valido Da',
            'valid_to' => 'Valido Fino',
            'appointment_type' => 'Tipo Appuntamento',
            'created_by' => 'Creato Da',
            'created_at' => 'Creato il',
            'id_setting' => 'Setting',
        ];
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
     * Gets query for [[Setting]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSetting()
    {
        return $this->hasOne(Setting::class, ['id' => 'id_setting']);
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
        return $this->hasMany(Appointment::class, ['pattern_id' => 'id']);
    }

    /**
     * Gets day names
     *
     * @return array
     */
    public static function getDayNames()
    {
        return [
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
     * Gets day name
     *
     * @return string
     */
    public function getDayName()
    {
        $dayNames = static::getDayNames();
        return $dayNames[$this->day_of_week] ?? 'N/A';
    }

    /**
     * Gets formatted time range
     *
     * @return string
     */
    public function getFormattedTimeRange()
    {
        $startTime = $this->start_time;
        $endTime = date('H:i', strtotime($startTime . ' + ' . $this->duration_minutes . ' minutes'));
        return $startTime . ' - ' . $endTime;
    }

    /**
     * Gets formatted pattern description
     *
     * @return string
     */
    public function getFormattedDescription()
    {
        return sprintf(
            '%s dalle %s (%s min) - %s',
            $this->getDayName(),
            $this->getFormattedTimeRange(),
            $this->duration_minutes,
            $this->getAppointmentTypeLabel()
        );
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
} 