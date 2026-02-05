<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use common\models\User;

class ServicingController extends Controller
{
    public function actionLogin($email, $key = null)
    {
        // Chiave segreta obbligatoria
        $secretKey = Yii::$app->params['servicingKey'] ?? null;
        if (!$secretKey || $key !== $secretKey) {
            throw new ForbiddenHttpException('Accesso negato.');
        }

        /** @var User|null $user */
        $user = User::find()->where(['email' => $email])->one();
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        Yii::$app->user->login($user);
        return $this->goHome();
    }
}
