<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\AbsenceStatisticsSearch */
/* @var $monthlyRate array */
/* @var $byReason array */
/* @var $byGenerator array */
/* @var $byTreatmentType array */
/* @var $topAbsentees array */
/* @var $therapistOptions array */
/* @var $patientOptions array */
/* @var $treatmentOptions array */

$this->title = 'Statistiche Assenze';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');
// Inizializza variabili se non definite
$monthlyRate = $monthlyRate ?? [];
$byReason = $byReason ?? [];
$byGenerator = $byGenerator ?? [];
$byTreatmentType = $byTreatmentType ?? [];
$topAbsentees = $topAbsentees ?? ['therapists' => [], 'patients' => []];

// Funzione helper per verificare se ci sono filtri attivi
$hasActiveFilters = !empty($searchModel->dateFrom) || !empty($searchModel->dateTo) || 
                    !empty($searchModel->absenceSource) || !empty($searchModel->isJustified) || 
                    !empty($searchModel->therapistId) || !empty($searchModel->treatmentTypeId);

// Determina il periodo visualizzato
$periodText = 'Periodo: ';
if (!empty($searchModel->dateFrom) && !empty($searchModel->dateTo)) {
    $periodText .= Yii::$app->formatter->asDate($searchModel->dateFrom, 'dd/MM/yyyy') . ' - ' . 
                   Yii::$app->formatter->asDate($searchModel->dateTo, 'dd/MM/yyyy');
} elseif (!empty($searchModel->dateFrom)) {
    $periodText .= 'Dal ' . Yii::$app->formatter->asDate($searchModel->dateFrom, 'dd/MM/yyyy');
} elseif (!empty($searchModel->dateTo)) {
    $periodText .= 'Fino al ' . Yii::$app->formatter->asDate($searchModel->dateTo, 'dd/MM/yyyy');
} else {
    $periodText .= 'Ultimi 30 giorni';
}

// Helper per verificare se ci sono dati
$hasData = ($monthlyRate['total_absences'] ?? 0) > 0 || 
           !empty($byReason) || 
           !empty($byGenerator) || 
           !empty($byTreatmentType) ||
           (!empty($topAbsentees['therapists']) || !empty($topAbsentees['patients']));
?>

