<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "absence_recoveries".
 *
 * @property int $id
 * @property int $absence_id
 * @property string $recovery_date
 * @property string $start_time
 * @property string $end_time
 * @property int $minutes_recovered
 * @property string|null $notes
 * @property string $status
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Absence $absence
 * @property User $createdBy
 */
class AbsenceRecovery extends ActiveRecord
{
    const STATUS_PLANNED = 'planned';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%absence_recoveries}}';
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
                    return 'Recupero Assenza';
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
            [['absence_id', 'recovery_date', 'start_time', 'end_time', 'minutes_recovered', 'created_by'], 'required'],
            [['absence_id', 'minutes_recovered', 'created_by'], 'integer'],
            [['recovery_date'], 'date', 'format' => 'php:Y-m-d'],
            [['start_time', 'end_time'], 'time', 'format' => 'php:H:i'],
            [['notes'], 'string'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_PLANNED, self::STATUS_COMPLETED, self::STATUS_CANCELLED]],
            [['minutes_recovered'], 'integer', 'min' => 15, 'max' => 480],
            [['end_time'], 'validateEndTime'],
            [['absence_id'], 'exist', 'skipOnError' => true, 'targetClass' => Absence::class, 'targetAttribute' => ['absence_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
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
            'absence_id' => 'Assenza',
            'recovery_date' => 'Data Recupero',
            'start_time' => 'Ora Inizio',
            'end_time' => 'Ora Fine',
            'minutes_recovered' => 'Minuti Recuperati',
            'notes' => 'Note',
            'status' => 'Stato',
            'created_by' => 'Creato da',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Absence]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAbsence()
    {
        return $this->hasOne(Absence::class, ['id' => 'absence_id']);
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
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PLANNED => 'Pianificato',
            self::STATUS_COMPLETED => 'Completato',
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
     * Gets formatted time range
     *
     * @return string
     */
    public function getTimeRange()
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    /**
     * Marks recovery as completed
     *
     * @return bool
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        return $this->save();
    }
} 