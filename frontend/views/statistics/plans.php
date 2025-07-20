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
", \yii\web\View::POS_READY);

?>

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
  <div class="space-y-4 md:space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4 sm:mb-0">
            <i class="fas fa-clipboard-list mr-2"></i>
            Analisi Piani Terapeutici
        </h1>
        <div class="flex gap-3">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-2"></i> Dashboard',
                ['index'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-2"></i> Esporta',
                ['export', 'type' => 'plans'],
                [
                    'class' => 'inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600',
                    'data-method' => 'post'
                ]
            ) ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="col-span-1">
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

        <div class="col-span-1">
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

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'In Scadenza',
                'value' => count($plansStats['expiring_list']),
                'icon' => 'fas fa-clock',
                'color' => 'warning',
                'footer' => 'Prossimi 60 giorni',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-span-1">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="col-span-1">
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

        <div class="col-span-1">
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

        <div class="col-span-1">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h6 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-percentage mr-2"></i>
                        Top 10 Piani per Tasso di Completamento
                    </h6>
                </div>
                <div class="p-6">
                    <?php if (!empty($plansStats['completion_rates'])): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Piano ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paziente</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Appuntamenti Totali</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Completati</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tasso</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($plansStats['completion_rates'] as $plan): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><?= $plan['id'] ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= Html::encode($plan['patient_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm text-gray-900"><?= $plan['total_appointments'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><?= $plan['completed_appointments'] ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <?php 
                                        $rate = $plan['completion_rate'];
                                        $badgeClass = $rate >= 80 ? 'bg-green-100 text-green-800' : ($rate >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>"><?= $rate ?>%</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">Nessun dato di completamento disponibile.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h6 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-info-circle mr-2"></i>
                        Statistiche Durata
                    </h6>
                </div>
                <div class="p-6">
                    <?php foreach ($plansStats['by_duration'] as $duration): ?>
                    <div class="mb-4 p-4 border border-gray-200 rounded-lg">
                        <h6 class="text-blue-600 font-medium mb-2">
                            <?php
                            switch($duration['duration_category']) {
                                case 'short': echo 'Piani Brevi (<90 giorni)'; break;
                                case 'medium': echo 'Piani Medi (90-365 giorni)'; break;
                                case 'long': echo 'Piani Lunghi (>365 giorni)'; break;
                                default: echo Html::encode($duration['duration_category']);
                            }
                            ?>
                        </h6>
                        <p class="mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><?= $duration['count'] ?></span>
                            <span class="text-sm text-gray-500 ml-2">piani</span>
                        </p>
                        <p class="text-sm text-gray-600">
                            Durata media: <span class="font-medium text-gray-900"><?= round($duration['avg_duration']) ?> giorni</span>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Plans -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h6 class="text-lg font-semibold text-yellow-600">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Piani in Scadenza (Prossimi 60 Giorni)
                </h6>
            </div>
            <div class="p-6">
                <?php if (!empty($plansStats['expiring_list'])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Piano ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paziente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Scadenza</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Giorni Rimanenti</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Urgenza</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($plansStats['expiring_list'] as $plan): ?>
                            <?php
                            $daysLeft = $plan['days_until_expiry'];
                            $urgencyClass = $daysLeft <= 7 ? 'bg-red-100 text-red-800' : ($daysLeft <= 30 ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                            $urgencyText = $daysLeft <= 7 ? 'Critica' : ($daysLeft <= 30 ? 'Alta' : 'Media');
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><?= $plan['id'] ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= Html::encode($plan['patient_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= date('d/m/Y', strtotime($plan['end_date'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $urgencyClass ?>"><?= $daysLeft ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $urgencyClass ?>"><?= $urgencyText ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <span class="font-medium">Attenzione:</span> I piani in scadenza necessitano di attenzione. 
                                    Considerare il rinnovo o la conclusione dei trattamenti.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">Nessun piano in scadenza nei prossimi 60 giorni.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h6 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-chart-line mr-2"></i>
                    Trend Creazione Piani Mensile (Ultimi 12 Mesi)
                </h6>
            </div>
            <div class="p-6">
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