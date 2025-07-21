<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\StatisticsAsset;
use frontend\widgets\StatsCard;
use frontend\widgets\ChartWidget;
use frontend\widgets\StatisticsFilter;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\AbsenceStatisticsSearch */
/* @var $monthlyRate array */
/* @var $byReason array */
/* @var $byGenerator array */
/* @var $therapistOptions array */
/* @var $patientOptions array */
/* @var $treatmentOptions array */

$this->title = 'Analisi Assenze';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

StatisticsAsset::register($this);

$this->registerJs("
", \yii\web\View::POS_READY);

?>

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
  <div class="space-y-4 md:space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4 sm:mb-0">
            <i class="fas fa-calendar-times mr-2"></i>
            Analisi Assenze
        </h1>
        <div class="flex gap-3">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-2"></i> Dashboard',
                ['index'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-2"></i> Esporta',
                ['export', 'type' => 'absences'],
                [
                    'class' => 'inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600',
                    'data-method' => 'post'
                ]
            ) ?>
        </div>
    </div>

    <!-- Filters -->
    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'options' => ['class' => 'mb-6', 'id' => 'absence-filters-form']
    ]); ?>

    <?= StatisticsFilter::widget([
        'model' => $searchModel,
        'form' => $form,
        'title' => 'Filtri Assenze',
        'fields' => [
            'dateFrom',
            'dateTo',
            'therapistId',
            'treatmentTypeId',
            'dayOfWeek',
            'hourFrom',
            'hourTo',
            'generatedBy',
            'isJustified'
        ],
        'options' => [
            'therapists' => $therapistOptions,
            'treatments' => $treatmentOptions
        ],
        'collapsible' => true,
        'collapsed' => false
    ]) ?>

    <?php ActiveForm::end(); ?>

    <!-- Monthly Rate Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Tasso Assenze Mensile',
                'value' => $monthlyRate['absence_rate'] ?? 0,
                'icon' => 'fas fa-percentage',
                'color' => 'danger',
                'footer' => 'Mese: ' . ($monthlyRate['month'] ?? date('Y-m')),
                'valueFormat' => 'percentage'
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Assenze Totali',
                'value' => $monthlyRate['total_absences'] ?? 0,
                'icon' => 'fas fa-calendar-times',
                'color' => 'primary',
                'footer' => 'Su ' . ($monthlyRate['total_appointments'] ?? 0) . ' appuntamenti',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Assenze Giustificate',
                'value' => $monthlyRate['justified_absences'] ?? 0,
                'icon' => 'fas fa-check-circle',
                'color' => 'success',
                'footer' => 'Ingiustificate: ' . ($monthlyRate['unjustified_absences'] ?? 0),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Tasso Ingiustificate',
                'value' => $monthlyRate['unjustified_rate'] ?? 0,
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'warning',
                'footer' => 'Da monitorare se > 10%',
                'valueFormat' => 'percentage'
            ]) ?>
        </div>
    </div>

    <!-- Heatmap Row -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h6 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-th mr-2"></i>
                    Heatmap Assenze (Orari x Giorni Settimana)
                </h6>
                <button class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="loadHeatmapData()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="p-6">
                <div id="absence-heatmap-container" class="heatmap-container">
                    <div class="text-center py-4">
                        <div class="inline-flex items-center justify-center w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" role="status">
                            <span class="sr-only">Caricamento...</span>
                        </div>
                        <p class="mt-2 text-gray-500">Caricamento heatmap...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="col-span-1">
            <?= ChartWidget::widget([
                'title' => 'Assenze per Motivo',
                'type' => 'pie',
                'data' => [
                    'labels' => array_column($byReason, 'reason'),
                    'datasets' => [
                        [
                            'label' => 'Numero Assenze',
                            'data' => array_column($byReason, 'count'),
                            'backgroundColor' => [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                                '#e74a3b', '#858796', '#5a5c69', '#1f2937'
                            ]
                        ]
                    ]
                ],
                'height' => 350
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= ChartWidget::widget([
                'title' => 'Chi Genera l\'Assenza',
                'type' => 'bar',
                'data' => [
                    'labels' => array_column($byGenerator, 'generated_by_label'),
                    'datasets' => [
                        [
                            'label' => 'Totale Assenze',
                            'data' => array_column($byGenerator, 'count'),
                            'backgroundColor' => '#4e73df'
                        ],
                        [
                            'label' => 'Assenze Giustificate',
                            'data' => array_column($byGenerator, 'justified_count'),
                            'backgroundColor' => '#1cc88a'
                        ]
                    ]
                ],
                'height' => 350
            ]) ?>
        </div>
    </div>

    <!-- Trend Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <?= ChartWidget::widget([
                'title' => 'Trend Assenze nel Tempo',
                'type' => 'line',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'absence-trend']),
                'height' => 300,
                'options' => [
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true
                        ]
                    ]
                ]
            ]) ?>
        </div>

        <div class="lg:col-span-1">
            <?= ChartWidget::widget([
                'title' => 'Assenze per Giorno Settimana',
                'type' => 'doughnut',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'absence-by-day']),
                'height' => 300
            ]) ?>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h6 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-table mr-2"></i>
                    Dettaglio Assenze per Motivo
                </h6>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Totale</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Giustificate</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Con Recupero</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Giustificate</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">% Con Recupero</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($byReason as $reason): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= Html::encode($reason['reason']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?= $reason['count'] ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><?= $reason['justified_count'] ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?= $reason['with_recovery_count'] ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php 
                                    $justifiedPerc = $reason['count'] > 0 ? round($reason['justified_count'] / $reason['count'] * 100, 1) : 0;
                                    $badgeClass = $justifiedPerc >= 70 ? 'bg-green-100 text-green-800' : ($justifiedPerc >= 40 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>"><?= $justifiedPerc ?>%</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php 
                                    $recoveryPerc = $reason['count'] > 0 ? round($reason['with_recovery_count'] / $reason['count'] * 100, 1) : 0;
                                    $recoveryBadgeClass = $recoveryPerc >= 50 ? 'bg-green-100 text-green-800' : ($recoveryPerc >= 25 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $recoveryBadgeClass ?>"><?= $recoveryPerc ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<script>
function loadHeatmapData() {
    const container = document.getElementById('absence-heatmap-container');
    
    // Mostra loading
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="sr-only">Caricamento...</span></div><p class="mt-2 text-muted">Caricamento heatmap...</p></div>';
    
    // Ottieni parametri filtro
    const form = document.getElementById('absence-filters-form');
    const formData = form ? new FormData(form) : new FormData();
    
    // Costruisci URL con parametri
    const params = new URLSearchParams();
    for (const pair of formData.entries()) {
        params.append(pair[0], pair[1]);
    }
    
    const url = '<?= Url::to(['chart-data', 'type' => 'absence-heatmap']) ?>' + 
                (params.toString() ? '?' + params.toString() : '');
    
    // Usa la utility AJAX del modulo Statistics con retry
    if (window.Statistics && window.Statistics.retryRequest) {
        window.Statistics.retryRequest(url, {}, 3)
            .then(function(data) {
                if (data.success && data.data) {
                    Statistics.createHeatmap('absence-heatmap-container', data.data);
                } else {
                    container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Nessun dato da visualizzare per i filtri selezionati.</div>';
                }
            })
            .catch(function(error) {
                console.error('Errore caricamento heatmap:', error);
                container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Errore nel caricamento dei dati.</div>';
            });
    } else {
        // Fallback se Statistics non è disponibile
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success && data.data) {
                Statistics.createHeatmap('absence-heatmap-container', data.data);
            } else {
                container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Nessun dato da visualizzare per i filtri selezionati.</div>';
            }
        })
        .catch(function(error) {
            console.error('Errore caricamento heatmap:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Errore nel caricamento dei dati.</div>';
        });
    }
}

// Carica heatmap al caricamento pagina
document.addEventListener('DOMContentLoaded', function() {
    loadHeatmapData();
    
    // Ricarica heatmap quando cambiano i filtri
    const form = document.getElementById('absence-filters-form');
    if (form) {
        const inputs = form.querySelectorAll('select, input');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                setTimeout(loadHeatmapData, 500);
            });
        });
    }
});
</script> 