<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $summary array */
/* @var $topTreatments array */
/* @var $patientGrowth array */

$this->title = 'Dashboard Statistiche';
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
// appendTimestamp: dopo un deploy il browser non deve usare CSS/JS vecchi dalla cache.
$this->registerCssFile('@web/css/statistics.css', ['appendTimestamp' => true]);
$this->registerJsFile('@web/js/drilldown-panel.js', ['appendTimestamp' => true]);
// Inizializza variabili se non definite
$summary = $summary ?? [
    'patients' => ['active' => 0, 'total' => 0, 'new_this_month' => 0, 'multi_treatment' => 0],
    'attendance' => ['present' => 0, 'absent' => 0, 'justified' => 0],
    'plans' => ['total' => 0, 'active' => 0, 'expiring_soon' => 0, 'new_this_month' => 0]
];
$attendance = $summary['attendance'] ?? ['present' => 0, 'absent' => 0, 'justified' => 0];
$attendanceTotal = $attendance['present'] + $attendance['absent'];
$attendanceRate = $attendanceTotal > 0 ? (int) round($attendance['present'] / $attendanceTotal * 100) : null;
$monthLabel = Yii::$app->formatter->asDate(date('Y-m-01'), 'LLLL yyyy');

$this->registerJs('DrilldownPanel.init(Object.assign(' . Json::htmlEncode([
    'toggle' => '#active-patients-toggle',
    'panel' => '#active-patients-panel',
    'treeUrl' => Url::to(['statistics/active-patients-breakdown']),
    'listUrl' => Url::to(['statistics/active-patients-list']),
    'emptyTreeText' => 'Nessun paziente attivo nel mese.',
    'columns' => [
        ['key' => 'name', 'label' => 'Paziente', 'link' => 'url'],
        ['key' => 'plan', 'label' => 'Piano'],
        ['key' => 'end', 'label' => 'Scadenza'],
        ['key' => 'hours', 'label' => 'Ore/sett.', 'align' => 'right'],
    ],
]) . ', {
    summary: function (data) {
        return "Mese di " + data.period + " · " + data.total + (data.total === 1 ? " paziente" : " pazienti") + " · clic su un trattamento per l\'elenco pazienti.";
    },
    listTitle: function (path, node) {
        return path.join(" › ") + " · " + node.count + (node.count === 1 ? " paziente" : " pazienti");
    }
}));', \yii\web\View::POS_READY);
$this->registerJs('DrilldownPanel.init(Object.assign(' . Json::htmlEncode([
    'toggle' => '#attendance-toggle',
    'panel' => '#attendance-panel',
    'treeUrl' => Url::to(['statistics/attendance-breakdown']),
    'listUrl' => Url::to(['statistics/attendance-list']),
    'emptyTreeText' => 'Nessuna presenza o assenza nel mese.',
    'emptyListText' => 'Nessun appuntamento.',
    'columns' => [
        ['key' => 'date', 'label' => 'Data'],
        ['key' => 'time', 'label' => 'Ora'],
        ['key' => 'patient', 'label' => 'Paziente', 'link' => 'url'],
        ['key' => 'therapist', 'label' => 'Terapista'],
        ['key' => 'status', 'label' => 'Stato'],
    ],
]) . ', {
    summary: function (data) {
        return "Mese di " + data.period + " fino a oggi · " + data.total + (data.total === 1 ? " appuntamento" : " appuntamenti") + " · clic su un trattamento per l\'elenco.";
    },
    listTitle: function (path, node) {
        return path.join(" › ") + " · " + node.counts[0] + (node.counts[0] === 1 ? " presenza" : " presenze") + ", " + node.counts[1] + (node.counts[1] === 1 ? " assenza" : " assenze");
    }
}));', \yii\web\View::POS_READY);
$topTreatments = $topTreatments ?? [];
$patientGrowth = $patientGrowth ?? [];
?>

