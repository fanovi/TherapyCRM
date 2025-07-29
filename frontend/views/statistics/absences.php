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

// Registra solo Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="statistics-absences">

    <h1 class="text-3xl font-bold mb-6"><?= Html::encode($this->title) ?></h1>

    <!-- Filtri di ricerca -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Filtri di ricerca</h3>

        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'options' => ['class' => 'grid grid-cols-1 md:grid-cols-3 gap-4']
        ]); ?>

        <div class="space-y-2">
            <?= $form->field($searchModel, 'dateFrom', [
                'template' => '{label}{input}{error}',
                'options' => ['class' => '']
            ])->textInput([
                'type' => 'date',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'
            ]) ?>
        </div>

        <div class="space-y-2">
            <?= $form->field($searchModel, 'dateTo', [
                'template' => '{label}{input}{error}',
                'options' => ['class' => '']
            ])->textInput([
                'type' => 'date',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'
            ]) ?>
        </div>

        <div class="space-y-2">
            <?= $form->field($searchModel, 'absenceSource')->dropDownList([
                '' => 'Tutti',
                'therapist' => 'Terapisti',
                'patient' => 'Pazienti'
            ], [
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'
            ]) ?>
        </div>

        <div class="space-y-2">
            <?= $form->field($searchModel, 'isJustified')->dropDownList([
                '' => 'Tutte',
                '1' => 'Giustificate',
                '0' => 'Non giustificate'
            ], [
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'
            ]) ?>
        </div>

        <div class="space-y-2">
            <?= $form->field($searchModel, 'therapistId')->dropDownList(
                ['' => 'Seleziona terapista...'] + $therapistOptions,
                ['class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500']
            ) ?>
        </div>

        <div class="space-y-2">
            <?= $form->field($searchModel, 'treatmentTypeId')->dropDownList(
                ['' => 'Seleziona trattamento...'] + $treatmentOptions,
                ['class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500']
            ) ?>
        </div>

        <div class="md:col-span-3 flex gap-2 mt-4">
            <?= Html::submitButton('Cerca', ['class' => 'px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700']) ?>
            <?= Html::a('Reset', ['absences'], ['class' => 'px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <!-- Riepilogo mensile -->
    <div class="bg-blue-50 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Riepilogo Mese Corrente</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-blue-600"><?= $monthlyRate['total_absences'] ?? 0 ?></div>
                <div class="text-gray-600">Assenze Totali</div>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-green-600"><?= $monthlyRate['justified_absences'] ?? 0 ?></div>
                <div class="text-gray-600">Assenze Giustificate</div>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-gray-600"><?= $monthlyRate['total_appointments'] ?? 0 ?></div>
                <div class="text-gray-600">Appuntamenti Totali</div>
            </div>
            <div class="bg-white rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-red-600"><?= $monthlyRate['absence_rate'] ?? 0 ?>%</div>
                <div class="text-gray-600">Tasso di Assenza</div>
            </div>
        </div>
    </div>

    <!-- Grafici principali -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Assenze per ora del giorno -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Assenze per Ora del Giorno</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="hourly-chart"></canvas>
            </div>
        </div>

        <!-- Assenze per giorno della settimana -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Assenze per Giorno della Settimana</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="day-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chi genera le assenze -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Chi Genera le Assenze</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Tipo</th>
                            <th class="text-left py-2">Dettaglio</th>
                            <th class="text-right py-2">Numero</th>
                            <th class="text-right py-2">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byGenerator as $item): ?>
                            <tr class="border-b">
                                <td class="py-2"><?= $item['generator'] === 'therapist' ? 'Terapista' : 'Paziente' ?></td>
                                <td class="py-2">
                                    <?php
                                    if ($item['absence_type'] === 'direct') echo 'Assenza diretta';
                                    elseif ($item['absence_type'] === 'substitution') echo 'Sostituzione';
                                    else echo '-';
                                    ?>
                                </td>
                                <td class="py-2 text-right"><?= $item['count'] ?></td>
                                <td class="py-2 text-right"><?= $item['percentage'] ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Motivazioni più frequenti -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Motivazioni Più Frequenti</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Motivazione</th>
                            <th class="text-left py-2">Chi</th>
                            <th class="text-right py-2">Numero</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $topReasons = array_slice($byReason, 0, 10);
                        foreach ($topReasons as $item):
                        ?>
                            <tr class="border-b">
                                <td class="py-2"><?= Html::encode($item['reason'] ?: 'Non specificata') ?></td>
                                <td class="py-2"><?= $item['source'] === 'therapist' ? 'Terapista' : 'Paziente' ?></td>
                                <td class="py-2 text-right"><?= $item['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Trattamenti con più assenze -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Trattamenti con Più Assenze</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Trattamento</th>
                        <th class="text-left py-2">Codice</th>
                        <th class="text-right py-2">Totale Assenze</th>
                        <th class="text-right py-2">Assenze Terapisti</th>
                        <th class="text-right py-2">Assenze Pazienti</th>
                        <th class="text-right py-2">% Giustificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byTreatmentType as $treatment): ?>
                        <tr class="border-b">
                            <td class="py-2"><?= Html::encode($treatment['treatment_name']) ?></td>
                            <td class="py-2"><?= Html::encode($treatment['treatment_code']) ?></td>
                            <td class="py-2 text-right font-semibold"><?= $treatment['total_absences'] ?></td>
                            <td class="py-2 text-right"><?= $treatment['therapist_absences'] ?></td>
                            <td class="py-2 text-right"><?= $treatment['patient_absences'] ?></td>
                            <td class="py-2 text-right"><?= $treatment['justified_rate'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top assenti -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-red-50 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4 text-red-700">Top 10 Terapisti Assenti (ultimi 3 mesi)</h3>
            <div class="space-y-2">
                <?php foreach ($topAbsentees['therapists'] as $therapist): ?>
                    <div class="flex justify-between items-center py-2 border-b">
                        <span><?= Html::encode($therapist['name']) ?></span>
                        <span class="font-semibold text-red-600"><?= $therapist['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-orange-50 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4 text-orange-700">Top 10 Pazienti Assenti (ultimi 3 mesi)</h3>
            <div class="space-y-2">
                <?php foreach ($topAbsentees['patients'] as $patient): ?>
                    <div class="flex justify-between items-center py-2 border-b">
                        <span><?= Html::encode($patient['name']) ?></span>
                        <span class="font-semibold text-orange-600"><?= $patient['count'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trend mensile -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Trend Assenze Ultimi 12 Mesi</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="trend-chart"></canvas>
        </div>
    </div>

    <!-- Export -->
    <div class="bg-white rounded-lg shadow-md p-6 text-right">
        <?= Html::a(
            '<svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Esporta in Excel',
            ['export', 'type' => 'absences'] + Yii::$app->request->queryParams,
            ['class' => 'inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700']
        ) ?>
    </div>

</div>

<?php
// Javascript per i grafici usando solo Chart.js
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

// Attendi che Chart.js sia caricato
document.addEventListener('DOMContentLoaded', function() {
    
    // Carica statistiche orarie
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-hourly']) . "',
        data: " . json_encode(Yii::$app->request->queryParams) . ",
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(hourlyChart);
                var ctx = document.getElementById('hourly-chart');
                if (ctx) {
                    hourlyChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: response.data.labels || [],
                            datasets: [{
                                label: 'Assenze per Ora',
                                data: response.data.values || [],
                                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1
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
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    });

    // Grafico assenze per giorno
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-by-day']) . "',
        data: " . json_encode(Yii::$app->request->queryParams) . ",
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(dayChart);
                var ctx = document.getElementById('day-chart');
                if (ctx) {
                    dayChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: response.data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    });

    // Trend mensile
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-trend']) . "',
        data: " . json_encode(Yii::$app->request->queryParams) . ",
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(trendChart);
                var ctx = document.getElementById('trend-chart');
                if (ctx) {
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
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    });
    
});
");
?>