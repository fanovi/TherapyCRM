<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PlanStatisticsSearch */
/* @var $plansStats array */

$this->title = 'Statistiche Piani Terapeutici';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');

// Inizializza variabili se non definite
$plansStats = $plansStats ?? [
    'by_status' => [],
    'by_duration' => [],
    'completion_rates' => [],
    'expiring_list' => [],
    'monthly_trends' => []
];

// Funzione helper per verificare se ci sono filtri attivi
$hasActiveFilters = !empty($searchModel->dateFrom) || !empty($searchModel->dateTo) || 
                    !empty($searchModel->status) || !empty($searchModel->minDuration) || 
                    !empty($searchModel->maxDuration);

// Calcola statistiche di riepilogo
$activeCount = 0;
$completedCount = 0;
$suspendedCount = 0;
foreach ($plansStats['by_status'] as $status) {
    if ($status['status'] === 'active') $activeCount = $status['count'];
    elseif ($status['status'] === 'completed') $completedCount = $status['count'];
    elseif ($status['status'] === 'suspended') $suspendedCount = $status['count'];
}
$totalPlans = $activeCount + $completedCount + $suspendedCount;

// Calcola tasso medio di completamento
$avgCompletion = 0;
if (!empty($plansStats['completion_rates'])) {
    $avgCompletion = round(array_sum(array_column($plansStats['completion_rates'], 'completion_rate')) / count($plansStats['completion_rates']), 1);
}

// Helper per verificare se ci sono dati
$hasData = $totalPlans > 0 || !empty($plansStats['by_duration']) || !empty($plansStats['completion_rates']);
?>

