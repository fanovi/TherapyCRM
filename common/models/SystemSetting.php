<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "system_setting".
 *
 * @property int $id
 * @property string $category
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property int $created_at
 * @property int $updated_at
 */
class SystemSetting extends ActiveRecord
{
    /**
     * Cache in-memory per evitare query ripetute nella stessa request
     */
    private static $_cache = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%system_setting}}';
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
            [['category', 'key'], 'required'],
            [['value'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['category'], 'string', 'max' => 50],
            [['key'], 'string', 'max' => 100],
            [['type'], 'string', 'max' => 20],
            [['description'], 'string', 'max' => 255],
            [['category', 'key'], 'unique', 'targetAttribute' => ['category', 'key']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Categoria',
            'key' => 'Chiave',
            'value' => 'Valore',
            'type' => 'Tipo',
            'description' => 'Descrizione',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Ottiene il valore di una impostazione
     *
     * @param string $category
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue($category, $key, $default = null)
    {
        $cacheKey = $category . '.' . $key;

        if (isset(self::$_cache[$cacheKey])) {
            return self::$_cache[$cacheKey];
        }

        $setting = static::findOne(['category' => $category, 'key' => $key]);

        if (!$setting) {
            return $default;
        }

        $value = self::castValue($setting->value, $setting->type);
        self::$_cache[$cacheKey] = $value;

        return $value;
    }

    /**
     * Imposta il valore di una impostazione
     *
     * @param string $category
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function setValue($category, $key, $value)
    {
        $setting = static::findOne(['category' => $category, 'key' => $key]);

        if (!$setting) {
            return false;
        }

        $setting->value = (string) $value;
        $result = $setting->save();

        // Aggiorna cache
        $cacheKey = $category . '.' . $key;
        self::$_cache[$cacheKey] = self::castValue($setting->value, $setting->type);

        return $result;
    }

    /**
     * Verifica se il 2FA è abilitato globalmente
     *
     * @return bool
     */
    public static function is2faEnabled()
    {
        return (bool) self::getValue('2fa', 'enabled', false);
    }

    /**
     * Verifica se "Ricorda dispositivo" è abilitato
     *
     * @return bool
     */
    public static function isRememberDeviceEnabled()
    {
        return (bool) self::getValue('2fa', 'remember_device_enabled', false);
    }

    /**
     * Ottiene i giorni di validità del dispositivo fidato
     *
     * @return int
     */
    public static function getRememberDeviceDays()
    {
        return (int) self::getValue('2fa', 'remember_device_days', 30);
    }

    /**
     * Ottiene la scadenza OTP email in secondi
     *
     * @return int
     */
    public static function getEmailOtpExpirySeconds()
    {
        return (int) self::getValue('2fa', 'email_otp_expiry_seconds', 300);
    }

    /**
     * Ottiene il max tentativi OTP
     *
     * @return int
     */
    public static function getMaxOtpAttempts()
    {
        return (int) self::getValue('2fa', 'max_otp_attempts', 5);
    }

    /**
     * Ottiene il nome issuer per TOTP
     *
     * @return string
     */
    public static function getTotpIssuerName()
    {
        return (string) self::getValue('2fa', 'totp_issuer_name', 'TherapyCRM');
    }

    /**
     * Ottiene il range passato del calendario paziente
     *
     * @return array ['value' => int, 'unit' => string]
     */
    public static function getPatientCalendarPastRange()
    {
        return [
            'value' => (int) self::getValue('calendar', 'patient_past_range_value', 3),
            'unit' => (string) self::getValue('calendar', 'patient_past_range_unit', 'months'),
        ];
    }

    /**
     * Ottiene il range futuro del calendario paziente
     *
     * @return array ['value' => int, 'unit' => string]
     */
    public static function getPatientCalendarFutureRange()
    {
        return [
            'value' => (int) self::getValue('calendar', 'patient_future_range_value', 3),
            'unit' => (string) self::getValue('calendar', 'patient_future_range_unit', 'months'),
        ];
    }

    /**
     * Calcola le date limite assolute del calendario paziente
     *
     * @return array ['minDate' => 'YYYY-MM-DD', 'maxDate' => 'YYYY-MM-DD']
     */
    public static function getPatientCalendarDateLimits()
    {
        $past = self::getPatientCalendarPastRange();
        $future = self::getPatientCalendarFutureRange();

        $today = new \DateTime();

        $minDate = clone $today;
        if ($past['value'] > 0) {
            $modifier = $past['unit'] === 'days' ? 'days' : 'months';
            $minDate->modify("-{$past['value']} {$modifier}");
        }

        $maxDate = clone $today;
        if ($future['value'] > 0) {
            $modifier = $future['unit'] === 'days' ? 'days' : 'months';
            $maxDate->modify("+{$future['value']} {$modifier}");
        }

        return [
            'minDate' => $minDate->format('Y-m-d'),
            'maxDate' => $maxDate->format('Y-m-d'),
        ];
    }

    /**
     * Ottiene tutte le impostazioni di una categoria
     *
     * @param string $category
     * @return array
     */
    public static function getByCategory($category)
    {
        $settings = static::find()->where(['category' => $category])->all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Converte il valore al tipo corretto
     *
     * @param string|null $value
     * @param string $type
     * @return mixed
     */
    private static function castValue($value, $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return (bool) (int) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Svuota la cache in-memory
     */
    public static function clearCache()
    {
        self::$_cache = [];
    }
}
