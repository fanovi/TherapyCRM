<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Impostazioni 2FA';
?>

<style>
.toggle-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#d1d5db;border-radius:9999px;transition:background-color .2s}
.toggle-slider:before{position:absolute;content:"";height:20px;width:20px;left:2px;bottom:2px;background-color:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle-switch input:checked+.toggle-slider{background-color:#4f46e5}
.toggle-switch input:checked+.toggle-slider:before{transform:translateX(20px)}
</style>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
        <p class="mt-1 text-sm text-gray-500">Gestisci le impostazioni dell'autenticazione a due fattori per l'app mobile.</p>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="rounded-md bg-green-50 p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm font-medium text-green-800"><?= Yii::$app->session->getFlash('success') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- IMPOSTAZIONI GLOBALI -->
    <?= Html::beginForm(['system-setting/update-two-factor'], 'post') ?>
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Impostazioni globali</h2>
        </div>
        <div class="px-6 py-4">
            <div class="flex items-center justify-between py-4 border-b border-gray-100">
                <div class="pr-4">
                    <label class="text-sm font-medium text-gray-900">Abilita 2FA globalmente</label>
                    <p class="text-sm text-gray-500 mt-1">Quando attivo, gli utenti con 2FA abilitato dovranno inserire un codice al login.</p>
                </div>
                <input type="hidden" name="enabled" value="0">
                <label class="toggle-switch">
                    <input type="checkbox" name="enabled" value="1" <?= !empty($settings['enabled']) ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="flex items-center justify-between py-4">
                <div class="pr-4">
                    <label class="text-sm font-medium text-gray-900">Permetti "Ricorda dispositivo"</label>
                    <p class="text-sm text-gray-500 mt-1">Gli utenti potranno saltare il 2FA su dispositivi fidati.</p>
                </div>
                <input type="hidden" name="remember_device_enabled" value="0">
                <label class="toggle-switch">
                    <input type="checkbox" name="remember_device_enabled" value="1" <?= !empty($settings['remember_device_enabled']) ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Parametri</h2>
        </div>
        <div class="px-6 py-4 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Giorni validità dispositivo fidato</label>
                <input type="number" name="remember_device_days" value="<?= Html::encode($settings['remember_device_days'] ?? 30) ?>" min="1" max="365"
                       class="mt-1 block w-64 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Scadenza OTP email (secondi)</label>
                <input type="number" name="email_otp_expiry_seconds" value="<?= Html::encode($settings['email_otp_expiry_seconds'] ?? 300) ?>" min="60" max="3600"
                       class="mt-1 block w-64 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Max tentativi OTP</label>
                <input type="number" name="max_otp_attempts" value="<?= Html::encode($settings['max_otp_attempts'] ?? 5) ?>" min="1" max="20"
                       class="mt-1 block w-64 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <?= Html::submitButton('Salva impostazioni', [
            'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        ]) ?>
    </div>
    <?= Html::endForm() ?>

    <!-- GESTIONE 2FA UTENTI -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900">Gestione 2FA Utenti</h2>
                <p class="text-sm text-gray-500 mt-1">Abilita o disabilita il 2FA per ogni singolo utente.</p>
            </div>
            <?= Html::beginForm(['system-setting/enable-all-2fa'], 'post', ['class' => 'inline', 'onsubmit' => "return confirm('Abilitare il 2FA per TUTTI gli utenti attivi?')"]) ?>
                <?= Html::submitButton('Abilita tutti', [
                    'class' => 'inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700'
                ]) ?>
            <?= Html::endForm() ?>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metodo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato 2FA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azione</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <?php
                        $tfa = $user->twoFactorAuth;
                        $isEnabled = $tfa && $tfa->is_enabled;
                        $method = $tfa ? $tfa->preferred_method : '-';
                        $name = $user->profile ? $user->profile->getFullName() : $user->username;
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $user->id ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= Html::encode($name) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= Html::encode($user->email) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $isEnabled ? Html::encode($method) : '-' ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($isEnabled): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Attivo</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Disattivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($isEnabled): ?>
                                <?= Html::beginForm(['system-setting/disable-user-2fa', 'userId' => $user->id], 'post', ['class' => 'inline']) ?>
                                    <?= Html::submitButton('Disabilita', [
                                        'class' => 'inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700',
                                        'onclick' => "return confirm('Disabilitare il 2FA per questo utente?')"
                                    ]) ?>
                                <?= Html::endForm() ?>
                            <?php else: ?>
                                <?= Html::beginForm(['system-setting/enable-user-2fa', 'userId' => $user->id], 'post', ['class' => 'inline']) ?>
                                    <?= Html::submitButton('Abilita', [
                                        'class' => 'inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700'
                                    ]) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