<div class="statistics-plans">
    <!-- Header con titolo -->
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text">Analisi piani attivi, completati e in scadenza</p>
    </div>

    <!-- Filtri di ricerca -->
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
            'action' => Url::to(['plans']),
            'options' => ['class' => 'filter-form']
        ]); ?>

        <!-- Filtri stato e durata -->
        <div class="filter-section">
            <h4>Filtri principali</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Tutti gli stati',
                        'active' => 'Attivi',
                        'completed' => 'Completati'
                    ], ['class' => 'form-control'])->label('Stato piano') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'minDuration')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'class' => 'form-control',
                        'placeholder' => 'Min giorni'
                    ])->label('Durata minima (giorni)') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'maxDuration')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'class' => 'form-control',
                        'placeholder' => 'Max giorni'
                    ])->label('Durata massima (giorni)') ?>
                </div>
            </div>
        </div>

        <!-- Filtri temporali -->
        <div class="filter-section">
            <h4>Periodo di riferimento</h4>
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

        <!-- Pulsanti azione -->
        <div class="filter-actions">
            <?= Html::submitButton('<i class="fas fa-search"></i> Applica filtri', [
                'class' => 'btn btn-primary'
            ]) ?>
            <?= Html::a('<i class="fas fa-undo"></i> Rimuovi filtri', ['plans'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if (!$hasData): ?>
        <!-- Messaggio quando non ci sono dati -->
        <div class="no-data-message">
            <i class="fas fa-info-circle"></i>
            <h3>Nessun piano trovato</h3>
            <p>
                Non sono presenti piani per i criteri selezionati.<br>
                Prova a modificare i filtri di ricerca per visualizzare i dati.
            </p>
        </div>
    <?php else: ?>

        <!-- 1. Riepilogo principale -->
        <div class="summary-card">
            <h3>Riepilogo Piani</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value green"><?= $activeCount ?></div>
                    <div class="stat-label">Piani Attivi</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value blue"><?= $completedCount ?></div>
                    <div class="stat-label">Completati</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value orange"><?= count($plansStats['expiring_list']) ?></div>
                    <div class="stat-label">In Scadenza</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value gray"><?= $avgCompletion ?>%</div>
                    <div class="stat-label">Completamento Medio</div>
                </div>
            </div>
        </div>

        <!-- 2. Grafici distribuzione -->
        <div class="section-title">
            <h3>Distribuzione Piani</h3>
        </div>
        <div class="charts-row">
            <div class="chart-card">
                <h4>Distribuzione per Stato</h4>
                <div class="chart-container">
                    <canvas id="status-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Distribuzione per Durata</h4>
                <div class="chart-container">
                    <canvas id="duration-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Top piani per completamento -->
        <?php if (!empty($plansStats['completion_rates'])): ?>
        <div class="full-width-card">
            <h3>Top 10 Piani per Tasso di Completamento</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Piano ID</th>
                        <th>Paziente</th>
                        <th class="text-center">Appuntamenti</th>
                        <th class="text-center">Completati</th>
                        <th class="text-center">Tasso</th>
                        <th class="text-center">Stato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($plansStats['completion_rates'], 0, 10) as $plan): ?>
                        <tr>
                            <td>
                                <span class="badge badge-gray">#<?= $plan['id'] ?></span>
                            </td>
                            <td class="font-bold">
                                <?= Html::a(
                                    Html::encode($plan['patient_name']), 
                                    ['patient/view', 'id' => $plan['patient_id']], 
                                    ['class' => 'text-blue-600 hover:text-blue-800 font-medium']
                                ) ?>
                            </td>
                            <td class="text-center"><?= $plan['total_appointments'] ?></td>
                            <td class="text-center">
                                <span class="badge badge-green"><?= $plan['completed_appointments'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $rate = $plan['completion_rate'];
                                $badgeClass = $rate >= 80 ? 'badge-green' : ($rate >= 60 ? 'badge-orange' : 'badge-red');
                                ?>
                                <span class="badge-small <?= $badgeClass ?>">
                                    <?= $rate ?>%
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge-small badge-blue">Attivo</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- 4. Statistiche durata -->
        <?php if (!empty($plansStats['by_duration'])): ?>
        <div class="section-title">
            <h3>Analisi per Durata</h3>
        </div>
        <div class="analysis-row">
            <?php foreach ($plansStats['by_duration'] as $duration): ?>
            <div class="table-card">
                <h4>
                    <?php
                    switch($duration['duration_category']) {
                        case 'short': echo 'Piani Brevi'; break;
                        case 'medium': echo 'Piani Medi'; break;
                        case 'long': echo 'Piani Lunghi'; break;
                        default: echo Html::encode($duration['duration_category']);
                    }
                    ?>
                </h4>
                <div class="duration-stats">
                    <div class="duration-item">
                        <span class="duration-label">Numero piani:</span>
                        <span class="duration-value"><?= $duration['count'] ?></span>
                    </div>
                    <div class="duration-item">
                        <span class="duration-label">Durata media:</span>
                        <span class="duration-value"><?= round($duration['avg_duration']) ?> giorni</span>
                    </div>
                    <div class="duration-item">
                        <span class="duration-label">Range:</span>
                        <span class="duration-value">
                            <?php
                            switch($duration['duration_category']) {
                                case 'short': echo '< 90 giorni'; break;
                                case 'medium': echo '90-365 giorni'; break;
                                case 'long': echo '> 365 giorni'; break;
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 5. Piani in scadenza -->
        <?php if (!empty($plansStats['expiring_list'])): ?>
        <div class="full-width-card warning">
            <h3><i class="fas fa-exclamation-triangle"></i> Piani in Scadenza (Prossimi 60 Giorni)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Piano ID</th>
                        <th>Paziente</th>
                        <th>Data Scadenza</th>
                        <th class="text-center">Giorni Rimanenti</th>
                        <th class="text-center">Urgenza</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plansStats['expiring_list'] as $plan): ?>
                        <?php
                        $daysLeft = $plan['days_until_expiry'];
                        $urgencyClass = $daysLeft <= 7 ? 'badge-red' : ($daysLeft <= 30 ? 'badge-orange' : 'badge-yellow');
                        $urgencyText = $daysLeft <= 7 ? 'Critica' : ($daysLeft <= 30 ? 'Alta' : 'Media');
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-gray">#<?= $plan['id'] ?></span>
                            </td>
                            <td class="font-bold">
                                <?= Html::a(
                                    Html::encode($plan['patient_name']), 
                                    ['patient/view', 'id' => $plan['patient_id']], 
                                    ['class' => 'text-blue-600 hover:text-blue-800 font-medium']
                                ) ?>
                            </td>
                            <td><?= Yii::$app->formatter->asDate($plan['end_date'], 'dd/MM/yyyy') ?></td>
                            <td class="text-center">
                                <span class="badge <?= $urgencyClass ?>"><?= $daysLeft ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge-small <?= $urgencyClass ?>"><?= $urgencyText ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="warning-message">
                <i class="fas fa-info-circle"></i>
                I piani in scadenza necessitano di attenzione. Considerare il rinnovo o la conclusione dei trattamenti.
            </div>
        </div>
        <?php endif; ?>

        <!-- 6. Trend mensile -->
        <div class="full-width-card">
            <h3>Trend Creazione Piani (Ultimi 12 Mesi)</h3>
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
                ['export', 'type' => 'plans'] + Yii::$app->request->queryParams,
                ['class' => 'btn btn-success']
            ) ?>
        </div>

    <?php endif; ?>
</div>

<!-- CSS aggiuntivo per questa view -->
<style>
/* Stili specifici per le statistiche durata */
.duration-stats {
    padding: 16px 0;
}

.duration-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.duration-item:last-child {
    border-bottom: none;
}

.duration-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.duration-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

/* Card di avviso */
.full-width-card.warning {
    border-color: #fbbf24;
    background-color: #fffbeb;
}

.full-width-card.warning h3 {
    color: #92400e;
}

.warning-message {
    margin-top: 20px;
    padding: 16px;
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    color: #92400e;
    font-size: 0.875rem;
}

.warning-message i {
    margin-right: 8px;
}

/* Badge giallo */
.badge-yellow {
    background-color: #fef3c7;
    color: #92400e;
}

.badge-small.badge-yellow {
    background-color: #fef3c7;
    color: #92400e;
}

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

<?php if ($hasData): ?>
<?php
// Javascript per i grafici
$this->registerJs("
// Configurazione globale per Chart.js
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

// Variabili per memorizzare i grafici
let statusChart = null;
let durationChart = null;
let trendChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    console.log('Inizializzazione grafici piani...');
    
    // Grafico stato
    loadStatusChart();
    
    // Grafico durata
    loadDurationChart();
    
    // Grafico trend
    loadTrendChart();
}

// Carica grafico stato
function loadStatusChart() {
    console.log('Caricamento grafico stato...');
    
    var statusData = " . json_encode($plansStats['by_status'] ?? []) . ";
    
    if (statusData && statusData.length > 0) {
        destroyChart(statusChart);
        var ctx = document.getElementById('status-chart');
        if (ctx) {
            statusChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: statusData.map(function(s) { 
                        switch(s.status) {
                            case 'active': return 'Attivi';
                            case 'completed': return 'Completati';
                            case 'suspended': return 'Sospesi';
                            default: return s.status;
                        }
                    }),
                    datasets: [{
                        label: 'Piani',
                        data: statusData.map(function(s) { return s.count; }),
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(249, 115, 22, 0.8)'
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(249, 115, 22, 1)'
                        ],
                        borderWidth: 1
                    }]
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
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}

// Carica grafico durata
function loadDurationChart() {
    console.log('Caricamento grafico durata...');
    
    var durationData = " . json_encode($plansStats['by_duration'] ?? []) . ";
    
    if (durationData && durationData.length > 0) {
        destroyChart(durationChart);
        var ctx = document.getElementById('duration-chart');
        if (ctx) {
            durationChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: durationData.map(function(d) { 
                        switch(d.duration_category) {
                            case 'short': return 'Breve (<90gg)';
                            case 'medium': return 'Medio (90-365gg)';
                            case 'long': return 'Lungo (>365gg)';
                            default: return d.duration_category;
                        }
                    }),
                    datasets: [{
                        label: 'Numero piani',
                        data: durationData.map(function(d) { return d.count; }),
                        backgroundColor: [
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(54, 185, 204, 0.8)',
                            'rgba(231, 74, 59, 0.8)'
                        ],
                        borderColor: [
                            'rgba(251, 191, 36, 1)',
                            'rgba(54, 185, 204, 1)',
                            'rgba(231, 74, 59, 1)'
                        ],
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
                                text: 'Numero piani'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Categoria durata'
                            }
                        }
                    }
                }
            });
        }
    }
}

// Carica grafico trend
function loadTrendChart() {
    console.log('Caricamento grafico trend...');
    
    var trendData = " . json_encode($plansStats['monthly_trends'] ?? []) . ";
    
    if (trendData && trendData.length > 0) {
        destroyChart(trendChart);
        var ctx = document.getElementById('trend-chart');
        if (ctx) {
            trendChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendData.map(function(t) { return t.month; }),
                    datasets: [{
                        label: 'Nuovi piani',
                        data: trendData.map(function(t) { return t.count; }),
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
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
                                text: 'Numero piani'
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
<?php endif; ?>