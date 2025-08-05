<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Recupera Password';
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
                Recupera Password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Inserisci la tua email per ricevere il link di reset
            </p>
        </div>

        <!-- Request Password Reset Form -->
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

            <?php $form = ActiveForm::begin([
                'id' => 'request-password-reset-form',
                'options' => ['class' => 'space-y-6'],
            ]); ?>

            <!-- Email Field -->
            <div>
                <?= $form->field($model, 'email', [
                    'template' => '<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{label}</label>
                                 <div class="relative">
                                     <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                         <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                             <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 7.06206V14.375C3.04175 14.6511 3.26561 14.875 3.54175 14.875H16.4584C16.7346 14.875 16.9584 14.6511 16.9584 14.375V7.06245L11.1443 11.1168C10.457 11.5961 9.54373 11.5961 8.85638 11.1168L3.04175 7.06206ZM16.9584 5.19262C16.9584 5.19341 16.9584 5.1942 16.9584 5.19498V5.20026C16.9572 5.22216 16.946 5.24239 16.9279 5.25501L10.2864 9.88638C10.1145 10.0062 9.8862 10.0062 9.71437 9.88638L3.07255 5.25485C3.05342 5.24151 3.04202 5.21967 3.04202 5.19636C3.042 5.15695 3.07394 5.125 3.11335 5.125H16.8871C16.9253 5.125 16.9564 5.15494 16.9584 5.19262ZM18.4584 5.21428V14.375C18.4584 15.4796 17.563 16.375 16.4584 16.375H3.54175C2.43718 16.375 1.54175 15.4796 1.54175 14.375V5.19498C1.54175 5.1852 1.54194 5.17546 1.54231 5.16577C1.55858 4.31209 2.25571 3.625 3.11335 3.625H16.8871C17.7549 3.625 18.4584 4.32843 18.4585 5.19622C18.4585 5.20225 18.4585 5.20826 18.4584 5.21428Z" fill="#667085"/>
                                         </svg>
                                     </span>
                                     {input}
                                 </div>
                                 {error}',
                    'labelOptions' => [],
                ])->textInput([
                    'placeholder' => 'Inserisci la tua email',
                    'autofocus' => true,
                    'class' => 'dark:bg-dark-900 shadow-theme-xs focus:border-blue-300 focus:ring-blue-500/10 dark:focus:border-blue-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                ]) ?>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg dark:bg-blue-900/20 dark:border-blue-800">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Come funziona il recupero password?
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <p>1. Inserisci la tua email</p>
                            <p>2. Riceverai un link di reset via email</p>
                            <p>3. Clicca sul link per impostare una nuova password</p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <?= Html::submitButton('Invia Link di Reset', [
                    'class' => 'w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200',
                    'name' => 'request-button',
                    'style' => 'background-color: #2563eb !important; color: white !important;'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
            </div>
        </div>

        <!-- Footer Links -->
        <div class="text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <a href="<?= Yii::$app->urlManager->createUrl(['site/login']) ?>" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                    Torna al login
                </a>
            </p>
        </div>
    </div>
</div>

<!-- Custom Styles for Validation Errors -->
<style>
.field-passwordresetrequestform-email.has-error .form-control {
    border-color: #fca5a5 !important;
}

.dark .field-passwordresetrequestform-email.has-error .form-control {
    border-color: #b91c1c !important;
}

.field-passwordresetrequestform-email .help-block {
    color: #ef4444 !important;
    font-size: 0.875rem !important;
    margin-top: 0.25rem !important;
}
</style>
