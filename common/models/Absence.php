<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "absences".
 *
 * @property int $id
 * @property int $therapist_id
 * @property string $start_date
 * @property string $end_date
 * @property string $type
 * @property string|null $reason
 * @property string $status
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $notes
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Therapist $therapist
 * @property User $approvedBy
 * @property User $createdBy
 * @property AbsenceRecovery[] $absenceRecoveries
 */
class Absence extends ActiveRecord
{
    const TYPE_VACATION = 'vacation';
    const TYPE_SICK_LEAVE = 'sick_leave';
    const TYPE_PERSONAL = 'personal';
    const TYPE_TRAINING = 'training';
    const TYPE_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%absences}}';
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
                    return 'Assenza';
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
            [['therapist_id', 'start_date', 'end_date', 'type', 'created_by'], 'required'],
            [['therapist_id', 'approved_by', 'created_by'], 'integer'],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['approved_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['reason', 'notes'], 'string'],
            [['type'], 'string', 'max' => 20],
            [['status'], 'string', 'max' => 20],
            [['type'], 'in', 'range' => [self::TYPE_VACATION, self::TYPE_SICK_LEAVE, self::TYPE_PERSONAL, self::TYPE_TRAINING, self::TYPE_OTHER]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED]],
            [['end_date'], 'validateEndDate'],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['approved_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['approved_by' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
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
            'therapist_id' => 'Terapista',
            'start_date' => 'Data Inizio',
            'end_date' => 'Data Fine',
            'type' => 'Tipo',
            'reason' => 'Motivo',
            'status' => 'Stato',
            'approved_by' => 'Approvato da',
            'approved_at' => 'Approvato il',
            'notes' => 'Note',
            'created_by' => 'Creato da',
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
     * Gets query for [[ApprovedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getApprovedBy()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
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
     * Gets query for [[AbsenceRecoveries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAbsenceRecoveries()
    {
        return $this->hasMany(AbsenceRecovery::class, ['absence_id' => 'id']);
    }

    /**
     * Gets type labels
     *
     * @return array
     */
    public static function getTypeLabels()
    {
        return [
            self::TYPE_VACATION => 'Ferie',
            self::TYPE_SICK_LEAVE => 'Congedo Malattia',
            self::TYPE_PERSONAL => 'Personale',
            self::TYPE_TRAINING => 'Formazione',
            self::TYPE_OTHER => 'Altro',
        ];
    }

    /**
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'In Attesa',
            self::STATUS_APPROVED => 'Approvato',
            self::STATUS_REJECTED => 'Rifiutato',
            self::STATUS_CANCELLED => 'Annullato',
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
     * Gets duration in days
     *
     * @return int
     */
    public function getDurationDays()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $start = new \DateTime($this->start_date);
        $end = new \DateTime($this->end_date);
        
        return $end->diff($start)->days + 1; // +1 to include both start and end days
    }

    /**
     * Checks if absence is approved
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Approves absence
     *
     * @param int $approvedBy
     * @return bool
     */
    public function approve($approvedBy)
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approvedBy;
        $this->approved_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Rejects absence
     *
     * @return bool
     */
    public function reject()
    {
        $this->status = self::STATUS_REJECTED;
        return $this->save();
    }

    /**
     * {@inheritdoc}
     * @return AbsenceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AbsenceQuery(get_called_class());
    }
} 