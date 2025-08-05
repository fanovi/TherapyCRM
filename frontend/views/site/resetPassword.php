<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Reset Password';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Reset Password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Crea una nuova password per il tuo account
            </p>
        </div>

        <!-- Reset Password Form -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="p-8 space-y-6">
            
            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800">
                                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800">
                                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($success) && $success): ?>
                <div class="mb-6 p-6 bg-green-50 border border-green-200 rounded-lg dark:bg-green-900/20 dark:border-green-800">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-green-800 dark:text-green-200">
                                Password aggiornata con successo!
                            </h3>
                            <p class="mt-2 text-sm text-green-700 dark:text-green-300">
                                La tua password è stata aggiornata. Ora puoi accedere all'app mobile con le nuove credenziali.
                            </p>
                           
                        </div>
                    </div>
                </div>
            <?php else: ?>

            <?php $form = ActiveForm::begin([
                'id' => 'reset-password-form',
                'options' => ['class' => 'space-y-6'],
            ]); ?>

            <?= Html::hiddenInput('token', $token) ?>

            <!-- Password Field -->
            <div x-data="{ showPassword: false }">
                <?= $form->field($model, 'password', [
                    'template' => '<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{label}</label>
                                 <div class="relative">
                                     {input}
                                     <span @click="showPassword = !showPassword" class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer">
                                         <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                             <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"/>
                                         </svg>
                                         <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                             <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"/>
                                         </svg>
                                     </span>
                                 </div>
                                 {error}',
                    'labelOptions' => [],
                ])->textInput([
                    'placeholder' => 'Inserisci la nuova password',
                    'autocomplete' => 'new-password',
                    'class' => 'dark:bg-dark-900 shadow-theme-xs focus:border-blue-300 focus:ring-blue-500/10 dark:focus:border-blue-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                    'x-bind:type' => "showPassword ? 'text' : 'password'"
                ]) ?>
            </div>

            <!-- Confirm Password Field -->
            <div x-data="{ showConfirmPassword: false }">
                <?= $form->field($model, 'password_repeat', [
                    'template' => '<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{label}</label>
                                 <div class="relative">
                                     {input}
                                     <span @click="showConfirmPassword = !showConfirmPassword" class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer">
                                         <svg x-show="!showConfirmPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                             <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"/>
                                         </svg>
                                         <svg x-show="showConfirmPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                             <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"/>
                                         </svg>
                                     </span>
                                 </div>
                                 {error}',
                    'labelOptions' => [],
                ])->textInput([
                    'placeholder' => 'Conferma la nuova password',
                    'autocomplete' => 'new-password',
                    'class' => 'dark:bg-dark-900 shadow-theme-xs focus:border-blue-300 focus:ring-blue-500/10 dark:focus:border-blue-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                    'x-bind:type' => "showConfirmPassword ? 'text' : 'password'"
                ]) ?>
            </div>

            <!-- Password Requirements -->
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg dark:bg-blue-900/20 dark:border-blue-800">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-3 flex items-center">
                    <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Requisiti password
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div class="flex items-center text-sm">
                        <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center mr-2 dark:bg-green-900/30">
                            <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-green-700 dark:text-green-300">Almeno 8 caratteri</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center mr-2 dark:bg-green-900/30">
                            <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-green-700 dark:text-green-300">Una lettera maiuscola</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center mr-2 dark:bg-green-900/30">
                            <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-green-700 dark:text-green-300">Una lettera minuscola</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center mr-2 dark:bg-green-900/30">
                            <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-green-700 dark:text-green-300">Un numero</span>
                    </div>
                    <div class="flex items-center text-sm sm:col-span-2">
                        <span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center mr-2 dark:bg-green-900/30">
                            <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-green-700 dark:text-green-300">Un carattere speciale (!@#$%^&*)</span>
                    </div>
                </div>
            </div>

            <div>
                <?= Html::submitButton('Reset Password', [
                    'class' => 'w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200',
                    'name' => 'reset-button',
                    'style' => 'background-color: #2563eb !important; color: white !important;'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
            <?php endif; ?>

            <?php if (!$isAdminOrManager): ?>
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                            Hai l'app mobile?
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 dark:bg-yellow-900/20 dark:border-yellow-800">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Token per app mobile
                                </h3>
                                <div class="mt-2">
                                    <div class="flex items-center space-x-2">
                                        <input type="text" 
                                               value="<?= Html::encode($token) ?>" 
                                               id="token-input"
                                               readonly
                                               class="flex-1 font-mono bg-yellow-100 p-2 rounded text-xs border-0 focus:ring-0 focus:outline-none dark:bg-yellow-900/30 dark:text-yellow-200">
                                        <button type="button" 
                                                onclick="copyToken()"
                                                class="px-3 py-2 bg-yellow-200 hover:bg-yellow-300 text-yellow-800 text-xs font-medium rounded transition-colors dark:bg-yellow-900/50 dark:hover:bg-yellow-900/70 dark:text-yellow-200">
                                            Copia
                                        </button>
                                    </div>
                                    <p class="mt-2 text-xs text-yellow-700 dark:text-yellow-300">
                                        Copia questo token e inseriscilo nell'app mobile per completare il reset password
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <a href="<?= Yii::$app->urlManager->createUrl(['site/login']) ?>" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                        Torna al login
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles for Validation Errors -->
<style>
.field-resetpasswordform-password.has-error .form-control,
.field-resetpasswordform-password_repeat.has-error .form-control {
    border-color: #fca5a5 !important;
}

.dark .field-resetpasswordform-password.has-error .form-control,
.dark .field-resetpasswordform-password_repeat.has-error .form-control {
    border-color: #b91c1c !important;
}

.field-resetpasswordform-password .help-block,
.field-resetpasswordform-password_repeat .help-block {
    color: #ef4444 !important;
    font-size: 0.875rem !important;
    margin-top: 0.25rem !important;
}
</style>



<script>
function copyToken() {
    const tokenInput = document.getElementById('token-input');
    tokenInput.select();
    tokenInput.setSelectionRange(0, 99999); // Per dispositivi mobili
    
    try {
        document.execCommand('copy');
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copiato!';
        button.classList.add('bg-green-200', 'text-green-800');
        button.classList.remove('bg-yellow-200', 'text-yellow-800');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-200', 'text-green-800');
            button.classList.add('bg-yellow-200', 'text-yellow-800');
        }, 2000);
    } catch (err) {
        console.error('Errore durante la copia:', err);
    }
}
</script>
