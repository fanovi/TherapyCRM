<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $settings array */

$this->title = 'Impostazioni 2FA';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
        <p class="mt-1 text-sm text-gray-500">Gestisci le impostazioni dell'autenticazione a due fattori per tutti gli utenti dell'app mobile.</p>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?= Yii::$app->session->getFlash('success') ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?= Html::beginForm(['system-setting/update-two-factor'], 'post') ?>

    <!-- Impostazioni generali -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Impostazioni generali</h2>
        </div>
        <div class="px-6 py-4 space-y-4">

            <!-- Toggle 2FA globale -->
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <div>
                    <label class="text-sm font-medium text-gray-900">Abilita 2FA globalmente</label>
                    <p class="text-sm text-gray-500">Quando attivo, gli utenti con 2FA abilitato dovranno inserire un codice di verifica al login nell'app.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= !empty($settings['enabled']) ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <!-- Toggle Ricorda dispositivo -->
            <div class="flex items-center justify-between py-3">
                <div>
                    <label class="text-sm font-medium text-gray-900">Permetti "Ricorda dispositivo"</label>
                    <p class="text-sm text-gray-500">Gli utenti potranno scegliere di non inserire il codice 2FA su dispositivi fidati.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="remember_device_enabled" value="0">
                    <input type="checkbox" name="remember_device_enabled" value="1" class="sr-only peer" <?= !empty($settings['remember_device_enabled']) ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Parametri -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Parametri</h2>
        </div>
        <div class="px-6 py-4 space-y-5">

            <!-- Giorni validità dispositivo -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Giorni validità dispositivo fidato</label>
                <input type="number" name="remember_device_days" value="<?= Html::encode($settings['remember_device_days'] ?? 30) ?>" min="1" max="365"
                       class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">Numero di giorni prima che un dispositivo fidato debba ripetere la verifica 2FA.</p>
            </div>

            <!-- Scadenza OTP -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Scadenza OTP email (secondi)</label>
                <input type="number" name="email_otp_expiry_seconds" value="<?= Html::encode($settings['email_otp_expiry_seconds'] ?? 300) ?>" min="60" max="3600"
                       class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">Tempo in secondi prima che il codice OTP email scada (default: 300 = 5 minuti).</p>
            </div>

            <!-- Max tentativi OTP -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Max tentativi OTP</label>
                <input type="number" name="max_otp_attempts" value="<?= Html::encode($settings['max_otp_attempts'] ?? 5) ?>" min="1" max="20"
                       class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <p class="mt-1 text-sm text-gray-500">Numero massimo di tentativi errati prima di bloccare il codice OTP.</p>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex justify-end">
        <?= Html::submitButton('Salva impostazioni', [
            'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        ]) ?>
    </div>

    <?= Html::endForm() ?>
</div>
