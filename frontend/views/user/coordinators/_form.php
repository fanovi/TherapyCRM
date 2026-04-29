<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var bool $isUpdate Indica se è in modalità modifica */

$isUpdate = $isUpdate ?? false;
$pageTitle = $isUpdate ? 'Modifica Coordinatore' : 'Nuovo Coordinatore';
?>

<div class="mx-auto max-w-4xl p-4 md:p-6 min-h-screen">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($pageTitle) ?>'}">
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/coordinators']) ?>">
                            Coordinatori
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
        'id' => 'coordinator-form',
        'method' => 'post',
        'options' => [
            'class' => 'space-y-6',
            'enctype' => 'multipart/form-data'
        ],
        'fieldConfig' => [
            'options' => ['class' => 'mb-4'],
            'errorOptions' => [
                'class' => 'text-red-600 text-sm mt-1 font-medium help-block-error'
            ],
            'inputOptions' => ['class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2'],
        ],
        'enableClientValidation' => true,
        'enableAjaxValidation' => false,
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnBlur' => true,
    ]); ?>

    <!-- Dati Personali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci i dati anagrafici del coordinatore.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($profile, 'first_name')->textInput([
                        'placeholder' => 'Nome'
                    ])->label('Nome <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($profile, 'last_name')->textInput([
                        'placeholder' => 'Cognome'
                    ])->label('Cognome <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($user, 'email')->textInput([
                        'placeholder' => 'email@example.com',
                        'type' => 'email'
                    ])->label('Email <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($profile, 'phone')->textInput([
                        'placeholder' => '+39 123 456 7890'
                    ])->label('Telefono') ?>
                </div>

                <div>
                    <?= $form->field($profile, 'fiscal_code')->textInput([
                        'placeholder' => 'RSSMRA80A01H501X',
                        'id' => 'fiscal-code-input'
                    ])->label('Codice Fiscale') ?>
                </div>

                <div class="sm:col-span-2">
                    <?= $form->field($profile, 'address')->textInput([
                        'placeholder' => 'Via Roma 123, 00100 Roma (RM)'
                    ])->label('Indirizzo') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Credenziali Accesso - Solo per CREATE -->
    <?php if (!$isUpdate): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Credenziali di Accesso
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Imposta le credenziali per l'accesso al sistema.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($user, 'password')->passwordInput([
                        'placeholder' => 'Inserisci password',
                        'minlength' => 6
                    ])->label('Password <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($user, 'password_repeat')->passwordInput([
                        'placeholder' => 'Ripeti password',
                        'minlength' => 6
                    ])->label('Conferma Password <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Impostazioni Account - Solo per UPDATE -->
    <?php if ($isUpdate): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Impostazioni Account
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configura le impostazioni dell'account coordinatore.
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

    <?php if (Yii::$app->user->can('manage_permissions')): ?>
        <?= $this->render('/permission/_permissions_grid', [
            'allPermissions' => $allPermissions ?? [],
            'rolePermissions' => $rolePermissions ?? [],
            'userDirectPermissions' => $userDirectPermissions ?? [],
            'categories' => $categories ?? [],
        ]) ?>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="mt-8 flex items-center justify-between gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            * Tutti i campi sono obbligatori
        </div>

        <div class="flex items-center gap-3">
            <?php if ($isUpdate): ?>
                <?= Html::a('Annulla', ['view-coordinator', 'id' => $user->id], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>

                <?= Html::submitButton('Salva Modifiche', [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            <?php else: ?>
                <?= Html::a('Annulla', ['coordinators'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>

                <?= Html::submitButton('Crea Coordinatore', [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

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

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Converte automaticamente il codice fiscale in maiuscolo al rilascio del tasto
    const fiscalCodeInput = document.getElementById('fiscal-code-input');
    
    if (fiscalCodeInput) {
        fiscalCodeInput.addEventListener('keyup', function() {
            const currentValue = this.value;
            this.value = currentValue.toUpperCase();
        });
    }
});
</script> 