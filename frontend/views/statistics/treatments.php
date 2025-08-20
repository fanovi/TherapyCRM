<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\TreatmentStatisticsSearch */
/* @var $ranking array */
/* @var $combinations array */
/* @var $bySettingType array */
/* @var $hoursDistribution array */
/* @var $searchResults array */
/* @var $treatmentOptions array */
/* @var $regimeOptions array */

$this->title = 'Statistiche Trattamenti';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');
// Inizializza variabili se non definite
$ranking = $ranking ?? [];
$combinations = $combinations ?? [];
$bySettingType = $bySettingType ?? [];
$hoursDistribution = $hoursDistribution ?? [];
$searchResults = $searchResults ?? [];

// Funzione helper per verificare se ci sono filtri attivi
$hasActiveFilters = !empty($searchModel->treatmentIds) || !empty($searchModel->regimeId) || 
                    !empty($searchModel->dateFrom) || !empty($searchModel->dateTo) ||
                    $searchModel->combinationMode !== 'any' || $searchModel->includeInactive;

// Helper per verificare se ci sono dati
$hasData = !empty($ranking) || !empty($combinations) || !empty($bySettingType) || !empty($hoursDistribution);

// Calcola totali per statistiche
$totalPatients = array_sum(array_column($ranking, 'patient_count'));
$totalTherapies = array_sum(array_column($ranking, 'therapy_count'));
$totalHours = array_sum(array_column($ranking, 'total_weekly_hours'));
$activeTreatments = count($ranking);
?>

