<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "trusted_device".
 *
 * @property int $id
 * @property int $user_id
 * @property string $device_token
 * @property string|null $device_name
 * @property int|null $last_used_at
 * @property int $expires_at
 * @property int $is_revoked
 * @property int $created_at
 *
 * @property User $user
 */
class TrustedDevice extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%trusted_device}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'device_token', 'expires_at', 'created_at'], 'required'],
            [['user_id', 'last_used_at', 'expires_at', 'is_revoked', 'created_at'], 'integer'],
            [['device_token'], 'string', 'max' => 64],
            [['device_name'], 'string', 'max' => 255],
            [['device_token'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['is_revoked'], 'default', 'value' => 0],
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
            'device_token' => 'Token Dispositivo',
            'device_name' => 'Nome Dispositivo',
            'last_used_at' => 'Ultimo utilizzo',
            'expires_at' => 'Scadenza',
            'is_revoked' => 'Revocato',
            'created_at' => 'Creato il',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Crea un nuovo dispositivo fidato
     *
     * @param int $userId
     * @param string|null $deviceName
     * @return static|null
     */
    public static function createTrusted($userId, $deviceName = null)
    {
        $days = SystemSetting::getRememberDeviceDays();

        $device = new static();
        $device->user_id = $userId;
        $device->device_token = Yii::$app->security->generateRandomString(64);
        $device->device_name = $deviceName;
        $device->expires_at = time() + ($days * 86400);
        $device->created_at = time();
        $device->last_used_at = time();

        return $device->save() ? $device : null;
    }

    /**
     * Valida un device token per un utente
     *
     * @param int $userId
     * @param string $deviceToken
     * @return bool
     */
    public static function isDeviceTrusted($userId, $deviceToken)
    {
        if (empty($deviceToken)) {
            return false;
        }

        $device = static::findOne([
            'user_id' => $userId,
            'device_token' => $deviceToken,
            'is_revoked' => 0,
        ]);

        if (!$device) {
            return false;
        }

        // Controlla scadenza
        if ($device->expires_at < time()) {
            $device->is_revoked = 1;
            $device->save();
            return false;
        }

        // Aggiorna ultimo utilizzo
        $device->last_used_at = time();
        $device->save();

        return true;
    }

    /**
     * Revoca tutti i dispositivi di un utente
     *
     * @param int $userId
     * @return int Numero di dispositivi revocati
     */
    public static function revokeAllForUser($userId)
    {
        return static::updateAll(
            ['is_revoked' => 1],
            ['user_id' => $userId, 'is_revoked' => 0]
        );
    }
}
