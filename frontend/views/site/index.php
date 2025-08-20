<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $totalPatients int */
/* @var $newPatientsThisMonth int */
/* @var $patientsGrowthPercentage float */
/* @var $totalTherapists int */
/* @var $newTherapistsThisMonth int */
/* @var $totalAppointmentsToday int */
/* @var $completedAppointmentsToday int */
/* @var $upcomingAppointments array */
/* @var $pendingDocumentRequests int */
/* @var $completedDocumentRequestsThisMonth int */
/* @var $requestsGrowthPercentage float */
/* @var $activeTherapeuticPlans int */
/* @var $unreadNotifications int */
/* @var $dailyAppointments array */
/* @var $dayLabels array */
/* @var $requestsData array */
/* @var $hasRealRequestsData bool */

$this->title = 'Dashboard';
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js per i grafici
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');

// Calcola percentuale completamento appuntamenti
$appointmentCompletionRate = $totalAppointmentsToday > 0 
    ? round(($completedAppointmentsToday / $totalAppointmentsToday) * 100) 
    : 0;
?>

<div class="dashboard-index">
    <!-- Header con titolo -->
   
<!-- CSS aggiuntivo per questa view -->
<style>
/* Stat change indicators */
.stat-change {
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 4px;
}

.stat-change.positive {
    color: #22c55e;
}

.stat-change.negative {
    color: #ef4444;
}

/* Progress bar in stat box */
.stat-progress {
    margin-top: 8px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background-color: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #3b82f6;
    transition: width 0.3s ease;
}

.progress-text {
    display: block;
    margin-top: 4px;
    font-size: 0.75rem;
    color: #6b7280;
    text-align: right;
}

/* System stats */
.system-stats {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.system-stat-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.system-stat-icon {
    width: 48px;
    height: 48px;
    background: #e5e7eb;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.system-stat-icon i {
    font-size: 1.25rem;
    color: #374151;
}

.system-stat-content {
    flex: 1;
}

.system-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}

.system-stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 2px;
}

/* New patients display */
.new-patients-display {
    text-align: center;
    padding: 32px 16px;
    background: #f0f9ff;
    border-radius: 8px;
}

.new-patients-number {
    font-size: 3.5rem;
    font-weight: 700;
    color: #3b82f6;
    line-height: 1;
}

.new-patients-label {
    font-size: 1.125rem;
    color: #4b5563;
    margin-top: 8px;
}

.new-patients-growth {
    font-size: 0.875rem;
    margin-top: 8px;
}

.new-patients-growth.positive {
    color: #22c55e;
}

.new-patients-growth.negative {
    color: #ef4444;
}

