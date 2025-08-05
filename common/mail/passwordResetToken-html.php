<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - <?= Yii::$app->name ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f9fafb; }
        .button { display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .warning { background-color: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= Yii::$app->name ?></h1>
            <p>Reset Password</p>
        </div>
        
        <div class="content">
            <p>Ciao <?= Html::encode($user->username) ?>,</p>
            
            <p>Abbiamo ricevuto una richiesta di reset password per il tuo account.</p>
            
            <p>Per completare il reset della password, clicca sul pulsante qui sotto:</p>
            
            <div style="text-align: center;">
                <a href="<?= $resetLink ?>" class="button">Reset Password</a>
            </div>
            
            <p>Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
            <p style="word-break: break-all; color: #2563eb;"><?= $resetLink ?></p>
            
            <div class="warning">
                <strong>Attenzione:</strong> Questo link è valido solo per 24 ore. Se non hai richiesto il reset della password, ignora questa email.
            </div>
            
            <p>Se hai problemi, contatta il supporto tecnico.</p>
        </div>
        
        <div class="footer">
            <p>Questa email è stata inviata automaticamente, non rispondere a questo messaggio.</p>
            <p>&copy; <?= date('Y') ?> <?= Yii::$app->name ?>. Tutti i diritti riservati.</p>
        </div>
    </div>
</body>
</html>
