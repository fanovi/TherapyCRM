<?php

/** @var yii\web\View $this */
/** @var common\models\User $user */
$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
?>
Ciao <?= $user->username ?>,

Abbiamo ricevuto una richiesta di reset password per il tuo account.

Per completare il reset della password, clicca sul link qui sotto:

<?= $resetLink ?>

Attenzione: Questo link è valido solo per 24 ore. Se non hai richiesto il reset della password, ignora questa email.

Se hai problemi, contatta il supporto tecnico.

Questa email è stata inviata automaticamente, non rispondere a questo messaggio.
