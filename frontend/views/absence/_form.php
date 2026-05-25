<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Absence $model */
/** @var array $therapists */
/** @var bool $isUpdate */

$isUpdate = $isUpdate ?? false;
$pageTitle = $isUpdate ? 'Modifica Assenza' : 'Nuova Assenza';
?>

<!-- Sostituisci la sezione dei link CSS/JS con questa -->
<?php
// Registra gli asset correttamente con Yii2
$this->registerCssFile('https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$this->registerJsFile('https://cdn.jsdelivr.net/momentjs/latest/moment.min.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END
]);

$this->registerJsFile('https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END
]);
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/absence/index']) ?>">
                            Assenze
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
        'id' => 'absence-form',
        'method' => 'post',
        'options' => [
            'class' => 'space-y-6',
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
    ]); ?>

    <style>
    .has-error .form-control,
    .has-error select,
    .has-error input {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 1px #dc2626 !important;
    }
    .has-error .form-control:focus,
    .has-error select:focus,
    .has-error input:focus {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 1px #dc2626 !important;
    }
    .help-block-error {
        color: #dc2626 !important;
        font-weight: 500;
    }
    .dark .has-error .form-control,
    .dark .has-error select,
    .dark .has-error input {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 1px #ef4444 !important;
    }
    .dark .help-block-error {
        color: #f87171 !important;
    }
    </style>

    <!-- Dati Assenza -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dettagli Assenza
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci i dettagli dell'assenza del terapista.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <?= $form->field($model, 'therapist_id')->dropDownList($therapists, [
                        'prompt' => 'Seleziona terapista...',
                        'id' => 'therapist-select'
                    ])->label('Terapista <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Periodo Assenza <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="date-range-picker" 
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2 pr-10"
                               placeholder="Seleziona periodo assenza..."
                               readonly>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div id="date-range-error" class="text-red-600 text-sm mt-1 font-medium help-block-error hidden"></div>
                    
                    <!-- Hidden inputs per Yii2 -->
                    <?= $form->field($model, 'start_date')->hiddenInput(['id' => 'start-date'])->label(false) ?>
                    <?= $form->field($model, 'end_date')->hiddenInput(['id' => 'end-date'])->label(false) ?>
                    
                    <!-- Info durata -->
                    <div id="duration-info" class="mt-2 text-sm text-gray-600 dark:text-gray-400"></div>
                </div>

                <!-- Toggle: tutto il giorno vs orario -->
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               id="all-day-toggle"
                               <?= $model->isHourly() ? '' : 'checked' ?>
                               class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tutto il giorno
                        </span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Disattivare per specificare un range orario (solo per assenze in un singolo giorno).
                    </p>
                </div>

                <!-- Campi orario (visibili solo se "tutto il giorno" e' OFF) -->
                <div id="hourly-fields" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 <?= $model->isHourly() ? '' : 'hidden' ?>">
                    <div>
                        <?= $form->field($model, 'start_time')->input('time', [
                            'id' => 'start-time',
                            'step' => 300,
                        ])->label('Ora Inizio <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>
                    <div>
                        <?= $form->field($model, 'end_time')->input('time', [
                            'id' => 'end-time',
                            'step' => 300,
                        ])->label('Ora Fine <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>
                </div>

                <div>
                    <?= $form->field($model, 'type')->dropDownList(\common\models\Absence::getTypeLabels(), [
                        'prompt' => 'Seleziona tipo...'
                    ])->label('Tipo Assenza <span class="text-red-500">*</span>', ['encode' => false]) ?>
                </div>

                <div>
                    <?= $form->field($model, 'reason')->textInput([
                        'placeholder' => 'Motivo dell\'assenza'
                    ])->label('Motivo') ?>
                </div>

                <div class="sm:col-span-2">
                    <?= $form->field($model, 'notes')->textarea([
                        'rows' => 3,
                        'placeholder' => 'Note aggiuntive...'
                    ])->label('Note') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Appuntamenti -->
    <div id="appointments-alert" class="hidden rounded-2xl border-2 border-orange-300 bg-gradient-to-r from-orange-50 to-yellow-50 dark:border-orange-600 dark:from-orange-900/30 dark:to-yellow-900/30 shadow-lg">
        <div class="px-6 py-5 sm:px-8 sm:py-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/50">
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-2">
                        ⚠️ Attenzione: Appuntamenti nel periodo selezionato
                    </h3>
                    <div class="text-sm text-orange-700 dark:text-orange-300">
                        <p id="appointments-count" class="font-medium mb-3"></p>
                        <div id="appointments-list" class="mb-4"></div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" 
                                       name="update_appointments" 
                                       value="1" 
                                       checked
                                       class="mt-1 rounded border-orange-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50">
                                <span class="ml-3 text-sm font-medium text-orange-800 dark:text-orange-200">
                                     Imposta tutti questi appuntamenti come "Terapista Assente"
                                </span>
                            </label>
                            <p class="mt-2 text-xs text-orange-600 dark:text-orange-400">
                                Questa azione aggiornerà automaticamente lo stato di tutti gli appuntamenti nel periodo selezionato.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex items-center justify-between gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            * Campi obbligatori
        </div>
        
        <div class="flex items-center gap-3">
            <?php if ($isUpdate): ?>
                <?= Html::a('Annulla', ['view', 'id' => $model->id], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
                
                <?= Html::submitButton('Salva Modifiche', [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            <?php else: ?>
                <?= Html::a('Annulla', ['index'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
                
                <?= Html::submitButton('Crea Assenza', [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Sostituisci il JavaScript esistente con questo -->
<?php
$checkAppointmentsUrl = \yii\helpers\Url::to(['check-appointments']);
$initialStartDate = $model->start_date ? date('d/m/Y', strtotime($model->start_date)) : '';
$initialEndDate = $model->end_date ? date('d/m/Y', strtotime($model->end_date)) : '';

$this->registerJs("
// TODO: Implementare notifiche ai pazienti quando gli appuntamenti vengono impostati come 'Terapista Assente'

// Configurazione Date Range Picker
$(function() {
    $('#date-range-picker').daterangepicker({
        startDate: " . ($initialStartDate ? "'{$initialStartDate}'" : "moment()") . ",
        endDate: " . ($initialEndDate ? "'{$initialEndDate}'" : "moment()") . ",
        minDate: moment(),
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Applica',
            cancelLabel: 'Annulla',
            fromLabel: 'Da',
            toLabel: 'A',
            customRangeLabel: 'Personalizzato',
            weekLabel: 'S',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Me', 'Gi', 'Ve', 'Sa'],
            monthNames: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
            firstDay: 1
        },
        autoApply: false,
        showDropdowns: true,
        opens: 'center',
        drops: 'down'
    }, function(start, end, label) {
        // Callback quando vengono selezionate le date
        $('#start-date').val(start.format('YYYY-MM-DD'));
        $('#end-date').val(end.format('YYYY-MM-DD'));
        
        // Calcola e mostra la durata
        var days = end.diff(start, 'days') + 1;
        $('#duration-info').html(
            '<span class=\"inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900\">' +
            '<svg class=\"w-4 h-4 mr-1\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">' +
            '<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\"></path>' +
            '</svg>' +
            'Durata: ' + days + ' ' + (days === 1 ? 'giorno' : 'giorni') +
            '</span>'
        );
        
        // Controlla appuntamenti
        checkAppointments();
    });
    
    // Se ci sono date iniziali, calcola la durata
    " . ($initialStartDate && $initialEndDate ? "
    var startInit = moment('{$initialStartDate}', 'DD/MM/YYYY');
    var endInit = moment('{$initialEndDate}', 'DD/MM/YYYY');
    var daysInit = endInit.diff(startInit, 'days') + 1;
    $('#duration-info').html(
        '<span class=\"inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900\">' +
        '<svg class=\"w-4 h-4 mr-1\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">' +
        '<path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\"></path>' +
        '</svg>' +
        'Durata: ' + daysInit + ' ' + (daysInit === 1 ? 'giorno' : 'giorni') +
        '</span>'
    );
    " : "") . "
});

function checkAppointments() {
    var therapistId = $('#therapist-select').val();
    var startDate = $('#start-date').val();
    var endDate = $('#end-date').val();
    
    if (!therapistId || !startDate || !endDate) {
        $('#appointments-alert').addClass('hidden');
        return;
    }

    // Resta nascosto durante l'ajax: mostra solo se ci sono appuntamenti
    $('#appointments-alert').addClass('hidden');
    $('#appointments-count').html('');
    $('#appointments-list').html('');
    
    $.ajax({
        url: '{$checkAppointmentsUrl}',
        type: 'POST',
        data: {
            therapist_id: therapistId,
            start_date: startDate,
            end_date: endDate,
            '" . Yii::$app->request->csrfParam . "': '" . Yii::$app->request->csrfToken . "'
        },
        success: function(data) {
            if (data.count > 0) {
                $('#appointments-count').html(
                    '<strong>Attenzione!</strong> Sono stati trovati <span class=\"font-bold\">' + data.count + '</span> appuntamenti nel periodo selezionato:'
                );
                
                var listHtml = '<div class=\"max-h-64 overflow-y-auto border-2 border-orange-200 rounded-lg p-4 bg-white/80 dark:border-orange-700 dark:bg-gray-800/80 shadow-inner\">';
                listHtml += '<div class=\"flex items-center justify-between mb-3\">';
                listHtml += '<h4 class=\"text-sm font-semibold text-orange-800 dark:text-orange-200\">📅 Appuntamenti trovati:</h4>';
                listHtml += '<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200\">' + data.count + ' appuntamenti</span>';
                listHtml += '</div>';
                listHtml += '<ul class=\"space-y-3\">';
                
                data.appointments.forEach(function(appointment, index) {
                    listHtml += '<li class=\"flex items-start p-3 rounded-lg bg-orange-50/50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800\">';
                    listHtml += '<div class=\"flex items-center justify-center w-6 h-6 rounded-full bg-orange-200 dark:bg-orange-800 text-orange-800 dark:text-orange-200 text-xs font-bold mr-3 flex-shrink-0\">' + (index + 1) + '</div>';
                    listHtml += '<div class=\"flex-1\">';
                    listHtml += '<div class=\"flex items-center justify-between\">';
                    listHtml += '<span class=\"font-semibold text-orange-900 dark:text-orange-100\">' + appointment.date + '</span>';
                    listHtml += '<span class=\"text-sm font-medium text-orange-700 dark:text-orange-300\">' + appointment.time + '</span>';
                    listHtml += '</div>';
                    listHtml += '<div class=\"mt-1\">';
                    listHtml += '<span class=\"font-medium text-gray-900 dark:text-white\">' + appointment.patient + '</span>';
                    listHtml += '<span class=\"ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200\">' + appointment.type + '</span>';
                    listHtml += '</div>';
                    listHtml += '</div>';
                    listHtml += '</li>';
                });
                
                listHtml += '</ul>';
                listHtml += '</div>';
                
                $('#appointments-list').html(listHtml);
                $('#appointments-alert').removeClass('hidden');
            } else {
                // Nessun appuntamento: alert resta nascosto, niente flash
                $('#appointments-count').html('');
                $('#appointments-list').html('');
                $('#appointments-alert').addClass('hidden');
            }
        },
        error: function() {
            $('#appointments-alert').addClass('hidden');
            console.error('Errore nel controllo appuntamenti');
        }
    });
}

$('#therapist-select').on('change', function() {
    checkAppointments();
});

// Check on page load if updating
if ($('#therapist-select').val() && $('#start-date').val() && $('#end-date').val()) {
    checkAppointments();
}

// Helper Swal coerente con il resto del progetto.
function showFormError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Dati mancanti o non validi',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#3b82f6',
        });
    } else {
        alert(message);
    }
}

// Validazione form
$('#absence-form').on('beforeSubmit', function() {
    const startDate = $('#start-date').val();
    const endDate = $('#end-date').val();

    if (!startDate || !endDate) {
        showFormError('Seleziona il periodo di assenza.');
        return false;
    }

    // Validazione assenza oraria
    if (!$('#all-day-toggle').is(':checked')) {
        if (startDate !== endDate) {
            showFormError('Un\'assenza oraria deve essere su un singolo giorno.');
            return false;
        }
        const st = $('#start-time').val();
        const et = $('#end-time').val();
        if (!st || !et) {
            showFormError('Specifica orario di inizio e di fine.');
            return false;
        }
        if (st >= et) {
            showFormError('L\'orario di fine deve essere successivo a quello di inizio.');
            return false;
        }
    }

    return true;
});

// Toggle tutto il giorno: mostra/nasconde i campi orario.
// Disattivato => forza single-day (end allineata a start).
$('#all-day-toggle').on('change', function() {
    var allDay = $(this).is(':checked');
    if (allDay) {
        $('#hourly-fields').addClass('hidden');
        $('#start-time').val('');
        $('#end-time').val('');
    } else {
        $('#hourly-fields').removeClass('hidden');
        // Forza single-day allineando end al start
        var picker = $('#date-range-picker').data('daterangepicker');
        if (picker) {
            picker.setEndDate(picker.startDate);
            $('#end-date').val(picker.startDate.format('YYYY-MM-DD'));
            $('#date-range-picker').val(picker.startDate.format('DD/MM/YYYY'));
        }
    }
});
", \yii\web\View::POS_READY);

// CSS personalizzato per Date Range Picker
$this->registerCss("
.daterangepicker {
    font-family: inherit;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.daterangepicker td.active,
.daterangepicker td.active:hover {
    background-color: #3b82f6;
    border-color: transparent;
}

.daterangepicker td.in-range {
    background-color: #dbeafe;
    color: #1e40af;
}

.daterangepicker .calendar-table {
    background-color: white;
    border: none;
}

.dark .daterangepicker {
    background-color: #1f2937;
    color: #f3f4f6;
}

.dark .daterangepicker .calendar-table {
    background-color: #1f2937;
}

.dark .daterangepicker td.off,
.dark .daterangepicker td.off.in-range,
.dark .daterangepicker td.off.start-date,
.dark .daterangepicker td.off.end-date {
    background-color: #374151;
    color: #6b7280;
}

.dark .daterangepicker td.available:hover,
.dark .daterangepicker th.available:hover {
    background-color: #4b5563;
}

.dark .daterangepicker td.in-range {
    background-color: #1e3a8a;
    color: #dbeafe;
}
");
?>