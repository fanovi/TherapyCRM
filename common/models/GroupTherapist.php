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
 * @property string $assigned_from
 * @property string|null $assigned_to
 * @property int $assigned_by
 *
 * @property CoordinatorGroup $group
 * @property Therapist $therapist
 * @property User $assignedBy
 */
class GroupTherapist extends ActiveRecord
{

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
             // Activity Logging
             [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => [
                    'assigned_from',  // Data gestita manualmente
                    'assigned_to',    // Data gestita manualmente
                ],
                'entityNameCallback' => function($model) {
                    return 'Gruppo Terapista';
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
            [['group_id', 'therapist_id', 'assigned_from', 'assigned_by'], 'required'],
            [['group_id', 'therapist_id', 'assigned_by'], 'integer'],
            [['assigned_from', 'assigned_to'], 'date', 'format' => 'php:Y-m-d'],
            [['group_id', 'therapist_id'], 'unique', 'targetAttribute' => ['group_id', 'therapist_id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => CoordinatorGroup::class, 'targetAttribute' => ['group_id' => 'id']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['assigned_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['assigned_by' => 'id']],
            [['assigned_to'], 'validateAssignedTo'],
        ];
    }

    /**
     * Validates assigned_to date
     */
    public function validateAssignedTo($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->assigned_from)) {
            if ($this->$attribute < $this->assigned_from) {
                $this->addError($attribute, 'La data di fine assegnazione non può essere precedente alla data di inizio');
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
            'assigned_from' => 'Assegnato dal',
            'assigned_to' => 'Assegnato fino al',
            'assigned_by' => 'Assegnato da',
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
     * Gets query for [[AssignedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedBy()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_by']);
    }

    /**
     * Checks if therapist is currently active in group
     *
     * @return bool
     */
    public function isActive()
    {
        return empty($this->assigned_to);
    }
} 