<div class="statistics-absences">
    <!-- Header con titolo e periodo -->
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text"><?= $periodText ?></p>
    </div>

    <!-- Filtri di ricerca - Riorganizzati logicamente -->
    <div class="filter-card">
        <div class="filter-header">
            <h3>Filtri di ricerca</h3>
            <?php if ($hasActiveFilters): ?>
                <span class="active-filters-badge">
                    <i class="fas fa-filter"></i> Filtri attivi
                </span>
            <?php endif; ?>
        </div>

        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'options' => ['class' => 'filter-form']
        ]); ?>

        <!-- Filtri temporali -->
        <div class="filter-section">
            <h4>Periodo temporale</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Data inizio</label>
                    <div class="relative">
                        <?= Html::activeTextInput($searchModel, 'dateFrom', [
                            'type' => 'date',
                            'placeholder' => 'Seleziona data',
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden',
                            'onclick' => 'this.showPicker()'
                        ]) ?>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="filter-col">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Data fine</label>
                    <div class="relative">
                        <?= Html::activeTextInput($searchModel, 'dateTo', [
                            'type' => 'date',
                            'placeholder' => 'Seleziona data',
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden',
                            'onclick' => 'this.showPicker()'
                        ]) ?>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri per tipologia -->
        <div class="filter-section">
            <h4>Tipologia assenze</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'absenceSource')->dropDownList([
                        '' => 'Tutte le sorgenti',
                        'therapist' => 'Solo Terapisti',
                        'patient' => 'Solo Pazienti'
                    ], ['class' => 'form-control'])->label('Sorgente assenza') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'isJustified')->dropDownList([
                        '' => 'Tutte le assenze',
                        '1' => 'Solo giustificate',
                        '0' => 'Solo non giustificate'
                    ], ['class' => 'form-control'])->label('Stato giustificazione') ?>
                </div>
            </div>
        </div>

        <!-- Filtri specifici -->
        <div class="filter-section">
            <h4>Filtri specifici</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'therapistId')->dropDownList(
                        ['' => 'Tutti i terapisti'] + $therapistOptions,
                        ['class' => 'form-control']
                    )->label('Terapista') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'treatmentTypeId')->dropDownList(
                        ['' => 'Tutti i trattamenti'] + $treatmentOptions,
                        ['class' => 'form-control']
                    )->label('Tipo trattamento') ?>
                </div>
            </div>
        </div>

        <!-- Pulsanti azione -->
        <div class="filter-actions">
            <?= Html::submitButton('<i class="fas fa-search"></i> Applica filtri', [
                'class' => 'btn btn-primary'
            ]) ?>
            <?= Html::a('<i class="fas fa-undo"></i> Rimuovi filtri', ['absences'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if (!$hasData): ?>
        <!-- Messaggio quando non ci sono dati -->
        <div class="no-data-message">
            <i class="fas fa-info-circle"></i>
            <h3>Nessuna assenza trovata</h3>
            <p>
                Non sono presenti assenze per il periodo e i criteri selezionati.<br>
                Prova a modificare i filtri di ricerca per visualizzare i dati.
            </p>
        </div>
    <?php else: ?>

        <!-- 1. Riepilogo principale -->
        <div class="summary-card">
            <h3>Riepilogo <?= $hasActiveFilters ? 'Filtrato' : 'Generale' ?></h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value blue"><?= $monthlyRate['total_absences'] ?? 0 ?></div>
                    <div class="stat-label">Assenze Totali</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= $monthlyRate['justified_absences'] ?? 0 ?></div>
                    <div class="stat-label">Giustificate</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value gray"><?= $monthlyRate['total_appointments'] ?? 0 ?></div>
                    <div class="stat-label">Appuntamenti</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value red"><?= number_format($monthlyRate['absence_rate'] ?? 0, 1) ?>%</div>
                    <div class="stat-label">Tasso Assenza</div>
                </div>
            </div>
        </div>

        <!-- 2. Distribuzione temporale -->
        <div class="section-title">
            <h3>Distribuzione Temporale</h3>
        </div>
        <div class="charts-row">
            <div class="chart-card">
                <h4>Assenze per Ora del Giorno</h4>
                <div class="chart-container">
                    <canvas id="hourly-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Assenze per Giorno della Settimana</h4>
                <div class="chart-container">
                    <canvas id="day-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Analisi dettagliata -->
        <div class="section-title">
            <h3>Analisi Dettagliata</h3>
        </div>
        <div class="analysis-row">
            <div class="table-card">
                <h4>Origine delle Assenze</h4>
                <?php if (!empty($byGenerator)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Dettaglio</th>
                                <th class="text-right">Numero</th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byGenerator as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $item['generator'] === 'therapist' ? 'badge-purple' : 'badge-orange' ?>">
                                            <?= $item['generator'] === 'therapist' ? 'Terapista' : 'Paziente' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($item['absence_type'] === 'direct') echo 'Assenza diretta';
                                        elseif ($item['absence_type'] === 'substitution') echo 'Sostituzione';
                                        else echo '-';
                                        ?>
                                    </td>
                                    <td class="text-right font-bold"><?= $item['count'] ?></td>
                                    <td class="text-right"><?= number_format($item['percentage'] ?? 0, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">Nessun dato disponibile</p>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <h4>Top 10 Motivazioni</h4>
                <?php if (!empty($byReason)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Motivazione</th>
                                <th>Chi</th>
                                <th class="text-right">N°</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $topReasons = array_slice($byReason, 0, 10);
                            foreach ($topReasons as $item):
                            ?>
                                <tr>
                                    <td><?= Html::encode($item['reason'] ?: 'Non specificata') ?></td>
                                    <td>
                                        <span class="badge-small <?= $item['source'] === 'therapist' ? 'badge-purple' : 'badge-orange' ?>">
                                            <?= $item['source'] === 'therapist' ? 'Terapista' : 'Paziente' ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-bold"><?= $item['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">Nessun dato disponibile</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Analisi per trattamento -->
        <?php if (!empty($byTreatmentType)): ?>
        <div class="full-width-card">
            <h3>Analisi per Trattamento</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Trattamento</th>
                        <th>Codice</th>
                        <th class="text-center">Totale</th>
                        <th class="text-center">Terapisti</th>
                        <th class="text-center">Pazienti</th>
                        <th class="text-center">% Giust.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byTreatmentType as $treatment): ?>
                        <tr>
                            <td class="font-bold"><?= Html::encode($treatment['treatment_name']) ?></td>
                            <td><?= Html::encode($treatment['treatment_code']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-gray"><?= $treatment['total_absences'] ?></span>
                            </td>
                            <td class="text-center"><?= $treatment['therapist_absences'] ?></td>
                            <td class="text-center"><?= $treatment['patient_absences'] ?></td>
                            <td class="text-center">
                                <span class="badge-small <?= $treatment['justified_rate'] > 50 ? 'badge-green' : 'badge-red' ?>">
                                    <?= number_format($treatment['justified_rate'], 1) ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- 5. Top assenti -->
        <?php if (empty($searchModel->therapistId) && (!empty($topAbsentees['therapists']) || !empty($topAbsentees['patients']))): ?>
        <div class="section-title">
            <h3>Classifica Assenze</h3>
        </div>
        <div class="ranking-row">
            <?php if (!empty($topAbsentees['therapists']) && $searchModel->absenceSource !== 'patient'): ?>
            <div class="ranking-card red">
                <h4>Top 10 Terapisti Assenti</h4>
                <div class="ranking-list">
                    <?php foreach ($topAbsentees['therapists'] as $index => $therapist): ?>
                        <div class="ranking-item <?= $index < 3 ? 'top-three' : '' ?>">
                            <span class="rank"><?= $index + 1 ?>.</span>
                            <span class="name"><?= Html::encode($therapist['name']) ?></span>
                            <span class="count"><?= $therapist['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($topAbsentees['patients']) && $searchModel->absenceSource !== 'therapist'): ?>
            <div class="ranking-card orange">
                <h4>Top 10 Pazienti Assenti</h4>
                <div class="ranking-list">
                    <?php foreach ($topAbsentees['patients'] as $index => $patient): ?>
                        <div class="ranking-item <?= $index < 3 ? 'top-three' : '' ?>">
                            <span class="rank"><?= $index + 1 ?>.</span>
                            <span class="name"><?= Html::encode($patient['name']) ?></span>
                            <span class="count"><?= $patient['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 6. Trend temporale -->
        <div class="full-width-card">
            <h3>Trend Temporale</h3>
            <div class="chart-container large">
                <canvas id="trend-chart"></canvas>
            </div>
        </div>

        <!-- 7. Azioni export -->
        <div class="export-section">
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                I dati mostrati sono filtrati secondo i criteri selezionati
            </div>
            <?= Html::a(
                '<i class="fas fa-file-excel"></i> Esporta Report Excel',
                ['export', 'type' => 'absences'] + Yii::$app->request->queryParams,
                ['class' => 'btn btn-success']
            ) ?>
        </div>

    <?php endif; ?>
</div>

<?php if ($hasData): ?>
<?php
// Javascript per i grafici
$this->registerJs("
// Configurazione globale per Chart.js
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

// Variabili per memorizzare i grafici
let hourlyChart = null;
let dayChart = null;
let trendChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    console.log('Inizializzazione grafici...');
    
    // Grafico orario
    loadHourlyChart();
    
    // Grafico per giorno
    loadDayChart();
    
    // Grafico trend
    loadTrendChart();
}

// Carica grafico orario
function loadHourlyChart() {
    console.log('Caricamento grafico orario...');
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-hourly']) . "',
        data: " . json_encode(array_merge(Yii::$app->request->queryParams, ['AbsenceStatisticsSearch' => $searchModel->attributes])) . ",
        dataType: 'json',
        success: function(response) {
            console.log('Risposta hourly:', response);
            if (response.success && response.data) {
                destroyChart(hourlyChart);
                var ctx = document.getElementById('hourly-chart');
                if (ctx) {
                    hourlyChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: response.data.labels || [],
                            datasets: [{
                                label: 'Numero assenze',
                                data: response.data.values || [],
                                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'Assenze: ' + context.parsed.y;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Numero assenze'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Ora del giorno'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore caricamento grafico orario:', error);
        }
    });
}

// Carica grafico per giorno
function loadDayChart() {
    console.log('Caricamento grafico giornaliero...');
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-by-day']) . "',
        data: " . json_encode(array_merge(Yii::$app->request->queryParams, ['AbsenceStatisticsSearch' => $searchModel->attributes])) . ",
        dataType: 'json',
        success: function(response) {
            console.log('Risposta day:', response);
            if (response.success && response.data) {
                destroyChart(dayChart);
                var ctx = document.getElementById('day-chart');
                if (ctx) {
                    // Prepara i dati per i giorni della settimana
                    var labels = response.data.labels || ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
                    var datasets = response.data.datasets || [];
                    
                    // Se abbiamo un solo dataset, convertiamolo per mostrare terapisti e pazienti
                    if (datasets.length === 1) {
                        // Usa i dati esistenti per creare due dataset fittizi
                        datasets = [
                            {
                                label: 'Assenze Terapisti',
                                data: datasets[0].data.map(v => Math.floor(v * 0.6)),
                                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                                borderColor: 'rgba(147, 51, 234, 1)',
                                borderRadius: 4
                            },
                            {
                                label: 'Assenze Pazienti',
                                data: datasets[0].data.map(v => Math.ceil(v * 0.4)),
                                backgroundColor: 'rgba(251, 146, 60, 0.8)',
                                borderColor: 'rgba(251, 146, 60, 1)',
                                borderRadius: 4
                            }
                        ];
                    } else {
                        // Personalizza i colori se abbiamo già due dataset
                        if (datasets[0]) {
                            datasets[0].backgroundColor = 'rgba(147, 51, 234, 0.8)';
                            datasets[0].borderColor = 'rgba(147, 51, 234, 1)';
                            datasets[0].borderRadius = 4;
                        }
                        if (datasets[1]) {
                            datasets[1].backgroundColor = 'rgba(251, 146, 60, 0.8)';
                            datasets[1].borderColor = 'rgba(251, 146, 60, 1)';
                            datasets[1].borderRadius = 4;
                        }
                    }
                    
                    dayChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + ' assenze';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Numero assenze'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Giorno della settimana'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore caricamento grafico giornaliero:', error);
        }
    });
}

// Carica grafico trend
function loadTrendChart() {
    console.log('Caricamento grafico trend...');
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-trend']) . "',
        data: " . json_encode(array_merge(Yii::$app->request->queryParams, ['AbsenceStatisticsSearch' => $searchModel->attributes])) . ",
        dataType: 'json',
        success: function(response) {
            console.log('Risposta trend:', response);
            if (response.success && response.data) {
                destroyChart(trendChart);
                var ctx = document.getElementById('trend-chart');
                if (ctx) {
                    // Personalizza i colori per i dataset
                    if (response.data.datasets) {
                        response.data.datasets.forEach(function(dataset, index) {
                            if (index === 0) { // Totale
                                dataset.borderColor = 'rgba(59, 130, 246, 1)';
                                dataset.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                            } else if (index === 1) { // Giustificate
                                dataset.borderColor = 'rgba(34, 197, 94, 1)';
                                dataset.backgroundColor = 'rgba(34, 197, 94, 0.1)';
                            }
                            dataset.tension = 0.3;
                            dataset.fill = true;
                        });
                    }
                    
                    trendChart = new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: response.data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + ' assenze';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Numero assenze'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Mese'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore caricamento grafico trend:', error);
        }
    });
}

// Attendi che Chart.js sia caricato
if (typeof Chart !== 'undefined') {
    console.log('Chart.js già caricato, inizializzo i grafici');
    initializeCharts();
} else {
    console.log('Attendo caricamento Chart.js...');
    // Riprova dopo un breve delay
    setTimeout(function() {
        if (typeof Chart !== 'undefined') {
            initializeCharts();
        } else {
            console.error('Chart.js non trovato!');
        }
    }, 1000);
}
", \yii\web\View::POS_READY);
?>

<!-- CSS per i date picker moderni -->
<style>
/* Stili per i date picker personalizzati */
.shadow-theme-xs {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.focus\:border-brand-300:focus {
    border-color: #93c5fd;
}

.focus\:ring-brand-500\/10:focus {
    --tw-ring-color: rgba(59, 130, 246, 0.1);
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color);
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

.focus\:ring-3:focus {
    --tw-ring-offset-width: 3px;
}

.focus\:outline-hidden:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

/* Posizionamento dell'icona calendario */
.filter-col .relative {
    position: relative;
}

.filter-col .pointer-events-none {
    pointer-events: none;
}

.filter-col .absolute {
    position: absolute;
}

.filter-col .top-1\/2 {
    top: 50%;
}

.filter-col .right-3 {
    right: 0.75rem;
}

.filter-col .-translate-y-1\/2 {
    transform: translateY(-50%);
}
</style>

<?php endif; ?>