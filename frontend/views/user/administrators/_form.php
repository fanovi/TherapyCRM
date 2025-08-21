<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\User;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var yii\widgets\ActiveForm $form */
/** @var bool $isUpdate */

$isUpdate = $isUpdate ?? false;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= $isUpdate ? 'Modifica Amministratore' : 'Nuovo Amministratore' ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/administrators']) ?>">
                            Amministratori
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName"></li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <?php $form = ActiveForm::begin([
        'id' => 'administrator-form',
        'enableClientValidation' => true,
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnBlur' => true,
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'options' => ['class' => 'mb-4'],
            'errorOptions' => [
                'class' => 'text-red-600 text-sm mt-1 font-medium help-block-error'
            ],
            'inputOptions' => ['class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2'],
        ],
    ]); ?>

    <style>
    /* Stili per i campi con errori */
    .has-error input,
    .has-error select,
    .has-error textarea {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 1px #dc2626 !important;
    }
    
    .has-error input:focus,
    .has-error select:focus,
    .has-error textarea:focus {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1) !important;
    }
    
    /* Assicuriamoci che i messaggi di errore siano rossi */
    .help-block-error {
        color: #dc2626 !important;
        font-weight: 500 !important;
        margin-top: 0.25rem !important;
        font-size: 0.875rem !important;
    }
    
    /* Stili per i messaggi di errore di Yii2 */
    .error-summary ul {
        color: #dc2626;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .error-summary li {
        color: #dc2626 !important;
        font-weight: 500;
    }
    </style>

    <?php if (!$isUpdate): ?>
    <!-- Credentials Section (only for CREATE) -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Credenziali di Accesso
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configura le credenziali di accesso per il nuovo amministratore.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($user, 'email')->textInput([
                        'placeholder' => 'Inserisci email amministratore'
                    ])->label('Email') ?>
                </div>
                
                <div>
                    <?= $form->field($user, 'username')->textInput([
                        'placeholder' => 'Inserisci username'
                    ])->label('Username') ?>
                </div>
                
                <div>
                    <?= $form->field($user, 'password')->passwordInput([
                        'placeholder' => 'Inserisci password'
                    ])->label('Password') ?>
                </div>
                
                <div>
                    <?= $form->field($user, 'password_repeat')->passwordInput([
                        'placeholder' => 'Ripeti password'
                    ])->label('Conferma Password') ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Personal Data Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci le informazioni personali dell'amministratore.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($profile, 'first_name')->textInput([
                        'placeholder' => 'Inserisci nome'
                    ])->label('Nome') ?>
                </div>
                
                <div>
                    <?= $form->field($profile, 'last_name')->textInput([
                        'placeholder' => 'Inserisci cognome'
                    ])->label('Cognome') ?>
                </div>
                
                <div>
                    <?= $form->field($profile, 'phone')->textInput([
                        'placeholder' => 'Inserisci telefono'
                    ])->label('Telefono') ?>
                </div>
                
                <div>
                    <?= $form->field($profile, 'fiscal_code')->textInput([
                        'placeholder' => 'Inserisci codice fiscale'
                    ])->label('Codice Fiscale') ?>
                </div>
                
                <div class="sm:col-span-2">
                    <?= $form->field($profile, 'address')->textArea([
                        'rows' => 3,
                        'placeholder' => 'Inserisci indirizzo completo'
                    ])->label('Indirizzo') ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isUpdate): ?>
    <!-- Account Settings Section (only for UPDATE) -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Impostazioni Account
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configura le impostazioni dell'account amministratore.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="flex items-center">
                <div class="mt-6">
                    <label class="flex items-center">
                        <input type="hidden" name="User[status]" value="inactive">
                        <input type="checkbox" 
                               name="User[status]" 
                               value="active" 
                               <?= $user->status === 'active' ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-300 focus:ring focus:ring-brand-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Account Attivo</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <div>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Annulla', 
                ['administrators'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>
        
        <div class="flex space-x-3">
            <?= Html::submitButton('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' . ($isUpdate ? 'Aggiorna Amministratore' : 'Crea Amministratore'), [
                'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                'id' => 'submit-button'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div> 