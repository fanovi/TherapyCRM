<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "therapist_substitutions".
 *
 * @property int $id
 * @property int $original_therapist_id
 * @property int $substitute_therapist_id
 * @property string $start_date
 * @property string $end_date
 * @property string|null $reason
 * @property string $status
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $notes
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Therapist $originalTherapist
 * @property Therapist $substituteTherapist
 * @property User $approvedBy
 * @property User $createdBy
 */
class TherapistSubstitution extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%therapist_substitutions}}';
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
            [['original_therapist_id', 'substitute_therapist_id', 'start_date', 'end_date', 'created_by'], 'required'],
            [['original_therapist_id', 'substitute_therapist_id', 'approved_by', 'created_by'], 'integer'],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['approved_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['reason', 'notes'], 'string'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_ACTIVE, self::STATUS_COMPLETED, self::STATUS_CANCELLED]],
            [['end_date'], 'validateEndDate'],
            [['substitute_therapist_id'], 'validateDifferentTherapist'],
            [['original_therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['original_therapist_id' => 'id']],
            [['substitute_therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['substitute_therapist_id' => 'id']],
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
     * Validates that substitute therapist is different from original
     */
    public function validateDifferentTherapist($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->original_therapist_id)) {
            if ($this->$attribute == $this->original_therapist_id) {
                $this->addError($attribute, 'Il terapista sostituto deve essere diverso dal terapista originale');
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
            'original_therapist_id' => 'Terapista Originale',
            'substitute_therapist_id' => 'Terapista Sostituto',
            'start_date' => 'Data Inizio',
            'end_date' => 'Data Fine',
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
     * Gets query for [[OriginalTherapist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOriginalTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'original_therapist_id']);
    }

    /**
     * Gets query for [[SubstituteTherapist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubstituteTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'substitute_therapist_id']);
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
            self::STATUS_ACTIVE => 'Attivo',
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
        
        return $end->diff($start)->days + 1;
    }

    /**
     * Checks if substitution is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->start_date <= date('Y-m-d') && 
               $this->end_date >= date('Y-m-d');
    }

    /**
     * Approves substitution
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
     * Activates substitution
     *
     * @return bool
     */
    public function activate()
    {
        $this->status = self::STATUS_ACTIVE;
        return $this->save();
    }
} 