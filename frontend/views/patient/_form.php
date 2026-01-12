<?php

use common\models\District;
use common\models\Provincia;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Patient $patient */
/** @var yii\widgets\ActiveForm $form */
/** @var bool $isUpdate */
/** @var array $districts */
$isUpdate = $isUpdate ?? false;
$districts = $districts ?? ArrayHelper::map(District::find()->all(), 'id', 'name');
$province = $province ?? ArrayHelper::map(Provincia::find()->orderBy('nome')->all(), 'id', 'nome');

// Imposta "nato in Italia" come default se è un nuovo paziente
if (!$isUpdate && $patient->born_in_italy === null) {
    $patient->born_in_italy = 1;
}

// Imposta "residenza in Italia" come default se è un nuovo paziente
if (!$isUpdate && $patient->residence_in_italy === null) {
    $patient->residence_in_italy = 1;
}
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
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($patient, 'first_name')->textInput([
                        'placeholder' => 'Inserisci nome',
                        'id' => 'patient-first-name'
                    ])->label('Nome') ?>
                </div>
                
                <div>
                    <?= $form->field($patient, 'last_name')->textInput([
                        'placeholder' => 'Inserisci cognome',
                        'id' => 'patient-last-name'
                    ])->label('Cognome') ?>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Data di Nascita
                    </label>

                    <div class="relative">
                        <?= $form->field($patient, 'birth_date')->input('date', [
                            'placeholder' => 'Seleziona data',
                            'id' => 'patient-birth-date',
                            'class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2 pr-10'
                        ])->label(false) ?>
                        <button type="button" onclick="document.getElementById('patient-birth-date').showPicker()" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div>
                    <?= $form->field($patient, 'phone_number')->textInput([
                        'placeholder' => '+39 123 456 7890'
                    ])->label('Telefono') ?>
                </div>
                
                <div>
                    <?= $form->field($patient, 'district_id')->dropDownList($districts, [
                        'prompt' => 'Seleziona distretto...'
                    ])->label('Distretto') ?>
                </div>
                
                <!-- Campo Sesso per generazione Codice Fiscale -->
                <div>
                    <?= $form->field($patient, 'gender')->dropDownList([
                        'M' => 'Maschio',
                        'F' => 'Femmina'
                    ], [
                        'prompt' => 'Seleziona sesso...',
                        'id' => 'patient-gender'
                    ])->label('Sesso') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dati di Nascita -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati di Nascita
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni sul luogo di nascita del paziente.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-4">
                <!-- Checkbox Nato in Italia -->
                <div class="flex items-center">
                    <?= $form->field($patient, 'born_in_italy')->checkbox([
                        'id' => 'born-in-italy-checkbox',
                        'template' => '<div class="flex items-center">{input}{label}</div>',
                        'labelOptions' => [
                            'class' => 'ml-2 text-sm font-medium text-gray-700 dark:text-gray-300'
                        ]
                    ])->label('Nato/a in Italia') ?>
                </div>
                
                <!-- Campi nascita Italia -->
                <div id="birth-location-italy-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <?= $form->field($patient, 'birth_province_id')->dropDownList($province, [
                            'prompt' => 'Seleziona provincia...',
                            'id' => 'birth-province-select',
                            'onchange' => 'loadBirthComuni(this.value)'
                        ])->label('Provincia di Nascita') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($patient, 'birth_city')->dropDownList([], [
                            'prompt' => 'Prima seleziona la provincia',
                            'id' => 'birth-city-select',
                            'data-current-value' => $patient->birth_city
                        ])->label('Comune di Nascita') ?>
                    </div>
                </div>
                
                <!-- Campo nascita estero (visibile solo se NON nato in Italia) -->
                <div id="birth-location-foreign-field" class="grid grid-cols-1 gap-4" style="display: none;">
                    <div>
                        <?= $form->field($patient, 'birth_city')->textInput([
                            'placeholder' => 'Stato estero di nascita',
                            'id' => 'birth-city-foreign',
                            'name' => '',  // Rimosso name attribute - sarà gestito da JavaScript
                            'disabled' => true
                        ])->label('Stato di Nascita') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Codice Fiscale -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Codice Fiscale
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci il codice fiscale manualmente o generalo automaticamente usando i dati inseriti sopra.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-4">
                <div class="relative">
                    <?= $form->field($patient, 'fiscal_code')->textInput([
                        'placeholder' => 'RSSMRO80A01H501X',
                        'id' => 'fiscal-code-input'
                    ])->label('Codice Fiscale') ?>
                </div>
                
                <!-- Pulsante Genera Codice Fiscale -->
                <div class="flex justify-center">
                    <button type="button" id="generate-fiscal-code-btn" 
                            class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 shadow-sm">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                        </svg>
                        Genera Codice Fiscale Automaticamente
                    </button>
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Per generare automaticamente il codice fiscale
                            </h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <p>Assicurati di aver compilato questi campi obbligatori:</p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>Nome e Cognome</li>
                                    <li>Data di Nascita</li>
                                    <li>Selezionare "Nato in Italia" e inserire il Comune di Nascita</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dati di Residenza -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati di Residenza
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni sulla residenza del paziente.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-4">
                <div class="sm:col-span-2">
                    <?= $form->field($patient, 'residence_address')->textInput([
                        'placeholder' => 'Via, Piazza, ecc.'
                    ])->label('Indirizzo di Residenza') ?>
                </div>
                
                <!-- Checkbox Residenza in Italia -->
                <div class="flex items-center">
                    <?= $form->field($patient, 'residence_in_italy')->checkbox([
                        'id' => 'residence-in-italy-checkbox',
                        'template' => '<div class="flex items-center">{input}{label}</div>',
                        'labelOptions' => [
                            'class' => 'ml-2 text-sm font-medium text-gray-700 dark:text-gray-300'
                        ]
                    ])->label('Residenza in Italia') ?>
                </div>
                
                <!-- Campi residenza Italia -->
                <div id="residence-location-italy-fields" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <?= $form->field($patient, 'residence_province_id')->dropDownList($province, [
                            'prompt' => 'Seleziona provincia...',
                            'id' => 'residence-province-select',
                            'onchange' => 'loadResidenceComuni(this.value)'
                        ])->label('Provincia di Residenza') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($patient, 'residence_city')->dropDownList([], [
                            'prompt' => 'Prima seleziona la provincia',
                            'id' => 'residence-city-select',
                            'data-current-value' => $patient->residence_city
                        ])->label('Comune di Residenza') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($patient, 'residence_postal_code')->textInput([
                            'placeholder' => '00100',
                            'maxlength' => 5
                        ])->label('CAP') ?>
                        <div class="text-xs text-gray-500 mt-1">
                            <span id="cap-auto-info" style="display: none;">
                                ✨ Il CAP verrà compilato automaticamente quando selezioni un comune
                            </span>
                            <span id="cap-manual-info">
                                💡 Inserisci il CAP manualmente
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Campo residenza estero (visibile solo se NON residenza in Italia) -->
                <div id="residence-location-foreign-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display: none;">
                    <div>
                        <?= $form->field($patient, 'residence_city')->textInput([
                            'placeholder' => 'Città/Stato estero di residenza',
                            'id' => 'residence-city-foreign',
                            'name' => '',  // Rimosso name attribute - sarà gestito da JavaScript
                            'disabled' => true
                        ])->label('Città/Stato di Residenza') ?>
                    </div>
                    
                    <div>
                        <?= $form->field($patient, 'residence_postal_code')->textInput([
                            'placeholder' => 'Codice postale',
                            'id' => 'residence-postal-code-foreign',
                            'name' => '',  // Rimosso name attribute - sarà gestito da JavaScript
                            'disabled' => true
                        ])->label('Codice Postale') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Note -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Note Aggiuntive
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni aggiuntive sul paziente (opzionale).
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <?= $form->field($patient, 'notes')->textArea([
                'rows' => 4,
                'placeholder' => 'Note aggiuntive sul paziente (opzionale)'
            ])->label(false) ?>
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
    
    <!-- DEBUG: Mostra dati form in tempo reale -->
    <div id="debug-panel" style="position: fixed; top: 10px; right: 10px; width: 400px; max-height: 80vh; overflow-y: auto; background: #f8f9fa; border: 2px solid #007bff; border-radius: 8px; padding: 15px; font-family: monospace; font-size: 12px; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        
       
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // DEBUG: Aggiorna pannello debug in tempo reale
    const form = document.querySelector('form');
    

    
    
    
    // Aggiorna il pannello ogni volta che cambia qualcosa
  
    // Converte automaticamente il codice fiscale in maiuscolo al rilascio del tasto
    const fiscalCodeInput = document.getElementById('fiscal-code-input');
    
    if (fiscalCodeInput) {
        fiscalCodeInput.addEventListener('keyup', function() {
            const currentValue = this.value;
            this.value = currentValue.toUpperCase();
        });
    }
    
    // Gestione checkbox "Nato in Italia"
    const bornInItalyCheckbox = document.getElementById('born-in-italy-checkbox');
    const birthLocationItalyFields = document.getElementById('birth-location-italy-fields');
    const birthLocationForeignField = document.getElementById('birth-location-foreign-field');
    
    if (bornInItalyCheckbox && birthLocationItalyFields && birthLocationForeignField) {
        // Aggiungi event listener prima di chiamare la funzione toggle
        bornInItalyCheckbox.addEventListener('change', function() {
            toggleBirthLocationFields();
        });
        
        // Controlla stato iniziale DOPO aver aggiunto i listener
        toggleBirthLocationFields();
        
        // Se stiamo modificando un paziente e c'è una provincia selezionata, carica i comuni
        const provinciaSelect = document.getElementById('birth-province-select');
        if (provinciaSelect && provinciaSelect.value) {
            const currentComune = document.getElementById('birth-city-select').getAttribute('data-current-value');
            loadBirthComuni(provinciaSelect.value, currentComune);
        }
    }
    
    // Gestione checkbox "Residenza in Italia"
    const residenceInItalyCheckbox = document.getElementById('residence-in-italy-checkbox');
    const residenceLocationItalyFields = document.getElementById('residence-location-italy-fields');
    const residenceLocationForeignFields = document.getElementById('residence-location-foreign-fields');
    
    if (residenceInItalyCheckbox && residenceLocationItalyFields && residenceLocationForeignFields) {
        // Aggiungi event listener prima di chiamare la funzione toggle
        residenceInItalyCheckbox.addEventListener('change', function() {
            toggleResidenceLocationFields();
        });
        
        // Controlla stato iniziale DOPO aver aggiunto i listener
        toggleResidenceLocationFields();
        
        // Se stiamo modificando un paziente e c'è una provincia selezionata, carica i comuni
        const residenceProvinciaSelect = document.getElementById('residence-province-select');
        if (residenceProvinciaSelect && residenceProvinciaSelect.value) {
            const currentResidenceComune = document.getElementById('residence-city-select').getAttribute('data-current-value');
            loadResidenceComuni(residenceProvinciaSelect.value, currentResidenceComune);
        }
    }
    
    // Inizializza i messaggi CAP per i nuovi pazienti
    initializeCapMessages();
    
    function toggleBirthLocationFields() {
        const birthCitySelect = document.getElementById('birth-city-select');
        const birthCityForeign = document.getElementById('birth-city-foreign');
        const fiscalCodeGenerationButton = document.getElementById('generate-fiscal-code-btn');
        
        if (bornInItalyCheckbox.checked) {
            // Mostra campi Italia
            birthLocationItalyFields.style.display = 'grid';
            birthLocationForeignField.style.display = 'none';
            fiscalCodeGenerationButton.style.display = 'inline-flex';
            
            // Abilita campi Italia e assegna name attribute
            birthCitySelect.disabled = false;
            birthCitySelect.name = 'Patient[birth_city]';
            
            // Disabilita campo estero e rimuovi name attribute
            birthCityForeign.disabled = true;
            birthCityForeign.name = '';
            birthCityForeign.value = '';
            
        } else {
            // Mostra campo estero
            birthLocationItalyFields.style.display = 'none';
            birthLocationForeignField.style.display = 'grid';
            fiscalCodeGenerationButton.style.display = 'none';
            
            // Disabilita campi Italia e rimuovi name attribute
            birthCitySelect.disabled = true;
            birthCitySelect.name = '';
            document.getElementById('birth-province-select').value = '';
            birthCitySelect.innerHTML = '<option value="">Prima seleziona la provincia</option>';
            
            // Abilita campo estero e assegna name attribute
            birthCityForeign.disabled = false;
            birthCityForeign.name = 'Patient[birth_city]';
        }
    }
    
    function toggleResidenceLocationFields() {
        const residenceCitySelect = document.getElementById('residence-city-select');
        const residenceCityForeign = document.getElementById('residence-city-foreign');
        const residencePostalCodeItaly = document.querySelector('#residence-location-italy-fields input[name="Patient[residence_postal_code]"]');
        const residencePostalCodeForeign = document.getElementById('residence-postal-code-foreign');
        
        if (residenceInItalyCheckbox.checked) {
            // Mostra campi Italia
            residenceLocationItalyFields.style.display = 'grid';
            residenceLocationForeignFields.style.display = 'none';
            
            // Abilita campi Italia e assegna name attributes
            residenceCitySelect.disabled = false;
            residenceCitySelect.name = 'Patient[residence_city]';
            if (residencePostalCodeItaly) {
                residencePostalCodeItaly.disabled = false;
                residencePostalCodeItaly.name = 'Patient[residence_postal_code]';
            }
            
            // Disabilita campi esteri e rimuovi name attributes
            residenceCityForeign.disabled = true;
            residenceCityForeign.name = '';
            residenceCityForeign.value = '';
            residencePostalCodeForeign.disabled = true;
            residencePostalCodeForeign.name = '';
            residencePostalCodeForeign.value = '';
            
        } else {
            // Mostra campi esteri
            residenceLocationItalyFields.style.display = 'none';
            residenceLocationForeignFields.style.display = 'grid';
            
            // Disabilita campi Italia e rimuovi name attributes
            residenceCitySelect.disabled = true;
            residenceCitySelect.name = '';
            document.getElementById('residence-province-select').value = '';
            residenceCitySelect.innerHTML = '<option value="">Prima seleziona la provincia</option>';
            if (residencePostalCodeItaly) {
                residencePostalCodeItaly.disabled = true;
                residencePostalCodeItaly.name = '';
            }
            
            // Abilita campi esteri e assegna name attributes
            residenceCityForeign.disabled = false;
            residenceCityForeign.name = 'Patient[residence_city]';
            residencePostalCodeForeign.disabled = false;
            residencePostalCodeForeign.name = 'Patient[residence_postal_code]';
        }
    }
    
    // Gestione generazione codice fiscale
    const generateBtn = document.getElementById('generate-fiscal-code-btn');
    
    if (generateBtn) {
        generateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Raccogli dati necessari
            const firstName = document.getElementById('patient-first-name').value.trim();
            const lastName = document.getElementById('patient-last-name').value.trim();
            const birthDate = document.getElementById('patient-birth-date').value;
            const gender = document.getElementById('patient-gender').value;
            
            // Determina il campo comune in base a se è nato in Italia
            let birthCity = '';
            if (bornInItalyCheckbox.checked) {
                birthCity = document.getElementById('birth-city-select').value.trim();
            } else {
                birthCity = document.getElementById('birth-city-foreign').value.trim();
            }
            
            // Validazione campi obbligatori
            const missingFields = [];
            if (!firstName) missingFields.push('Nome');
            if (!lastName) missingFields.push('Cognome'); 
            if (!birthDate) missingFields.push('Data di nascita');
            if (!gender) missingFields.push('Sesso');
            if (!birthCity) {
                if (bornInItalyCheckbox.checked) {
                    missingFields.push('Comune di nascita (seleziona prima la provincia)');
                } else {
                    missingFields.push('Stato di nascita');
                }
            }
            
            if (missingFields.length > 0) {
                alert('Compila i seguenti campi obbligatori per generare il codice fiscale:\n\n' + missingFields.join(', '));
                return;
            }
            
            // Genera direttamente il codice fiscale senza modal
            generateFiscalCode(gender, {
                first_name: firstName,
                last_name: lastName,
                birth_date: birthDate,
                birth_city: birthCity
            });
        });
    }
    
    function generateFiscalCode(gender, fiscalCodeData) {
        // Aggiungi il sesso ai dati
        const requestData = {
            ...fiscalCodeData,
            gender: gender
        };
        
        // Disabilita il pulsante e mostra loading
        generateBtn.disabled = true;
        generateBtn.innerHTML = 'Generazione...';
        
        // Invia richiesta AJAX
        fetch('<?= \yii\helpers\Url::to(['generate-fiscal-code']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: new URLSearchParams(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Popola il campo codice fiscale
                fiscalCodeInput.value = data.fiscal_code;
                
                // Mostra messaggio di successo
                showNotification('Codice fiscale generato con successo!', 'success');
            } else {
                showNotification('Errore: ' + (data.error || 'Impossibile generare il codice fiscale'), 'error');
            }
        })
        .catch(error => {
            console.error('Errore nella generazione codice fiscale:', error);
            showNotification('Errore di connessione durante la generazione del codice fiscale', 'error');
        })
        .finally(() => {
            // Ripristina il pulsante
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path></svg>Genera Codice Fiscale Automaticamente';
        });
    }
    
    function showNotification(message, type = 'info') {
        // Crea notifica temporanea
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button type="button" class="ml-3 flex-shrink-0" onclick="this.parentElement.parentElement.remove()">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animazione entrata
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto-rimozione dopo 5 secondi
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }
    
    // Auto-uppercase per codici provincia
    const provinceCodeInputs = document.querySelectorAll('input[name="Patient[residence_province_code]"]');
    provinceCodeInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });
    
    // Validazione CAP (solo numeri)
    const capInput = document.querySelector('input[name="Patient[residence_postal_code]"]');
    if (capInput) {
        capInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});