<div class="mx-auto max-w-4xl p-4 md:p-6 statistics-dashboard">
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
            <div class="stats-grid stats-grid-3">
                <button type="button" class="stat-box stat-box-toggle" id="active-patients-toggle" aria-expanded="false" aria-controls="active-patients-panel">
                    <div class="stat-value blue"><?= $summary['patients']['active'] ?? 0 ?></div>
                    <div class="stat-label">Pazienti Attivi</div>
                    <div class="stat-period">Mese di <?= Html::encode($monthLabel) ?> · piano nel mese</div>
                    <small>Totale anagrafica: <?= $summary['patients']['total'] ?? 0 ?></small>
                    <div class="stat-toggle-hint">
                        <span data-role="hint">Mostra dettaglio</span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </div>
                </button>
                <button type="button" class="stat-box stat-box-toggle" id="attendance-toggle" aria-expanded="false" aria-controls="attendance-panel">
                    <div class="stat-value"><span class="green"><?= (int) $attendance['present'] ?></span> / <span class="red"><?= (int) $attendance['absent'] ?></span></div>
                    <div class="stat-label">Presenze / Assenze</div>
                    <div class="stat-period">Mese di <?= Html::encode($monthLabel) ?> fino a oggi · assenze dei pazienti</div>
                    <small>
                        <?= $attendanceRate !== null ? 'Presenza ' . $attendanceRate . '%' : 'Nessun appuntamento' ?>
                        <?php if ($attendance['absent'] > 0): ?>
                            · <?= (int) $attendance['justified'] ?> <?= $attendance['justified'] == 1 ? 'assenza giustificata' : 'assenze giustificate' ?>
                        <?php endif; ?>
                    </small>
                    <div class="stat-toggle-hint">
                        <span data-role="hint">Mostra dettaglio</span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </div>
                </button>
                <a class="stat-box stat-box-toggle" href="<?= Url::to(['plans']) ?>">
                    <div class="stat-value orange"><?= $summary['plans']['expiring_soon'] ?? 0 ?></div>
                    <div class="stat-label">Piani in Scadenza</div>
                    <div class="stat-period">Prossimi 30 giorni</div>
                    <small>Piani attivi: <?= $summary['plans']['active'] ?? 0 ?></small>
                    <div class="stat-toggle-hint">
                        <span>Vai all'analisi piani</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </div>
                </a>
            </div>

            <section id="active-patients-panel" class="drilldown-panel" aria-label="Dettaglio pazienti attivi" hidden>
                <div class="drilldown-header">
                    <div>
                        <h4>Pazienti attivi per regime</h4>
                        <p data-role="summary">Regime → setting → trattamento</p>
                    </div>
                    <div class="drilldown-actions">
                        <button type="button" class="btn btn-secondary btn-sm" data-action="expand-all">Espandi tutto</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-action="collapse-all">Comprimi</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-action="close" aria-label="Chiudi dettaglio">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="drilldown-note">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <span>Pazienti con un piano il cui periodo tocca il mese, in qualunque stato tranne bozza e in attesa (per i piani interrotti conta la data di interruzione). Ogni riga conta pazienti distinti: un paziente con più piani o più terapie compare in più righe, quindi le somme possono superare il totale.</span>
                </div>
                <div class="drilldown-head">
                    <span></span>
                    <span>Regime / setting / trattamento</span>
                    <span class="text-right">Pazienti</span>
                </div>
                <div data-role="body"></div>
            </section>

            <section id="attendance-panel" class="drilldown-panel has-split-counts" aria-label="Dettaglio presenze e assenze" hidden>
                <div class="drilldown-header">
                    <div>
                        <h4>Presenze e assenze per regime</h4>
                        <p data-role="summary">Regime → setting → trattamento</p>
                    </div>
                    <div class="drilldown-actions">
                        <button type="button" class="btn btn-secondary btn-sm" data-action="expand-all">Espandi tutto</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-action="collapse-all">Comprimi</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-action="close" aria-label="Chiudi dettaglio">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="drilldown-note">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <span>Presenze = appuntamenti completati; assenze = assenze del paziente, giustificate e non. Le assenze dei terapisti sono nell'Analisi assenze. Una seduta di gruppo conta un appuntamento per paziente.</span>
                </div>
                <div class="drilldown-head">
                    <span></span>
                    <span>Regime / setting / trattamento</span>
                    <span class="text-right">Presenze</span>
                    <span class="text-right">Assenze</span>
                </div>
                <div data-role="body"></div>
            </section>
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
                    <p style="margin-bottom: 20px; color: #4b5563;">Volume, setting e capacità persa nel periodo</p>
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

/* Scheda Presenze / Assenze: due numeri colorati nella stessa riga */
.stat-value .green {
    color: #10b981;
}

.stat-value .red {
    color: #ef4444;
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
                    labels: growthData.map(function(d) { return d.month_label || d.month || ''; }),
                    datasets: [{
                        label: 'Nuovi Pazienti',
                        data: growthData.map(function(d) { return Number(d.count || 0); }),
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
        data: {
            AbsenceStatisticsSearch: {
                dateFrom: '" . date('Y-m-01') . "',
                dateTo: '" . date('Y-m-t') . "'
            }
        },
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