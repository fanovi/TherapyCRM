<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\Appointment;
use yii\db\Expression;
use yii\helpers\Console;

/**
 * Gestisce il completamento automatico degli appuntamenti
 * 
 * @author Your Name
 */
class EmailController extends Controller {

    public function actionTest()
    {
        $email_sended = Yii::$app->mailer->compose()
        ->setTo('vito.fasano@badil.it')
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setSubject('Test email')
        ->setTextBody('Test email body')
        ->send();
    }
}
