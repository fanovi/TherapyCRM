<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * AuthToken model
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $token
 * @property string $refresh_token
 * @property integer $is_revoked
 * @property integer $created_at
 * @property integer $expires_at
 * @property integer $refresh_expires_at
 * @property integer $last_used_at
 *
 * @property User $user
 */
class AuthToken extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_token}}';
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
                'updatedAtAttribute' => false, // Non utilizzare updated_at
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'token', 'expires_at', 'refresh_expires_at'], 'required'],
            [['user_id', 'is_revoked', 'created_at', 'expires_at', 'refresh_expires_at', 'last_used_at'], 'integer'],
            [['token', 'refresh_token'], 'string'],
            [['is_revoked'], 'default', 'value' => 0],
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
            'user_id' => 'Utente',
            'token' => 'Token',
            'refresh_token' => 'Refresh Token',
            'is_revoked' => 'Revocato',
            'created_at' => 'Creato il',
            'expires_at' => 'Scade il',
            'refresh_expires_at' => 'Refresh Scade il',
            'last_used_at' => 'Ultimo uso',
        ];
    }

    /**
     * Relazione con l'utente
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Verifica se il token è scaduto
     * @return bool
     */
    public function isExpired()
    {
        return time() >= $this->expires_at;
    }

    /**
     * Verifica se il refresh token è scaduto
     * @return bool
     */
    public function isRefreshExpired()
    {
        return time() >= $this->refresh_expires_at;
    }

    /**
     * Verifica se il token è valido
     * @return bool
     */
    public function isValid()
    {
        return !$this->is_revoked && !$this->isExpired();
    }

    /**
     * Revoca il token
     * @return bool
     */
    public function revoke()
    {
        $this->is_revoked = 1;
        return $this->save();
    }

    /**
     * Aggiorna il timestamp dell'ultimo uso
     * @return bool
     */
    public function updateLastUsed()
    {
        $this->last_used_at = time();
        return $this->save();
    }

    /**
     * Trova token valido per stringa token
     * @param string $tokenString
     * @return static|null
     */
    public static function findValidToken($tokenString)
    {
        return static::find()
            ->where(['token' => $tokenString])
            ->andWhere(['is_revoked' => 0])
            ->andWhere(['>', 'expires_at', time()])
            ->one();
    }

    /**
     * Pulisce i token scaduti
     * @return int numero di token eliminati
     */
    public static function cleanupExpiredTokens()
    {
        return static::deleteAll(['<', 'expires_at', time()]);
    }
}

