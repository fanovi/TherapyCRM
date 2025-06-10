<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use common\models\User;
use common\models\AuthToken; // Assicurati che il modello AuthToken esista e sia configurato correttamente.
use frontend\models\PasswordResetRequestForm;

class AuthController extends Controller
{
    /**
     * Definiamo i behaviors per escludere l'azione di login
     * dalle verifiche di autenticazione (visto che qui si richiede il login).
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        // Rimuoviamo l'autenticazione per le azioni "login".
        $behaviors['authenticator']['except'] = ['login'];
        return $behaviors;
    }

    /**
     * Action per gestire il login.
     * L'utente invia username e password in una richiesta POST.
     *
     * L'endpoint può essere qualcosa come:
     * POST https://app.managify.it/api/auth/login
     *
     * L'app React Native invierà i dati nelle POST data, e in caso di successo
     * riceverà un token JWT (e un eventuale refresh token) da utilizzare
     * nelle chiamate successive.
     *
     * @return array Risposta JSON contenente il token e le informazioni utili.
     */
    public function actionLogin()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;


        $data = $request->getRawBody();

        if (!$data) {
            return [
                'success' => false,
                'error'   => 'Richiesta non valida.',
            ];
        }

        $dataDecoded = json_decode($data, true);

        $username = $dataDecoded['username'] ?? null;
        $password = $dataDecoded['password'] ?? null;



        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'error'   => 'I campi username e password sono obbligatori.',
            ];
        }

        // Tenta di trovare l'utente in base allo username
        $user = User::findOne(['username' => $username, 'status' => 10]);
        if (!$user || !$user->validatePassword($password)) {
            return [
                'success' => false,
                'error'   => 'Credenziali non valide.',
            ];
        }

        // Controlla se l'utente ha il ruolo di amministratore
        $auth = Yii::$app->authManager;
        $userRoles = array_keys($auth->getRolesByUser($user->id));

        if (
            !in_array('admin', $userRoles, true)
            && !in_array('super_admin', $userRoles, true)
            && !in_array('operator_manager', $userRoles, true)
        ) {
            return [
                'success' => false,
                'error'   => 'Accesso consentito solo ad admin o super_admin.',
            ];
        }
        // FINE Controlla se l'utente ha il ruolo di amministratore

        // Prepara il payload del token: aggiungi i dati dell'utente che ti servono
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
        ];

        // Genera il token JWT usando il componente configurato (Yii::$app->jwt)
        $token = Yii::$app->jwt->generateToken($payload);
        $refreshToken = Yii::$app->jwt->generateRefreshToken($payload);

        // (Opzionale) Salva il token nel database per poterlo revocare successivamente
        $authToken = new AuthToken();
        $authToken->user_id = $user->id;
        $authToken->token = $token;
        $authToken->refresh_token = $refreshToken; // Nuovo campo
        $authToken->is_revoked = 0;
        $authToken->created_at = time();
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
            $roleNames[] = $role->name;
        }

        // Restituisce la risposta con i dati del token
        return [
            'success' => true,
            'token' => $token,
            'refreshToken' => $refreshToken,
            'expires_in' => $authToken->expires_at,
            'refresh_expires_in' => $authToken->refresh_expires_at,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'nome' => $user->nome,
                'cognome' => $user->cognome,
                'roles' => $roleNames, // Aggiungi i ruoli qui
            ],
        ];
    }


    public function actionRefresh()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

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
                'roles' => $roleNames, // Aggiungi i ruoli qui
            ],
        ];
    }

    public function actionCheckToken()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $token = Yii::$app->request->headers->get('Authorization');
        if (!$token) {
            return ['success' => false, 'valid' => false];
        }

        // Rimuovi "Bearer "
        $token = str_replace('Bearer ', '', $token);

        $authToken = AuthToken::findOne(['token' => $token, 'is_revoked' => 0]);

        if (!$authToken || $authToken->expires_at < time()) {
            return ['success' => false, 'valid' => false];
        }

        return ['success' => true, 'valid' => true];
    }

    public function actionRequestPasswordReset()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new PasswordResetRequestForm();
        $params = Yii::$app->request->getRawBody();

        $params = json_decode($params, true);

        $model->email = $params['email'] ?? null;

        if (!$model->validate()) {
            // Puoi anche restituire l'errore specifico del model, se vuoi più dettagli in app
            return [
                'success' => false,
                'error' => $model->getFirstError('email') ?: 'Email non valida.',
            ];
        }

        // Chiama la logica originale: il model penserà a trovare l'utente, creare il token e inviare la mail.
        if ($model->sendEmail()) {
            return [
                'success' => true,
                'message' => "Se l'email è registrata, riceverai le istruzioni per resettare la password.",
            ];
        } else {
            // Se fallisce l'invio (es: SMTP giù), puoi fare logging e mostrare un errore generico
            Yii::error('Impossibile inviare email di reset password per: ' . $model->email, __METHOD__);
            return [
                'success' => false,
                'error' => "Spiacenti, non siamo in grado di procedere con la richiesta.",
            ];
        }
    }
}