<div class="mx-auto max-w-4xl p-4 md:p-6 statistics-treatments">
    <!-- Header con titolo -->
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text">Analisi ranking, combinazioni e distribuzione trattamenti</p>
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
            'action' => Url::to(['treatments']),
            'options' => ['class' => 'filter-form']
        ]); ?>

        <!-- Filtri trattamenti -->
        <div class="filter-section">
            <h4>Selezione trattamenti</h4>
            <div class="filter-row">
                <div class="filter-col" style="flex: 2;">
                    <?= $form->field($searchModel, 'treatmentIds')->dropDownList(
                        $treatmentOptions,
                        [
                            'class' => 'form-control',
                            'multiple' => true,
                            'size' => 5,
                            'prompt' => 'Seleziona uno o più trattamenti...'
                        ]
                    )->label('Trattamenti specifici')->hint('Tieni premuto Ctrl per selezione multipla') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'regimeId')->dropDownList(
                        ['' => 'Tutti i regimi'] + $regimeOptions,
                        ['class' => 'form-control']
                    )->label('Regime sanitario') ?>
                </div>
            </div>
            
            <!-- Modalità combinazione -->
            <div class="filter-row">
                <div class="filter-col">
                    <label class="control-label">Modalità Combinazione</label>
                    <div style="display: flex; gap: 20px; margin-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <?= Html::radio('TreatmentStatisticsSearch[combinationMode]', 
                                $searchModel->combinationMode === 'any' || empty($searchModel->combinationMode), 
                                ['value' => 'any', 'class' => 'form-check-input']) ?>
                            <span>Almeno uno (ANY)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <?= Html::radio('TreatmentStatisticsSearch[combinationMode]', 
                                $searchModel->combinationMode === 'all', 
                                ['value' => 'all', 'class' => 'form-check-input']) ?>
                            <span>Tutti (ALL)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <?= Html::radio('TreatmentStatisticsSearch[combinationMode]', 
                                $searchModel->combinationMode === 'exact', 
                                ['value' => 'exact', 'class' => 'form-check-input']) ?>
                            <span>Esattamente questi (EXACT)</span>
                        </label>
                    </div>
                </div>
                <div class="filter-col">
                    <label class="control-label">Includi Piani Inattivi</label>
                    <div style="margin-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <?= Html::checkbox('TreatmentStatisticsSearch[includeInactive]', 
                                $searchModel->includeInactive, 
                                ['class' => 'form-check-input']) ?>
                            <span>Includi inattivi</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri temporali -->
        <div class="filter-section">
            <h4>Periodo analisi</h4>
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
            <?= Html::a('<i class="fas fa-undo"></i> Rimuovi filtri', ['treatments'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <!-- Risultati ricerca filtrata (solo se ci sono filtri attivi) -->
    <?php if ($hasActiveFilters && !empty($treatmentOptions)): ?>
        <?php if (!empty($searchModel->treatmentIds)): ?>
            <div class="filter-card" style="background: #f0f9ff; border-color: #3b82f6;">
                <h3 style="color: #1e40af;">Risultati per Trattamenti Selezionati</h3>
                <div class="table-card" style="background: white;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Trattamento</th>
                                <th>Codice</th>
                                <th class="text-center">Pazienti</th>
                                <th class="text-center">Terapie</th>
                                <th class="text-center">Ore Sett.</th>
                                <th class="text-center">Media Ore</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Filtra il ranking per mostrare solo i trattamenti selezionati
                            $selectedTreatments = array_filter($ranking, function($treatment) use ($searchModel) {
                                return in_array($treatment['id'], $searchModel->treatmentIds);
                            });
                            
                            if (empty($selectedTreatments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center no-data">
                                        Nessun dato trovato per i trattamenti selezionati
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($selectedTreatments as $treatment): ?>
                                    <tr>
                                        <td class="font-bold"><?= Html::encode($treatment['name']) ?></td>
                                        <td><?= Html::encode($treatment['code']) ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-blue"><?= $treatment['patient_count'] ?></span>
                                        </td>
                                        <td class="text-center"><?= $treatment['therapy_count'] ?></td>
                                        <td class="text-center"><?= $treatment['total_weekly_hours'] ?></td>
                                        <td class="text-center"><?= round($treatment['avg_weekly_hours'] ?? 0, 1) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background: #f3f4f6; font-weight: 600;">
                                    <td colspan="2">Totale Selezionati</td>
                                    <td class="text-center"><?= array_sum(array_column($selectedTreatments, 'patient_count')) ?></td>
                                    <td class="text-center"><?= array_sum(array_column($selectedTreatments, 'therapy_count')) ?></td>
                                    <td class="text-center"><?= array_sum(array_column($selectedTreatments, 'total_weekly_hours')) ?></td>
                                    <td class="text-center">-</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$hasData): ?>
        <!-- Messaggio quando non ci sono dati -->
        <div class="no-data-message">
            <i class="fas fa-info-circle"></i>
            <h3>Nessun dato disponibile</h3>
            <p>
                Non sono presenti dati per l'analisi dei trattamenti.<br>
                Verifica che ci siano trattamenti attivi nel sistema.
            </p>
        </div>
    <?php else: ?>

        <!-- 1. Riepilogo generale -->
        <div class="summary-card">
            <h3>Riepilogo Trattamenti</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value blue"><?= $activeTreatments ?></div>
                    <div class="stat-label">Tipi Attivi</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= $totalPatients ?></div>
                    <div class="stat-label">Pazienti Totali</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value purple"><?= $totalTherapies ?></div>
                    <div class="stat-label">Terapie Totali</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value orange"><?= $totalHours ?></div>
                    <div class="stat-label">Ore Settimanali</div>
                </div>
            </div>
        </div>

        <!-- 2. Ranking trattamenti -->
        <?php if (!empty($ranking)): ?>
        <div class="full-width-card">
            <h3>Ranking Trattamenti per Numero Pazienti</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Pos.</th>
                        <th>Trattamento</th>
                        <th>Codice</th>
                        <th class="text-center">Pazienti</th>
                        <th class="text-center">Terapie</th>
                        <th class="text-center">Ore Sett.</th>
                        <th class="text-center">Media Ore</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($ranking, 0, 20) as $index => $treatment): ?>
                        <tr class="<?= $index < 3 ? 'top-three' : '' ?>">
                            <td class="text-center">
                                <span class="rank"><?= $index + 1 ?></span>
                            </td>
                            <td class="font-bold"><?= Html::encode($treatment['name']) ?></td>
                            <td><?= Html::encode($treatment['code']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $treatment['patient_count'] > 50 ? 'badge-green' : 'badge-gray' ?>">
                                    <?= $treatment['patient_count'] ?>
                                </span>
                            </td>
                            <td class="text-center"><?= $treatment['therapy_count'] ?></td>
                            <td class="text-center"><?= $treatment['total_weekly_hours'] ?></td>
                            <td class="text-center"><?= round($treatment['avg_weekly_hours'] ?? 0, 1) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($ranking) > 20): ?>
            <p class="no-data" style="text-align: center; padding: 16px;">
                Mostrati i primi 20 di <?= count($ranking) ?> trattamenti
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 3. Grafici distribuzione -->
        <div class="section-title">
            <h3>Distribuzione Trattamenti</h3>
        </div>
        <div class="charts-row">
            <div class="chart-card">
                <h4>Distribuzione per Ore Settimanali</h4>
                <div class="chart-container">
                    <canvas id="hours-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Distribuzione per Setting</h4>
                <div class="chart-container">
                    <canvas id="setting-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 4. Combinazioni frequenti -->
        <?php if (!empty($combinations)): ?>
        <div class="full-width-card">
            <h3>Combinazioni di Trattamenti più Frequenti</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Pos.</th>
                        <th>Combinazione Trattamenti</th>
                        <th class="text-center">N° Pazienti</th>
                        <th class="text-center">% sul Totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalMultiPatients = array_sum(array_column($combinations, 'patient_count'));
                    foreach ($combinations as $index => $combo): 
                        $percentage = $totalMultiPatients > 0 ? round(($combo['patient_count'] / $totalMultiPatients) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td class="text-center">
                                <span class="rank"><?= $index + 1 ?></span>
                            </td>
                            <td><?= Html::encode($combo['combination']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-purple"><?= $combo['patient_count'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge-small <?= $percentage > 20 ? 'badge-green' : 'badge-gray' ?>">
                                    <?= $percentage ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- 5. Azioni export -->
        <div class="export-section">
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                I dati mostrati includono tutti i trattamenti attivi nel sistema
            </div>
            <?= Html::a(
                '<i class="fas fa-file-excel"></i> Esporta Report Excel',
                ['export', 'type' => 'treatments'] + Yii::$app->request->queryParams,
                ['class' => 'btn btn-success']
            ) ?>
        </div>

    <?php endif; ?>
</div>

<!-- Stili aggiuntivi specifici -->
<style>
.stat-value.purple {
    color: #8b5cf6;
}

.form-check-input {
    margin-right: 4px;
}

.control-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 4px;
}

.top-three {
    background: #f9fafb;
}

.rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f3f4f6;
    font-weight: 600;
    color: #374151;
}

.top-three .rank {
    background: #fbbf24;
    color: #92400e;
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
let hoursChart = null;
let settingChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    
    // Grafico distribuzione ore
    loadHoursChart();
    
    // Grafico distribuzione setting
    loadSettingChart();
}

// Grafico distribuzione ore
function loadHoursChart() {
    var hoursData = " . json_encode($hoursDistribution) . ";
    
    if (hoursData && hoursData.length > 0) {
        destroyChart(hoursChart);
        var ctx = document.getElementById('hours-chart');
        if (ctx) {
            hoursChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: hoursData.map(function(h) { return h.hours_range || ''; }),
                    datasets: [{
                        label: 'Numero terapie',
                        data: hoursData.map(function(h) { return h.therapy_count || 0; }),
                        backgroundColor: 'rgba(139, 92, 246, 0.8)',
                        borderColor: 'rgba(139, 92, 246, 1)',
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
                                text: 'Numero terapie'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Ore settimanali'
                            }
                        }
                    }
                }
            });
        }
    }
}

// Grafico distribuzione setting
function loadSettingChart() {
    var settingData = " . json_encode($bySettingType) . ";
    
    if (settingData && settingData.length > 0) {
        destroyChart(settingChart);
        var ctx = document.getElementById('setting-chart');
        if (ctx) {
            settingChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: settingData.map(function(s) { return s.setting_type || 'Non specificato'; }),
                    datasets: [{
                        label: 'Terapie',
                        data: settingData.map(function(s) { return s.therapy_count || 0; }),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(107, 114, 128, 0.8)'
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
<?php endif; ?>