<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PatientStatisticsSearch */
/* @var $demographics array */
/* @var $byTreatment array */
/* @var $byRegime array */
/* @var $multiTreatmentStats array */
/* @var $treatmentOptions array */
/* @var $regimeOptions array */
/* @var $districtOptions array */

$this->title = 'Statistiche Pazienti';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');
// Inizializza variabili se non definite
$demographics = $demographics ?? [];
$byTreatment = $byTreatment ?? [];
$byRegime = $byRegime ?? [];
$multiTreatmentStats = $multiTreatmentStats ?? ['patients' => [], 'stats' => []];

// Funzione helper per verificare se ci sono filtri attivi
$hasActiveFilters = !empty($searchModel->gender) || !empty($searchModel->ageFrom) || 
                    !empty($searchModel->ageTo) || !empty($searchModel->status) || 
                    !empty($searchModel->dateFrom) || !empty($searchModel->dateTo) ||
                    !empty($searchModel->treatmentTypeIds) || !empty($searchModel->districtId);

// Calcola il totale pazienti
$totalPatients = $demographics['age_stats']['total_patients'] ?? 0;

// Helper per verificare se ci sono dati
$hasData = $totalPatients > 0 || !empty($byTreatment) || !empty($byRegime) || !empty($multiTreatmentStats['patients']);

// Helper per calcolo percentuali
function calculatePercentage($part, $total, $decimals = 1) {
    return $total > 0 ? round($part / $total * 100, $decimals) : 0;
}
?>

<div class="statistics-patients">
    <!-- Header con titolo -->
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text">Analisi demografica e distribuzione pazienti</p>
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
            'options' => ['class' => 'filter-form']
        ]); ?>

        <!-- Filtri demografici -->
        <div class="filter-section">
            <h4>Filtri demografici</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'gender')->dropDownList([
                        '' => 'Tutti i generi',
                        'M' => 'Maschio',
                        'F' => 'Femmina',
                        'N' => 'Non specificato'
                    ], ['class' => 'form-control'])->label('Genere') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Tutti gli stati',
                        'active' => 'Con piano attivo',
                        'inactive' => 'Senza piano attivo',
                        'dismissed' => 'Dimessi'
                    ], ['class' => 'form-control'])->label('Stato paziente') ?>
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'ageFrom')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'max' => 120,
                        'class' => 'form-control'
                    ])->label('Età minima') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'ageTo')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'max' => 120,
                        'class' => 'form-control'
                    ])->label('Età massima') ?>
                </div>
            </div>
        </div>

        <!-- Filtri per trattamento e distretto -->
        <div class="filter-section">
            <h4>Filtri specifici</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'treatmentTypeIds')->dropDownList(
                        $treatmentOptions,
                        [
                            'class' => 'form-control',
                            'multiple' => true,
                            'prompt' => 'Tutti i trattamenti'
                        ]
                    )->label('Tipo trattamento') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'districtId')->dropDownList(
                        ['' => 'Tutti i distretti'] + $districtOptions,
                        ['class' => 'form-control']
                    )->label('Distretto') ?>
                </div>
            </div>
        </div>

        <!-- Filtri temporali -->
        <div class="filter-section">
            <h4>Periodo registrazione</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'dateFrom')->textInput([
                        'type' => 'date',
                        'class' => 'form-control'
                    ])->label('Data inizio') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'dateTo')->textInput([
                        'type' => 'date',
                        'class' => 'form-control'
                    ])->label('Data fine') ?>
                </div>
            </div>
        </div>

        <!-- Pulsanti azione -->
        <div class="filter-actions">
            <?= Html::submitButton('<i class="fas fa-search"></i> Applica filtri', [
                'class' => 'btn btn-primary'
            ]) ?>
            <?= Html::a('<i class="fas fa-undo"></i> Rimuovi filtri', ['patients'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if (!$hasData): ?>
        <!-- Messaggio quando non ci sono dati -->
        <div class="no-data-message">
            <i class="fas fa-info-circle"></i>
            <h3>Nessun paziente trovato</h3>
            <p>
                Non sono presenti pazienti per i criteri selezionati.<br>
                Prova a modificare i filtri di ricerca per visualizzare i dati.
            </p>
        </div>
    <?php else: ?>

        <!-- 1. Riepilogo demografico -->
        <div class="summary-card">
            <h3>Riepilogo Demografico</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value blue"><?= $totalPatients ?></div>
                    <div class="stat-label">Totale Pazienti</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= round($demographics['age_stats']['avg_age'] ?? 0, 1) ?></div>
                    <div class="stat-label">Età Media</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value gray"><?= count($multiTreatmentStats['patients'] ?? []) ?></div>
                    <div class="stat-label">Multi-trattamento</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value orange"><?= round($multiTreatmentStats['stats']['avg_treatments'] ?? 0, 1) ?></div>
                    <div class="stat-label">Media Trattamenti</div>
                </div>
            </div>
        </div>

        <!-- 2. Distribuzione demografica -->
        <div class="section-title">
            <h3>Distribuzione Demografica</h3>
        </div>
        <div class="charts-row">
            <div class="chart-card">
                <h4>Distribuzione per Età</h4>
                <div class="chart-container">
                    <canvas id="age-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Distribuzione per Genere</h4>
                <div class="chart-container">
                    <canvas id="gender-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Analisi per trattamento -->
        <?php if (!empty($byTreatment)): ?>
        <div class="full-width-card">
            <h3>Pazienti per Trattamento</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Trattamento</th>
                        <th>Codice</th>
                        <th class="text-center">Pazienti</th>
                        <th class="text-center">% del Totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($byTreatment, 0, 15) as $treatment): ?>
                        <tr>
                            <td class="font-bold"><?= Html::encode($treatment['name']) ?></td>
                            <td><?= Html::encode($treatment['code']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-gray"><?= $treatment['patient_count'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $percentage = calculatePercentage($treatment['patient_count'], $totalPatients);
                                $badgeClass = $percentage >= 20 ? 'badge-green' : ($percentage >= 10 ? 'badge-orange' : 'badge-gray');
                                ?>
                                <span class="badge-small <?= $badgeClass ?>">
                                    <?= $percentage ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($byTreatment) > 15): ?>
            <p class="text-center mt-4 text-sm text-gray-600">
                Mostrati i primi 15 di <?= count($byTreatment) ?> trattamenti
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 4. Pazienti con trattamenti multipli -->
        <?php if (!empty($multiTreatmentStats['patients'])): ?>
        <div class="full-width-card">
            <h3>Pazienti con Trattamenti Multipli (escluso ABA)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paziente</th>
                        <th class="text-center">N° Trattamenti</th>
                        <th>Trattamenti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($multiTreatmentStats['patients'], 0, 20) as $patient): ?>
                        <tr>
                            <td class="font-bold"><?= Html::encode($patient['patient_name']) ?></td>
                            <td class="text-center">
                                <?php 
                                $count = $patient['treatment_count'];
                                $badgeClass = $count >= 4 ? 'badge-red' : ($count >= 3 ? 'badge-orange' : 'badge-green');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $count ?></span>
                            </td>
                            <td class="text-sm"><?= Html::encode($patient['treatments']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($multiTreatmentStats['patients']) > 20): ?>
            <p class="text-center mt-4 text-sm text-gray-600">
                Mostrati i primi 20 di <?= count($multiTreatmentStats['patients']) ?> pazienti con trattamenti multipli
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 5. Distribuzione per regime -->
        <?php if (!empty($byRegime)): ?>
        <div class="section-title">
            <h3>Distribuzione per Regime Sanitario</h3>
        </div>
        <div class="regime-grid">
            <?php foreach ($byRegime as $regime): ?>
            <div class="regime-card">
                <h4><?= Html::encode($regime['regime_name']) ?></h4>
                <div class="regime-count">
                    <?= $regime['patient_count'] ?> pazienti
                </div>
                <?php if (!empty($regime['avg_duration']) && $regime['avg_duration'] > 0): ?>
                <div class="regime-duration">
                    Durata media: <?= round($regime['avg_duration']) ?> giorni
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 6. Azioni export -->
        <div class="export-section">
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                I dati mostrati sono filtrati secondo i criteri selezionati
            </div>
            <?= Html::a(
                '<i class="fas fa-file-excel"></i> Esporta Report Excel',
                ['export', 'type' => 'patients'] + Yii::$app->request->queryParams,
                ['class' => 'btn btn-success']
            ) ?>
        </div>

    <?php endif; ?>
