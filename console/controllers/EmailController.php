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
 * Gestisce il completamento automatico degli appuntamenti
 * 
 * @author Your Name
 */
class EmailController extends Controller
{

    public function actionTest()
    {
        try {
            Console::output('Invio email a: vito.fasano@badil.it');
            Console::output('Da: ' . Yii::$app->params['senderEmail'] . ' - ' . Yii::$app->params['senderName']);

            $result = Yii::$app->mailer->compose()
                ->setTo('vito.fasano@badil.it')
                ->setFrom(Yii::$app->params['senderEmail'])
                ->setSubject('Test email')
                ->setTextBody('Test email body')
                ->send();
            if ($result) {
                Console::output(':segno_spunta_bianco: Email inviata correttamente');
            } else {
                Console::output(':x: Email NON inviata (send() = false)');
            }
        } catch (TransportExceptionInterface $e) {
            Console::error(':x: ERRORE SMTP');
            Console::error($e->getMessage());
        } catch (\Throwable $e) {
            Console::error(':x: ERRORE GENERICO');
            Console::error($e->getMessage());
        }
        return ExitCode::OK;
    }
}