// Funzione globale per caricare i comuni di nascita (chiamata dall'onchange della select provincia)
function loadBirthComuni(provinciaId, selectedComune = null) {
    const comuniSelect = document.getElementById('birth-city-select');
    
    if (!provinciaId) {
        comuniSelect.innerHTML = '<option value="">Prima seleziona la provincia</option>';
        return;
    }
    
    // Mostra loading
    comuniSelect.innerHTML = '<option value="">Caricamento comuni...</option>';
    comuniSelect.disabled = true;
    
    // Carica comuni via AJAX
    let url = '<?= \yii\helpers\Url::to(['load-comuni']) ?>?provincia_id=' + provinciaId;
    if (selectedComune) {
        url += '&selected_comune=' + encodeURIComponent(selectedComune);
    }
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Pulisci la select
        comuniSelect.innerHTML = '<option value="">Seleziona comune...</option>';
        
        // Aggiungi i comuni
        if (data.comuni && Object.keys(data.comuni).length > 0) {
            Object.keys(data.comuni).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = data.comuni[key];
                
                // Se questo è il comune attualmente selezionato, selezionalo
                if (selectedComune && key === selectedComune) {
                    option.selected = true;
                }
                
                comuniSelect.appendChild(option);
            });
        } else {
            comuniSelect.innerHTML = '<option value="">Nessun comune trovato</option>';
        }
        
        comuniSelect.disabled = false;
        
        // Nota: per la nascita non gestiamo il CAP automatico
        // Il CAP è rilevante solo per la residenza
    })
    .catch(error => {
        console.error('Errore nel caricamento comuni:', error);
        comuniSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
        comuniSelect.disabled = false;
    });
}

