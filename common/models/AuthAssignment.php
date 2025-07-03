<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * AuthAssignment model
 *
 * @property string $item_name
 * @property string $user_id
 * @property integer $created_at
 *
 * @property AuthItem $item
 * @property User $user
 */
class AuthAssignment extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_assignment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['item_name', 'user_id'], 'required'],
            [['created_at'], 'integer'],
            [['item_name', 'user_id'], 'string', 'max' => 64],
            [['item_name'], 'exist', 'skipOnError' => true, 'targetClass' => AuthItem::class, 'targetAttribute' => ['item_name' => 'name']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'item_name' => 'Ruolo/Permesso',
            'user_id' => 'Utente',
            'created_at' => 'Assegnato il',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItem()
    {
        return $this->hasOne(AuthItem::class, ['name' => 'item_name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Assegna un ruolo ad un utente
     * @param string $itemName
     * @param string|int $userId
     * @return bool
     */
    public static function assign($itemName, $userId)
    {
        $assignment = new static();
        $assignment->item_name = $itemName;
        $assignment->user_id = (string)$userId;
        $assignment->created_at = time();
        
        return $assignment->save();
    }

    /**
     * Rimuove un'assegnazione
     * @param string $itemName
     * @param string|int $userId
     * @return int
     */
    public static function revoke($itemName, $userId)
    {
        return static::deleteAll([
            'item_name' => $itemName,
            'user_id' => (string)$userId
        ]);
    }

    /**
     * Ottiene tutti i ruoli di un utente
     * @param string|int $userId
     * @return array
     */
    public static function getUserRoles($userId)
    {
        return static::find()
            ->joinWith('item')
            ->where(['user_id' => (string)$userId])
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_ROLE])
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at'],
                'entityNameCallback' => function($model) {
                    return 'Assegnazione Ruolo';
                },
            ],
        ];
    }
} 