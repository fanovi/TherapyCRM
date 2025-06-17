<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "account_patients".
 *
 * @property int $id
 * @property int $user_id
 * @property int $patient_id
 * @property string $relationship
 * @property int $can_view_appointments
 * @property int $can_cancel_appointments
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property Patient $patient
 */
class AccountPatient extends ActiveRecord
{
    const RELATIONSHIP_PARENT = 'parent';
    const RELATIONSHIP_GUARDIAN = 'guardian';
    const RELATIONSHIP_SPOUSE = 'spouse';
    const RELATIONSHIP_SELF = 'self';
    const RELATIONSHIP_OTHER = 'other';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%account_patients}}';
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
            [['user_id', 'patient_id', 'relationship'], 'required'],
            [['user_id', 'patient_id', 'can_view_appointments', 'can_cancel_appointments'], 'integer'],
            [['can_view_appointments', 'can_cancel_appointments'], 'boolean'],
            [['relationship'], 'string', 'max' => 50],
            [['relationship'], 'in', 'range' => $this->getRelationshipOptions()],
            [['user_id', 'patient_id'], 'unique', 'targetAttribute' => ['user_id', 'patient_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Utente',
            'patient_id' => 'Paziente',
            'relationship' => 'Relazione',
            'can_view_appointments' => 'Può visualizzare appuntamenti',
            'can_cancel_appointments' => 'Può cancellare appuntamenti',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
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
     * Gets available relationship options
     *
     * @return array
     */
    public function getRelationshipOptions()
    {
        return [
            self::RELATIONSHIP_SELF,
            self::RELATIONSHIP_PARENT,
            self::RELATIONSHIP_GUARDIAN,
            self::RELATIONSHIP_SPOUSE,
            self::RELATIONSHIP_OTHER,
        ];
    }

    /**
     * Gets relationship labels
     *
     * @return array
     */
    public static function getRelationshipLabels()
    {
        return [
            self::RELATIONSHIP_SELF => 'Io stesso',
            self::RELATIONSHIP_PARENT => 'Genitore',
            self::RELATIONSHIP_GUARDIAN => 'Tutore',
            self::RELATIONSHIP_SPOUSE => 'Coniuge',
            self::RELATIONSHIP_OTHER => 'Altro',
        ];
    }

    /**
     * Gets relationship label
     *
     * @return string
     */
    public function getRelationshipLabel()
    {
        $labels = static::getRelationshipLabels();
        return $labels[$this->relationship] ?? $this->relationship;
    }

    /**
     * Checks if user can view patient appointments
     *
     * @return bool
     */
    public function canViewAppointments()
    {
        return (bool) $this->can_view_appointments;
    }

    /**
     * Checks if user can cancel patient appointments
     *
     * @return bool
     */
    public function canCancelAppointments()
    {
        return (bool) $this->can_cancel_appointments;
    }

    /**
     * Gets patients for specific user
     *
     * @param int $userId
     * @return static[]
     */
    public static function findByUser($userId)
    {
        return static::find()
            ->where(['user_id' => $userId])
            ->joinWith('patient')
            ->orderBy('patients.last_name, patients.first_name')
            ->all();
    }
} 