<?php

use yii\helpers\Html;
use yii\helpers\Url;
use frontend\assets\StatisticsAsset;
use frontend\widgets\StatsCard;
use frontend\widgets\ChartWidget;

/* @var $this yii\web\View */
/* @var $plansStats array */

$this->title = 'Analisi Piani Terapeutici';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

StatisticsAsset::register($this);

$this->registerJs("
    Statistics.init();
", \yii\web\View::POS_READY);

?>

<div class="plans-statistics">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list mr-2"></i>
            Analisi Piani Terapeutici
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-1"></i> Dashboard',
                ['index'],
                ['class' => 'btn btn-sm btn-secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-1"></i> Esporta',
                ['export', 'type' => 'plans'],
                [
                    'class' => 'btn btn-sm btn-success',
                    'data-method' => 'post'
                ]
            ) ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $activeCount = array_sum(array_filter(array_column($plansStats['by_status'], 'count'), function($item, $key) use ($plansStats) {
                return $plansStats['by_status'][$key]['status'] === 'active';
            }, ARRAY_FILTER_USE_BOTH));
            ?>
            <?= StatsCard::widget([
                'title' => 'Piani Attivi',
                'value' => $activeCount,
                'icon' => 'fas fa-play-circle',
                'color' => 'success',
                'footer' => 'In corso di trattamento',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $completedCount = array_sum(array_filter(array_column($plansStats['by_status'], 'count'), function($item, $key) use ($plansStats) {
                return $plansStats['by_status'][$key]['status'] === 'completed';
            }, ARRAY_FILTER_USE_BOTH));
            ?>
            <?= StatsCard::widget([
                'title' => 'Piani Completati',
                'value' => $completedCount,
                'icon' => 'fas fa-check-circle',
                'color' => 'primary',
                'footer' => 'Terminati con successo',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'In Scadenza',
                'value' => count($plansStats['expiring_list']),
                'icon' => 'fas fa-clock',
                'color' => 'warning',
                'footer' => 'Prossimi 60 giorni',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $avgCompletion = count($plansStats['completion_rates']) > 0 
                ? round(array_sum(array_column($plansStats['completion_rates'], 'completion_rate')) / count($plansStats['completion_rates']), 1)
                : 0;
            ?>
            <?= StatsCard::widget([
                'title' => 'Tasso Completamento',
                'value' => $avgCompletion,
                'icon' => 'fas fa-chart-bar',
                'color' => 'info',
                'footer' => 'Media appuntamenti',
                'valueFormat' => 'percentage'
            ]) ?>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-lg-4 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Distribuzione per Stato',
                'type' => 'doughnut',
                'data' => [
                    'labels' => array_map(function($item) {
                        return $item['status'] === 'active' ? 'Attivi' : 'Completati';
                    }, $plansStats['by_status']),
                    'datasets' => [
                        [
                            'label' => 'Numero Piani',
                            'data' => array_column($plansStats['by_status'], 'count'),
                            'backgroundColor' => ['#1cc88a', '#4e73df']
                        ]
                    ]
                ],
                'height' => 300
            ]) ?>
        </div>

        <div class="col-lg-4 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Distribuzione per Durata',
                'type' => 'bar',
                'data' => [
                    'labels' => array_map(function($item) {
                        switch($item['duration_category']) {
                            case 'short': return 'Breve (<90gg)';
                            case 'medium': return 'Medio (90-365gg)';
                            case 'long': return 'Lungo (>365gg)';
                            default: return $item['duration_category'];
                        }
                    }, $plansStats['by_duration']),
                    'datasets' => [
                        [
                            'label' => 'Numero Piani',
                            'data' => array_column($plansStats['by_duration'], 'count'),
                            'backgroundColor' => ['#f6c23e', '#36b9cc', '#e74a3b']
                        ]
                    ]
                ],
                'height' => 300
            ]) ?>
        </div>

        <div class="col-lg-4 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Trend Creazione Mensile',
                'type' => 'line',
                'data' => [
                    'labels' => array_column($plansStats['monthly_trends'], 'month'),
                    'datasets' => [
                        [
                            'label' => 'Nuovi Piani',
                            'data' => array_column($plansStats['monthly_trends'], 'count'),
                            'borderColor' => '#4e73df',
                            'backgroundColor' => 'rgba(78, 115, 223, 0.1)',
                            'fill' => true
                        ]
                    ]
                ],
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
    </div>

    <!-- Completion Rates Table -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-percentage mr-2"></i>
                        Top 10 Piani per Tasso di Completamento
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($plansStats['completion_rates'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Piano ID</th>
                                    <th>Paziente</th>
                                    <th class="text-center">Appuntamenti Totali</th>
                                    <th class="text-center">Completati</th>
                                    <th class="text-center">Tasso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plansStats['completion_rates'] as $plan): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-secondary"><?= $plan['id'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= Html::encode($plan['patient_name']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?= $plan['total_appointments'] ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?= $plan['completed_appointments'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $rate = $plan['completion_rate'];
                                        $badgeClass = $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge badge-<?= $badgeClass ?>"><?= $rate ?>%</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nessun dato di completamento disponibile.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>
                        Statistiche Durata
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($plansStats['by_duration'] as $duration): ?>
                    <div class="mb-3 p-3 border rounded">
                        <h6 class="text-primary mb-2">
                            <?php
                            switch($duration['duration_category']) {
                                case 'short': echo 'Piani Brevi (<90 giorni)'; break;
                                case 'medium': echo 'Piani Medi (90-365 giorni)'; break;
                                case 'long': echo 'Piani Lunghi (>365 giorni)'; break;
                                default: echo Html::encode($duration['duration_category']);
                            }
                            ?>
                        </h6>
                        <p class="mb-1">
                            <span class="badge badge-primary"><?= $duration['count'] ?></span>
                            <small class="text-muted">piani</small>
                        </p>
                        <p class="mb-0">
                            <small class="text-muted">
                                Durata media: <strong><?= round($duration['avg_duration']) ?> giorni</strong>
                            </small>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Plans -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Piani in Scadenza (Prossimi 60 Giorni)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($plansStats['expiring_list'])): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Piano ID</th>
                                    <th>Paziente</th>
                                    <th>Data Scadenza</th>
                                    <th class="text-center">Giorni Rimanenti</th>
                                    <th class="text-center">Urgenza</th>
                                    <th class="text-center">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plansStats['expiring_list'] as $plan): ?>
                                <?php
                                $daysLeft = $plan['days_until_expiry'];
                                $urgencyClass = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info');
                                $urgencyText = $daysLeft <= 7 ? 'Critica' : ($daysLeft <= 30 ? 'Alta' : 'Media');
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-secondary"><?= $plan['id'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= Html::encode($plan['patient_name']) ?></strong>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($plan['end_date'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $urgencyClass ?>"><?= $daysLeft ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $urgencyClass ?>"><?= $urgencyText ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?= Html::a(
                                                '<i class="fas fa-eye"></i>',
                                                ['therapeutic-plan/view', 'id' => $plan['id']],
                                                [
                                                    'class' => 'btn btn-outline-primary',
                                                    'title' => 'Visualizza piano',
                                                    'data-toggle' => 'tooltip'
                                                ]
                                            ) ?>
                                            <?= Html::a(
                                                '<i class="fas fa-edit"></i>',
                                                ['therapeutic-plan/update', 'id' => $plan['id']],
                                                [
                                                    'class' => 'btn btn-outline-secondary',
                                                    'title' => 'Modifica piano',
                                                    'data-toggle' => 'tooltip'
                                                ]
                                            ) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Attenzione:</strong> I piani in scadenza necessitano di attenzione. 
                            Considerare il rinnovo o la conclusione dei trattamenti.
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        Nessun piano in scadenza nei prossimi 60 giorni.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>
                        Trend Creazione Piani Mensile (Ultimi 12 Mesi)
                    </h6>
                </div>
                <div class="card-body">
                    <?= ChartWidget::widget([
                        'title' => false,
                        'type' => 'line',
                        'ajaxUrl' => Url::to(['chart-data', 'type' => 'plans-monthly']),
                        'height' => 350,
                        'options' => [
                            'scales' => [
                                'y' => [
                                    'beginAtZero' => true,
                                    'ticks' => [
                                        'precision' => 0
                                    ]
                                ]
                            ],
                            'plugins' => [
                                'legend' => [
                                    'display' => true
                                ]
                            ]
                        ]
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div> 