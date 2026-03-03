<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $settings array */

$this->title = 'Impostazioni 2FA';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="p-5">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= Html::encode($this->title) ?></h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Gestisci le impostazioni dell'autenticazione a due fattori per tutti gli utenti.</p>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif; ?>

    <?= Html::beginForm(['system-setting/update-two-factor'], 'post', ['class' => 'max-w-2xl']) ?>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Impostazioni generali</h3>

        <!-- Toggle 2FA globale -->
        <div class="flex items-center justify-between py-4 border-b border-gray-100 dark:border-gray-700">
            <div>
                <label class="font-medium text-gray-700 dark:text-gray-300">Abilita 2FA globalmente</label>
                <p class="text-sm text-gray-500 dark:text-gray-400">Quando attivo, gli utenti con 2FA abilitato dovranno inserire un codice di verifica al login.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= !empty($settings['enabled']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-blue-600"></div>
            </label>
        </div>

    </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Parametri</h3>

        <!-- Scadenza OTP -->
        <div class="mb-4">
            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Scadenza OTP email (secondi)</label>
            <input type="number" name="email_otp_expiry_seconds" value="<?= Html::encode($settings['email_otp_expiry_seconds'] ?? 300) ?>" min="60" max="3600"
                   class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tempo in secondi prima che il codice OTP email scada (default: 300 = 5 minuti).</p>
        </div>

        <!-- Max tentativi OTP -->
        <div class="mb-4">
            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Max tentativi OTP</label>
            <input type="number" name="max_otp_attempts" value="<?= Html::encode($settings['max_otp_attempts'] ?? 5) ?>" min="1" max="20"
                   class="w-full max-w-xs px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Numero massimo di tentativi errati prima di bloccare il codice OTP.</p>
        </div>
    </div>

    <div class="flex justify-end">
        <?= Html::submitButton('Salva impostazioni', ['class' => 'px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors']) ?>
    </div>

    <?= Html::endForm() ?>
</div>
