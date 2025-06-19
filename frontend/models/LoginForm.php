<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\Therapist;
use common\models\AccountPatient;

/**
 * Login form per autenticazione tramite email e password
 */
class LoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = false;

    private $_user = false;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 300; // 5 minuti

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required', 'message' => '{attribute} è obbligatorio.'],
            ['email', 'email', 'message' => 'Inserire un indirizzo email valido.'],
            ['password', 'string', 'min' => 6, 'message' => 'La password deve contenere almeno 6 caratteri.'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
            ['email', 'validateUserType'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Email',
            'password' => 'Password',
            'rememberMe' => 'Ricordami',
        ];
    }

    /**
     * Valida la password dell'utente corrente
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                // Log tentativo di accesso fallito
                $this->logLoginAttempt($this->email, false);
                
                // Verifica protezione brute force
                if ($this->isBruteForceProtected($this->email)) {
                    $this->addError($attribute, 'Troppi tentativi di accesso falliti. Riprova tra 5 minuti.');
                } else {
                    $this->addError($attribute, 'Email o password non corretti.');
                }
            }
        }
    }

    /**
     * Valida che l'utente abbia il permesso platform_login
     *
     * @param string $attribute
     * @param array $params
     */
    public function validateUserType($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            
            if ($user) {
                // Verifica se l'utente ha il permesso platform_login tramite RBAC
                if (!$this->hasPermissionToLogin($user)) {
                    $this->addError($attribute, 'Accesso non consentito. Non hai i permessi necessari per accedere a questa piattaforma.');
                    return;
                }
            }
        }
    }

    /**
     * Effettua il login dell'utente
     *
     * @return bool whether the login is successful
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            
            // Double check: verifica finale che l'utente abbia il permesso platform_login
            if (!$this->hasPermissionToLogin($user)) {
                Yii::error("Tentativo di login non autorizzato per user_id: {$user->id}, email: {$user->email} - Permesso platform_login mancante", __METHOD__);
                return false;
            }
            
            // Log accesso riuscito
            $this->logLoginAttempt($this->email, true);
            
            // Effettua il login
            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0; // 30 giorni se "ricordami"
            return Yii::$app->user->login($user, $duration);
        }

        return false;
    }

    /**
     * Verifica se l'utente ha il permesso platform_login tramite RBAC
     *
     * @param User $user
     * @return bool
     */
    private function hasPermissionToLogin($user)
    {
        if (!$user) {
            return false;
        }
        
        // Verifica il permesso tramite il sistema RBAC di Yii2
        $authManager = Yii::$app->authManager;
        if (!$authManager) {
            Yii::error("AuthManager non configurato correttamente", __METHOD__);
            return false;
        }
        
        return $authManager->checkAccess($user->id, 'platform_login');
    }

    /**
     * Controlla se l'utente è di tipo ristretto (terapista o paziente)
     * @deprecated Questo metodo è deprecato, usa hasPermissionToLogin() con RBAC
     *
     * @param User $user
     * @return bool
     */
    private function isRestrictedUser($user)
    {
        if (!$user) {
            return false;
        }
        
        // Verifica se è un terapista
        if (Therapist::find()->where(['user_id' => $user->id])->exists()) {
            return true;
        }
        
        // Verifica se è un account paziente
        if (AccountPatient::find()->where(['user_id' => $user->id])->exists()) {
            return true;
        }
        
        return false;
    }

    /**
     * Trova l'utente tramite email
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByEmail($this->email);
        }

        return $this->_user;
    }

    /**
     * Verifica la protezione contro brute force
     *
     * @param string $email
     * @return bool
     */
    private function isBruteForceProtected($email)
    {
        $cacheKey = 'login_attempts_' . md5($email);
        $attempts = Yii::$app->cache->get($cacheKey);
        
        if ($attempts && $attempts['count'] >= $this->maxLoginAttempts) {
            $timeDiff = time() - $attempts['last_attempt'];
            return $timeDiff < $this->lockoutDuration;
        }
        
        return false;
    }

    /**
     * Registra i tentativi di login
     *
     * @param string $email
     * @param bool $success
     */
    private function logLoginAttempt($email, $success)
    {
        $userAgent = Yii::$app->request->userAgent ?? 'Unknown';
        $ip = Yii::$app->request->userIP ?? 'Unknown';
        
        if ($success) {
            // Reset cache su login riuscito
            $cacheKey = 'login_attempts_' . md5($email);
            Yii::$app->cache->delete($cacheKey);
            
            Yii::info("Successful login for email: $email, IP: $ip, User-Agent: $userAgent", __METHOD__);
        } else {
            // Incrementa contatore tentativi falliti
            $cacheKey = 'login_attempts_' . md5($email);
            $attempts = Yii::$app->cache->get($cacheKey) ?: ['count' => 0, 'last_attempt' => 0];
            
            $attempts['count']++;
            $attempts['last_attempt'] = time();
            
            Yii::$app->cache->set($cacheKey, $attempts, $this->lockoutDuration);
            
            Yii::warning("Failed login attempt for email: $email, IP: $ip, User-Agent: $userAgent, Attempts: {$attempts['count']}", __METHOD__);
        }
    }
} 