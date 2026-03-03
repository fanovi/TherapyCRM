<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "biometric_device".
 *
 * @property int $id
 * @property int $user_id
 * @property string $biometric_token
 * @property string|null $device_name
 * @property string|null $platform
 * @property int $is_active
 * @property int $failed_attempts
 * @property int $created_at
 * @property int|null $last_used_at
 * @property int $expires_at
 *
 * @property User $user
 */
class BiometricDevice extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%biometric_device}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'biometric_token', 'expires_at', 'created_at'], 'required'],
            [['user_id', 'is_active', 'failed_attempts', 'created_at', 'last_used_at', 'expires_at'], 'integer'],
            [['biometric_token'], 'string', 'max' => 128],
            [['device_name'], 'string', 'max' => 255],
            [['platform'], 'string', 'max' => 20],
            [['biometric_token'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['is_active'], 'default', 'value' => 1],
            [['failed_attempts'], 'default', 'value' => 0],
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
            'biometric_token' => 'Token Biometrico',
            'device_name' => 'Nome Dispositivo',
            'platform' => 'Piattaforma',
            'is_active' => 'Attivo',
            'failed_attempts' => 'Tentativi Falliti',
            'created_at' => 'Creato il',
            'last_used_at' => 'Ultimo utilizzo',
            'expires_at' => 'Scadenza',
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
     * Registra un nuovo dispositivo biometrico per l'utente.
     * Se il limite max dispositivi viene superato, revoca il più vecchio.
     *
     * @param int $userId
     * @param string|null $deviceName
     * @param string|null $platform
     * @return static|null
     */
    public static function register($userId, $deviceName = null, $platform = null)
    {
        $maxDevices = SystemSetting::getBiometricMaxDevices();
        $expiryDays = SystemSetting::getBiometricTokenExpiryDays();

        // Conta dispositivi attivi per l'utente
        $activeCount = static::find()
            ->where(['user_id' => $userId, 'is_active' => 1])
            ->count();

        // Se raggiunto il limite, revoca il più vecchio
        if ($activeCount >= $maxDevices) {
            $oldest = static::find()
                ->where(['user_id' => $userId, 'is_active' => 1])
                ->orderBy(['created_at' => SORT_ASC])
                ->one();

            if ($oldest) {
                $oldest->is_active = 0;
                $oldest->save(false);
            }
        }

        $device = new static();
        $device->user_id = $userId;
        $device->biometric_token = Yii::$app->security->generateRandomString(128);
        $device->device_name = $deviceName;
        $device->platform = $platform;
        $device->is_active = 1;
        $device->failed_attempts = 0;
        $device->created_at = time();
        $device->last_used_at = time();
        $device->expires_at = time() + ($expiryDays * 86400);

        return $device->save() ? $device : null;
    }

    /**
     * Valida un token biometrico.
     * Controlla: attivo, non scaduto, tentativi falliti sotto il limite.
     * Se valido, aggiorna last_used_at e resetta failed_attempts.
     *
     * @param string $biometricToken
     * @return static|null Il device se valido, null altrimenti
     */
    public static function validateToken($biometricToken)
    {
        if (empty($biometricToken)) {
            return null;
        }

        $device = static::findOne([
            'biometric_token' => $biometricToken,
            'is_active' => 1,
        ]);

        if (!$device) {
            return null;
        }

        // Controlla scadenza
        if ($device->expires_at < time()) {
            $device->is_active = 0;
            $device->save(false);
            return null;
        }

        // Controlla tentativi falliti
        $maxAttempts = SystemSetting::getBiometricMaxFailedAttempts();
        if ($device->failed_attempts >= $maxAttempts) {
            $device->is_active = 0;
            $device->save(false);
            return null;
        }

        // Token valido - aggiorna
        $device->last_used_at = time();
        $device->failed_attempts = 0;
        $device->save(false);

        return $device;
    }

    /**
     * Incrementa il contatore tentativi falliti per un token.
     *
     * @param string $biometricToken
     */
    public static function incrementFailedAttempts($biometricToken)
    {
        $device = static::findOne([
            'biometric_token' => $biometricToken,
            'is_active' => 1,
        ]);

        if ($device) {
            $device->failed_attempts++;
            $maxAttempts = SystemSetting::getBiometricMaxFailedAttempts();
            if ($device->failed_attempts >= $maxAttempts) {
                $device->is_active = 0;
            }
            $device->save(false);
        }
    }

    /**
     * Revoca tutti i dispositivi biometrici di un utente
     *
     * @param int $userId
     * @return int Numero di dispositivi revocati
     */
    public static function revokeAllForUser($userId)
    {
        return static::updateAll(
            ['is_active' => 0],
            ['user_id' => $userId, 'is_active' => 1]
        );
    }

    /**
     * Revoca un singolo dispositivo biometrico
     *
     * @param int $userId
     * @param string $biometricToken
     * @return bool
     */
    public static function revokeDevice($userId, $biometricToken)
    {
        return static::updateAll(
            ['is_active' => 0],
            ['user_id' => $userId, 'biometric_token' => $biometricToken, 'is_active' => 1]
        ) > 0;
    }

    /**
     * Verifica se l'utente ha almeno un dispositivo biometrico attivo
     *
     * @param int $userId
     * @return bool
     */
    public static function hasActiveDevice($userId)
    {
        return static::find()
            ->where(['user_id' => $userId, 'is_active' => 1])
            ->andWhere(['>', 'expires_at', time()])
            ->exists();
    }

    /**
     * Ritorna tutti i dispositivi biometrici attivi dell'utente
     *
     * @param int $userId
     * @return array
     */
    public static function getActiveDevices($userId)
    {
        return static::find()
            ->where(['user_id' => $userId, 'is_active' => 1])
            ->andWhere(['>', 'expires_at', time()])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
}
