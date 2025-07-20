<?php

use yii\helpers\Html;
use yii\helpers\Url;
use frontend\assets\StatisticsAsset;
use frontend\widgets\StatsCard;
use frontend\widgets\ChartWidget;

/* @var $this yii\web\View */
/* @var $summary array */
/* @var $topTreatments array */
/* @var $patientGrowth array */

$this->title = 'Dashboard Statistiche';
$this->params['breadcrumbs'][] = $this->title;

StatisticsAsset::register($this);

$this->registerJs("
    document.body.classList.add('dashboard-page');
", \yii\web\View::POS_READY);

?>

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
  <div class="space-y-4 md:space-y-6">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4 sm:mb-0">
            <i class="fas fa-chart-bar mr-2"></i>
            Dashboard Statistiche
        </h1>
        <div class="flex gap-3">
            <?= Html::a(
                '<i class="fas fa-sync-alt mr-2"></i> Aggiorna',
                ['index'],
                ['class' => 'inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600']
            ) ?>
            <div class="relative" x-data="{ open: false }">
                <?= Html::a(
                    '<i class="fas fa-chart-line mr-2"></i> Analisi Dettagliate',
                    '#',
                    [
                        'class' => 'inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50',
                        '@click.prevent' => 'open = !open',
                        '@click.away' => 'open = false'
                    ]
                ) ?>
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                    <div class="py-1">
                        <?= Html::a('Analisi Assenze', ['absences'], ['class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100']) ?>
                        <?= Html::a('Analisi Pazienti', ['patients'], ['class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100']) ?>
                        <?= Html::a('Analisi Trattamenti', ['treatments'], ['class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100']) ?>
                        <?= Html::a('Analisi Piani', ['plans'], ['class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700"><?= Html::encode($error) ?></p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <!-- Summary Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Pazienti Attivi',
                'value' => $summary['patients']['active'] ?? 0,
                'icon' => 'fas fa-users',
                'color' => 'primary',
                'footer' => 'Totale: ' . ($summary['patients']['total'] ?? 0),
                'url' => Url::to(['patients']),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Assenze Questo Mese',
                'value' => $summary['absences']['total_this_month'] ?? 0,
                'icon' => 'fas fa-calendar-times',
                'color' => 'danger',
                'footer' => 'Tasso ingiustificate: ' . ($summary['absences']['unjustified_rate'] ?? 0) . '%',
                'url' => Url::to(['absences']),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Trattamenti Attivi',
                'value' => $summary['treatments']['active_types'] ?? 0,
                'icon' => 'fas fa-stethoscope',
                'color' => 'success',
                'footer' => 'Ore settimanali: ' . ($summary['treatments']['total_weekly_hours'] ?? 0),
                'url' => Url::to(['treatments']),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= StatsCard::widget([
                'title' => 'Piani in Scadenza',
                'value' => $summary['plans']['expiring_soon'] ?? 0,
                'icon' => 'fas fa-clock',
                'color' => 'warning',
                'footer' => 'Piani attivi: ' . ($summary['plans']['active'] ?? 0),
                'url' => Url::to(['plans']),
                'valueFormat' => 'number'
            ]) ?>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <?= ChartWidget::widget([
                'title' => 'Crescita Pazienti (Ultimi 6 Mesi)',
                'type' => 'line',
                'data' => [
                    'labels' => array_column($patientGrowth, 'month'),
                    'datasets' => [
                        [
                            'label' => 'Nuovi Pazienti',
                            'data' => array_column($patientGrowth, 'new_patients'),
                            'borderColor' => '#4e73df',
                            'backgroundColor' => 'rgba(78, 115, 223, 0.1)',
                            'fill' => true,
                            'tension' => 0.4
                        ]
                    ]
                ],
                'height' => 300,
                'options' => [
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'ticks' => ['precision' => 0]
                        ]
                    ]
                ]
            ]) ?>
        </div>

        <div class="lg:col-span-1">
            <?= ChartWidget::widget([
                'title' => 'Top 5 Trattamenti',
                'type' => 'doughnut',
                'data' => [
                    'labels' => array_column($topTreatments, 'name'),
                    'datasets' => [
                        [
                            'label' => 'Pazienti',
                            'data' => array_column($topTreatments, 'patient_count'),
                            'backgroundColor' => [
                                '#4e73df',
                                '#1cc88a',
                                '#36b9cc',
                                '#f6c23e',
                                '#e74a3b'
                            ]
                        ]
                    ]
                ],
                'height' => 300,
                'options' => [
                    'plugins' => [
                        'legend' => [
                            'position' => 'bottom'
                        ]
                    ]
                ]
            ]) ?>
        </div>
    </div>

    <!-- Dynamic Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="col-span-1">
            <?= ChartWidget::widget([
                'title' => 'Trend Assenze per Giorno Settimana',
                'type' => 'bar',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'absence-by-day']),
                'height' => 300
            ]) ?>
        </div>

        <div class="col-span-1">
            <?= ChartWidget::widget([
                'title' => 'Distribuzione Età Pazienti',
                'type' => 'pie',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'patient-age-groups']),
                'height' => 300
            ]) ?>
        </div>
    </div>

    <!-- Quick Links Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h6 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-link mr-2"></i>
                Accesso Rapido alle Analisi
            </h6>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-blue-light-500 text-white rounded-lg p-6 h-full">
                        <div class="text-center">
                            <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                            <h5 class="text-lg font-semibold mb-2">Assenze</h5>
                            <p class="text-sm opacity-75 mb-4">Analisi dettagliata pattern assenze</p>
                            <?= Html::a('Vai all\'analisi', ['absences'], [
                                'class' => 'inline-block bg-white text-blue-light-500 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors duration-200'
                            ]) ?>
                        </div>
                    </div>

                    <div class="bg-success-500 text-white rounded-lg p-6 h-full">
                        <div class="text-center">
                            <i class="fas fa-users text-4xl mb-3 block"></i>
                            <h5 class="text-lg font-semibold mb-2">Pazienti</h5>
                            <p class="text-sm opacity-75 mb-4">Demografia e trattamenti multipli</p>
                            <?= Html::a('Vai all\'analisi', ['patients'], [
                                'class' => 'inline-block bg-white text-success-500 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors duration-200'
                            ]) ?>
                        </div>
                    </div>

                    <div class="bg-brand-500 text-white rounded-lg p-6 h-full">
                        <div class="text-center">
                            <i class="fas fa-stethoscope text-4xl mb-3 block"></i>
                            <h5 class="text-lg font-semibold mb-2">Trattamenti</h5>
                            <p class="text-sm opacity-75 mb-4">Ranking e combinazioni</p>
                            <?= Html::a('Vai all\'analisi', ['treatments'], [
                                'class' => 'inline-block bg-white text-brand-500 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors duration-200'
                            ]) ?>
                        </div>
                    </div>

                    <div class="bg-warning-500 text-white rounded-lg p-6 h-full">
                        <div class="text-center">
                            <i class="fas fa-clipboard-list text-4xl mb-3 block"></i>
                            <h5 class="text-lg font-semibold mb-2">Piani</h5>
                            <p class="text-sm opacity-75 mb-4">Stati e scadenze</p>
                            <?= Html::a('Vai all\'analisi', ['plans'], [
                                'class' => 'inline-block bg-white text-warning-500 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors duration-200'
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
  </div>
</div> 