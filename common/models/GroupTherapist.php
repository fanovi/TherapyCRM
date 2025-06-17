<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "group_therapists".
 *
 * @property int $id
 * @property int $group_id
 * @property int $therapist_id
 * @property string $role
 * @property int $is_active
 * @property string $joined_at
 * @property string|null $left_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property CoordinatorGroup $group
 * @property Therapist $therapist
 */
class GroupTherapist extends ActiveRecord
{
    const ROLE_MEMBER = 'member';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_ASSISTANT = 'assistant';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%group_therapists}}';
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
            [['group_id', 'therapist_id', 'role', 'joined_at'], 'required'],
            [['group_id', 'therapist_id', 'is_active'], 'integer'],
            [['is_active'], 'boolean'],
            [['joined_at', 'left_at'], 'date', 'format' => 'php:Y-m-d'],
            [['role'], 'string', 'max' => 50],
            [['role'], 'in', 'range' => [self::ROLE_MEMBER, self::ROLE_SUPERVISOR, self::ROLE_ASSISTANT]],
            [['group_id', 'therapist_id'], 'unique', 'targetAttribute' => ['group_id', 'therapist_id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => CoordinatorGroup::class, 'targetAttribute' => ['group_id' => 'id']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['left_at'], 'validateLeftAt'],
        ];
    }

    /**
     * Validates left_at date
     */
    public function validateLeftAt($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->joined_at)) {
            if ($this->$attribute < $this->joined_at) {
                $this->addError($attribute, 'La data di uscita non può essere precedente alla data di ingresso');
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
            'group_id' => 'Gruppo',
            'therapist_id' => 'Terapista',
            'role' => 'Ruolo',
            'is_active' => 'Attivo',
            'joined_at' => 'Data Ingresso',
            'left_at' => 'Data Uscita',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Group]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(CoordinatorGroup::class, ['id' => 'group_id']);
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
     * Gets role labels
     *
     * @return array
     */
    public static function getRoleLabels()
    {
        return [
            self::ROLE_MEMBER => 'Membro',
            self::ROLE_SUPERVISOR => 'Supervisore',
            self::ROLE_ASSISTANT => 'Assistente',
        ];
    }

    /**
     * Gets role label
     *
     * @return string
     */
    public function getRoleLabel()
    {
        $labels = static::getRoleLabels();
        return $labels[$this->role] ?? $this->role;
    }

    /**
     * Checks if therapist is active in group
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->is_active && empty($this->left_at);
    }
} 