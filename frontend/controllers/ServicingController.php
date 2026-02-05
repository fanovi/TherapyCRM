<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\User;

class ServicingController extends Controller
{
    public function actionLogin($email)
    {
        /** @var User|null $user */
        $user = User::find()->where(['email' => $email])->one();
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        Yii::$app->user->login($user);
        return $this->goHome();
    }
}
