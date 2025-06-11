<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;

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

        // Cerca automaticamente prima tra i pazienti, poi tra i terapisti
        $user = $this->findUserInBothTables($email, $password);

        if ($user) {
            // Controlla se è il primo login
            $requiresPasswordChange = isset($user['first_login']) && $user['first_login'] === true;
            
            // Se è primo login, non genera il token completo
            if ($requiresPasswordChange) {
                return [
                    'success' => true,
                    'message' => 'Login effettuato. È necessario cambiare la password.',
                    'data' => [
                        'user' => [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'nome' => $user['nome'],
                            'cognome' => $user['cognome'],
                            'user_type' => $user['user_type']
                        ],
                        'requires_password_change' => true,
                        'temp_token' => $this->generateTempToken($user) // Token temporaneo per cambio password
                    ]
                ];
            }
            
            // Login normale - genera token di accesso completo
            $token = $this->generateAccessToken($user);
            
            return [
                'success' => true,
                'message' => 'Login effettuato con successo',
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600, // 1 ora
                    'requires_password_change' => false
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

        // Per ora simuliamo semplicemente il logout
        // In futuro qui si invaliderebbero i token nel database
        
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
        $user = $this->simulateTokenVerification($token);
        
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
     * Cerca un utente prima nella tabella pazienti, poi in quella terapisti
     */
    private function findUserInBothTables($email, $password)
    {
        // Prima cerca tra i pazienti
        $user = $this->searchInPazientiTable($email, $password);
        if ($user) {
            return $user;
        }

        // Se non trovato, cerca tra i terapisti
        $user = $this->searchInTerapistiTable($email, $password);
        if ($user) {
            return $user;
        }

        return null;
    }

    /**
     * Simula la ricerca nella tabella pazienti
     */
    private function searchInPazientiTable($email, $password)
    {
        // Dati simulati per pazienti
        $pazienti = [
            [
                'id' => 1,
                'email' => 'paziente1@example.com',
                'password' => 'password123', // In produzione sarà hashata
                'nome' => 'Marco',
                'cognome' => 'Rossi',
                'codice_fiscale' => 'RSSMRC80A01H501Z',
                'telefono' => '123456789',
                'data_nascita' => '1980-01-01',
                'indirizzo' => 'Via Roma 123, Milano',
                'user_type' => 'paziente',
                'status' => 'attivo',
                'first_login' => true
            ],
            [
                'id' => 2,
                'email' => 'paziente2@example.com',
                'password' => 'password123',
                'nome' => 'Laura',
                'cognome' => 'Bianchi',
                'codice_fiscale' => 'BNCLAURA85B02F205W',
                'telefono' => '987654321',
                'data_nascita' => '1985-02-15',
                'indirizzo' => 'Via Napoli 456, Roma',
                'user_type' => 'paziente',
                'status' => 'attivo',
                'first_login' => false
            ]
        ];

        foreach ($pazienti as $paziente) {
            if ($paziente['email'] === $email && $paziente['password'] === $password) {
                // Rimuovi la password dalla risposta
                unset($paziente['password']);
                return $paziente;
            }
        }

        return null;
    }

    /**
     * Simula la ricerca nella tabella terapisti
     */
    private function searchInTerapistiTable($email, $password)
    {
        // Dati simulati per terapisti
        $terapisti = [
            [
                'id' => 1,
                'email' => 'terapista1@example.com',
                'password' => 'password789',
                'nome' => 'Dr. Giuseppe',
                'cognome' => 'Verdi',
                'codice_fiscale' => 'VRDGPP75C03L219X',
                'telefono' => '555123456',
                'specializzazione' => 'Fisioterapia',
                'numero_albo' => 'FT12345',
                'user_type' => 'terapista',
                'status' => 'attivo',
                'first_login' => true
            ],
            [
                'id' => 2,
                'email' => 'terapista2@example.com',
                'password' => 'password000',
                'nome' => 'Dr.ssa Anna',
                'cognome' => 'Neri',
                'codice_fiscale' => 'NRANNA82D04M123Y',
                'telefono' => '555789012',
                'specializzazione' => 'Psicoterapia',
                'numero_albo' => 'PSI67890',
                'user_type' => 'terapista',
                'status' => 'attivo',
                'first_login' => false
            ]
        ];

        foreach ($terapisti as $terapista) {
            if ($terapista['email'] === $email && $terapista['password'] === $password) {
                // Rimuovi la password dalla risposta
                unset($terapista['password']);
                return $terapista;
            }
        }

        return null;
    }

    /**
     * Genera un token di accesso simulato
     */
    private function generateAccessToken($user)
    {
        // In produzione useresti JWT o un sistema di token più sicuro
        $payload = [
            'user_id' => $user['id'],
            'user_type' => $user['user_type'],
            'email' => $user['email'],
            'exp' => time() + 3600 // Scade in 1 ora
        ];
        
        // Token simulato (in produzione useresti JWT)
        return base64_encode(json_encode($payload));
    }

    /**
     * Simula la verifica di un token
     */
    private function simulateTokenVerification($token)
    {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
                return null; // Token scaduto o non valido
            }
            
            // Simula il recupero dell'utente dal token
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
        $updateResult = $this->simulatePasswordUpdate($user['id'], $user['user_type'], $newPassword);
        
        if ($updateResult) {
            // Genera token di accesso normale dopo il cambio password
            $user['first_login'] = false; // Non è più primo login
            $accessToken = $this->generateAccessToken($user);
            
            return [
                'success' => true,
                'message' => 'Password cambiata con successo',
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
                'message' => 'Errore durante il cambio password',
                'error_code' => 'UPDATE_FAILED'
            ];
        }
    }

    /**
     * Genera un token temporaneo per il cambio password
     */
    private function generateTempToken($user)
    {
        // Token temporaneo con durata limitata (10 minuti)
        $payload = [
            'user_id' => $user['id'],
            'user_type' => $user['user_type'],
            'email' => $user['email'],
            'purpose' => 'password_change',
            'exp' => time() + 600 // Scade in 10 minuti
        ];
        
        return base64_encode(json_encode($payload));
    }

    /**
     * Verifica un token temporaneo per il cambio password
     */
    private function verifyTempToken($token)
    {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || 
                !isset($payload['exp']) || 
                $payload['exp'] < time() ||
                !isset($payload['purpose']) ||
                $payload['purpose'] !== 'password_change') {
                return null; // Token scaduto, non valido o non per cambio password
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
            return false; // Nessuna maiuscola
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return false; // Nessuna minuscola
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return false; // Nessun numero
        }
        
        return true;
    }

    /**
     * Simula l'aggiornamento della password nel database
     */
    private function simulatePasswordUpdate($userId, $userType, $newPassword)
    {
        // In produzione qui faresti l'update nel database
        // UPDATE pazienti/terapisti SET password = hash($newPassword), first_login = false WHERE id = $userId
        
        // Per ora simuliamo un aggiornamento sempre riuscito
        Yii::info("Simulazione aggiornamento password per utente ID: {$userId}, tipo: {$userType}", __METHOD__);
        
        // Simula che l'operazione è sempre riuscita
        return true;
    }
}