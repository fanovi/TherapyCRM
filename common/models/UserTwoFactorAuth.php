<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_two_factor_auth".
 *
 * @property int $id
 * @property int $user_id
 * @property int $is_enabled
 * @property string $preferred_method
 * @property string|null $totp_secret
 * @property int|null $totp_confirmed_at
 * @property string|null $email_otp_code
 * @property int|null $email_otp_expires_at
 * @property int $email_otp_attempts
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class UserTwoFactorAuth extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_two_factor_auth}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => time(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'is_enabled', 'totp_confirmed_at', 'email_otp_expires_at', 'email_otp_attempts', 'created_at', 'updated_at'], 'integer'],
            [['preferred_method'], 'string', 'max' => 10],
            [['preferred_method'], 'in', 'range' => ['email', 'totp']],
            [['totp_secret'], 'string', 'max' => 255],
            [['email_otp_code'], 'string', 'max' => 255],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['is_enabled'], 'default', 'value' => 0],
            [['preferred_method'], 'default', 'value' => 'email'],
            [['email_otp_attempts'], 'default', 'value' => 0],
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
            'is_enabled' => 'Abilitato',
            'preferred_method' => 'Metodo preferito',
            'totp_secret' => 'Secret TOTP',
            'totp_confirmed_at' => 'TOTP confermato il',
            'email_otp_code' => 'Codice OTP Email',
            'email_otp_expires_at' => 'Scadenza OTP',
            'email_otp_attempts' => 'Tentativi OTP',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
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
     * Genera e salva un codice OTP email (hash)
     *
     * @return string Il codice OTP in chiaro (da inviare via email)
     */
    public function generateEmailOtp()
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->email_otp_code = Yii::$app->security->generatePasswordHash($code);
        $this->email_otp_expires_at = time() + SystemSetting::getEmailOtpExpirySeconds();
        $this->email_otp_attempts = 0;
        if (!$this->save()) {
            Yii::error('Failed to save email OTP: ' . json_encode($this->errors), 'app.2fa');
            throw new \RuntimeException('Impossibile generare il codice OTP');
        }

        return $code;
    }

    /**
     * Valida un codice OTP email
     *
     * @param string $code
     * @return bool
     */
    public function validateEmailOtp($code)
    {
        // Controlla scadenza
        if ($this->email_otp_expires_at < time()) {
            return false;
        }

        // Controlla max tentativi
        if ($this->email_otp_attempts >= SystemSetting::getMaxOtpAttempts()) {
            return false;
        }

        // Incrementa tentativi
        $this->email_otp_attempts += 1;
        if (!$this->save()) {
            Yii::error('Failed to save OTP attempt increment: ' . json_encode($this->errors), 'app.2fa');
        }

        // Valida hash
        if (!Yii::$app->security->validatePassword($code, $this->email_otp_code)) {
            return false;
        }

        // Invalida OTP dopo uso
        $this->email_otp_code = null;
        $this->email_otp_expires_at = null;
        $this->email_otp_attempts = 0;
        if (!$this->save()) {
            Yii::error('Failed to invalidate OTP after use: ' . json_encode($this->errors), 'app.2fa');
        }

        return true;
    }

    /**
     * Cripta il secret TOTP prima del salvataggio
     *
     * @param string $secret Secret in chiaro
     */
    public function setTotpSecretEncrypted($secret)
    {
        $encryptionKey = Yii::$app->params['encryptionKey'];
        $encrypted = Yii::$app->security->encryptByKey($secret, $encryptionKey);
        $this->totp_secret = base64_encode($encrypted);
    }

    /**
     * Decripta il secret TOTP
     *
     * @return string|null Secret in chiaro
     */
    public function getTotpSecretDecrypted()
    {
        if (empty($this->totp_secret)) {
            return null;
        }

        $encryptionKey = Yii::$app->params['encryptionKey'];
        $decoded = base64_decode($this->totp_secret, true);

        if ($decoded === false) {
            return null;
        }

        $decrypted = Yii::$app->security->decryptByKey($decoded, $encryptionKey);

        return $decrypted !== false ? $decrypted : null;
    }

    /**
     * Trova o crea il record 2FA per un utente
     *
     * @param int $userId
     * @return static
     */
    public static function findOrCreate($userId)
    {
        $model = static::findOne(['user_id' => $userId]);

        if (!$model) {
            $model = new static();
            $model->user_id = $userId;
            $model->save();
        }

        return $model;
    }
}
