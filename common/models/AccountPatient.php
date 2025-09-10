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
 * @property bool $has_parental_authority
 * @property string $relationship_type
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
    const RELATIONSHIP_SELF = 'self';
    const RELATIONSHIP_PARENT = 'parent';
    const RELATIONSHIP_TUTOR = 'tutor';
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
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function($model) {
                    return 'AccountPaziente';
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
            [['user_id', 'patient_id', 'relationship_type'], 'required'],
            [['user_id', 'patient_id'], 'integer'],
            [['has_parental_authority'], 'boolean'],
            [['relationship_type'], 'string', 'max' => 10],
            [['relationship_type'], 'in', 'range' => $this->getRelationshipOptions()],
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
            'relationship_type' => 'Tipo Relazione',
            'has_parental_authority' => 'Ha Autorità Genitoriale',
            'created_at' => 'Creato il',
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
            self::RELATIONSHIP_TUTOR,
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
            self::RELATIONSHIP_TUTOR => 'Tutore',
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
        return $labels[$this->relationship_type] ?? $this->relationship_type;
    }

    /**
     * Checks if user has parental authority
     *
     * @return bool
     */
    public function hasParentalAuthority()
    {
        return (bool) $this->has_parental_authority;
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