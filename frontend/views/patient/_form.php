<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use common\models\District;

/** @var yii\web\View $this */
/** @var common\models\Patient $patient */
/** @var yii\widgets\ActiveForm $form */
/** @var bool $isUpdate */
/** @var array $districts */

$isUpdate = $isUpdate ?? false;
$districts = $districts ?? ArrayHelper::map(District::find()->all(), 'id', 'name');
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= $isUpdate ? 'Modifica Paziente' : 'Nuovo Paziente' ?>'}">
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/index']) ?>">
                            Pazienti
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
        'id' => 'patient-form',
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

    <!-- Dati Personali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci le informazioni personali del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <?= $form->field($patient, 'first_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci nome'
                    ])->label('Nome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
                
                <div class="sm:col-span-1">
                    <?= $form->field($patient, 'last_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci cognome'
                    ])->label('Cognome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
                
                <div class="sm:col-span-1">
                    <?= $form->field($patient, 'birth_date')->input('date', [
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                    ])->label('Data di Nascita', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
                
                <div class="sm:col-span-1">
                    <?= $form->field($patient, 'fiscal_code')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'RSSMRO80A01H501X'
                    ])->label('Codice Fiscale', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
                
                <div class="sm:col-span-1">
                    <?= $form->field($patient, 'district_id')->dropDownList($districts, [
                        'prompt' => 'Seleziona distretto...',
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                    ])->label('Distretto', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
                
                <div class="sm:col-span-2">
                    <?= $form->field($patient, 'notes')->textArea([
                        'rows' => 4,
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Note aggiuntive sul paziente (opzionale)'
                    ])->label('Note', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <div>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Annulla', 
                ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>
        
        <div class="flex space-x-3">
            <?= Html::submitButton('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' . ($isUpdate ? 'Aggiorna Paziente' : 'Crea Paziente'), [
                'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                'id' => 'submit-button'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div> 