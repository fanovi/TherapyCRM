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

<div class="dashboard-statistics">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar mr-2"></i>
            Dashboard Statistiche
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="fas fa-sync-alt mr-1"></i> Aggiorna',
                ['index'],
                ['class' => 'btn btn-sm btn-primary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-chart-line mr-1"></i> Analisi Dettagliate',
                '#',
                [
                    'class' => 'btn btn-sm btn-outline-primary dropdown-toggle',
                    'data-toggle' => 'dropdown'
                ]
            ) ?>
            <div class="dropdown-menu">
                <?= Html::a('Analisi Assenze', ['absences'], ['class' => 'dropdown-item']) ?>
                <?= Html::a('Analisi Pazienti', ['patients'], ['class' => 'dropdown-item']) ?>
                <?= Html::a('Analisi Trattamenti', ['treatments'], ['class' => 'dropdown-item']) ?>
                <?= Html::a('Analisi Piani', ['plans'], ['class' => 'dropdown-item']) ?>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= Html::encode($error) ?>
    </div>
    <?php else: ?>

    <!-- Summary Cards Row -->
    <div class="row dashboard-summary">
        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="row dashboard-charts">
        <div class="col-lg-8 mb-4">
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

        <div class="col-lg-4 mb-4">
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
    <div class="row">
        <div class="col-lg-6 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Trend Assenze per Giorno Settimana',
                'type' => 'bar',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'absence-by-day']),
                'height' => 300
            ]) ?>
        </div>

        <div class="col-lg-6 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Distribuzione Età Pazienti',
                'type' => 'pie',
                'ajaxUrl' => Url::to(['chart-data', 'type' => 'patient-age-groups']),
                'height' => 300
            ]) ?>
        </div>
    </div>

    <!-- Quick Links Section -->
    <div class="mb-6">
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h6 class="text-lg font-semibold text-blue-light-500 m-0">
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