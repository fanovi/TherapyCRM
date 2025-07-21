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
    <div id="appointments-alert" class="hidden rounded-2xl border border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Attenzione: Appuntamenti nel periodo selezionato
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p id="appointments-count"></p>
                        <div id="appointments-list" class="mt-2"></div>
                        
                        <div class="mt-3">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="update_appointments" 
                                       value="1" 
                                       class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-300 focus:ring focus:ring-brand-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm">Imposta tutti questi appuntamenti come "Terapista Assente"</span>
                            </label>
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
    
    // Mostra un indicatore di caricamento
    $('#appointments-alert').removeClass('hidden');
    $('#appointments-count').html('<span class=\"inline-flex items-center\"><svg class=\"animate-spin -ml-1 mr-2 h-4 w-4 text-yellow-500\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>Controllo appuntamenti in corso...</span>');
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
                
                var listHtml = '<div class=\"mt-3 max-h-48 overflow-y-auto border border-yellow-200 rounded-lg p-3 bg-yellow-50/50 dark:border-yellow-700 dark:bg-yellow-900/30\">';
                listHtml += '<ul class=\"space-y-2\">';
                
                data.appointments.forEach(function(appointment) {
                    listHtml += '<li class=\"flex items-start\">';
                    listHtml += '<svg class=\"w-4 h-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0\" fill=\"currentColor\" viewBox=\"0 0 20 20\">';
                    listHtml += '<path fill-rule=\"evenodd\" d=\"M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9.5l-3.293-3.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l4-4a1 1 0 000-1.414z\" clip-rule=\"evenodd\"/>';
                    listHtml += '</svg>';
                    listHtml += '<span class=\"text-sm\">';
                    listHtml += '<strong>' + appointment.date + '</strong> alle ' + appointment.time + ' - ';
                    listHtml += '<span class=\"font-medium\">' + appointment.patient + '</span> ';
                    listHtml += '<span class=\"text-gray-600\">(' + appointment.type + ')</span>';
                    listHtml += '</span>';
                    listHtml += '</li>';
                });
                
                listHtml += '</ul>';
                listHtml += '</div>';
                
                $('#appointments-list').html(listHtml);
                $('#appointments-alert').removeClass('hidden');
            } else {
                $('#appointments-count').html(
                    '<span class=\"text-green-800 dark:text-green-200\"><strong>Ottimo!</strong> Nessun appuntamento programmato nel periodo selezionato.</span>'
                );
                $('#appointments-list').html('');
                $('#appointments-alert').removeClass('hidden');
                
                // Nascondi dopo 3 secondi se non ci sono appuntamenti
                setTimeout(function() {
                    if ($('#appointments-list').html() === '') {
                        $('#appointments-alert').addClass('hidden');
                    }
                }, 3000);
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

// Validazione form
$('#absence-form').on('beforeSubmit', function() {
    const startDate = $('#start-date').val();
    const endDate = $('#end-date').val();
    
    if (!startDate || !endDate) {
        alert('Seleziona il periodo di assenza');
        return false;
    }
    
    return true;
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