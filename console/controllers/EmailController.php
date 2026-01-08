<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\Appointment;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use yii\db\Expression;
use yii\helpers\Console;

/**
 * Test email controller
 * 
 * @author Vito Fasano
 */
class EmailController extends Controller
{

    public function actionTest()
    {
        try {
            $message = Yii::$app->mailer->compose();
            $message->setFrom('noreply@sanlucacentromedico.it');
            $message->setTo('vito.fasano@badil.it');
            $message->setSubject('SMTP ATOMIC TEST');
            $message->setTextBody('Test body');
            // :fuoco: FONDAMENTALE
            $message->setEnvelopeFrom('noreply@sanlucacentromedico.it');
            $sent = $message->send();
            var_dump($sent);
        } catch (\Throwable $e) {
            echo "ERRORE:\n";
            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString();
        }
        return ExitCode::OK;
    }
}
