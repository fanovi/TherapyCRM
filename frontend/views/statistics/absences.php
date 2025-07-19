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

<div class="absence-statistics">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calendar-times mr-2"></i>
            Analisi Assenze
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-1"></i> Dashboard',
                ['index'],
                ['class' => 'btn btn-sm btn-secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-1"></i> Esporta',
                ['export', 'type' => 'absences'],
                [
                    'class' => 'btn btn-sm btn-success',
                    'data-method' => 'post'
                ]
            ) ?>
        </div>
    </div>

    <!-- Filters -->
    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'options' => ['class' => 'mb-4', 'id' => 'absence-filters-form']
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
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'Tasso Assenze Mensile',
                'value' => $monthlyRate['absence_rate'] ?? 0,
                'icon' => 'fas fa-percentage',
                'color' => 'danger',
                'footer' => 'Mese: ' . ($monthlyRate['month'] ?? date('Y-m')),
                'valueFormat' => 'percentage'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'Assenze Totali',
                'value' => $monthlyRate['total_absences'] ?? 0,
                'icon' => 'fas fa-calendar-times',
                'color' => 'primary',
                'footer' => 'Su ' . ($monthlyRate['total_appointments'] ?? 0) . ' appuntamenti',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'Assenze Giustificate',
                'value' => $monthlyRate['justified_absences'] ?? 0,
                'icon' => 'fas fa-check-circle',
                'color' => 'success',
                'footer' => 'Ingiustificate: ' . ($monthlyRate['unjustified_absences'] ?? 0),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-th mr-2"></i>
                        Heatmap Assenze (Orari x Giorni Settimana)
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadHeatmapData()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="absence-heatmap-container" class="heatmap-container">
                        <div class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">Caricamento...</span>
                            </div>
                            <p class="mt-2 text-muted">Caricamento heatmap...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-lg-6 mb-4">
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

        <div class="col-lg-6 mb-4">
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
    <div class="row">
        <div class="col-lg-8 mb-4">
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

        <div class="col-lg-4 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Assenze per Giorno Settimana',
                'type' => 'doughnut',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'absence-by-day']),
                'height' => 300
            ]) ?>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-2"></i>
                        Dettaglio Assenze per Motivo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Motivo</th>
                                    <th class="text-center">Totale</th>
                                    <th class="text-center">Giustificate</th>
                                    <th class="text-center">Con Recupero</th>
                                    <th class="text-center">% Giustificate</th>
                                    <th class="text-center">% Con Recupero</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byReason as $reason): ?>
                                <tr>
                                    <td><?= Html::encode($reason['reason']) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $reason['count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?= $reason['justified_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $reason['with_recovery_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $justifiedPerc = $reason['count'] > 0 ? round($reason['justified_count'] / $reason['count'] * 100, 1) : 0;
                                        $badgeClass = $justifiedPerc >= 70 ? 'success' : ($justifiedPerc >= 40 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge badge-<?= $badgeClass ?>"><?= $justifiedPerc ?>%</span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $recoveryPerc = $reason['count'] > 0 ? round($reason['with_recovery_count'] / $reason['count'] * 100, 1) : 0;
                                        $recoveryBadgeClass = $recoveryPerc >= 50 ? 'success' : ($recoveryPerc >= 25 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge badge-<?= $recoveryBadgeClass ?>"><?= $recoveryPerc ?>%</span>
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