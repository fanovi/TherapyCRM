<?php

namespace api\controllers;

use common\models\AccountPatient;
use common\models\AuthToken;
use common\models\Patient;
use common\models\Therapist;
use common\models\TrustedDevice;
use common\models\User;
use common\models\UserProfile;
use common\models\UserTwoFactorAuth;
use common\models\SystemSetting;
use common\services\TwoFactorService;
use yii\web\Controller;
use yii\web\HttpException;
use Yii;

/**
 * @OA\Info(
 *     title="TherapyCRM Authentication API",
 *     version="1.0.0",
 *     description="API per l'autenticazione di pazienti e terapisti nel sistema TherapyCRM"
 * )
 *
 * @OA\Server(
 *     url="/TherapyCRM/api",
 *     description="Server API TherapyCRM"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    /**
     * Abilita CORS per tutte le action del controller
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Rimuovi l'autenticazione per permettere accesso pubblico al login
        unset($behaviors['authenticator']);

        // Aggiungi CORS
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        return $behaviors;
    }

    /**
     * Gestisce le azioni consentite
     */
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Login per pazienti e terapisti",
     *     description="Autentica un utente (paziente o terapista) nel sistema. Il sistema cerca automaticamente prima nella tabella pazienti, poi in quella terapisti.",
     *     operationId="login",
     *     tags={"Autenticazione"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Credenziali di accesso",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="Indirizzo email dell'utente",
     *                     example="paziente1@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     format="password",
     *                     description="Password dell'utente",
     *                     example="password123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login effettuato con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login effettuato con successo"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="paziente1@example.com"),
     *                     @OA\Property(property="nome", type="string", example="Marco"),
     *                     @OA\Property(property="cognome", type="string", example="Rossi"),
     *                     @OA\Property(property="user_type", type="string", enum={"paziente", "terapista"}, example="paziente"),
     *                     @OA\Property(property="status", type="string", example="attivo"),
     *                     @OA\Property(property="first_login", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="requires_password_change", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Primo login - richiesto cambio password",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login effettuato. È necessario cambiare la password."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="paziente1@example.com"),
     *                     @OA\Property(property="nome", type="string", example="Marco"),
     *                     @OA\Property(property="cognome", type="string", example="Rossi"),
     *                     @OA\Property(property="user_type", type="string", example="paziente")
     *                 ),
     *                 @OA\Property(property="requires_password_change", type="boolean", example=true),
     *                 @OA\Property(property="temp_token", type="string", example="temp_token_for_password_change")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email e password sono obbligatori"),
     *             @OA\Property(property="error_code", type="string", example="MISSING_PARAMETERS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenziali non valide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Credenziali non valide"),
     *             @OA\Property(property="error_code", type="string", example="INVALID_CREDENTIALS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Metodo non consentito",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metodo non consentito. Utilizzare POST.")
     *         )
     *     )
     * )
     *
     * Login per pazienti e terapisti
     * POST /auth/login
     *
     * Parametri richiesti:
     * - email: string
     * - password: string
     *
     * Il sistema cerca automaticamente prima nella tabella pazienti,
     * poi in quella terapisti e restituisce il tipo utente trovato.
     *
     * Restituisce anche se l'utente è al primo login e deve cambiare password.
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $email = $request->post('email');
        $password = $request->post('password');

        // Validazione input
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email e password sono obbligatori',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Formato email non valido',
                'error_code' => 'INVALID_EMAIL'
            ];
        }

        // Cerca l'utente nel database usando i modelli reali
        $userData = $this->findAndValidateUser($email, $password);

        if ($userData) {
            // TODO: Gestire correttamente il first_login invece di metterlo sempre true
            $requiresPasswordChange = $userData['first_login'];  // Per ora sempre true come richiesto

            // Se è primo login, non genera il token completo
            if ($requiresPasswordChange) {
                return [
                    'success' => true,
                    'message' => 'Login effettuato. È necessario cambiare la password.',
                    'data' => [
                        'user' => $userData,
                        'requires_password_change' => 1,
                        'temp_token' => $this->generateTempToken($userData)  // Token temporaneo per cambio password
                    ]
                ];
            }

            // Check 2FA
            $tfaService = new TwoFactorService();
            $deviceToken = $request->post('device_token');
            $user = User::findOne($userData['id']);

            if ($user && $tfaService->is2faRequired($user, $deviceToken)) {
                $method = $tfaService->getPreferredMethod($user->id);

                // Check if TOTP is already configured
                $tfa = UserTwoFactorAuth::findOne(['user_id' => $user->id]);
                $totpConfigured = $tfa && !empty($tfa->totp_secret) && !empty($tfa->totp_confirmed_at);

                $tempToken2fa = $this->generate2faTempToken($userData);

                return [
                    'success' => true,
                    'message' => 'Verifica a due fattori richiesta.',
                    'data' => [
                        'user' => $userData,
                        'requires_2fa' => true,
                        'two_factor_method' => $method,
                        'totp_configured' => $totpConfigured,
                        'temp_token' => $tempToken2fa,
                        'show_remember_device' => SystemSetting::isRememberDeviceEnabled(),
                        'requires_password_change' => 0
                    ]
                ];
            }

            // Login normale - genera token di accesso completo
            $accessToken = $this->generateAccessToken([
                'user_id' => $userData['id'],
                'email' => $userData['email']
            ]);

            return [
                'success' => true,
                'message' => 'Login effettuato con successo',
                'data' => [
                    'user' => $userData,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,  // 1 ora
                    'requires_password_change' => 0
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Credenziali non valide',
                'error_code' => 'INVALID_CREDENTIALS'
            ];
        }
    }

    /**
     * Verifica se un token è valido (per uso interno)
     */
    public function actionCheckToken()
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return ['success' => false, 'valid' => false, 'message' => 'Token mancante'];
        }

        $token = $matches[1];

        $authToken = AuthToken::findOne(['token' => $token, 'is_revoked' => 0]);

        if (!$authToken || $authToken->expires_at < time()) {
            return ['success' => false, 'valid' => false, 'message' => 'Token non valido o scaduto'];
        }

        // Aggiorna last_used_at
        $authToken->last_used_at = time();
        $authToken->save();

        return ['success' => true, 'valid' => true, 'message' => 'Token valido'];
    }

    // TODO
    public function actionRefresh()
    {
        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);

        $refreshToken = $data['refreshToken'] ?? null;

        if (!$refreshToken) {
            return ['success' => false, 'error' => 'Refresh token mancante.'];
        }

        // Trova il token non revocato
        $authToken = AuthToken::findOne(['refresh_token' => $refreshToken, 'is_revoked' => 0]);

        if (!$authToken || $authToken->refresh_expires_at < time()) {
            return ['success' => false, 'error' => 'Invalid refresh token'];
        }

        $user = User::findOne($authToken->user_id);

        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        // Genera nuovi token
        $payload = ['user_id' => $user->id, 'username' => $user->username];
        $newToken = Yii::$app->jwt->generateToken($payload);
        $newRefreshToken = Yii::$app->jwt->generateRefreshToken($payload);

        // Aggiorna il token esistente invece di crearne uno nuovo
        $authToken->token = $newToken;
        $authToken->refresh_token = $newRefreshToken;
        $authToken->expires_at = time() + Yii::$app->jwt->tokenDuration;
        $authToken->refresh_expires_at = time() + Yii::$app->jwt->refreshTokenDuration;
        $authToken->last_used_at = time();
        $authToken->save();

        // Recupera i ruoli dell'utente
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser($user->id);

        // Estrai solo i nomi dei ruoli
        $roleNames = [];
        foreach ($roles as $role) {
            if ($role->name !== 'general') {
                $roleNames[] = $role->name;
            }
        }

        return [
            'success' => true,
            'token' => $newToken,
            'refreshToken' => $newRefreshToken,
            'expires_in' => $authToken->expires_at,
            'refresh_expires_in' => $authToken->refresh_expires_at,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'nome' => $user->nome,
                'cognome' => $user->cognome,
                'roles' => $roleNames,  // Aggiungi i ruoli qui
            ],
        ];
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Logout utente",
     *     description="Effettua il logout dell'utente autenticato invalidando il token di accesso",
     *     operationId="logout",
     *     tags={"Autenticazione"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout effettuato con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout effettuato con successo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Metodo non consentito",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metodo non consentito. Utilizzare POST.")
     *         )
     *     )
     * )
     *
     * Logout utente
     * POST /auth/logout
     */
    public function actionLogout()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        // Ottieni il token dall'header Authorization
        $authHeader = $request->getHeaders()->get('Authorization');

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];

            // Trova e revoca il token nel database
            $authToken = AuthToken::findOne([
                'token' => $token,
                'is_revoked' => 0
            ]);

            if ($authToken) {
                $authToken->is_revoked = 1;
                $authToken->last_used_at = time();
                $authToken->save();

                Yii::info("Token revoked for user ID: {$authToken->user_id}", __METHOD__);
            }
        }

        return [
            'success' => true,
            'message' => 'Logout effettuato con successo'
        ];
    }

    /**
     * @OA\Get(
     *     path="/auth/verify",
     *     summary="Verifica validità token",
     *     description="Verifica se il token di accesso fornito è valido e restituisce le informazioni dell'utente",
     *     operationId="verifyToken",
     *     tags={"Autenticazione"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token di accesso nel format Bearer {token}",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token valido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token valido"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="paziente1@example.com"),
     *                     @OA\Property(property="user_type", type="string", example="paziente")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token mancante o non valido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token di accesso mancante"),
     *             @OA\Property(property="error_code", type="string", example="TOKEN_MISSING")
     *         )
     *     )
     * )
     *
     * Verifica validità token
     * GET /auth/verify
     */
    public function actionVerify()
    {
        $request = Yii::$app->request;
        $authHeader = $request->getHeaders()->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'message' => 'Token di accesso mancante',
                'error_code' => 'TOKEN_MISSING'
            ];
        }

        $token = $matches[1];

        // Simula la verifica del token
        $user = $this->verifyTokenWithDatabase($token);

        if ($user) {
            return [
                'success' => true,
                'message' => 'Token valido',
                'data' => [
                    'user' => $user
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Token non valido o scaduto',
                'error_code' => 'TOKEN_INVALID'
            ];
        }
    }

    /**
     * Trova e valida un utente usando i modelli reali di Yii2
     */
    private function findAndValidateUser($email, $password)
    {
        // Trova l'utente per email con eager loading del profilo
        $user = User::find()
            ->with('profile')  // Eager loading per evitare N+1 query
            ->where(['email' => $email])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->one();

        if (!$user) {
            Yii::info("User not found for email: $email", __METHOD__);
            return null;
        }

        // Valida la password usando il metodo standard di Yii2
        if (!$user->validatePassword($password)) {
            Yii::info("Invalid password for email: $email", __METHOD__);
            return null;
        }

        // Verifica il permesso app_login tramite RBAC
        if (!$this->hasAppLoginPermission($user)) {
            Yii::error("User {$user->id} (email: $email) does not have app_login permission", __METHOD__);
            return null;
        }

        // Determina il tipo di utente (terapista o account paziente)
        $userData = $this->buildUserData($user);

        if (!$userData) {
            Yii::error("User found but not associated with Therapist or AccountPatient: {$user->id}", __METHOD__);
            return null;
        }

        return $userData;
    }

    /**
     * Verifica se l'utente ha il permesso app_login tramite RBAC
     *
     * @param User $user
     * @return bool
     */
    private function hasAppLoginPermission($user)
    {
        if (!$user) {
            return false;
        }

        // Verifica il permesso tramite il sistema RBAC di Yii2
        $authManager = Yii::$app->authManager;
        if (!$authManager) {
            Yii::error('AuthManager non configurato correttamente', __METHOD__);
            return false;
        }

        return $authManager->checkAccess($user->id, 'app_login');
    }

    /**
     * Costruisce i dati utente basandosi sul tipo (terapista o account paziente)
     */
    private function buildUserData(User $user)
    {
        // Carica il profilo utente
        $profile = $user->profile;

        if (!$profile) {
            Yii::error("User profile not found for user: {$user->id}", __METHOD__);
            return null;
        }

        // Verifica se è un terapista con eager loading della specializzazione
        $therapist = Therapist::find()
            ->with('specialization')  // Eager loading per evitare N+1 query
            ->where(['user_id' => $user->id])
            ->one();
        if ($therapist) {
            return $this->buildTherapistData($user, $profile, $therapist);
        }

        // Verifica se è un account paziente con eager loading dei pazienti
        $accountPatients = AccountPatient::find()
            ->with('patient')  // Eager loading per evitare N+1 query
            ->where(['user_id' => $user->id])
            ->all();
        if (!empty($accountPatients)) {
            return $this->buildPatientAccountData($user, $profile, $accountPatients[0], $accountPatients);
        }

        // Se non è né terapista né account paziente, restituisce null
        return null;
    }

    /**
     * Costruisce i dati per un terapista
     */
    private function buildTherapistData(User $user, UserProfile $profile, Therapist $therapist)
    {
        // La specializzazione è già caricata tramite eager loading
        $specialization = $therapist->specialization;

        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'nome' => $profile->first_name,
            'cognome' => $profile->last_name,
            'codice_fiscale' => $profile->fiscal_code,
            'telefono' => $profile->phone,
            'indirizzo' => $profile->address,
            'user_type' => 'terapista',
            'status' => $user->status === User::STATUS_ACTIVE ? 'attivo' : 'inattivo',
            'specializzazione' => $specialization ? $specialization->name : null,
            'first_login' => $user->requires_password_change  // TODO: Gestire correttamente
        ];

        // Decodifica i dati sensibili se necessario
        return $this->decodeSensitiveData($userData);
    }

    /**
     * Costruisce i dati per un account paziente
     */
    private function buildPatientAccountData(User $user, UserProfile $profile, AccountPatient $accountPatient, $accountPatients = null)
    {
        // Se non sono stati passati i dati, li recuperiamo (fallback)
        if ($accountPatients === null) {
            $accountPatients = AccountPatient::find()
                ->where(['user_id' => $user->id])
                ->with('patient')  // Eager loading per performance
                ->all();
        }

        if (empty($accountPatients)) {
            Yii::error("No patients found for AccountPatient user: {$user->id}", __METHOD__);
            return null;
        }

        // Costruisce l'array dei pazienti (i dati sono già caricati tramite eager loading)
        $patients = [];
        foreach ($accountPatients as $ap) {
            $patient = $ap->patient;
            if ($patient) {
                $patients[] = [
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->fullName,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'relationship' => $ap->relationship_type ?? 'self',
                    'has_parental_authority' => (bool) $ap->has_parental_authority,
                    'account_patient_id' => $ap->id,
                    // Dati anagrafici del paziente
                    'birth_date' => $patient->birth_date,
                    'fiscal_code' => $patient->fiscal_code,
                    'gender' => $patient->gender,
                    'phone_number' => $patient->phone_number,
                    // Dati di nascita
                    'birth_city' => $patient->birth_city,
                    'birth_province' => $patient->birth_province_name,
                    'birth_province_code' => $patient->birth_province_code,
                    'born_in_italy' => (bool) $patient->born_in_italy,
                    // Dati di residenza
                    'residence_address' => $patient->residence_address,
                    'residence_city' => $patient->residence_city,
                    'residence_province' => $patient->residence_province_name,
                    'residence_province_code' => $patient->residence_province_code,
                    'residence_postal_code' => $patient->residence_postal_code,
                    // Campi calcolati
                    'age' => $patient->age,
                    'full_residence_address' => $patient->fullResidenceAddress,
                    'birth_location' => $patient->birthLocation,
                ];
            }
        }

        if (empty($patients)) {
            Yii::error("No valid patients found for AccountPatient user: {$user->id}", __METHOD__);
            return null;
        }

        // Debug: log dei dati del profilo
        Yii::info("Building patient account data for user {$user->id}", __METHOD__);
        Yii::info("Profile data - fiscal_code: " . ($profile->fiscal_code ?? 'NULL') .
                  ", phone: " . ($profile->phone ?? 'NULL') .
                  ", address: " . ($profile->address ?? 'NULL'), __METHOD__);

        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'nome' => $profile->first_name,
            'cognome' => $profile->last_name,
            'codice_fiscale' => $profile->fiscal_code,
            'telefono' => $profile->phone,
            'indirizzo' => $profile->address,
            'user_type' => 'paziente',
            'status' => $user->status === User::STATUS_ACTIVE ? 'attivo' : 'inattivo',
            'patients' => $patients,  // Array di tutti i pazienti collegati
            'first_login' => $user->requires_password_change,
        ];

        // Decodifica i dati sensibili se necessario
        return $this->decodeSensitiveData($userData);
    }

    /**
     * Decodifica i dati sensibili usando il componente security di Yii2
     */
    private function decodeSensitiveData($userData)
    {
        try {
            $encryptionKey = Yii::$app->params['encryptionKey'];

            // Decodifica il telefono se presente e crittografato
            if (!empty($userData['telefono'])) {
                try {
                    $decoded = base64_decode($userData['telefono'], true);
                    // Solo se è base64 valido, prova a decriptare
                    if ($decoded !== false) {
                        $decryptedPhone = Yii::$app->security->decryptByKey($decoded, $encryptionKey);
                        // Solo se la decrittazione riesce, usa il valore decriptato
                        if ($decryptedPhone !== false) {
                            $userData['telefono'] = $decryptedPhone;
                        }
                        // Altrimenti mantieni il valore originale (non era crittografato)
                    }
                } catch (\Exception $e) {
                    Yii::error('Failed to decrypt phone number: ' . $e->getMessage(), __METHOD__);
                    // Se la decodifica fallisce, mantieni il valore originale (potrebbe non essere crittografato)
                }
            }

            // Decodifica l'indirizzo se presente e crittografato
            if (!empty($userData['indirizzo'])) {
                try {
                    $decoded = base64_decode($userData['indirizzo'], true);
                    // Solo se è base64 valido, prova a decriptare
                    if ($decoded !== false) {
                        $decryptedAddress = Yii::$app->security->decryptByKey($decoded, $encryptionKey);
                        // Solo se la decrittazione riesce, usa il valore decriptato
                        if ($decryptedAddress !== false) {
                            $userData['indirizzo'] = $decryptedAddress;
                        }
                        // Altrimenti mantieni il valore originale (non era crittografato)
                    }
                } catch (\Exception $e) {
                    Yii::error('Failed to decrypt address: ' . $e->getMessage(), __METHOD__);
                    // Se la decodifica fallisce, mantieni il valore originale (potrebbe non essere crittografato)
                }
            }
        } catch (\Exception $e) {
            Yii::error('Error in decodeSensitiveData: ' . $e->getMessage(), __METHOD__);
        }

        return $userData;
    }

    /**
     * Genera un token di accesso usando JWT e lo salva nel database
     */
    private function generateAccessToken($payload)
    {
        // Genera il token JWT usando il componente configurato (Yii::$app->jwt)
        $token = Yii::$app->jwt->generateToken($payload);
        $refreshToken = Yii::$app->jwt->generateRefreshToken($payload);

        // Salva il token nel database per poterlo revocare successivamente
        $authToken = new AuthToken();
        $authToken->user_id = $payload['user_id'];
        $authToken->token = $token;
        $authToken->refresh_token = $refreshToken;
        $authToken->is_revoked = 0;
        $authToken->created_at = time();
        $authToken->expires_at = time() + Yii::$app->jwt->tokenDuration;
        $authToken->refresh_expires_at = time() + Yii::$app->jwt->refreshTokenDuration;
        $authToken->last_used_at = time();

        if (!$authToken->save()) {
            Yii::error('Failed to save auth token: ' . json_encode($authToken->errors), __METHOD__);
        }

        // Restituisce solo il token string per compatibilità con il codice esistente
        return $token;
    }

    /**
     * Verifica la validità di un token JWT controllando anche il database
     */
    private function verifyTokenWithDatabase($token)
    {
        try {
            // Prima verifica se il token esiste e non è stato revocato nel database
            $authToken = AuthToken::findOne([
                'token' => $token,
                'is_revoked' => 0
            ]);

            if (!$authToken) {
                Yii::error('Token not found in database or has been revoked', __METHOD__);
                return null;  // Token non trovato o revocato
            }

            // Verifica se il token è scaduto dal database
            if ($authToken->expires_at < time()) {
                Yii::error("Token expired (database check): expires_at = {$authToken->expires_at}, current = " . time(), __METHOD__);
                return null;  // Token scaduto
            }

            // Ora decodifica il JWT per ottenere i dati utente
            try {
                $payload = Yii::$app->jwt->decodeToken($token);
            } catch (\Exception $e) {
                // Fallback: decodifica manuale del JWT
                $payload = $this->decodeJWTManually($token);
            }

            if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
                Yii::error('Token expired or invalid payload (JWT check)', __METHOD__);
                return null;  // Token scaduto o non valido
            }

            // Verifica che l'utente esista ancora (simulazione)
            $userId = isset($payload['user_id']) ? $payload['user_id'] : null;
            $email = isset($payload['email']) ? $payload['email'] : null;

            if (!$userId || !$email) {
                Yii::error('Token missing required fields: user_id or email', __METHOD__);
                return null;
            }

            // Verifica che l'user_id del token corrisponda a quello nel database
            if ($authToken->user_id != $userId) {
                Yii::error("Token user_id mismatch: DB={$authToken->user_id}, JWT={$userId}", __METHOD__);
                return null;
            }

            // Cerca l'utente nel database usando i modelli reali
            $user = $this->findUserById($userId, $email);

            if (!$user) {
                Yii::error("User not found in database: ID $userId, Email $email", __METHOD__);
                return null;  // Utente non trovato
            }

            // Aggiorna last_used_at per tracking
            $authToken->last_used_at = time();
            $authToken->save();

            Yii::info("Token verified successfully for user: {$user['email']}", __METHOD__);

            // Restituisce i dati dell'utente dal "database"
            return $user;
        } catch (\Exception $e) {
            // Log per debug
            Yii::error('Token verification failed: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Decodifica manualmente un JWT per estrarre il payload
     */
    private function decodeJWTManually($token)
    {
        // Un JWT ha 3 parti separate da punti: header.payload.signature
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \Exception('Invalid JWT format');
        }

        // La seconda parte è il payload
        $payload = $parts[1];

        // Aggiungi padding se necessario per base64_decode
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $decodedPayload = base64_decode($payload);

        if (!$decodedPayload) {
            throw new \Exception('Failed to decode JWT payload');
        }

        $payloadArray = json_decode($decodedPayload, true);

        if (!$payloadArray) {
            throw new \Exception('Failed to parse JWT payload JSON');
        }

        return $payloadArray;
    }

    /**
     * Trova un utente per ID e email usando i modelli reali
     */
    private function findUserById($userId, $email)
    {
        // Trova l'utente nel database
        $user = User::find()
            ->where(['id' => $userId, 'email' => $email])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->one();

        if (!$user) {
            Yii::error("User not found: ID $userId, Email $email", __METHOD__);
            return null;
        }

        // Costruisce i dati utente usando la stessa logica del login
        return $this->buildUserData($user);
    }

    /**
     * @OA\Post(
     *     path="/auth/change-first-password",
     *     summary="Cambio password per primo login",
     *     description="Permette agli utenti di cambiare la password al primo accesso utilizzando un token temporaneo",
     *     operationId="changeFirstPassword",
     *     tags={"Autenticazione"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dati per il cambio password",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"temp_token", "new_password", "confirm_password"},
     *                 @OA\Property(
     *                     property="temp_token",
     *                     type="string",
     *                     description="Token temporaneo ricevuto al primo login",
     *                     example="temp_token_from_login_response"
     *                 ),
     *                 @OA\Property(
     *                     property="new_password",
     *                     type="string",
     *                     format="password",
     *                     description="Nuova password (min 8 caratteri, almeno 1 maiuscola, 1 minuscola, 1 numero)",
     *                     example="NuovaPassword123"
     *                 ),
     *                 @OA\Property(
     *                     property="confirm_password",
     *                     type="string",
     *                     format="password",
     *                     description="Conferma della nuova password",
     *                     example="NuovaPassword123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password cambiata con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password cambiata con successo"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="paziente1@example.com"),
     *                     @OA\Property(property="nome", type="string", example="Marco"),
     *                     @OA\Property(property="cognome", type="string", example="Rossi"),
     *                     @OA\Property(property="user_type", type="string", example="paziente")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Tutti i campi sono obbligatori"),
     *                     @OA\Property(property="error_code", type="string", example="MISSING_PARAMETERS")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Le password non coincidono"),
     *                     @OA\Property(property="error_code", type="string", example="PASSWORD_MISMATCH")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="La password deve essere lunga almeno 8 caratteri e contenere almeno una lettera maiuscola, una minuscola e un numero"),
     *                     @OA\Property(property="error_code", type="string", example="WEAK_PASSWORD")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token temporaneo non valido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token temporaneo non valido o scaduto"),
     *             @OA\Property(property="error_code", type="string", example="INVALID_TEMP_TOKEN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Metodo non consentito",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metodo non consentito. Utilizzare POST.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Errore interno del server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errore durante il cambio password"),
     *             @OA\Property(property="error_code", type="string", example="UPDATE_FAILED")
     *         )
     *     )
     * )
     *
     * Cambio password per primo login
     * POST /auth/change-first-password
     *
     * Parametri richiesti:
     * - temp_token: string (token temporaneo ricevuto al login)
     * - new_password: string
     * - confirm_password: string
     */
    public function actionChangeFirstPassword()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $tempToken = $request->post('temp_token');
        $newPassword = $request->post('new_password');
        $confirmPassword = $request->post('confirm_password');

        // Validazione input
        if (empty($tempToken) || empty($newPassword) || empty($confirmPassword)) {
            return [
                'success' => false,
                'message' => 'Tutti i campi sono obbligatori',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        // Verifica che le password coincidano
        if ($newPassword !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'Le password non coincidono',
                'error_code' => 'PASSWORD_MISMATCH'
            ];
        }

        // Validazione robustezza password
        if (!$this->isValidPassword($newPassword)) {
            return [
                'success' => false,
                'message' => 'La password deve essere lunga almeno 8 caratteri e contenere almeno una lettera maiuscola, una minuscola e un numero',
                'error_code' => 'WEAK_PASSWORD'
            ];
        }

        // Verifica token temporaneo
        $user = $this->verifyTempToken($tempToken);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Token temporaneo non valido o scaduto',
                'error_code' => 'INVALID_TEMP_TOKEN'
            ];
        }

        // Simula l'aggiornamento della password nel database
        $updateResult = $this->updateUserPassword($user['id'], $user['user_type'], $newPassword);

        if ($updateResult) {
            // Recupera l'utente aggiornato dal database per costruire i dati corretti
            $userModel = User::findOne($user['id']);
            if (!$userModel) {
                return [
                    'success' => false,
                    'message' => 'Errore: utente non trovato dopo aggiornamento password',
                    'error_code' => 'USER_NOT_FOUND'
                ];
            }

            // Costruisce i dati utente usando la stessa logica del login normale
            $userData = $this->buildUserData($userModel);
            if (!$userData) {
                return [
                    'success' => false,
                    'message' => 'Errore: impossibile costruire dati utente',
                    'error_code' => 'USER_DATA_BUILD_FAILED'
                ];
            }

            // Aggiorna i campi specifici per il cambio password
            $userData['first_login'] = false;  // Non è più primo login
            $userData['isFirstLogin'] = false;  // Campo per compatibilità con app
            $userData['isPasswordResetRequired'] = false;  // Password non richiede più reset

            // Check 2FA before issuing JWT
            $tfaService = new TwoFactorService();
            if ($tfaService->is2faRequired($userModel)) {
                $method = $tfaService->getPreferredMethod($userModel->id);

                // Check if TOTP is already configured
                $tfa = UserTwoFactorAuth::findOne(['user_id' => $userModel->id]);
                $totpConfigured = $tfa && !empty($tfa->totp_secret) && !empty($tfa->totp_confirmed_at);

                $tempToken2fa = $this->generate2faTempToken($userData);

                return [
                    'success' => true,
                    'message' => 'Password cambiata. Verifica a due fattori richiesta.',
                    'data' => [
                        'user' => $userData,
                        'requires_2fa' => true,
                        'two_factor_method' => $method,
                        'totp_configured' => $totpConfigured,
                        'temp_token' => $tempToken2fa,
                        'show_remember_device' => SystemSetting::isRememberDeviceEnabled(),
                        'requires_password_change' => 0
                    ]
                ];
            }

            // Genera token di accesso normale dopo il cambio password (no 2FA needed)
            $accessToken = $this->generateAccessToken([
                'user_id' => $userData['id'],
                'email' => $userData['email']
            ]);

            return [
                'success' => true,
                'message' => 'Password cambiata con successo',
                'data' => [
                    'user' => $userData,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Errore durante il cambio password',
                'error_code' => 'UPDATE_FAILED'
            ];
        }
    }

    /**
     * Genera un token temporaneo per il cambio password (firmato con HMAC)
     */
    private function generateTempToken($user)
    {
        // Token temporaneo con durata limitata (10 minuti)
        $payload = [
            'user_id' => $user['id'],
            'user_type' => $user['user_type'],
            'email' => $user['email'],
            'purpose' => 'password_change',
            'exp' => time() + 600  // Scade in 10 minuti
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, Yii::$app->params['encryptionKey']);

        return base64_encode($payloadJson . '.' . $signature);
    }

    /**
     * Verifica un token temporaneo per il cambio password (verifica HMAC)
     */
    private function verifyTempToken($token)
    {
        try {
            $decoded = base64_decode($token);
            if ($decoded === false) {
                return null;
            }

            $lastDot = strrpos($decoded, '.');
            if ($lastDot === false) {
                return null;
            }

            $payloadJson = substr($decoded, 0, $lastDot);
            $signature = substr($decoded, $lastDot + 1);

            // Verifica firma HMAC
            $expectedSignature = hash_hmac('sha256', $payloadJson, Yii::$app->params['encryptionKey']);
            if (!hash_equals($expectedSignature, $signature)) {
                Yii::error('Password change temp token signature mismatch', __METHOD__);
                return null;
            }

            $payload = json_decode($payloadJson, true);

            if (!$payload ||
                    !isset($payload['exp']) ||
                    $payload['exp'] < time() ||
                    !isset($payload['purpose']) ||
                    $payload['purpose'] !== 'password_change' ||
                    !isset($payload['user_id']) ||
                    !isset($payload['email'])) {
                return null;  // Token scaduto, non valido o non per cambio password
            }

            // Restituisce i dati dell'utente dal token
            return [
                'id' => $payload['user_id'],
                'email' => $payload['email'],
                'user_type' => $payload['user_type']
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Valida la robustezza di una password
     */
    private function isValidPassword($password)
    {
        // Almeno 8 caratteri, una maiuscola, una minuscola e un numero
        if (strlen($password) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;  // Nessuna maiuscola
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;  // Nessuna minuscola
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;  // Nessun numero
        }

        return true;
    }

    /**
     * Aggiorna la password nel database usando i modelli reali di Yii2
     */
    private function updateUserPassword($userId, $userType, $newPassword)
    {
        try {
            // Trova l'utente
            $user = User::findOne(['id' => $userId, 'status' => User::STATUS_ACTIVE]);
            if (!$user) {
                Yii::error("User not found for password update: ID $userId", __METHOD__);
                return false;
            }

            // Aggiorna la password usando il metodo standard di Yii2
            $user->setPassword($newPassword);

            // TODO: Aggiungere campo first_login alla tabella users e gestirlo qui
            $user->requires_password_change = 0;

            // Salva le modifiche
            if ($user->save()) {
                Yii::info("Password updated successfully for user: {$user->id}", __METHOD__);
                return true;
            } else {
                Yii::error("Failed to save password update for user: {$user->id}. Errors: " . json_encode($user->errors), __METHOD__);
                return false;
            }
        } catch (\Exception $e) {
            Yii::error('Exception during password update: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/request-password-reset",
     *     summary="Richiesta reset password",
     *     description="Invia una email con un link per resettare la password dell'utente",
     *     operationId="requestPasswordReset",
     *     tags={"Autenticazione"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email dell'utente",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"email"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="Indirizzo email dell'utente",
     *                     example="paziente1@example.com"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Richiesta inviata con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se l'email è registrata, riceverai le istruzioni per resettare la password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email non valida"),
     *             @OA\Property(property="error_code", type="string", example="INVALID_EMAIL")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Metodo non consentito",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metodo non consentito. Utilizzare POST.")
     *         )
     *     )
     * )
     *
     * Richiesta reset password
     * POST /auth/request-password-reset
     *
     * Parametri richiesti:
     * - email: string
     */
    public function actionRequestPasswordReset()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $email = $request->post('email');

        // Validazione input
        if (empty($email)) {
            return [
                'success' => false,
                'message' => 'Email è obbligatoria',
                'error_code' => 'MISSING_EMAIL'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Formato email non valido',
                'error_code' => 'INVALID_EMAIL'
            ];
        }

        // Cerca l'utente nel database
        $user = User::find()
            ->where(['email' => $email])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->one();

        if (!$user) {
            // Per sicurezza, non rivelare se l'email esiste o meno
            return [
                'success' => true,
                'message' => "Se l'email è registrata, riceverai le istruzioni per resettare la password"
            ];
        }

        // Verifica se l'utente ha il permesso app_login
        if (!$this->hasAppLoginPermission($user)) {
            Yii::error("User {$user->id} (email: $email) does not have app_login permission", __METHOD__);
            return [
                'success' => true,
                'message' => "Se l'email è registrata, riceverai le istruzioni per resettare la password"
            ];
        }

        // Genera token di reset password
        $resetToken = $this->generatePasswordResetToken($user);

        if ($resetToken) {
            // Invia email con il token
            $emailSent = $this->sendPasswordResetEmail($user, $resetToken);

            if ($emailSent) {
                return [
                    'success' => true,
                    'message' => "Se l'email è registrata, riceverai le istruzioni per resettare la password"
                ];
            } else {
                Yii::error("Failed to send password reset email to: $email", __METHOD__);
                return [
                    'success' => false,
                    'message' => "Errore nell'invio dell'email. Riprova più tardi.",
                    'error_code' => 'EMAIL_SEND_FAILED'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Errore nella generazione del token di reset',
                'error_code' => 'TOKEN_GENERATION_FAILED'
            ];
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/reset-password",
     *     summary="Reset password con token",
     *     description="Resetta la password dell'utente utilizzando il token ricevuto via email",
     *     operationId="resetPassword",
     *     tags={"Autenticazione"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dati per il reset password",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"reset_token", "new_password", "confirm_password"},
     *                 @OA\Property(
     *                     property="reset_token",
     *                     type="string",
     *                     description="Token di reset ricevuto via email",
     *                     example="reset_token_from_email"
     *                 ),
     *                 @OA\Property(
     *                     property="new_password",
     *                     type="string",
     *                     format="password",
     *                     description="Nuova password (min 8 caratteri, almeno 1 maiuscola, 1 minuscola, 1 numero)",
     *                     example="NuovaPassword123"
     *                 ),
     *                 @OA\Property(
     *                     property="confirm_password",
     *                     type="string",
     *                     format="password",
     *                     description="Conferma della nuova password",
     *                     example="NuovaPassword123"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password resettata con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password resettata con successo"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="paziente1@example.com"),
     *                     @OA\Property(property="nome", type="string", example="Marco"),
     *                     @OA\Property(property="cognome", type="string", example="Rossi"),
     *                     @OA\Property(property="user_type", type="string", example="paziente")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Tutti i campi sono obbligatori"),
     *                     @OA\Property(property="error_code", type="string", example="MISSING_PARAMETERS")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Le password non coincidono"),
     *                     @OA\Property(property="error_code", type="string", example="PASSWORD_MISMATCH")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="La password deve essere lunga almeno 8 caratteri e contenere almeno una lettera maiuscola, una minuscola e un numero"),
     *                     @OA\Property(property="error_code", type="string", example="WEAK_PASSWORD")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token di reset non valido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token di reset non valido o scaduto"),
     *             @OA\Property(property="error_code", type="string", example="INVALID_RESET_TOKEN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Metodo non consentito",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metodo non consentito. Utilizzare POST.")
     *         )
     *     )
     * )
     *
     * Reset password con token
     * POST /auth/reset-password
     *
     * Parametri richiesti:
     * - reset_token: string (token ricevuto via email)
     * - new_password: string
     * - confirm_password: string
     */
    public function actionResetPassword()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $resetToken = $request->post('reset_token');
        $newPassword = $request->post('new_password');
        $confirmPassword = $request->post('confirm_password');

        // Validazione input
        if (empty($resetToken) || empty($newPassword) || empty($confirmPassword)) {
            return [
                'success' => false,
                'message' => 'Tutti i campi sono obbligatori',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        // Verifica che le password coincidano
        if ($newPassword !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'Le password non coincidono',
                'error_code' => 'PASSWORD_MISMATCH'
            ];
        }

        // Validazione robustezza password
        if (!$this->isValidPassword($newPassword)) {
            return [
                'success' => false,
                'message' => 'La password deve essere lunga almeno 8 caratteri e contenere almeno una lettera maiuscola, una minuscola e un numero',
                'error_code' => 'WEAK_PASSWORD'
            ];
        }

        // Verifica token di reset
        $user = $this->verifyPasswordResetToken($resetToken);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Token di reset non valido o scaduto',
                'error_code' => 'INVALID_RESET_TOKEN'
            ];
        }

        // Aggiorna la password
        $updateResult = $this->updateUserPassword($user['id'], $user['user_type'], $newPassword);

        if ($updateResult) {
            // Invalida il token di reset
            $this->invalidatePasswordResetToken($resetToken);

            // Genera token di accesso normale
            $accessToken = $this->generateAccessToken([
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            return [
                'success' => true,
                'message' => 'Password resettata con successo',
                'data' => [
                    'user' => $user,
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Errore durante il reset della password',
                'error_code' => 'UPDATE_FAILED'
            ];
        }
    }

    /**
     * Verifica codice 2FA e completa il login
     * POST /auth/2fa/verify
     */
    public function action2faVerify()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $tempToken = $request->post('temp_token');
        $code = $request->post('code');
        $rememberDevice = (bool) $request->post('remember_device', false);
        $deviceName = $request->post('device_name');

        if (empty($tempToken) || empty($code)) {
            return [
                'success' => false,
                'message' => 'Token e codice sono obbligatori',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        // Verifica temp token 2FA
        $tokenData = $this->verify2faTempToken($tempToken);
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Token non valido o scaduto',
                'error_code' => 'INVALID_TEMP_TOKEN'
            ];
        }

        $userId = $tokenData['user_id'];
        $tfaService = new TwoFactorService();

        $method = $request->post('method');
        if (empty($method)) {
            // Fallback to preferred method
            $method = $tfaService->getPreferredMethod($userId);
        }

        // Verifica il codice in base al metodo
        $valid = false;
        if ($method === 'totp') {
            $valid = $tfaService->verifyTotpCode($userId, $code);
        } else {
            $valid = $tfaService->verifyEmailOtp($userId, $code);
        }

        if (!$valid) {
            return [
                'success' => false,
                'message' => 'Codice di verifica non valido',
                'error_code' => 'INVALID_2FA_CODE'
            ];
        }

        // Update preferred method based on what the user actually used
        if ($method === 'totp') {
            $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
            if ($tfa && $tfa->preferred_method !== 'totp') {
                $tfa->preferred_method = 'totp';
                $tfa->save(false);
            }
        } elseif ($method === 'email') {
            $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
            if ($tfa && $tfa->preferred_method !== 'email') {
                $tfa->preferred_method = 'email';
                $tfa->save(false);
            }
        }

        // Codice valido - ricarica dati utente dal database
        $user = User::findOne($tokenData['user_id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utente non trovato',
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        $userData = $this->buildUserData($user);
        if (!$userData) {
            return [
                'success' => false,
                'message' => 'Errore nel recupero dati utente',
                'error_code' => 'USER_DATA_BUILD_FAILED'
            ];
        }

        // Genera token di accesso completo
        $accessToken = $this->generateAccessToken([
            'user_id' => $tokenData['user_id'],
            'email' => $tokenData['email']
        ]);

        $responseData = [
            'user' => $userData,
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'requires_password_change' => 0
        ];

        // Se "ricorda dispositivo" richiesto e abilitato
        if ($rememberDevice && SystemSetting::isRememberDeviceEnabled()) {
            $device = TrustedDevice::createTrusted($userId, $deviceName);
            if ($device) {
                $responseData['device_token'] = $device->device_token;
            }
        }

        return [
            'success' => true,
            'message' => 'Autenticazione completata con successo',
            'data' => $responseData
        ];
    }

    /**
     * Reinvia OTP email
     * POST /auth/2fa/send-email-otp
     */
    public function action2faSendEmailOtp()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $tempToken = $request->post('temp_token');

        if (empty($tempToken)) {
            return [
                'success' => false,
                'message' => 'Token obbligatorio',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        $tokenData = $this->verify2faTempToken($tempToken);
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Token non valido o scaduto',
                'error_code' => 'INVALID_TEMP_TOKEN'
            ];
        }

        $user = User::findOne($tokenData['user_id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utente non trovato',
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        // Rate limit: impedisci reinvio OTP se generato meno di 60 secondi fa
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $tokenData['user_id']]);
        if ($tfa && $tfa->email_otp_expires_at) {
            $otpCreatedAt = $tfa->email_otp_expires_at - SystemSetting::getEmailOtpExpirySeconds();
            if (time() - $otpCreatedAt < 60) {
                return [
                    'success' => false,
                    'message' => 'Attendi prima di richiedere un nuovo codice',
                    'error_code' => 'RATE_LIMIT_EXCEEDED'
                ];
            }
        }

        $tfaService = new TwoFactorService();
        $sent = $tfaService->sendEmailOtp($user);

        if ($sent) {
            return [
                'success' => true,
                'message' => 'Codice di verifica inviato via email'
            ];
        }

        return [
            'success' => false,
            'message' => 'Errore nell\'invio del codice. Riprova.',
            'error_code' => 'EMAIL_SEND_FAILED'
        ];
    }

    /**
     * Setup TOTP - genera secret e URI
     * POST /auth/2fa/setup
     * Richiede JWT auth (utente già loggato)
     */
    public function action2faSetup()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        // Richiede JWT - verifica token dall'header
        $authHeader = $request->getHeaders()->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'message' => 'Token di accesso mancante',
                'error_code' => 'TOKEN_MISSING'
            ];
        }

        $userData = $this->verifyTokenWithDatabase($matches[1]);
        if (!$userData) {
            return [
                'success' => false,
                'message' => 'Token non valido o scaduto',
                'error_code' => 'TOKEN_INVALID'
            ];
        }

        $user = User::findOne($userData['id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utente non trovato',
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        $tfaService = new TwoFactorService();
        $setupData = $tfaService->initTotpSetup($user);

        return [
            'success' => true,
            'message' => 'Setup TOTP inizializzato',
            'data' => [
                'secret' => $setupData['secret'],
                'otpauth_uri' => $setupData['otpauth_uri'],
            ]
        ];
    }

    /**
     * Conferma setup TOTP con primo codice
     * POST /auth/2fa/confirm-setup
     * Richiede JWT auth (utente già loggato)
     */
    public function action2faConfirmSetup()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        // Richiede JWT
        $authHeader = $request->getHeaders()->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'message' => 'Token di accesso mancante',
                'error_code' => 'TOKEN_MISSING'
            ];
        }

        $userData = $this->verifyTokenWithDatabase($matches[1]);
        if (!$userData) {
            return [
                'success' => false,
                'message' => 'Token non valido o scaduto',
                'error_code' => 'TOKEN_INVALID'
            ];
        }

        $code = $request->post('code');
        if (empty($code)) {
            return [
                'success' => false,
                'message' => 'Codice obbligatorio',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        $tfaService = new TwoFactorService();
        if ($tfaService->confirmTotpSetup($userData['id'], $code)) {
            return [
                'success' => true,
                'message' => 'TOTP configurato con successo'
            ];
        }

        return [
            'success' => false,
            'message' => 'Codice non valido. Riprova.',
            'error_code' => 'INVALID_TOTP_CODE'
        ];
    }

    /**
     * Setup TOTP durante il flusso 2FA (usa temp_token)
     * POST /auth/2fa/setup-temp
     */
    public function action2faSetupTemp()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new HttpException(405, 'Metodo non consentito. Utilizzare POST.');
        }

        $tempToken = $request->post('temp_token');
        if (empty($tempToken)) {
            return [
                'success' => false,
                'message' => 'Token obbligatorio',
                'error_code' => 'MISSING_PARAMETERS'
            ];
        }

        $tokenData = $this->verify2faTempToken($tempToken);
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Token non valido o scaduto',
                'error_code' => 'INVALID_TEMP_TOKEN'
            ];
        }

        $user = User::findOne($tokenData['user_id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utente non trovato',
                'error_code' => 'USER_NOT_FOUND'
            ];
        }

        try {
            $tfaService = new TwoFactorService();
            $setupData = $tfaService->initTotpSetup($user);

            return [
                'success' => true,
                'message' => 'Setup TOTP inizializzato',
                'data' => [
                    'secret' => $setupData['secret'],
                    'otpauth_uri' => $setupData['otpauth_uri'],
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('TOTP setup-temp error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Errore configurazione TOTP: ' . $e->getMessage(),
                'error_code' => 'TOTP_SETUP_FAILED'
            ];
        }
    }

    /**
     * Genera un token temporaneo per la sfida 2FA (firmato con HMAC)
     */
    private function generate2faTempToken($userData)
    {
        // Rimuovi dati sensibili dei pazienti dal token per ridurre dimensione
        $safeUserData = $userData;
        unset($safeUserData['patients']);

        $payload = [
            'user_id' => $userData['id'],
            'user_type' => $userData['user_type'],
            'email' => $userData['email'],
            'purpose' => '2fa_challenge',
            'exp' => time() + 300  // Scade in 5 minuti
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, Yii::$app->params['encryptionKey']);

        return base64_encode($payloadJson . '.' . $signature);
    }

    /**
     * Verifica un token temporaneo 2FA (verifica HMAC)
     */
    private function verify2faTempToken($token)
    {
        try {
            $decoded = base64_decode($token);
            if ($decoded === false) {
                return null;
            }

            $lastDot = strrpos($decoded, '.');
            if ($lastDot === false) {
                return null;
            }

            $payloadJson = substr($decoded, 0, $lastDot);
            $signature = substr($decoded, $lastDot + 1);

            // Verifica firma HMAC
            $expectedSignature = hash_hmac('sha256', $payloadJson, Yii::$app->params['encryptionKey']);
            if (!hash_equals($expectedSignature, $signature)) {
                Yii::error('2FA temp token signature mismatch', __METHOD__);
                return null;
            }

            $payload = json_decode($payloadJson, true);

            if (!$payload ||
                    !isset($payload['exp']) ||
                    $payload['exp'] < time() ||
                    !isset($payload['purpose']) ||
                    $payload['purpose'] !== '2fa_challenge' ||
                    !isset($payload['user_id']) ||
                    !isset($payload['email'])) {
                return null;
            }

            return [
                'user_id' => $payload['user_id'],
                'email' => $payload['email'],
                'user_type' => $payload['user_type'],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Genera un token di reset password usando il sistema Yii2
     */
    private function generatePasswordResetToken($user)
    {
        try {
            // Usa il metodo standard di Yii2 per generare il token
            $user->generatePasswordResetToken();

            if ($user->save()) {
                Yii::info("Password reset token generated for user: {$user->id}", __METHOD__);
                // Restituisci solo il token puro, non l'URL completo
                return $user->password_reset_token;
            } else {
                Yii::error("Failed to save password reset token for user: {$user->id}", __METHOD__);
                return null;
            }
        } catch (\Exception $e) {
            Yii::error('Exception generating password reset token: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Verifica un token di reset password usando il sistema Yii2
     */
    private function verifyPasswordResetToken($token)
    {
        try {
            // Usa il metodo standard di Yii2 per trovare l'utente dal token
            $user = User::findByPasswordResetToken($token);

            if (!$user) {
                return null;
            }

            // Costruisce i dati utente usando la stessa logica del login
            return $this->buildUserData($user);
        } catch (\Exception $e) {
            Yii::error('Exception verifying password reset token: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Invalida un token di reset password usando il sistema Yii2
     */
    private function invalidatePasswordResetToken($token)
    {
        try {
            $user = User::findByPasswordResetToken($token);

            if ($user) {
                // Usa il metodo standard di Yii2 per rimuovere il token
                $user->removePasswordResetToken();
                $user->save();

                Yii::info("Password reset token invalidated for user: {$user->id}", __METHOD__);
            }
        } catch (\Exception $e) {
            Yii::error('Exception invalidating password reset token: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Invia email con il link di reset password
     */
    private function sendPasswordResetEmail($user, $resetToken)
    {
        try {
            // Il resetToken dovrebbe essere già il token puro
            $pureToken = $resetToken;

            // Costruisci l'URL di reset per la pagina web
            $resetUrl = Yii::$app->params['frontendUrl'] . '/site/reset-password?token=' . $pureToken;

            $emailSent = Yii::$app
                ->mailer
                ->compose()
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('Reset password per ' . Yii::$app->name)
                ->setHtmlBody($this->generatePasswordResetEmailHtml($user, $pureToken, $resetUrl))
                ->setTextBody($this->generatePasswordResetEmailText($user, $pureToken, $resetUrl))
                ->send();

            if ($emailSent) {
                Yii::info("Password reset email sent to: {$user->email}", __METHOD__);
                return true;
            } else {
                Yii::error("Failed to send password reset email to: {$user->email}", __METHOD__);
                return false;
            }
        } catch (\Exception $e) {
            Yii::error('Exception sending password reset email: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Genera il contenuto HTML dell'email di reset password
     */
    private function generatePasswordResetEmailHtml($user, $resetToken, $resetUrl)
    {
        $profile = $user->profile;
        $userName = $profile ? $profile->first_name : $user->email;

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Reset Password - " . Yii::$app->name . "</h2>
                
                <p style='color: #666; line-height: 1.6;'>Ciao {$userName},</p>
                
                <p style='color: #666; line-height: 1.6;'>
                    Hai richiesto il reset della password per il tuo account. 
                    Clicca sul pulsante qui sotto per completare il processo.
                </p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetUrl}' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                
                <p style='color: #666; line-height: 1.6;'>
                    <strong>Alternativa per l'app mobile:</strong>
                </p>
                <div style='background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; font-weight: bold; color: #495057;'>Token di Reset:</p>
                    <p style='margin: 10px 0 0 0; font-family: monospace; font-size: 16px; color: #007bff;'>{$resetToken}</p>
                </div>
                
                <p style='color: #666; line-height: 1.6;'>
                    <strong>Istruzioni per app mobile:</strong>
                </p>
                <ol style='color: #666; line-height: 1.6;'>
                    <li>Apri l'app " . Yii::$app->name . "</li>
                    <li>Vai alla schermata di login</li>
                    <li>Clicca su 'Password dimenticata?'</li>
                    <li>Inserisci il token di reset sopra indicato</li>
                    <li>Crea una nuova password</li>
                </ol>
                
                <p style='color: #666; line-height: 1.6;'>
                    <strong>Importante:</strong> Questo link scade tra 1 ora. 
                    Se non hai richiesto tu questo reset, ignora questa email.
                </p>
                
                <hr style='border: none; border-top: 1px solid #dee2e6; margin: 20px 0;'>
                
                <p style='color: #999; font-size: 12px; text-align: center;'>
                    Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.
                </p>
            </div>
        </div>
        ";
    }

    /**
     * Genera il contenuto testuale dell'email di reset password
     */
    private function generatePasswordResetEmailText($user, $resetToken, $resetUrl)
    {
        $profile = $user->profile;
        $userName = $profile ? $profile->first_name : $user->email;

        return '
Reset Password - ' . Yii::$app->name . "

Ciao {$userName},

Hai richiesto il reset della password per il tuo account. 
Clicca sul link qui sotto per completare il processo:

{$resetUrl}

Alternativa per l'app mobile:
Token di Reset: {$resetToken}

Istruzioni per app mobile:
1. Apri l'app " . Yii::$app->name . "
2. Vai alla schermata di login
3. Clicca su 'Password dimenticata?'
4. Inserisci il token di reset sopra indicato
5. Crea una nuova password

Importante: Questo link scade tra 1 ora. 
Se non hai richiesto tu questo reset, ignora questa email.

---
Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.
        ";
    }
}
