<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "auth_token".
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property int $is_revoked
 * @property int $created_at
 * @property int $expires_at
 * @property int|null $last_used_at
 *
 * @property User $user
 */
class AuthToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'token', 'created_at', 'expires_at'], 'required'],
            [['user_id', 'is_revoked', 'created_at', 'expires_at', 'last_used_at'], 'integer'],
            [['token', 'refresh_token'], 'string'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'token' => Yii::t('app', 'Token'),
            'is_revoked' => Yii::t('app', 'Is Revoked'),
            'created_at' => Yii::t('app', 'Created At'),
            'expires_at' => Yii::t('app', 'Expires At'),
            'last_used_at' => Yii::t('app', 'Last Used At'),
            'refresh_token' => Yii::t('app', 'Refresh Token'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery|yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     * @return AuthTokenQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AuthTokenQuery(get_called_class());
    }
}