/* Patient info in table */
.patient-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.patient-avatar {
    width: 40px;
    height: 40px;
    background: #dbeafe;
    color: #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Card header with action */
.card-header-with-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-header-with-action h3 {
    margin: 0;
}

/* Small button */
.btn-sm {
    padding: 6px 12px;
    font-size: 0.875rem;
}

/* Quick actions */
.quick-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* Text gray utility */
.text-gray {
    color: #6b7280;
}

/* Chart card large variant */
.chart-card.large {
    grid-column: span 2;
}

/* Info button style */
.btn-info {
    background-color: #06b6d4;
    border-color: #06b6d4;
    color: white;
}

.btn-info:hover {
    background-color: #0891b2;
    border-color: #0891b2;
}

/* Empty state */
.no-data {
    text-align: center;
    padding: 48px;
    color: #6b7280;
}

.no-data i {
    font-size: 3rem;
    margin-bottom: 16px;
    display: block;
    opacity: 0.3;
}

.no-data p {
    font-size: 1rem;
    margin: 0;
}
</style>

<?php
// Javascript per i grafici
$this->registerJs("
// Configurazione globale per Chart.js
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

// Variabili per memorizzare i grafici
let appointmentsChart = null;
let requestsChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    
    // Grafico appuntamenti
    loadAppointmentsChart();
    
    // Grafico richieste (se ci sono dati)
    " . ($hasRealRequestsData ? "loadRequestsChart();" : "") . "
}

// Carica grafico appuntamenti
function loadAppointmentsChart() {
    
    var dailyData = " . json_encode($dailyAppointments) . ";
    var dayLabels = " . json_encode($dayLabels) . ";
    
    destroyChart(appointmentsChart);
    var ctx = document.getElementById('appointments-chart');
    if (ctx) {
        appointmentsChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: dayLabels,
                datasets: [{
                    label: 'Appuntamenti',
                    data: dailyData,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
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
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        cornerRadius: 4,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Appuntamenti: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6b7280'
                        },
                        grid: {
                            color: '#f3f4f6',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6b7280'
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }
}

" . ($hasRealRequestsData ? "
// Carica grafico richieste
function loadRequestsChart() {
    
    var requestsData = " . json_encode($requestsData) . ";
    
    if (requestsData && requestsData.length > 0) {
        destroyChart(requestsChart);
        var ctx = document.getElementById('requests-chart');
        if (ctx) {
            // Prepara i dati
            var labels = requestsData.map(item => item.status_name || 'Sconosciuto');
            var data = requestsData.map(item => parseInt(item.count));
            
            // Colori per stato
            var colorMap = {
                'Inviata': 'rgba(59, 130, 246, 0.8)',
                'Presa in carico': 'rgba(251, 146, 60, 0.8)',
                'Stampato': 'rgba(139, 92, 246, 0.8)',
                'Consegnato': 'rgba(34, 197, 94, 0.8)',
                'Richieste Presenti': 'rgba(107, 114, 128, 0.8)'
            };
            
            var backgroundColors = labels.map(label => colorMap[label] || 'rgba(107, 114, 128, 0.8)');
            var borderColors = backgroundColors.map(color => color.replace('0.8', '1'));
            
            requestsChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Richieste',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
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
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
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
" : "") . "

// Attendi che Chart.js sia caricato
if (typeof Chart !== 'undefined') {
    initializeCharts();
} else {
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

    <!-- 1. Riepilogo principale -->
    <div class="summary-card">
        <h3>Riepilogo Generale</h3>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value blue"><?= number_format($totalPatients) ?></div>
                <div class="stat-label">Pazienti Totali</div>
                <?php if ($patientsGrowthPercentage != 0): ?>
                    <div class="stat-change <?= $patientsGrowthPercentage >= 0 ? 'positive' : 'negative' ?>">
                        <?= $patientsGrowthPercentage >= 0 ? '+' : '' ?><?= $patientsGrowthPercentage ?>%
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-box">
                <div class="stat-value orange"><?= $pendingDocumentRequests ?></div>
                <div class="stat-label">Richieste Pendenti</div>
                <?php if ($requestsGrowthPercentage != 0): ?>
                    <div class="stat-change <?= $requestsGrowthPercentage >= 0 ? 'positive' : 'negative' ?>">
                        <?= $requestsGrowthPercentage >= 0 ? '+' : '' ?><?= $requestsGrowthPercentage ?>%
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-box">
                <div class="stat-value green"><?= $completedAppointmentsToday ?>/<?= $totalAppointmentsToday ?></div>
                <div class="stat-label">Appuntamenti Oggi</div>
                <div class="stat-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $appointmentCompletionRate ?>%"></div>
                    </div>
                    <span class="progress-text"><?= $appointmentCompletionRate ?>%</span>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-value gray"><?= $newPatientsThisMonth ?></div>
                <div class="stat-label">Nuovi Questo Mese</div>
            </div>
        </div>
    </div>

    <!-- 2. Grafici principali -->
    <div class="section-title">
        <h3>Andamento e Distribuzione</h3>
    </div>
    <div class="charts-row">
        <div class="chart-card <?= !$hasRealRequestsData ? 'large' : '' ?>">
            <h4>Appuntamenti Ultimi 7 Giorni</h4>
            <div class="chart-container">
                <canvas id="appointments-chart"></canvas>
            </div>
        </div>
        <?php if ($hasRealRequestsData): ?>
        <div class="chart-card">
            <h4>Stato Richieste Documenti</h4>
            <div class="chart-container">
                <canvas id="requests-chart"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3. Statistiche sistema -->
    <div class="section-title">
        <h3>Statistiche Sistema</h3>
    </div>
    <div class="analysis-row">
        <div class="table-card">
            <h4>Risorse Attive</h4>
            <div class="system-stats">
                <div class="system-stat-item">
                    <div class="system-stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="system-stat-content">
                        <div class="system-stat-value"><?= $totalTherapists ?></div>
                        <div class="system-stat-label">Terapisti Attivi</div>
                    </div>
                    <span class="badge badge-green">Attivo</span>
                </div>
                <div class="system-stat-item">
                    <div class="system-stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="system-stat-content">
                        <div class="system-stat-value"><?= $activeTherapeuticPlans ?></div>
                        <div class="system-stat-label">Piani Terapeutici</div>
                    </div>
                    <span class="badge badge-blue">In corso</span>
                </div>
                <div class="system-stat-item">
                    <div class="system-stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="system-stat-content">
                        <div class="system-stat-value"><?= $unreadNotifications ?></div>
                        <div class="system-stat-label">Notifiche</div>
                    </div>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="badge badge-orange">Da leggere</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Nessuna</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="table-card">
            <h4>Nuovi Pazienti</h4>
            <div class="new-patients-display">
                <div class="new-patients-number"><?= $newPatientsThisMonth ?></div>
                <div class="new-patients-label">Nuovi pazienti questo mese</div>
                <?php if ($patientsGrowthPercentage != 0): ?>
                    <div class="new-patients-growth <?= $patientsGrowthPercentage >= 0 ? 'positive' : 'negative' ?>">
                        <?= $patientsGrowthPercentage >= 0 ? '+' : '' ?><?= $patientsGrowthPercentage ?>% rispetto al mese scorso
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4. Prossimi appuntamenti -->
    <div class="full-width-card">
        <div class="card-header-with-action">
            <h3>Prossimi Appuntamenti</h3>
            <a href="<?= Url::to(['/appointment/index']) ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-calendar"></i> Vedi tutti
            </a>
        </div>
        
        <?php if (empty($upcomingAppointments)): ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <p>Nessun appuntamento programmato</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paziente</th>
                        <th>Terapista</th>
                        <th>Data e Ora</th>
                        <th class="text-center">Stato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingAppointments as $appointment): ?>
                        <tr>
                            <td>
                                <div class="patient-info">
                                    <div class="patient-avatar">
                                        <?php if ($appointment->patient && $appointment->patient->first_name && $appointment->patient->last_name): ?>
                                            <?= substr($appointment->patient->first_name, 0, 1) . substr($appointment->patient->last_name, 0, 1) ?>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="font-bold">
                                            <?= $appointment->patient ? Html::encode($appointment->patient->getFullName()) : 'Paziente non trovato' ?>
                                        </div>
                                        <div class="text-sm text-gray">
                                            ID: <?= $appointment->patient ? $appointment->patient->id : 'N/A' ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($appointment->therapist && $appointment->therapist->user && $appointment->therapist->user->profile): ?>
                                    <?= Html::encode($appointment->therapist->user->profile->first_name . ' ' . $appointment->therapist->user->profile->last_name) ?>
                                <?php else: ?>
                                    <span class="text-gray">Terapista non assegnato</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-clock text-gray"></i>
                                <?= Yii::$app->formatter->asDatetime($appointment->appointment_datetime, 'dd/MM/yyyy HH:mm') ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-orange">Programmato</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- 5. Azioni rapide -->
    <div class="export-section">
        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            Dashboard aggiornata in tempo reale
        </div>
        <div class="quick-actions">
            <?= Html::a(
                '<i class="fas fa-user-plus"></i> Nuovo Paziente',
                ['/patient/create'],
                ['class' => 'btn btn-primary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-calendar-plus"></i> Nuovo Appuntamento',
                ['/appointment/create'],
                ['class' => 'btn btn-success']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-chart-bar"></i> Statistiche',
                ['/statistics/index'],
                ['class' => 'btn btn-info']
            ) ?>
        </div>
    </div>
</div>