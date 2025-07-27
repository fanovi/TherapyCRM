<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "therapeutic_plan_notifications".
 *
 * @property int $id
 * @property int $therapeutic_plan_id
 * @property int $user_id
 * @property int $days_before
 * @property int|null $notification_id
 * @property string $sent_at
 * @property string $created_at
 *
 * @property TherapeuticPlan $therapeuticPlan
 * @property User $user
 * @property Notification $notification
 */
class TherapeuticPlanNotification extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%therapeutic_plan_notifications}}';
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['therapeutic_plan_id', 'user_id', 'days_before', 'sent_at'], 'required'],
            [['therapeutic_plan_id', 'user_id', 'days_before', 'notification_id'], 'integer'],
            [['sent_at', 'created_at'], 'safe'],
            [['therapeutic_plan_id', 'user_id', 'days_before'], 'unique', 'targetAttribute' => ['therapeutic_plan_id', 'user_id', 'days_before']],
            [['therapeutic_plan_id'], 'exist', 'skipOnError' => true, 'targetClass' => TherapeuticPlan::class, 'targetAttribute' => ['therapeutic_plan_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['notification_id'], 'exist', 'skipOnError' => true, 'targetClass' => Notification::class, 'targetAttribute' => ['notification_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'therapeutic_plan_id' => 'Piano Terapeutico',
            'user_id' => 'Utente',
            'days_before' => 'Giorni Prima',
            'notification_id' => 'Notifica',
            'sent_at' => 'Inviata il',
            'created_at' => 'Creata il',
        ];
    }

    /**
     * Gets query for [[TherapeuticPlan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapeuticPlan()
    {
        return $this->hasOne(TherapeuticPlan::class, ['id' => 'therapeutic_plan_id']);
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
     * Gets query for [[Notification]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotification()
    {
        return $this->hasOne(Notification::class, ['id' => 'notification_id']);
    }

    /**
     * Verifica se una notifica è già stata inviata
     *
     * @param int $therapeuticPlanId
     * @param int $userId
     * @param int $daysBefore
     * @return bool
     */
    public static function isAlreadySent($therapeuticPlanId, $userId, $daysBefore)
    {
        return static::find()
            ->where([
                'therapeutic_plan_id' => $therapeuticPlanId,
                'user_id' => $userId,
                'days_before' => $daysBefore,
            ])
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if ($insert && !$this->sent_at) {
            $this->sent_at = date('Y-m-d H:i:s');
        }
        
        return parent::beforeSave($insert);
    }
}