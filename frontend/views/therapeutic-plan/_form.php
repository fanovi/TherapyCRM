<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $therapyModel common\models\PlanTherapy */
/* @var $form yii\widgets\ActiveForm */
/* @var $patients array */
/* @var $regimes array */
/* @var $treatmentTypes array */
/* @var $settings array */
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
        
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
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
            <div class="form-group mt-6">
                <?= $form->field($model, 'notes')->textarea([
                    'rows' => 4,
                    'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 resize-none',
                    'placeholder' => 'Inserisci eventuali note aggiuntive per il piano terapeutico...',
                ])->label('Note') ?>
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
        
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tipo Trattamento -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tipo Trattamento <span class="text-red-500">*</span>
                            </label>
                            <select name="PlanTherapy[{index}][treatment_type_id]" class="treatment-type w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200" required>
                                <option value="">Seleziona tipo...</option>
                                <?php foreach ($treatmentTypes as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Ore Settimanali -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ore Settimanali <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="PlanTherapy[{index}][weekly_hours]" class="weekly-hours w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200" min="0.5" max="50" step="0.5" required>
                        </div>
                        
                        <!-- Setting -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Setting <span class="text-red-500">*</span>
                            </label>
                            <select name="PlanTherapy[{index}][setting_id]" class="setting w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200" required>
                                <option value="">Seleziona setting...</option>
                                <?php foreach ($settings as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Terapia di Gruppo -->
                        <div>
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
                        <textarea name="PlanTherapy[{index}][notes]" class="notes w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 resize-none" rows="2"></textarea>
                    </div>
                </div>
            </template>

            <?php
            // Aggiungi script per inizializzare le terapie esistenti
            $this->registerJs("
                // Debug flag
                const DEBUG = true;
                
                function log(message, data = null) {
                    if (DEBUG) {
                        console.log(message, data || '');
                    }
                }

                // Funzione per aggiungere una terapia con dati
                function addTherapyWithData(data) {
                    log('Aggiunta terapia con dati:', data);
                    
                    const container = document.getElementById('therapies-container');
                    const template = document.getElementById('therapy-template');
                    const clone = template.content.cloneNode(true);
                    const index = container.children.length;
                    
                    log('Indice nuova terapia:', index);
                    
                    // Aggiorna numero terapia
                    clone.querySelector('.therapy-number').textContent = index + 1;
                    
                    // Aggiorna gli attributi name con l'indice corretto
                    clone.querySelectorAll('[name*=\"{index}\"]').forEach(el => {
                        const newName = el.name.replace('{index}', index);
                        log('Aggiornamento nome campo:', { old: el.name, new: newName });
                        el.name = newName;
                    });
                    
                    // Imposta i valori se presenti
                    if (data) {
                        const treatmentTypeSelect = clone.querySelector('.treatment-type');
                        const weeklyHoursInput = clone.querySelector('.weekly-hours');
                        const settingSelect = clone.querySelector('.setting');
                        
                        if (treatmentTypeSelect && data.treatment_type_id) {
                            treatmentTypeSelect.value = data.treatment_type_id;
                            log('Impostato tipo trattamento:', data.treatment_type_id);
                        }
                        
                        if (weeklyHoursInput && data.weekly_hours) {
                            weeklyHoursInput.value = data.weekly_hours;
                            log('Impostate ore settimanali:', data.weekly_hours);
                        }
                        
                        if (settingSelect && data.setting_id) {
                            settingSelect.value = data.setting_id;
                            log('Impostato setting:', data.setting_id);
                        }
                    }
                    
                    // Aggiungi evento per rimuovere la terapia
                    const removeButton = clone.querySelector('.remove-therapy');
                    if (removeButton) {
                        removeButton.addEventListener('click', function() {
                            log('Rimozione terapia');
                            this.closest('.therapy-item').remove();
                            // Aggiorna i numeri delle terapie
                            document.querySelectorAll('.therapy-number').forEach((el, i) => {
                                el.textContent = i + 1;
                            });
                            updateTherapyIndexes();
                        });
                    }
                    
                    container.appendChild(clone);
                    log('Terapia aggiunta al container');
                }

                // Funzione per aggiornare gli indici delle terapie
                function updateTherapyIndexes() {
                    log('Aggiornamento indici terapie');
                    const container = document.getElementById('therapies-container');
                    const items = container.querySelectorAll('.therapy-item');
                    
                    items.forEach((item, index) => {
                        item.querySelectorAll('[name*=\"PlanTherapy[\"]').forEach(el => {
                            const newName = el.name.replace(/PlanTherapy\[\d+\]/, `PlanTherapy[${index}]`);
                            log('Aggiornamento nome campo:', { old: el.name, new: newName });
                            el.name = newName;
                        });
                    });
                }

                // Inizializza le terapie esistenti
                const postedTherapies = " . json_encode($postedTherapies) . ";
                log('Terapie da inizializzare:', postedTherapies);
                
                if (postedTherapies && postedTherapies.length > 0) {
                    postedTherapies.forEach(therapy => addTherapyWithData(therapy));
                }

                // Evento per aggiungere nuova terapia
                document.getElementById('add-therapy').addEventListener('click', function() {
                    log('Click su aggiungi terapia');
                    addTherapyWithData();
                });

                // Aggiungi almeno una terapia se non ce ne sono
                if (document.getElementById('therapies-container').children.length === 0) {
                    log('Nessuna terapia presente, ne aggiungo una vuota');
                    addTherapyWithData();
                }

                // Aggiungi validazione al form
                document.getElementById('therapeutic-plan-form').addEventListener('submit', function(e) {
                    log('Submit del form');
                    
                    const container = document.getElementById('therapies-container');
                    const therapies = container.querySelectorAll('.therapy-item');
                    
                    if (therapies.length === 0) {
                        e.preventDefault();
                        alert('È necessario inserire almeno una terapia.');
                        return false;
                    }
                    
                    let isValid = true;
                    const treatmentTypes = new Set();
                    
                    therapies.forEach((therapy, index) => {
                        const treatmentType = therapy.querySelector('.treatment-type').value;
                        const weeklyHours = therapy.querySelector('.weekly-hours').value;
                        const setting = therapy.querySelector('.setting').value;
                        
                        log('Validazione terapia ' + (index + 1), {
                            treatmentType,
                            weeklyHours,
                            setting
                        });
                        
                        if (!treatmentType || !weeklyHours || !setting) {
                            isValid = false;
                            alert('Tutti i campi della terapia ' + (index + 1) + ' sono obbligatori.');
                        }
                        
                        if (treatmentType && treatmentTypes.has(treatmentType)) {
                            isValid = false;
                            alert('Non è possibile assegnare lo stesso tipo di trattamento più volte.');
                        }
                        
                        treatmentTypes.add(treatmentType);
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        return false;
                    }
                    
                    log('Form valido, procedo con il submit');
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
    
    // Therapies management
    var therapyIndex = 0;
    
    // Add therapy
    $('#add-therapy').on('click', function() {
        var template = document.getElementById('therapy-template').innerHTML;
        template = template.replace(/{index}/g, therapyIndex);
        
        var container = document.getElementById('therapies-container');
        var div = document.createElement('div');
        div.innerHTML = template;
        container.appendChild(div.firstElementChild);
        
        updateTherapyNumbers();
        therapyIndex++;
    });
    
    // Remove therapy
    $(document).on('click', '.remove-therapy', function() {
        $(this).closest('.therapy-item').remove();
        updateTherapyNumbers();
    });
    
    // Update therapy numbers
    function updateTherapyNumbers() {
        $('.therapy-number').each(function(index) {
            $(this).text(index + 1);
        });
    }
    
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
        
        // Check therapies
        var therapies = $('.therapy-item');
        if (therapies.length === 0) {
            errors.push('Aggiungi almeno una terapia');
            isValid = false;
        }
        
        // Check for duplicate treatment types
        var treatmentTypes = [];
        $('.treatment-type').each(function() {
            var type = $(this).val();
            if (type && treatmentTypes.includes(type)) {
                errors.push('Non puoi assegnare lo stesso tipo di trattamento più volte');
                isValid = false;
                return false;
            }
            if (type) {
                treatmentTypes.push(type);
            }
        });
        
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