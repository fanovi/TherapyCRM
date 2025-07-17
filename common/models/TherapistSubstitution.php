<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "therapist_substitutions".
 *
 * @property int $id
 * @property int $appointment_id
 * @property int $original_therapist_id
 * @property int $substitute_therapist_id
 * @property string|null $reason
 * @property int $substituted_by
 * @property string $substituted_at
 *
 * @property Appointment $appointment
 * @property Therapist $originalTherapist
 * @property Therapist $substituteTherapist
 * @property User $substitutedBy
 */
class TherapistSubstitution extends ActiveRecord
{

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
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['substituted_at'],
                'entityNameCallback' => function($model) {
                    return 'Sostituzione Terapista';
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
            [['appointment_id', 'original_therapist_id', 'substitute_therapist_id', 'substituted_by'], 'required'],
            [['appointment_id', 'original_therapist_id', 'substitute_therapist_id', 'substituted_by'], 'integer'],
            [['substituted_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['reason'], 'string'],
            [['substitute_therapist_id'], 'validateDifferentTherapist'],
            [['appointment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Appointment::class, 'targetAttribute' => ['appointment_id' => 'id']],
            [['original_therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['original_therapist_id' => 'id']],
            [['substitute_therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['substitute_therapist_id' => 'id']],
            [['substituted_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['substituted_by' => 'id']],
        ];
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
            'appointment_id' => 'Appuntamento',
            'original_therapist_id' => 'Terapista Originale',
            'substitute_therapist_id' => 'Terapista Sostituto',
            'reason' => 'Motivo',
            'substituted_by' => 'Sostituito da',
            'substituted_at' => 'Sostituito il',
        ];
    }

    /**
     * Gets query for [[Appointment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAppointment()
    {
        return $this->hasOne(Appointment::class, ['id' => 'appointment_id']);
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
     * Gets query for [[SubstitutedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubstitutedBy()
    {
        return $this->hasOne(User::class, ['id' => 'substituted_by']);
    }

    /**
     * Creates a new substitution record
     *
     * @param int $appointmentId
     * @param int $originalTherapistId
     * @param int $substituteTherapistId
     * @param string|null $reason
     * @param int $substitutedBy
     * @return static
     */
    public static function createSubstitution($appointmentId, $originalTherapistId, $substituteTherapistId, $reason = null, $substitutedBy = null)
    {
        $substitution = new static();
        $substitution->appointment_id = $appointmentId;
        $substitution->original_therapist_id = $originalTherapistId;
        $substitution->substitute_therapist_id = $substituteTherapistId;
        $substitution->reason = $reason;
        $substitution->substituted_by = $substitutedBy ?: Yii::$app->user->id;
        $substitution->substituted_at = date('Y-m-d H:i:s');
        
        return $substitution;
    }

    /**
     * Gets substitution for specific appointment
     *
     * @param int $appointmentId
     * @return static|null
     */
    public static function findByAppointment($appointmentId)
    {
        return static::find()
            ->where(['appointment_id' => $appointmentId])
            ->one();
    }

    /**
     * Gets all substitutions for a therapist
     *
     * @param int $therapistId
     * @param bool $asOriginal If true, finds where therapist was original, if false where they were substitute
     * @return static[]
     */
    public static function findByTherapist($therapistId, $asOriginal = true)
    {
        $attribute = $asOriginal ? 'original_therapist_id' : 'substitute_therapist_id';
        
        return static::find()
            ->where([$attribute => $therapistId])
            ->orderBy(['substituted_at' => SORT_DESC])
            ->all();
    }
} 