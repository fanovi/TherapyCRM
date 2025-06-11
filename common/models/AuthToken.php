<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "auth_token".
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $refresh_token
 * @property int $is_revoked
 * @property int $created_at
 * @property int $expires_at
 * @property int $refresh_expires_at
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
            [['refresh_token', 'last_used_at'], 'default', 'value' => null],
            [['is_revoked'], 'default', 'value' => 0],
            [['user_id', 'token', 'created_at', 'expires_at', 'refresh_expires_at'], 'required'],
            [['user_id', 'is_revoked', 'created_at', 'expires_at', 'refresh_expires_at', 'last_used_at'], 'integer'],
            [['token', 'refresh_token'], 'string'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'token' => 'Token',
            'refresh_token' => 'Refresh Token',
            'is_revoked' => 'Is Revoked',
            'created_at' => 'Created At',
            'expires_at' => 'Expires At',
            'refresh_expires_at' => 'Refresh Expires At',
            'last_used_at' => 'Last Used At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery|yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
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