// Funzione globale per caricare i comuni di residenza (chiamata dall'onchange della select provincia)
function loadResidenceComuni(provinciaId, selectedComune = null) {
    const comuniSelect = document.getElementById('residence-city-select');
    
    if (!provinciaId) {
        comuniSelect.innerHTML = '<option value="">Prima seleziona la provincia</option>';
        return;
    }
    
    // Mostra loading
    comuniSelect.innerHTML = '<option value="">Caricamento comuni...</option>';
    comuniSelect.disabled = true;
    
    // Carica comuni via AJAX
    let url = '<?= \yii\helpers\Url::to(['load-comuni']) ?>?provincia_id=' + provinciaId;
    if (selectedComune) {
        url += '&selected_comune=' + encodeURIComponent(selectedComune);
    }
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Pulisci la select
        comuniSelect.innerHTML = '<option value="">Seleziona comune...</option>';
        
        // Aggiungi i comuni
        if (data.comuni && Object.keys(data.comuni).length > 0) {
            Object.keys(data.comuni).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = data.comuni[key];
                
                // Se questo è il comune attualmente selezionato, selezionalo
                if (selectedComune && key === selectedComune) {
                    option.selected = true;
                }
                
                comuniSelect.appendChild(option);
            });
            
            // Salva i dati CAP per la compilazione automatica
            if (data.cap_data && data.has_cap_field) {
                comuniSelect.setAttribute('data-cap-info', JSON.stringify(data.cap_data));
                comuniSelect.setAttribute('data-has-cap-field', 'true');
                
                // Mostra messaggio CAP automatico
                const capAutoInfo = document.getElementById('cap-auto-info');
                const capManualInfo = document.getElementById('cap-manual-info');
                if (capAutoInfo && capManualInfo) {
                    capAutoInfo.style.display = 'inline';
                    capManualInfo.style.display = 'none';
                }
            } else {
                comuniSelect.setAttribute('data-has-cap-field', 'false');
                
                // Mostra messaggio CAP manuale
                const capAutoInfo = document.getElementById('cap-auto-info');
                const capManualInfo = document.getElementById('cap-manual-info');
                if (capAutoInfo && capManualInfo) {
                    capAutoInfo.style.display = 'none';
                    capManualInfo.style.display = 'inline';
                }
            }
            
            // Aggiungi listener per la compilazione automatica del CAP
            setupCapAutoFill();
            
        } else {
            comuniSelect.innerHTML = '<option value="">Nessun comune trovato</option>';
        }
        
        comuniSelect.disabled = false;
    })
    .catch(error => {
        console.error('Errore nel caricamento comuni residenza:', error);
        comuniSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
        comuniSelect.disabled = false;
    });
}

