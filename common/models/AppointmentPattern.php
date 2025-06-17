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
 * @property string $frequency_type
 * @property int $frequency_value
 * @property string $start_time
 * @property int $duration_minutes
 * @property string $days_of_week
 * @property string $start_date
 * @property string|null $end_date
 * @property int $is_active
 * @property string|null $notes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property PlanTherapy $planTherapy
 * @property Appointment[] $appointments
 */
class AppointmentPattern extends ActiveRecord
{
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BIWEEKLY = 'biweekly';
    const FREQUENCY_MONTHLY = 'monthly';

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
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['plan_therapy_id', 'frequency_type', 'frequency_value', 'start_time', 'duration_minutes', 'days_of_week', 'start_date'], 'required'],
            [['plan_therapy_id', 'frequency_value', 'duration_minutes', 'is_active'], 'integer'],
            [['is_active'], 'boolean'],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['start_time'], 'time', 'format' => 'php:H:i'],
            [['notes'], 'string'],
            [['frequency_type'], 'string', 'max' => 20],
            [['frequency_type'], 'in', 'range' => [self::FREQUENCY_WEEKLY, self::FREQUENCY_BIWEEKLY, self::FREQUENCY_MONTHLY]],
            [['days_of_week'], 'string', 'max' => 20],
            [['days_of_week'], 'validateDaysOfWeek'],
            [['frequency_value'], 'integer', 'min' => 1, 'max' => 4],
            [['duration_minutes'], 'integer', 'min' => 15, 'max' => 180],
            [['end_date'], 'validateEndDate'],
            [['plan_therapy_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanTherapy::class, 'targetAttribute' => ['plan_therapy_id' => 'id']],
        ];
    }

    /**
     * Validates days of week format
     */
    public function validateDaysOfWeek($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            $days = explode(',', $this->$attribute);
            foreach ($days as $day) {
                if (!in_array(trim($day), ['1', '2', '3', '4', '5', '6', '7'])) {
                    $this->addError($attribute, 'I giorni della settimana devono essere numeri da 1 a 7');
                    break;
                }
            }
        }
    }

    /**
     * Validates end date
     */
    public function validateEndDate($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->start_date)) {
            if ($this->$attribute < $this->start_date) {
                $this->addError($attribute, 'La data di fine non può essere precedente alla data di inizio');
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
            'frequency_type' => 'Tipo Frequenza',
            'frequency_value' => 'Valore Frequenza',
            'start_time' => 'Ora Inizio',
            'duration_minutes' => 'Durata (minuti)',
            'days_of_week' => 'Giorni della Settimana',
            'start_date' => 'Data Inizio',
            'end_date' => 'Data Fine',
            'is_active' => 'Attivo',
            'notes' => 'Note',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
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
     * Gets query for [[Appointments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAppointments()
    {
        return $this->hasMany(Appointment::class, ['pattern_id' => 'id']);
    }

    /**
     * Gets frequency type labels
     *
     * @return array
     */
    public static function getFrequencyTypeLabels()
    {
        return [
            self::FREQUENCY_WEEKLY => 'Settimanale',
            self::FREQUENCY_BIWEEKLY => 'Bisettimanale',
            self::FREQUENCY_MONTHLY => 'Mensile',
        ];
    }

    /**
     * Gets frequency type label
     *
     * @return string
     */
    public function getFrequencyTypeLabel()
    {
        $labels = static::getFrequencyTypeLabels();
        return $labels[$this->frequency_type] ?? $this->frequency_type;
    }

    /**
     * Gets days of week as array
     *
     * @return array
     */
    public function getDaysOfWeekArray()
    {
        return array_map('trim', explode(',', $this->days_of_week));
    }

    /**
     * Gets day names
     *
     * @return array
     */
    public static function getDayNames()
    {
        return [
            '1' => 'Lunedì',
            '2' => 'Martedì',
            '3' => 'Mercoledì',
            '4' => 'Giovedì',
            '5' => 'Venerdì',
            '6' => 'Sabato',
            '7' => 'Domenica',
        ];
    }

    /**
     * Gets formatted days of week
     *
     * @return string
     */
    public function getFormattedDaysOfWeek()
    {
        $dayNames = static::getDayNames();
        $days = $this->getDaysOfWeekArray();
        $formattedDays = [];
        
        foreach ($days as $day) {
            if (isset($dayNames[$day])) {
                $formattedDays[] = $dayNames[$day];
            }
        }
        
        return implode(', ', $formattedDays);
    }
} 