<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $therapyModel common\models\PlanTherapy */
/* @var $form yii\widgets\ActiveForm */
/* @var $patients array */
/* @var $regimes array */
/* @var $districts array */
/* @var $treatmentTypes array */
/* @var $settings array */
/* @var $postedTherapies array */

?>

<div class="mx-auto max-w-4xl p-4 md:p-6">

    <?php $form = ActiveForm::begin([
        'id' => 'therapeutic-plan-form',
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

    <!-- Dati Piano -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Piano Terapeutico
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci le informazioni di base del piano terapeutico.
            </p>
        </div>

        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Paziente -->
                <div class="form-group relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Paziente <span class="text-red-500">*</span>
                    </label>

                    <!-- Search input -->
                    <input type="text"
                        id="patient-search"
                        class="block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2"
                        placeholder="Cerca paziente per nome, cognome o codice fiscale..."
                        autocomplete="off">

                    <!-- Hidden field for actual patient ID -->
                    <?= \yii\helpers\Html::hiddenInput('TherapeuticPlan[patient_id]', $model->patient_id, ['id' => 'patient-id-hidden']) ?>

                    <!-- Selected patient display -->
                    <div id="selected-patient" class="mt-2 hidden">
                        <div class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <div>
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-200" id="selected-patient-name"></span>
                                <span class="text-xs text-blue-600 dark:text-blue-400 ml-2" id="selected-patient-cf"></span>
                            </div>
                            <button type="button" id="clear-patient" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Search results -->
                    <div id="patient-search-results" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                        <div id="search-results-list"></div>
                    </div>

                    <!-- Error display -->
                    <div id="patient-error" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden"></div>
                </div>

                <!-- Regime -->
                <div class="form-group">
                    <?= $form->field($model, 'regime_id')->dropDownList(
                        $regimes,
                        [
                            'prompt' => 'Seleziona un regime...',
                            'id' => 'regime-select',
                            'onchange' => 'getSettingsList(this)',
                            'data-url' => Url::to(['therapeutic-plan/get-settings-list']),
                        ]
                    )->label('Regime <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    <?php if ($model->hasErrors('regime_id')): ?>
                        <div class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-700 dark:text-red-300"><?= Html::encode($model->getFirstError('regime_id')) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                <!-- Data Approvazione -->
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Data Approvazione
                    </label>

                    <div class="relative">
                        <?= $form->field($model, 'approval_date')->input('date', [
                            'placeholder' => 'Seleziona data',
                            'id' => 'therapeuticplan-approval-date',
                            'class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2 pr-10'
                        ])->label(false) ?>
                        <button type="button" onclick="document.getElementById('therapeuticplan-approval-date').showPicker()" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill="" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Numero Protocollo -->
                <div class="form-group">
                    <?= $form->field($model, 'protocol_number')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Inserisci il numero di protocollo',
                    ])->label('Numero Protocollo') ?>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Il numero di protocollo deve essere univoco per ogni piano terapeutico.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                <!-- Distretto -->
                <div class="form-group">
                    <?= $form->field($model, 'district_id')->dropDownList(
                        $districts,
                        [
                            'prompt' => 'Seleziona un distretto...',
                            'id' => 'district-select',
                        ]
                    )->label('Distretto') ?>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Seleziona il distretto di riferimento per questo piano terapeutico.
                    </p>
                </div>

                <!-- Data Inizio -->
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Data Inizio <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <?= $form->field($model, 'start_date')->input('date', [
                            'placeholder' => 'Seleziona data',
                            'id' => 'therapeuticplan-start-date',
                            'class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2 pr-10'
                        ])->label(false) ?>
                        <button type="button" onclick="document.getElementById('therapeuticplan-start-date').showPicker()" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill="" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Durata in Giorni -->
                <div class="form-group">
                    <?= $form->field($model, 'duration_days')->textInput([
                        'type' => 'number',
                        'min' => 1,
                        'max' => 9999,
                        'placeholder' => 'Inserisci la durata in giorni',
                    ])->label('Durata (giorni) <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        La data di fine sarà calcolata automaticamente in base alla durata inserita.
                    </p>
                </div>

                <?php if (!$model->isNewRecord): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                        <div class="form-group">
                            <?= $form->field($model, 'status')->dropDownList([
                                'draft' => 'Bozza',
                                'pending' => 'In Attesa',
                                'active' => 'Attivo',
                                'suspended' => 'Sospeso',
                                'completed' => 'Completato',
                                'terminated' => 'Interrotto',
                                'expired' => 'Scaduto'
                            ], [
                                'prompt' => 'Seleziona stato...',
                                'id' => 'status-select',
                            ])->label('Stato <span class="text-red-500">*</span>', ['encode' => false]) ?>
                        </div>

                        <!-- Data Sospensione (visibile solo se status = suspended) -->
                        <div class="form-group" id="suspension-date-container" style="<?= $model->status === 'suspended' ? '' : 'display:none;' ?>">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Data Sospensione
                            </label>
                            <div class="relative">
                                <?= $form->field($model, 'suspension_date')->input('date', [
                                    'placeholder' => 'Seleziona data',
                                    'id' => 'therapeuticplan-suspension-date',
                                    'class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2 pr-10'
                                ])->label(false) ?>
                                <button type="button" onclick="document.getElementById('therapeuticplan-suspension-date').showPicker()" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer">
                                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill="" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo Sospensione (visibile solo se status = suspended) -->
                    <div class="form-group mt-6" id="suspension-reason-container" style="<?= $model->status === 'suspended' ? '' : 'display:none;' ?>">
                        <?= $form->field($model, 'suspension_reason')->textarea([
                            'rows' => 3,
                            'placeholder' => 'Inserisci il motivo della sospensione...',
                        ])->label('Motivo Sospensione') ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Note -->
            <div class="form-group mt-6">
                <?= $form->field($model, 'notes')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Inserisci eventuali note aggiuntive per il piano terapeutico...',
                ])->label('Note') ?>
            </div>
        </div>
    </div>

    <!-- Avviso ABA -->
    <div id="aba-notice" class="rounded-lg border border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-600 p-4 hidden" style="background-color: #fefce8 !important; border-color: #fde047 !important;">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #facc15 !important;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200" style="color: #92400e !important;">Regime ABA selezionato</h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300" style="color: #b45309 !important;">
                    <ul class="list-disc list-inside space-y-1" id="aba-requirements-list">
                        <li>È obbligatorio inserire ore di <strong>SUPERVISIONE</strong> e <strong>PARENT TRAINING</strong></li>
                        <li>Le ore inserite saranno considerate come ore <strong>mensili</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Terapie -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Terapie
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Aggiungi le terapie previste per questo piano terapeutico.
                    </p>
                </div>
                <button type="button" id="add-therapy" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Aggiungi Terapia
                </button>
            </div>
        </div>

        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div id="therapies-container" class="space-y-6">
                <!-- I form delle terapie verranno aggiunti qui dinamicamente -->
            </div>

            <!-- Template per nuova terapia -->
            <template id="therapy-template">
                <div class="therapy-item border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Terapia #<span class="therapy-number">1</span></h4>
                        <button type="button" class="remove-therapy text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tipo Trattamento -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo Trattamento <span class="text-red-500">*</span>
                            </label>
                            <select name="PlanTherapy[{index}][treatment_type_id]" class="treatment-type block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2" required>
                                <option value="">Seleziona tipo...</option>
                                <?php foreach ($treatmentTypes as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Ore Settimanali/Mensili -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="hours-label">Ore Settimanali</span> <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="PlanTherapy[{index}][weekly_hours]" class="weekly-hours block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2" min="0.5" max="999.99" step="0.5" required>
                        </div>

                        <!-- Setting -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Setting <span class="text-red-500">*</span>
                            </label>
                            <select name="PlanTherapy[{index}][setting_id]" class="setting block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2" required>
                                <option value="">Seleziona setting...</option>
                                <?php foreach ($settings as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Terapia di Gruppo -->
                        <div style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo Terapia
                            </label>
                            <div class="flex items-center">
                                <input type="checkbox" name="PlanTherapy[{index}][is_group]" class="is-group h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Terapia di gruppo</span>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Note
                        </label>
                        <textarea name="PlanTherapy[{index}][notes]" class="notes block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2" rows="2"></textarea>
                    </div>
                </div>
            </template>

            <?php
            // Aggiungi script per inizializzare le terapie esistenti
            $this->registerJs("
                // Store per i tipi di trattamento selezionati
                let selectedTreatmentTypes = new Set();
                
                // Funzione per aggiornare le opzioni disponibili nei select dei tipi di trattamento
                function updateAvailableTreatmentTypes() {
                    const allSelects = document.querySelectorAll('.treatment-type');
                    
                    allSelects.forEach(select => {
                        const currentValue = select.value;
                        const options = select.querySelectorAll('option');
                        
                        options.forEach(option => {
                            if (option.value && option.value !== currentValue) {
                                if (selectedTreatmentTypes.has(option.value)) {
                                    option.style.display = 'none';
                                    option.disabled = true;
                                } else {
                                    option.style.display = '';
                                    option.disabled = false;
                                }
                            }
                        });
                    });
                }

                // Funzione per ricostruire il set dei tipi di trattamento selezionati
                function rebuildSelectedTreatmentTypes() {
                    selectedTreatmentTypes.clear();
                    document.querySelectorAll('.treatment-type').forEach(select => {
                        if (select.value) {
                            selectedTreatmentTypes.add(select.value);
                        }
                    });
                    updateAvailableTreatmentTypes();
                }

                // Funzione per aggiornare le label delle ore
                function updateHoursLabels() {
                    const regimeSelect = document.getElementById('regime-select');
                    if (!regimeSelect || !regimeSelect.value) return;
                    
                    const selectedOption = regimeSelect.options[regimeSelect.selectedIndex];
                    const regimeText = selectedOption ? selectedOption.text : '';
                    const isABA = regimeText.toUpperCase().includes('ABA');
                    
                    document.querySelectorAll('.hours-label').forEach(label => {
                        label.textContent = isABA ? 'Ore Mensili' : 'Ore Settimanali';
                    });
                    
                    const abaNotice = document.getElementById('aba-notice');
                    if (isABA) {
                        abaNotice.classList.remove('hidden');
                        updateABARequirements();
                    } else {
                        abaNotice.classList.add('hidden');
                    }
                }

                // Funzione per aggiornare i requisiti ABA in base all'età del paziente
                window.updateABARequirements = function() {
                    const patientId = \$('#patient-id-hidden').val();
                    if (!patientId) {
                        // Se non c'è paziente selezionato, mostra i requisiti generici
                        updateABAMessage(null);
                        return;
                    }

                    // Chiamata AJAX per ottenere l'età del paziente
                    \$.ajax({
                        url: '" . \yii\helpers\Url::to(['/patient/get-age']) . "',
                        type: 'GET',
                        data: { id: patientId },
                        success: function(response) {
                            if (response.success) {
                                updateABAMessage(response.age);
                            } else {
                                updateABAMessage(null);
                            }
                        },
                        error: function() {
                            updateABAMessage(null);
                        }
                    });
                };

                // Funzione per aggiornare il messaggio ABA
                window.updateABAMessage = function(age) {
                    const requirementsList = document.getElementById('aba-requirements-list');
                    let html = '';
                    
                    if (age !== null && age < 14) {
                        // Paziente sotto i 14 anni - tutti i controlli obbligatori
                        html = '<li>Paziente selezionato: <strong>' + age + ' anni</strong></li>' +
                               '<li>Per pazienti <strong>minori di 14 anni</strong> è <strong>obbligatorio</strong> inserire ore di <strong>SUPERVISIONE</strong> e <strong>PARENT TRAINING</strong></li>' +
                               '<li>È obbligatorio inserire almeno una <strong>terapia principale</strong></li>' +
                               '<li>Le ore inserite saranno considerate come ore <strong>mensili</strong></li>';
                    } else if (age !== null && age >= 14) {
                        // Paziente dai 14 anni in su - solo terapia obbligatoria
                        html = '<li>Paziente selezionato: <strong>' + age + ' anni</strong></li>' +
                               '<li>Per pazienti di <strong>14 anni o più</strong>, <strong>SUPERVISIONE</strong> e <strong>PARENT TRAINING</strong> sono <em>facoltativi</em></li>' +
                               '<li>È obbligatorio inserire almeno una <strong>terapia principale</strong></li>' +
                               '<li>Le ore inserite saranno considerate come ore <strong>mensili</strong></li>';
                    } else {
                        // Età non disponibile - messaggio generico
                        html = '<li>Seleziona un paziente per vedere i requisiti specifici</li>' +
                               '<li>Le ore inserite saranno considerate come ore <strong>mensili</strong></li>';
                    }
                    
                    requirementsList.innerHTML = html;
                };

                // Funzione per aggiungere una terapia con dati
                function addTherapyWithData(data) {
                    const container = document.getElementById('therapies-container');
                    const template = document.getElementById('therapy-template');
                    const clone = template.content.cloneNode(true);
                    const index = container.children.length;
                    
                    clone.querySelector('.therapy-number').textContent = index + 1;
                    
                    clone.querySelectorAll('[name*=\"{index}\"]').forEach(el => {
                        const newName = el.name.replace('{index}', index);
                        el.name = newName;
                    });
                    
                    if (data) {
                        const treatmentTypeSelect = clone.querySelector('.treatment-type');
                        const weeklyHoursInput = clone.querySelector('.weekly-hours');
                        const isGroupCheckbox = clone.querySelector('.is-group');
                        const notesTextarea = clone.querySelector('.notes');
                        
                        if (treatmentTypeSelect && data.treatment_type_id) {
                            treatmentTypeSelect.value = data.treatment_type_id;
                        }
                        
                        if (weeklyHoursInput && data.weekly_hours) {
                            weeklyHoursInput.value = data.weekly_hours;
                        }
                        
                        // Il setting viene gestito nel blocco sottostante con i settings dinamici
                        
                        if (isGroupCheckbox && data.is_group !== undefined) {
                            isGroupCheckbox.checked = data.is_group == 1 || data.is_group === true || data.is_group === 'true';
                        }
                        
                        if (notesTextarea && data.notes) {
                            notesTextarea.value = data.notes;
                        }
                    }
                    
                    const treatmentTypeSelect = clone.querySelector('.treatment-type');
                    if (treatmentTypeSelect) {
                        treatmentTypeSelect.addEventListener('change', function() {
                            rebuildSelectedTreatmentTypes();
                        });
                    }
                    
                    const removeButton = clone.querySelector('.remove-therapy');
                    if (removeButton) {
                        removeButton.addEventListener('click', function() {
                            this.closest('.therapy-item').remove();
                            document.querySelectorAll('.therapy-number').forEach((el, i) => {
                                el.textContent = i + 1;
                            });
                            updateTherapyIndexes();
                            rebuildSelectedTreatmentTypes();
                        });
                    }
                    
                    // Popola la select dei settings con i settings del regime corrente
                    const settingSelectNew = clone.querySelector('.setting');
                    if (settingSelectNew && Object.keys(currentRegimeSettings).length > 0) {
                        settingSelectNew.innerHTML = '<option value=\"\">Seleziona setting...</option>';
                        for (const [id, nome] of Object.entries(currentRegimeSettings)) {
                            const option = document.createElement('option');
                            option.value = id;
                            option.textContent = nome;
                            // Se abbiamo dati e un setting_id, seleziona quello
                            if (data && data.setting_id && id == data.setting_id) {
                                option.selected = true;
                            }
                            settingSelectNew.appendChild(option);
                        }
                    }
                    
                    container.appendChild(clone);
                    updateHoursLabels();
                    rebuildSelectedTreatmentTypes();
                }

                // Funzione per aggiornare gli indici delle terapie
                function updateTherapyIndexes() {
                    const container = document.getElementById('therapies-container');
                    const items = container.querySelectorAll('.therapy-item');
                    
                    items.forEach((item, index) => {
                        item.querySelectorAll('[name*=\"PlanTherapy[\"]').forEach(el => {
                            const newName = el.name.replace(/PlanTherapy\[\d+\]/, `PlanTherapy[\${index}]`);
                            el.name = newName;
                        });
                    });
                }

                // Gestione cambio regime
                document.getElementById('regime-select').addEventListener('change', function() {
                    updateHoursLabels();
                });

                // Inizializza il regime corrente se già selezionato
                updateHoursLabels();

                // Inizializza le terapie esistenti
                const postedTherapies = " . json_encode($postedTherapies ?? []) . ";
                
                if (postedTherapies && postedTherapies.length > 0) {
                    postedTherapies.forEach(therapy => addTherapyWithData(therapy));
                }

                // Evento per aggiungere nuova terapia
                document.getElementById('add-therapy').addEventListener('click', function() {
                    addTherapyWithData();
                    
                    // Scroll verso la nuova terapia aggiunta
                    setTimeout(function() {
                        const container = document.getElementById('therapies-container');
                        const therapies = container.querySelectorAll('.therapy-item');
                        const lastTherapy = therapies[therapies.length - 1];
                        
                        if (lastTherapy) {
                            lastTherapy.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    }, 100);
                });

                // Aggiungi almeno una terapia se non ce ne sono
                if (document.getElementById('therapies-container').children.length === 0) {
                    addTherapyWithData();
                }

                // Validazione base al submit del form
                document.getElementById('therapeutic-plan-form').addEventListener('submit', function(e) {
                    const container = document.getElementById('therapies-container');
                    const therapies = container.querySelectorAll('.therapy-item');
                    
                    if (therapies.length === 0) {
                        e.preventDefault();
                        alert('È necessario inserire almeno una terapia.');
                        return false;
                    }
                    
                    let isValid = true;
                    
                    therapies.forEach((therapy, index) => {
                        const treatmentType = therapy.querySelector('.treatment-type').value;
                        const weeklyHours = therapy.querySelector('.weekly-hours').value;
                        const setting = therapy.querySelector('.setting').value;
                        
                        if (!treatmentType || !weeklyHours || !setting) {
                            isValid = false;
                            alert('Tutti i campi della terapia ' + (index + 1) + ' sono obbligatori.');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Disable submit button to prevent double submission
                    const submitBtn = document.getElementById('submit-btn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<svg class=\"animate-spin -ml-1 mr-3 h-4 w-4 text-white\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>Salvataggio...';
                    }
                });
            ");
            ?>
        </div>
    </div>

    <!-- Informazioni Aggiuntive -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Informazioni Importanti</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li>La data di fine verrà calcolata automaticamente sommando la durata alla data di inizio</li>
                        <li>Tutti i campi contrassegnati con <span class="text-red-500">*</span> sono obbligatori</li>
                        <li>Il regime selezionato determinerà le opzioni disponibili per le singole terapie</li>
                        <li>Ogni terapia deve avere un tipo di trattamento diverso</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <div>
            <?= Html::a(
                '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Annulla',
                ['index'],
                [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]
            ) ?>
        </div>

        <div class="flex space-x-3">
            <?= Html::submitButton('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' . ($model->isNewRecord ? 'Crea Piano Terapeutico' : 'Salva Modifiche'), [
                'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                'id' => 'submit-btn'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
    // Variabile globale per salvare i settings correnti del regime selezionato
    let currentRegimeSettings = {};

    function getSettingsList(el) {
        let settings_url = el.dataset.url;
        let regime_id = el.value;

        if (!regime_id) {
            currentRegimeSettings = {};
            updateAllSettingsSelects();
            return;
        }

        fetch(`${settings_url}/${regime_id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(response => response.json()).then(data => {
            if (data.success) {
                // Salva i settings per uso successivo (quando si aggiungono nuove terapie)
                currentRegimeSettings = data.data;
                // Aggiorna tutte le select dei settings esistenti
                updateAllSettingsSelects();
            }
        });
    }

    function updateAllSettingsSelects() {
        document.querySelectorAll('#therapies-container .setting').forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Seleziona setting...</option>';
            
            for (const [id, nome] of Object.entries(currentRegimeSettings)) {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = nome;
                // Preserva il valore selezionato se ancora valido
                if (id === currentValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            }
        });
    }
</script>

<?php
$this->registerJs('
    // Patient search functionality
    var searchTimeout;
    var selectedPatientId = ' . ($model->patient_id ? $model->patient_id : 'null') . ";
    
    // Initialize if editing existing record
    if (selectedPatientId && \$('#patient-id-hidden').val()) {
        // Load patient data for editing
        \$.ajax({
            url: '" . \yii\helpers\Url::to(['search-patients']) . "',
            type: 'GET',
            data: { id: selectedPatientId },
            success: function(response) {
                if (response.results && response.results.length > 0) {
                    showSelectedPatient(response.results[0]);
                }
            }
        });
    }
    
    // Patient search input
    \$('#patient-search').on('input', function() {
        var query = \$(this).val();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            \$('#patient-search-results').addClass('hidden');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchPatients(query);
        }, 300);
    });
    
    // Search patients function
    function searchPatients(query) {
        \$.ajax({
            url: '" . \yii\helpers\Url::to(['search-patients']) . "',
            type: 'GET',
            data: { q: query },
            success: function(response) {
                displaySearchResults(response.results);
            },
            error: function() {
                \$('#patient-error').text('Errore durante la ricerca pazienti').removeClass('hidden');
            }
        });
    }
    
    // Display search results
    function displaySearchResults(results) {
        var html = '';
        
        if (results.length === 0) {
            html = '<div class=\"px-4 py-3 text-sm text-gray-500 dark:text-gray-400\">Nessun paziente trovato</div>';
        } else {
            \$.each(results, function(index, patient) {
                html += '<div class=\"px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0 patient-result\" data-patient-id=\"' + patient.id + '\" data-patient-name=\"' + patient.full_name + '\" data-patient-cf=\"' + patient.fiscal_code + '\">';
                html += '<div class=\"font-medium text-gray-900 dark:text-white\">' + patient.full_name + '</div>';
                html += '<div class=\"text-sm text-gray-500 dark:text-gray-400\">' + patient.fiscal_code + '</div>';
                html += '</div>';
            });
        }
        
        \$('#search-results-list').html(html);
        \$('#patient-search-results').removeClass('hidden');
    }
    
    // Handle patient selection
    \$(document).on('click', '.patient-result', function() {
        var patientId = \$(this).data('patient-id');
        var patientName = \$(this).data('patient-name');
        var patientCf = \$(this).data('patient-cf');
        
        var patient = {
            id: patientId,
            full_name: patientName,
            fiscal_code: patientCf
        };
        
        showSelectedPatient(patient);
        \$('#patient-search-results').addClass('hidden');
        \$('#patient-search').val('');
    });
    
    // Show selected patient
    function showSelectedPatient(patient) {
        \$('#patient-id-hidden').val(patient.id);
        \$('#selected-patient-name').text(patient.full_name);
        \$('#selected-patient-cf').text('(' + patient.fiscal_code + ')');
        \$('#selected-patient').removeClass('hidden');
        \$('#patient-search').addClass('hidden');
        \$('#patient-error').addClass('hidden');
        selectedPatientId = patient.id;
        
        // Aggiorna i requisiti ABA se il regime è selezionato
        const regimeSelect = document.getElementById('regime-select');
        if (regimeSelect && regimeSelect.value) {
            const selectedOption = regimeSelect.options[regimeSelect.selectedIndex];
            const regimeText = selectedOption ? selectedOption.text : '';
            const isABA = regimeText.toUpperCase().includes('ABA');
            
            if (isABA) {
                updateABARequirements();
            }
        }
    }
    
    // Clear patient selection
    \$('#clear-patient').on('click', function() {
        \$('#patient-id-hidden').val('');
        \$('#selected-patient').addClass('hidden');
        \$('#patient-search').removeClass('hidden').val('').focus();
        \$('#patient-search-results').addClass('hidden');
        selectedPatientId = null;
    });
    
    // Hide search results when clicking outside
    \$(document).on('click', function(e) {
        if (!\$(e.target).closest('#patient-search, #patient-search-results').length) {
            \$('#patient-search-results').addClass('hidden');
        }
    });
    
    // Auto-calculate end date when start date or duration changes
    function calculateEndDate() {
        var startDate = \$('#therapeuticplan-start-date').val();
        var duration = \$('#therapeuticplan-duration_days').val();
        
        if (startDate && duration && duration > 0) {
            var start = new Date(startDate);
            var end = new Date(start.getTime() + (duration * 24 * 60 * 60 * 1000));
            var endDateStr = end.toISOString().split('T')[0];
            
            // Show calculated end date info
            var infoHtml = '<div class=\"mt-2 text-sm text-green-600 dark:text-green-400\">' +
                          '<svg class=\"inline w-4 h-4 mr-1\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">' +
                          '<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\"></path>' +
                          '</svg>' +
                          'Data di fine calcolata: ' + end.toLocaleDateString('it-IT') + '</div>';
            
            \$('#therapeuticplan-duration_days').parent().find('.calculated-end-date').remove();
            \$('#therapeuticplan-duration_days').parent().append('<div class=\"calculated-end-date\">' + infoHtml + '</div>');
        } else {
            \$('#therapeuticplan-duration_days').parent().find('.calculated-end-date').remove();
        }
    }
    
    \$('#therapeuticplan-start-date, #therapeuticplan-duration_days').on('change input', calculateEndDate);
    
    // Calculate on page load if values are present
    calculateEndDate();
    
    // Form validation
    \$('#therapeutic-plan-form').on('beforeSubmit', function() {
        var isValid = true;
        var errors = [];
        
        // Check required fields
        if (!\$('#patient-id-hidden').val()) {
            errors.push('Seleziona un paziente');
            \$('#patient-error').text('Seleziona un paziente').removeClass('hidden');
            isValid = false;
        } else {
            \$('#patient-error').addClass('hidden');
        }
        
        if (!\$('#regime-select').val()) {
            errors.push('Seleziona un regime');
            isValid = false;
        }
        
        if (!\$('#therapeuticplan-start-date').val()) {
            errors.push('Inserisci la data di inizio');
            isValid = false;
        }
        
        var duration = \$('#therapeuticplan-duration_days').val();
        if (!duration || duration <= 0) {
            errors.push('Inserisci una durata valida');
            isValid = false;
        }
        
        // Check therapies
        var therapies = \$('.therapy-item');
        if (therapies.length === 0) {
            errors.push('Aggiungi almeno una terapia');
            isValid = false;
        }
        
        if (!isValid) {
            alert('Errori di validazione:\\n\\n' + errors.join('\\n'));
            return false;
        }
        
        // Disable submit button to prevent double submission
        \$('#submit-btn').prop('disabled', true).html('<svg class=\"animate-spin -ml-1 mr-3 h-4 w-4 text-white\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>Salvataggio...');
        
        return true;
    });
    // Gestione visibilità campi sospensione
    $('#status-select').on('change', function() {
        if ($(this).val() === 'suspended') {
            $('#suspension-date-container').show();
            $('#suspension-reason-container').show();
            $('#therapeuticplan-suspension-date').attr('required', true);
        } else {
            $('#suspension-date-container').hide();
            $('#suspension-reason-container').hide();
            $('#therapeuticplan-suspension-date').attr('required', false);
            $('#therapeuticplan-suspension-date').val('');
            $('#therapeuticplan-suspension_reason').val('');
        }
    });
");
?>