// Funzione per configurare la compilazione automatica del CAP
function setupCapAutoFill() {
    const comuniSelect = document.getElementById('residence-city-select');
    const capInput = document.querySelector('input[name="Patient[residence_postal_code]"]');
    
    if (!comuniSelect || !capInput) {
        return;
    }
    
    // Rimuovi listener precedenti per evitare duplicati
    comuniSelect.removeEventListener('change', handleCapAutoFill);
    
    // Aggiungi nuovo listener
    comuniSelect.addEventListener('change', handleCapAutoFill);
}

// Handler separato per la compilazione automatica del CAP
function handleCapAutoFill() {
    const selectedComune = this.value;
    const hasCapField = this.getAttribute('data-has-cap-field') === 'true';
    const capData = this.getAttribute('data-cap-info');
    const capInput = document.querySelector('input[name="Patient[residence_postal_code]"]');
    
    if (!capInput) {
        return;
    }
    
    if (!hasCapField || !capData || !selectedComune) {
        // Se non c'è il campo CAP nel database, lascia che l'utente compili manualmente
        console.log('Campo CAP non disponibile nel database, compilazione manuale richiesta');
        return;
    }
    
    try {
        const capInfo = JSON.parse(capData);
        if (capInfo[selectedComune]) {
            // Compila automaticamente il CAP
            capInput.value = capInfo[selectedComune];
            capInput.style.backgroundColor = '#f0f9ff'; // Sfondo azzurrino per indicare auto-compilazione
            
            // Mostra notifica
            showNotification('CAP compilato automaticamente: ' + capInfo[selectedComune], 'success');
            
            // Rimuovi lo sfondo dopo 3 secondi
            setTimeout(() => {
                capInput.style.backgroundColor = '';
            }, 3000);
        } else {
            console.log('CAP non trovato per il comune: ' + selectedComune);
        }
    } catch (e) {
        console.error('Errore nel parsing dei dati CAP:', e);
    }
}

// Funzione per inizializzare i messaggi CAP per nuovi pazienti
function initializeCapMessages() {
    // Per i nuovi pazienti, mostra il messaggio di compilazione manuale di default
    const capAutoInfo = document.getElementById('cap-auto-info');
    const capManualInfo = document.getElementById('cap-manual-info');
    
    if (capAutoInfo && capManualInfo) {
        // Di default mostra compilazione manuale
        capAutoInfo.style.display = 'none';
        capManualInfo.style.display = 'inline';
    }
}
</script> 