<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property string $status
 * @property integer $requires_password_change
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 *
 * @property UserProfile $profile
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * @var string password per il form
     */
    public $password;
    
    /**
     * @var string password repeat per il form
     */
    public $password_repeat;

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['email', 'username', 'password', 'password_repeat', 'status'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%users}}';
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
             // Activity Logging
             [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => [
                    'created_at',
                    'updated_at',
                ],
                'entityNameCallback' => function($model) {
                    return 'User';
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
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['requires_password_change', 'boolean'],
            ['requires_password_change', 'default', 'value' => 0],
            [['email'], 'required', 'message' => 'L\'email è obbligatoria.'],
            [['email'], 'email', 'message' => 'Inserisci un indirizzo email valido.'],
            [['email'], 'unique', 'targetClass' => '\common\models\User', 'message' => 'Questa email è già stata registrata.'],
            [['username'], 'string', 'max' => 255],
            [['username'], 'unique', 'targetClass' => '\common\models\User', 'message' => 'Questo username è già stato registrato.'],
            [['username'], 'default', 'value' => function($model, $attribute) {
                return $model->email;
            }],
            [['password'], 'required', 'on' => 'create', 'message' => 'La password è obbligatoria.'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength'], 'max' => Yii::$app->params['user.passwordMaxLength']],
            ['password', 'match', 'pattern' => '/^.*(?=.*\d)(?=\S*[\W])(?=.*[a-z])(?=.*[A-Z]).*$/', 'message' => 'La password deve essere compresa tra 8 e 20 caratteri tra cui: un carattere maiuscolo, un carattere minuscolo, un numero ed un carattere speciale'],
            [['password_repeat'], 'required', 'on' => 'create', 'message' => 'La conferma password è obbligatoria.'],
            [['password_repeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Le password devono coincidere.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'password_repeat' => 'Conferma Password',
            'status' => 'Stato',
            'requires_password_change' => 'Richiede Cambio Password',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Relazione con il profilo utente
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }

    /**
     * Relazione con le assegnazioni di ruoli/permessi
     * @return \yii\db\ActiveQuery
     */
    public function getAuthAssignments()
    {
        return $this->hasMany(AuthAssignment::class, ['user_id' => 'id']);
    }

    /**
     * Relazione con gli account pazienti
     * @return \yii\db\ActiveQuery
     */
    public function getAccountPatients()
    {
        return $this->hasMany(AccountPatient::class, ['user_id' => 'id']);
    }

    /**
     * Relazione con le impostazioni 2FA dell'utente
     * @return \yii\db\ActiveQuery
     */
    public function getTwoFactorAuth()
    {
        return $this->hasOne(UserTwoFactorAuth::class, ['user_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        Yii::info('findIdentityByAccessToken chiamato con token: ' . substr($token, 0, 20) . '...', __METHOD__);
        
        try {
            // Usa il componente JWT per decodificare il token
            $jwtComponent = Yii::$app->jwt;
            Yii::info('Componente JWT ottenuto, decodificando token...', __METHOD__);
            
            $decodedToken = $jwtComponent->decodeToken($token);
            Yii::info('Token decodificato: ' . json_encode($decodedToken), __METHOD__);
            
            if ($decodedToken && isset($decodedToken['user_id'])) {
                $user = static::findIdentity($decodedToken['user_id']);
                Yii::info('Utente trovato: ' . ($user ? $user->id : 'NULL'), __METHOD__);
                return $user;
            }
        } catch (\Exception $e) {
            Yii::error('Errore nella decodifica del JWT token: ' . $e->getMessage(), __METHOD__);
            Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
        }
        
        Yii::info('Ritornando null da findIdentityByAccessToken', __METHOD__);
        return null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        $this->password_changed_at = date('Y-m-d H:i:s');
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            // Genera auth_key se non presente
            if (empty($this->auth_key)) {
                $this->generateAuthKey();
            }
            
            // Genera password hash se è stata impostata una password
            if (!empty($this->password)) {
                $this->setPassword($this->password);
            }
        }
        
        return parent::beforeSave($insert);
    }
}
