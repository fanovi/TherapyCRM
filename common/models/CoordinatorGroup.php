<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "coordinator_groups".
 *
 * @property int $id
 * @property string $name
 * @property int $coordinator_user_id
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $coordinator
 * @property GroupTherapist[] $groupTherapists
 * @property Therapist[] $therapists
 */
class CoordinatorGroup extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%coordinator_groups}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'coordinator_user_id'], 'required'],
            [['coordinator_user_id', 'is_active'], 'integer'],
            [['is_active'], 'boolean'],
            [['name'], 'string', 'max' => 100],
            [['name'], 'unique'],
            [['coordinator_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['coordinator_user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Nome Gruppo',
            'coordinator_user_id' => 'Coordinatore',
            'is_active' => 'Attivo',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Coordinator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCoordinator()
    {
        return $this->hasOne(User::class, ['id' => 'coordinator_user_id']);
    }

    /**
     * Gets query for [[GroupTherapists]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroupTherapists()
    {
        return $this->hasMany(GroupTherapist::class, ['group_id' => 'id']);
    }

    /**
     * Gets query for [[Therapists]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapists()
    {
        return $this->hasMany(Therapist::class, ['id' => 'therapist_id'])
            ->viaTable('{{%group_therapists}}', ['group_id' => 'id']);
    }

    /**
     * Gets all groups for dropdown
     *
     * @return array
     */
    public static function getDropdownData()
    {
        return ArrayHelper::map(
            static::find()->orderBy('name')->all(),
            'id',
            'name'
        );
    }

    /**
     * Gets groups by coordinator
     *
     * @param int $coordinatorId
     * @return static[]
     */
    public static function findByCoordinator($coordinatorId)
    {
        return static::find()
            ->where(['coordinator_user_id' => $coordinatorId])
            ->orderBy('name')
            ->all();
    }

    /**
     * Gets therapist count
     *
     * @return int
     */
    public function getTherapistCount()
    {
        return $this->getTherapists()->count();
    }
} 