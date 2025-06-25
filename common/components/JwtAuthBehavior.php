<?php

namespace common\components;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;


use common\models\AuthToken;
use common\models\User;

/**
 * Behavior per la verifica del token JWT nelle richieste API
 */
class JwtAuthBehavior extends Behavior
{
    /**
     * @var array Lista delle azioni che non richiedono autenticazione
     */
    public $excludeActions = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
     * Verifica il token JWT prima dell'esecuzione delle azioni del controller
     * 
     * @param \yii\base\ActionEvent $event
     * @return bool
     * @throws UnauthorizedHttpException
     */
    public function beforeAction($event)
    {
        $action = $event->action->id;

        // Skip authentication for excluded actions
        if (in_array($action, $this->excludeActions)) {
            return true;
        }

        // Get the authorization header
        $authHeader = Yii::$app->request->headers->get('Authorization');
        
        // Fallback per Apache che potrebbe non passare l'header correttamente
        if (!$authHeader) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        }

        if (!$authHeader || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $this->sendErrorResponse('UNAUTHORIZED', 'Il token di autenticazione non è stato fornito.', [], 401);
            return false;
        }

        $token = $matches[1];

        // Verifica il token nel database
        $authTokenRecord = AuthToken::findOne(['token' => $token, 'is_revoked' => 0]);

        if (!$authTokenRecord) {
            $this->sendErrorResponse('UNAUTHORIZED', 'Token non valido o revocato.', [], 401);
            return false;
        }

        // Verifica scadenza del token
        if ($authTokenRecord->expires_at < time()) {
            $this->sendErrorResponse('UNAUTHORIZED', 'Token scaduto.', [], 401);
            return false;
        }

        // Verifica validità del token tramite il componente JWT
        $jwtComponent = Yii::$app->jwt; // Usa il componente configurato in Yii
        $decoded = $jwtComponent->validateToken($token);

        if (!$decoded) {
            // Se il token non è valido, revocalo nel database
            $authTokenRecord->is_revoked = 1;
            $authTokenRecord->save();
            $this->sendErrorResponse('UNAUTHORIZED', 'Token non valido.', [], 401);
            return false;
        }

        // Aggiorna last_used_at
        $authTokenRecord->last_used_at = time();
        $authTokenRecord->save();

        // Imposta l'utente corrente
        $user = User::findOne($authTokenRecord->user_id);
        if (!$user) {
            $this->sendErrorResponse('UNAUTHORIZED', 'Utente non trovato.', [], 401);
            return false;
        }

        Yii::$app->user->setIdentity($user);

        return true;
    }
    
    /**
     * Invia una risposta di errore nel formato standardizzato
     */
    private function sendErrorResponse($errorCode, $message, $details = [], $statusCode = 401)
    {
        Yii::$app->response->statusCode = $statusCode;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $errorCode
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        Yii::$app->response->data = $response;
        Yii::$app->response->send();
        Yii::$app->end();
    }
}
