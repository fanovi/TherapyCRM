<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $patient common\models\Patient */
/* @var $user common\models\User */
/* @var $profile common\models\UserProfile */
/* @var $accountPatient common\models\AccountPatient */
/* @var $relationshipLabels array */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Crea Credenziali per ' . $patient->first_name . ' ' . $patient->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $patient->fullName, 'url' => ['view', 'id' => $patient->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>

            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/index']) ?>">
                            Pazienti
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/view', 'id' => $patient->id]) ?>">
                            <?= Html::encode($patient->fullName) ?>
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
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
        'id' => 'credentials-form',
        'enableClientValidation' => true,
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnBlur' => true,
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'errorOptions' => ['class' => 'text-red-500 text-sm mt-1 help-block-error'],
        ],
    ]); ?>

    <style>
        .has-error .form-control {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px #ef4444 !important;
        }

        .help-block-error {
            color: #ef4444 !important;
        }
    </style>

    <!-- Informazioni Paziente -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Paziente
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dati del paziente per cui creare le credenziali di accesso.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Nome</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->first_name) ?></div>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Cognome</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->last_name) ?></div>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Codice Fiscale</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->fiscal_code ?: '-') ?></div>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Data di Nascita</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode(Yii::$app->formatter->asDate($patient->birth_date)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credenziali di Accesso -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Credenziali di Accesso
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci email e password per l'accesso all'app mobile.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <?= $form->field($user, 'email')->input('email', [
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'inserisci.email@esempio.com'
                    ])->label('Email di Accesso', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($user, 'password')->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Password (minimo 6 caratteri)'
                    ])->label('Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($user, 'password_repeat')->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Ripeti la password'
                    ])->label('Conferma Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Informazioni Profilo -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Profilo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dati della persona che utilizzerà l'account.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'first_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci nome'
                    ])->label('Nome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'last_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci cognome'
                    ])->label('Cognome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'fiscal_code')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'RSSMRA80E45F205T'
                    ])->label('Codice Fiscale', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'phone')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => '+39 123 456 7890'
                    ])->label('Telefono', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Relazione con il Paziente -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Relazione con il Paziente
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Specifica il tipo di relazione e i permessi.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <?= $form->field($accountPatient, 'relationship_type')->dropDownList($relationshipLabels, [
                        'prompt' => 'Seleziona tipo di relazione...',
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                    ])->label('Tipo di Relazione', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-2">
                    <?= $form->field($accountPatient, 'has_parental_authority', [
                        'template' => '<div class="flex items-center">{input}{label}</div>{error}{hint}',
                        'labelOptions' => [
                            'class' => 'ml-2 text-sm font-medium text-gray-700 dark:text-gray-300'
                        ],
                        'options' => ['class' => '']
                    ])->checkbox([
                        'class' => 'w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600',
                        'uncheck' => '0',
                        'value' => '1'
                    ])->label('Ha Autorità Genitoriale') ?>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Seleziona se questa persona ha l'autorità genitoriale sul paziente.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <div>
            <?= Html::a(
                '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Annulla',
                ['view', 'id' => $patient->id],
                [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]
            ) ?>
        </div>

        <div class="flex space-x-3">
            <?= Html::submitButton('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Crea Credenziali', [
                'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                'id' => 'submit-button',
                'style' => 'background-color: #2563eb !important; color: white !important;'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$this->registerJs("
// Funzione per mostrare notifiche toast (stesso stile di Statistics)
function showNotification(message, type = 'info') {
    // Notifica vanilla con lo stesso stile di Statistics
    var notification = document.createElement('div');
    notification.className = 'vanilla-notification notification-' + type;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-size: 14px;
        z-index: 99999;
        opacity: 0;
        transition: opacity 0.3s;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    // Colori per tipo (stesso stile di Statistics)
    var colors = {
        'success': '#1cc88a',
        'error': '#e74a3b',
        'warning': '#f6c23e',
        'info': '#36b9cc'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    // Mostra con animazione
    setTimeout(function() {
        notification.style.opacity = '1';
    }, 10);
    
    // Rimuovi dopo 3 secondi
    setTimeout(function() {
        notification.style.opacity = '0';
        setTimeout(function() {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

$(document).ready(function() {
    $('#credentials-form').on('beforeSubmit', function(e) {
        e.preventDefault();
        
        var \$form = $(this);
        var \$submitBtn = $('#submit-button');
        
        // Disable submit button and show loading
        \$submitBtn.prop('disabled', true);
        \$submitBtn.html('<svg class=\"animate-spin -ml-1 mr-2 h-4 w-4 text-white\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>Creazione in corso...');
        
        $.ajax({
            url: \$form.attr('action'),
            type: 'POST',
            data: \$form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message with toast
                    showNotification('Credenziali create con successo!', 'success');
                    
                    // Download PDF automatically
                    if (response.downloadUrl) {
                        console.log('Opening PDF download:', response.downloadUrl);
                        window.open(response.downloadUrl, '_blank');
                    }
                    
                    // Redirect after a short delay
                    setTimeout(function() {
                        if (response.redirectUrl) {
                            window.location.href = response.redirectUrl;
                        }
                    }, 1000);
                } else {
                    showNotification('Errore: ' + (response.error || response.message || 'Errore sconosciuto'), 'error');
                    \$submitBtn.prop('disabled', false);
                    \$submitBtn.html('<svg class=\"mr-2 h-4 w-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg>Crea Credenziali');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Errore di comunicazione con il server. Riprova.', 'error');
                \$submitBtn.prop('disabled', false);
                \$submitBtn.html('<svg class=\"mr-2 h-4 w-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg>Crea Credenziali');
            }
        });
        
        return false;
    });
});
");
?>