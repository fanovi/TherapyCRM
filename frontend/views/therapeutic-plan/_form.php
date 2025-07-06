<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $form yii\widgets\ActiveForm */
/* @var $patients array */
/* @var $regimes array */
?>

<div class="therapeutic-plan-form">

    <?php $form = ActiveForm::begin([
        'id' => 'therapeutic-plan-form',
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'template' => '{label}{input}{error}',
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2'],
            'inputOptions' => ['class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200'],
            'errorOptions' => ['class' => 'mt-1 text-sm text-red-600 dark:text-red-400'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Paziente -->
        <div class="form-group relative">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Paziente <span class="text-red-500">*</span>
            </label>
            
            <!-- Search input -->
            <input type="text" 
                   id="patient-search" 
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
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
                    'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200',
                ]
            )->label('Regime <span class="text-red-500">*</span>', ['encode' => false]) ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Data Inizio -->
        <div class="form-group">
            <?= $form->field($model, 'start_date')->input('date', [
                'class' => 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                'value' => $model->start_date ? date('Y-m-d', strtotime($model->start_date)) : '',
            ])->label('Data Inizio <span class="text-red-500">*</span>', ['encode' => false]) ?>
        </div>

        <!-- Durata in Giorni -->
        <div class="form-group">
            <?= $form->field($model, 'duration_days')->textInput([
                'type' => 'number',
                'min' => 1,
                'max' => 9999,
                'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200',
                'placeholder' => 'Inserisci la durata in giorni',
            ])->label('Durata (giorni) <span class="text-red-500">*</span>', ['encode' => false]) ?>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                La data di fine sarà calcolata automaticamente in base alla durata inserita.
            </p>
        </div>
    </div>

    <!-- Note -->
    <div class="form-group">
        <?= $form->field($model, 'notes')->textarea([
            'rows' => 4,
            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 resize-none',
            'placeholder' => 'Inserisci eventuali note aggiuntive per il piano terapeutico...',
        ])->label('Note') ?>
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
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Pulsanti -->
    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
        <?= Html::a(
            'Annulla',
            ['index'],
            [
                'class' => 'inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]'
            ]
        ) ?>
        
        <?= Html::submitButton(
            $model->isNewRecord ? 
                '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Crea Piano Terapeutico' : 
                '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Salva Modifiche',
            [
                'class' => 'inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600',
                'id' => 'submit-btn'
            ]
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$this->registerJs("
    // Patient search functionality
    var searchTimeout;
    var selectedPatientId = " . ($model->patient_id ? $model->patient_id : 'null') . ";
    
    // Initialize if editing existing record
    if (selectedPatientId && $('#patient-id-hidden').val()) {
        // Load patient data for editing
        $.ajax({
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
    $('#patient-search').on('input', function() {
        var query = $(this).val();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $('#patient-search-results').addClass('hidden');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchPatients(query);
        }, 300);
    });
    
    // Search patients function
    function searchPatients(query) {
        $.ajax({
            url: '" . \yii\helpers\Url::to(['search-patients']) . "',
            type: 'GET',
            data: { q: query },
            success: function(response) {
                displaySearchResults(response.results);
            },
            error: function() {
                $('#patient-error').text('Errore durante la ricerca pazienti').removeClass('hidden');
            }
        });
    }
    
    // Display search results
    function displaySearchResults(results) {
        var html = '';
        
        if (results.length === 0) {
            html = '<div class=\"px-4 py-3 text-sm text-gray-500 dark:text-gray-400\">Nessun paziente trovato</div>';
        } else {
            $.each(results, function(index, patient) {
                html += '<div class=\"px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0 patient-result\" data-patient-id=\"' + patient.id + '\" data-patient-name=\"' + patient.full_name + '\" data-patient-cf=\"' + patient.fiscal_code + '\">';
                html += '<div class=\"font-medium text-gray-900 dark:text-white\">' + patient.full_name + '</div>';
                html += '<div class=\"text-sm text-gray-500 dark:text-gray-400\">' + patient.fiscal_code + '</div>';
                html += '</div>';
            });
        }
        
        $('#search-results-list').html(html);
        $('#patient-search-results').removeClass('hidden');
    }
    
    // Handle patient selection
    $(document).on('click', '.patient-result', function() {
        var patientId = $(this).data('patient-id');
        var patientName = $(this).data('patient-name');
        var patientCf = $(this).data('patient-cf');
        
        var patient = {
            id: patientId,
            full_name: patientName,
            fiscal_code: patientCf
        };
        
        showSelectedPatient(patient);
        $('#patient-search-results').addClass('hidden');
        $('#patient-search').val('');
    });
    
    // Show selected patient
    function showSelectedPatient(patient) {
        $('#patient-id-hidden').val(patient.id);
        $('#selected-patient-name').text(patient.full_name);
        $('#selected-patient-cf').text('(' + patient.fiscal_code + ')');
        $('#selected-patient').removeClass('hidden');
        $('#patient-search').addClass('hidden');
        $('#patient-error').addClass('hidden');
        selectedPatientId = patient.id;
    }
    
    // Clear patient selection
    $('#clear-patient').on('click', function() {
        $('#patient-id-hidden').val('');
        $('#selected-patient').addClass('hidden');
        $('#patient-search').removeClass('hidden').val('').focus();
        $('#patient-search-results').addClass('hidden');
        selectedPatientId = null;
    });
    
    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#patient-search, #patient-search-results').length) {
            $('#patient-search-results').addClass('hidden');
        }
    });
    
    // Auto-calculate end date when start date or duration changes
    function calculateEndDate() {
        var startDate = $('#therapeuticplan-start_date').val();
        var duration = $('#therapeuticplan-duration_days').val();
        
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
            
            $('#therapeuticplan-duration_days').parent().find('.calculated-end-date').remove();
            $('#therapeuticplan-duration_days').parent().append('<div class=\"calculated-end-date\">' + infoHtml + '</div>');
        } else {
            $('#therapeuticplan-duration_days').parent().find('.calculated-end-date').remove();
        }
    }
    
    $('#therapeuticplan-start_date, #therapeuticplan-duration_days').on('change input', calculateEndDate);
    
    // Calculate on page load if values are present
    calculateEndDate();
    
    // Form validation
    $('#therapeutic-plan-form').on('beforeSubmit', function() {
        var isValid = true;
        var errors = [];
        
        // Check required fields
        if (!$('#patient-id-hidden').val()) {
            errors.push('Seleziona un paziente');
            $('#patient-error').text('Seleziona un paziente').removeClass('hidden');
            isValid = false;
        } else {
            $('#patient-error').addClass('hidden');
        }
        
        if (!$('#therapeuticplan-regime_id').val()) {
            errors.push('Seleziona un regime');
            isValid = false;
        }
        
        if (!$('#therapeuticplan-start_date').val()) {
            errors.push('Inserisci la data di inizio');
            isValid = false;
        }
        
        var duration = $('#therapeuticplan-duration_days').val();
        if (!duration || duration <= 0) {
            errors.push('Inserisci una durata valida');
            isValid = false;
        }
        
        if (!isValid) {
            alert('Errori di validazione:\\n\\n' + errors.join('\\n'));
            return false;
        }
        
        // Disable submit button to prevent double submission
        $('#submit-btn').prop('disabled', true).html('<svg class=\"animate-spin -ml-1 mr-3 h-4 w-4 text-white\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>Salvataggio...');
        
        return true;
    });
");
?> 