</div>

<!-- CSS aggiuntivo per questa view -->
<style>
/* Stili specifici per i regimi */
.regime-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.regime-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #3182ce;
}

.regime-card h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 12px;
}

.regime-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: #3182ce;
    margin-bottom: 8px;
}

.regime-duration {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Colore arancione per stat-value */
.stat-value.orange {
    color: #f97316;
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
let ageChart = null;
let genderChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    console.log('Inizializzazione grafici pazienti...');
    
    // Grafico età
    loadAgeChart();
    
    // Grafico genere
    loadGenderChart();
}

// Carica grafico età
function loadAgeChart() {
    console.log('Caricamento grafico età...');
    
    // Prepara i dati dalle statistiche demografiche
    var ageGroups = " . json_encode($demographics['age_groups'] ?? []) . ";
    
    if (ageGroups && ageGroups.length > 0) {
        destroyChart(ageChart);
        var ctx = document.getElementById('age-chart');
        if (ctx) {
            ageChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ageGroups.map(function(g) { return g.age_group; }),
                    datasets: [{
                        label: 'Numero pazienti',
                        data: ageGroups.map(function(g) { return g.count; }),
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
                                    return 'Pazienti: ' + context.parsed.y;
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
                                text: 'Numero pazienti'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Fascia di età'
                            }
                        }
                    }
                }
            });
        }
    }
}

// Carica grafico genere
function loadGenderChart() {
    console.log('Caricamento grafico genere...');
    
    // Prepara i dati dalle statistiche demografiche
    var genderData = " . json_encode($demographics['gender_distribution'] ?? []) . ";
    
    if (genderData && genderData.length > 0) {
        destroyChart(genderChart);
        var ctx = document.getElementById('gender-chart');
        if (ctx) {
            genderChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: genderData.map(function(g) { return g.gender_label; }),
                    datasets: [{
                        label: 'Pazienti',
                        data: genderData.map(function(g) { return g.count; }),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(201, 203, 207, 0.8)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(201, 203, 207, 1)'
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