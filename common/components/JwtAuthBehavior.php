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

        if (!$authHeader || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            throw new UnauthorizedHttpException('Il token di autenticazione non è stato fornito.');
        }

        $token = $matches[1];

        // Verifica il token nel database
        $authTokenRecord = AuthToken::findOne(['token' => $token, 'is_revoked' => 0]);

        if (!$authTokenRecord) {
            throw new UnauthorizedHttpException('Token non valido o revocato.');
        }

        // Verifica scadenza del token
        if ($authTokenRecord->expires_at < time()) {
            throw new UnauthorizedHttpException('Token scaduto.');
        }

        // Verifica validità del token tramite il componente JWT
        $jwtComponent = Yii::$app->jwt; // Usa il componente configurato in Yii
        $decoded = $jwtComponent->validateToken($token);

        if (!$decoded) {
            // Se il token non è valido, revocalo nel database
            $authTokenRecord->is_revoked = 1;
            $authTokenRecord->save();
            throw new UnauthorizedHttpException('Token non valido.');
        }

        // Aggiorna last_used_at
        $authTokenRecord->last_used_at = time();
        $authTokenRecord->save();

        // Imposta l'utente corrente
        $user = User::findOne($authTokenRecord->user_id);
        if (!$user) {
            throw new UnauthorizedHttpException('Utente non trovato.');
        }

        Yii::$app->user->setIdentity($user);

        return true;
    }
}
