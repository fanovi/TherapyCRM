<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "therapist_busy_slots".
 *
 * @property int $id
 * @property int $therapist_id
 * @property string $date
 * @property string $start_time
 * @property string $end_time
 * @property string $type
 * @property string|null $reason
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Therapist $therapist
 */
class TherapistBusySlot extends ActiveRecord
{
    const TYPE_MEETING = 'meeting';
    const TYPE_TRAINING = 'training';
    const TYPE_PERSONAL = 'personal';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_OTHER = 'other';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%therapist_busy_slots}}';
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
            [['therapist_id', 'date', 'start_time', 'end_time', 'type'], 'required'],
            [['therapist_id'], 'integer'],
            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['start_time', 'end_time'], 'time', 'format' => 'php:H:i'],
            [['reason'], 'string'],
            [['type'], 'string', 'max' => 20],
            [['type'], 'in', 'range' => [self::TYPE_MEETING, self::TYPE_TRAINING, self::TYPE_PERSONAL, self::TYPE_MAINTENANCE, self::TYPE_OTHER]],
            [['end_time'], 'validateEndTime'],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
        ];
    }

    /**
     * Validates end time
     */
    public function validateEndTime($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->start_time)) {
            if ($this->$attribute <= $this->start_time) {
                $this->addError($attribute, 'L\'ora di fine deve essere successiva all\'ora di inizio');
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
            'therapist_id' => 'Terapista',
            'date' => 'Data',
            'start_time' => 'Ora Inizio',
            'end_time' => 'Ora Fine',
            'type' => 'Tipo',
            'reason' => 'Motivo',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
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
     * Gets type labels
     *
     * @return array
     */
    public static function getTypeLabels()
    {
        return [
            self::TYPE_MEETING => 'Riunione',
            self::TYPE_TRAINING => 'Formazione',
            self::TYPE_PERSONAL => 'Personale',
            self::TYPE_MAINTENANCE => 'Manutenzione',
            self::TYPE_OTHER => 'Altro',
        ];
    }

    /**
     * Gets type label
     *
     * @return string
     */
    public function getTypeLabel()
    {
        $labels = static::getTypeLabels();
        return $labels[$this->type] ?? $this->type;
    }

    /**
     * Gets formatted time range
     *
     * @return string
     */
    public function getTimeRange()
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    /**
     * Gets busy slots for therapist on date
     *
     * @param int $therapistId
     * @param string $date
     * @return static[]
     */
    public static function findByTherapistAndDate($therapistId, $date)
    {
        return static::find()
            ->where(['therapist_id' => $therapistId, 'date' => $date])
            ->orderBy('start_time')
            ->all();
    }
} 