<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $summary array */
/* @var $topTreatments array */
/* @var $patientGrowth array */

$this->title = 'Dashboard Statistiche';
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');
// Inizializza variabili se non definite
$summary = $summary ?? [
    'patients' => ['active' => 0, 'total' => 0, 'new_this_month' => 0, 'multi_treatment' => 0],
    'absences' => ['total_this_month' => 0, 'justified_this_month' => 0, 'with_recovery' => 0, 'unjustified_rate' => 0],
    'treatments' => ['active_types' => 0, 'total_weekly_hours' => 0, 'top_treatment' => 'N/A'],
    'plans' => ['total' => 0, 'active' => 0, 'expiring_soon' => 0, 'new_this_month' => 0]
];
$topTreatments = $topTreatments ?? [];
$patientGrowth = $patientGrowth ?? [];
?>

<div class="statistics-dashboard">
    <!-- Header con titolo -->
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text">Panoramica generale del sistema</p>
    </div>

    <?php if (isset($error)): ?>
        <!-- Messaggio di errore -->
        <div class="no-data-message">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Errore nel caricamento dati</h3>
            <p><?= Html::encode($error) ?></p>
        </div>
    <?php else: ?>

        <!-- 1. Riepilogo principale KPI -->
        <div class="summary-card">
            <h3>Riepilogo Sistema</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value blue"><?= $summary['patients']['active'] ?? 0 ?></div>
                    <div class="stat-label">Pazienti Attivi</div>
                    <small>Totale: <?= $summary['patients']['total'] ?? 0 ?></small>
                </div>
                <div class="stat-box">
                    <div class="stat-value red"><?= $summary['absences']['total_this_month'] ?? 0 ?></div>
                    <div class="stat-label">Assenze Questo Mese</div>
                    <small>Non giustificate: <?= $summary['absences']['unjustified_rate'] ?? 0 ?>%</small>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= $summary['treatments']['active_types'] ?? 0 ?></div>
                    <div class="stat-label">Trattamenti Attivi</div>
                    <small>Ore settimanali: <?= $summary['treatments']['total_weekly_hours'] ?? 0 ?></small>
                </div>
                <div class="stat-box">
                    <div class="stat-value orange"><?= $summary['plans']['expiring_soon'] ?? 0 ?></div>
                    <div class="stat-label">Piani in Scadenza</div>
                    <small>Piani attivi: <?= $summary['plans']['active'] ?? 0 ?></small>
                </div>
            </div>
        </div>

        <!-- 2. Grafici principali -->
        <div class="section-title">
            <h3>Analisi Temporali</h3>
        </div>
        <div class="analysis-row">
            <div class="table-card" style="flex: 2;">
                <h4>Crescita Pazienti (Ultimi 6 Mesi)</h4>
                <div class="chart-container">
                    <canvas id="growth-chart"></canvas>
                </div>
            </div>
            <div class="table-card">
                <h4>Top 5 Trattamenti</h4>
                <div class="chart-container">
                    <canvas id="treatments-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Grafici secondari -->
        <div class="charts-row">
            <div class="chart-card">
                <h4>Trend Assenze per Giorno</h4>
                <div class="chart-container">
                    <canvas id="absence-day-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Distribuzione Età Pazienti</h4>
                <div class="chart-container">
                    <canvas id="age-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 4. Quick Links con stesso stile ranking cards -->
        <div class="section-title">
            <h3>Accesso Rapido alle Analisi</h3>
        </div>
        <div class="ranking-row">
            <div class="ranking-card" style="background: #ebf8ff; border-color: #90cdf4;">
                <h4 style="color: #1e40af;">Analisi Assenze</h4>
                <div class="text-center" style="padding: 20px;">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: #3b82f6; margin-bottom: 16px; display: block;"></i>
                    <p style="margin-bottom: 20px; color: #4b5563;">Analisi dettagliata pattern assenze</p>
                    <?= Html::a('Vai all\'analisi <i class="fas fa-arrow-right"></i>', ['absences'], [
                        'class' => 'btn btn-primary',
                        'style' => 'display: inline-block;'
                    ]) ?>
                </div>
            </div>

            <div class="ranking-card" style="background: #f0fdf4; border-color: #86efac;">
                <h4 style="color: #166534;">Analisi Pazienti</h4>
                <div class="text-center" style="padding: 20px;">
                    <i class="fas fa-users" style="font-size: 3rem; color: #10b981; margin-bottom: 16px; display: block;"></i>
                    <p style="margin-bottom: 20px; color: #4b5563;">Demografia e trattamenti multipli</p>
                    <?= Html::a('Vai all\'analisi <i class="fas fa-arrow-right"></i>', ['patients'], [
                        'class' => 'btn btn-success'
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="ranking-row">
            <div class="ranking-card" style="background: #f3e8ff; border-color: #c084fc;">
                <h4 style="color: #6b21a8;">Analisi Trattamenti</h4>
                <div class="text-center" style="padding: 20px;">
                    <i class="fas fa-stethoscope" style="font-size: 3rem; color: #8b5cf6; margin-bottom: 16px; display: block;"></i>
                    <p style="margin-bottom: 20px; color: #4b5563;">Ranking e combinazioni</p>
                    <?= Html::a('Vai all\'analisi <i class="fas fa-arrow-right"></i>', ['treatments'], [
                        'class' => 'btn btn-secondary',
                        'style' => 'background: #8b5cf6; color: white; border-color: #8b5cf6;'
                    ]) ?>
                </div>
            </div>

            <div class="ranking-card orange">
                <h4>Analisi Piani</h4>
                <div class="text-center" style="padding: 20px;">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #ea580c; margin-bottom: 16px; display: block;"></i>
                    <p style="margin-bottom: 20px; color: #4b5563;">Stati e scadenze</p>
                    <?= Html::a('Vai all\'analisi <i class="fas fa-arrow-right"></i>', ['plans'], [
                        'class' => 'btn',
                        'style' => 'background: #f97316; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;'
                    ]) ?>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Stili aggiuntivi minimi solo per elementi specifici dashboard -->
<style>
.stat-box small {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

.stat-value.orange {
    color: #f97316;
}

.analysis-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 1024px) {
    .analysis-row {
        flex-direction: column;
    }
}

.text-center {
    text-align: center;
}
</style>

<?php
// Javascript per i grafici
$this->registerJs("
// Configurazione globale per Chart.js
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

// Variabili per memorizzare i grafici
let growthChart = null;
let treatmentsChart = null;
let absenceDayChart = null;
let ageChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    
    // Grafico crescita pazienti
    loadGrowthChart();
    
    // Grafico top trattamenti
    loadTreatmentsChart();
    
    // Carica grafici con dati AJAX
    loadAbsenceDayChart();
    loadAgeChart();
}

// Grafico crescita pazienti
function loadGrowthChart() {
    var growthData = " . json_encode($patientGrowth) . ";
    
    if (growthData && growthData.length > 0) {
        destroyChart(growthChart);
        var ctx = document.getElementById('growth-chart');
        if (ctx) {
            growthChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: growthData.map(function(d) { return d.month || ''; }),
                    datasets: [{
                        label: 'Nuovi Pazienti',
                        data: growthData.map(function(d) { return d.new_patients || 0; }),
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
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
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
}

// Grafico top trattamenti
function loadTreatmentsChart() {
    var treatmentsData = " . json_encode(array_slice($topTreatments, 0, 5)) . ";
    
    if (treatmentsData && treatmentsData.length > 0) {
        destroyChart(treatmentsChart);
        var ctx = document.getElementById('treatments-chart');
        if (ctx) {
            treatmentsChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: treatmentsData.map(function(t) { return t.name || ''; }),
                    datasets: [{
                        label: 'Pazienti',
                        data: treatmentsData.map(function(t) { return t.patient_count || 0; }),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(147, 51, 234, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ]
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
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}

// Grafico assenze per giorno (AJAX)
function loadAbsenceDayChart() {
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-by-day']) . "',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(absenceDayChart);
                var ctx = document.getElementById('absence-day-chart');
                if (ctx) {
                    // Usa solo il primo dataset o crea uno aggregato
                    var datasets = response.data.datasets || [];
                    var data = [];
                    
                    if (datasets.length > 0) {
                        data = datasets[0].data;
                    }
                    
                    absenceDayChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: response.data.labels || [],
                            datasets: [{
                                label: 'Assenze totali',
                                data: data,
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
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    });
}

// Grafico età pazienti (AJAX)
function loadAgeChart() {
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'patient-age-groups']) . "',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(ageChart);
                var ctx = document.getElementById('age-chart');
                if (ctx) {
                    ageChart = new Chart(ctx.getContext('2d'), {
                        type: 'pie',
                        data: response.data,
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
                                            size: 11
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    });
}

// Inizializza quando Chart.js è pronto
if (typeof Chart !== 'undefined') {
    initializeCharts();
} else {
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