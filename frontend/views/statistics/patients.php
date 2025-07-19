<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\StatisticsAsset;
use frontend\widgets\StatsCard;
use frontend\widgets\ChartWidget;
use frontend\widgets\StatisticsFilter;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PatientStatisticsSearch */
/* @var $demographics array */
/* @var $byTreatment array */
/* @var $byRegime array */
/* @var $multiTreatmentStats array */
/* @var $treatmentOptions array */
/* @var $regimeOptions array */
/* @var $districtOptions array */

$this->title = 'Analisi Pazienti';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

StatisticsAsset::register($this);

$this->registerJs("
", \yii\web\View::POS_READY);

// Helper function per calcolo sicuro delle percentuali
function calculatePercentage($part, $total, $decimals = 1) {
    return $total > 0 ? round($part / $total * 100, $decimals) : 0;
}

// Calcola il totale pazienti una volta sola
$totalPatients = $demographics['age_stats']['total_patients'] ?? 0;
?>

<div class="patient-statistics p-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
            <i class="fas fa-users text-blue-600"></i>
            Analisi Pazienti
        </h1>
        <div class="flex gap-3">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-2"></i> Dashboard',
                ['index'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-2"></i> Esporta',
                ['export', 'type' => 'patients'],
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
        'options' => ['class' => 'mb-4', 'id' => 'patient-filters-form']
    ]); ?>

    <?= StatisticsFilter::widget([
        'model' => $searchModel,
        'form' => $form,
        'title' => 'Filtri Pazienti',
        'fields' => [
            'gender',
            'ageFrom',
            'ageTo',
            'treatments',
            'status',
            'dateFrom',
            'dateTo'
        ],
        'options' => [
            'treatments' => $treatmentOptions,
            'regimes' => $regimeOptions,
            'districts' => $districtOptions
        ],
        'collapsible' => true,
        'collapsed' => false
    ]) ?>

    <?php ActiveForm::end(); ?>

    <?php if ($totalPatients == 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Nessun paziente trovato con i filtri selezionati.
    </div>
    <?php endif; ?>

    <!-- Demographics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div>
            <?= StatsCard::widget([
                'title' => 'Età Media',
                'value' => round($demographics['age_stats']['avg_age'] ?? 0, 1),
                'icon' => 'fas fa-birthday-cake',
                'color' => 'primary',
                'footer' => 'Range: ' . ($demographics['age_stats']['min_age'] ?? 0) . '-' . ($demographics['age_stats']['max_age'] ?? 0),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div>
            <?= StatsCard::widget([
                'title' => 'Totale Pazienti',
                'value' => $totalPatients,
                'icon' => 'fas fa-users',
                'color' => 'success',
                'footer' => 'Nei filtri selezionati',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div>
            <?php
            $multiTreatmentCount = count($multiTreatmentStats['patients'] ?? []);
            $multiTreatmentPerc = calculatePercentage($multiTreatmentCount, $totalPatients);
            ?>
            <?= StatsCard::widget([
                'title' => 'Trattamenti Multipli',
                'value' => $multiTreatmentCount,
                'icon' => 'fas fa-layer-group',
                'color' => 'info',
                'footer' => $multiTreatmentPerc . '% del totale',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div>
            <?= StatsCard::widget([
                'title' => 'Media Trattamenti',
                'value' => round($multiTreatmentStats['stats']['avg_treatments'] ?? 0, 1),
                'icon' => 'fas fa-chart-bar',
                'color' => 'warning',
                'footer' => 'Max: ' . ($multiTreatmentStats['stats']['max_treatments'] ?? 0),
                'valueFormat' => 'number'
            ]) ?>
        </div>
    </div>

    <!-- Demographics Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div>
            <?= ChartWidget::widget([
                'title' => 'Distribuzione per Età',
                'type' => 'bar',
                'data' => [
                    'labels' => array_column($demographics['age_groups'] ?? [], 'age_group'),
                    'datasets' => [
                        [
                            'label' => 'Numero Pazienti',
                            'data' => array_column($demographics['age_groups'] ?? [], 'count'),
                            'backgroundColor' => '#4e73df'
                        ]
                    ]
                ],
                'height' => 350
            ]) ?>
        </div>

        <div>
            <?= ChartWidget::widget([
                'title' => 'Distribuzione per Genere',
                'type' => 'doughnut',
                'data' => [
                    'labels' => array_column($demographics['gender_distribution'] ?? [], 'gender_label'),
                    'datasets' => [
                        [
                            'label' => 'Numero Pazienti',
                            'data' => array_column($demographics['gender_distribution'] ?? [], 'count'),
                            'backgroundColor' => ['#36b9cc', '#e74a3b', '#858796']
                        ]
                    ]
                ],
                'height' => 350
            ]) ?>
        </div>
    </div>

    <!-- Treatment Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-stethoscope text-blue-600"></i>
                        Pazienti per Trattamento
                    </h6>
                </div>
                <div class="p-6">
                    <?php if (!empty($byTreatment)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Trattamento</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Codice</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Pazienti</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">% del Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byTreatment as $treatment): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= Html::encode($treatment['name']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                            <?= Html::encode($treatment['code']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <?= $treatment['patient_count'] ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php 
                                        $percentage = calculatePercentage($treatment['patient_count'], $totalPatients);
                                        $badgeClass = $percentage >= 20 ? 'bg-green-100 text-green-800' : ($percentage >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium <?= $badgeClass ?> rounded-full">
                                            <?= $percentage ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center p-4 bg-blue-50 text-blue-700 rounded-lg">
                        <i class="fas fa-info-circle mr-3"></i>
                        Nessun dato disponibile per i trattamenti.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <?= ChartWidget::widget([
                'title' => 'Top 10 Trattamenti',
                'type' => 'pie',
                'data' => [
                    'labels' => array_slice(array_column($byTreatment ?? [], 'name'), 0, 10),
                    'datasets' => [
                        [
                            'label' => 'Pazienti',
                            'data' => array_slice(array_column($byTreatment ?? [], 'patient_count'), 0, 10),
                            'backgroundColor' => [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                                '#858796', '#5a5c69', '#1f2937', '#374151', '#6b7280'
                            ]
                        ]
                    ]
                ],
                'height' => 400
            ]) ?>
        </div>
    </div>

    <!-- Multi-Treatment Analysis -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-100">
                <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-layer-group text-blue-600"></i>
                    Pazienti con Trattamenti Multipli (escluso ABA)
                </h6>
            </div>
            <div class="p-6">
                <?php if (!empty($multiTreatmentStats['patients'])): ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Paziente</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">N° Trattamenti</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Trattamenti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($multiTreatmentStats['patients'], 0, 20) as $patient): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <strong class="text-gray-900"><?= Html::encode($patient['patient_name']) ?></strong>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php 
                                    $count = $patient['treatment_count'];
                                    $badgeClass = $count >= 4 ? 'bg-red-100 text-red-800' : ($count >= 3 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium <?= $badgeClass ?> rounded-full">
                                        <?= $count ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm text-gray-600"><?= Html::encode($patient['treatments']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($multiTreatmentStats['patients']) > 20): ?>
                <div class="text-center mt-4">
                    <span class="text-sm text-gray-500">
                        Mostrati i primi 20 di <?= count($multiTreatmentStats['patients']) ?> pazienti con trattamenti multipli
                    </span>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="flex items-center p-4 bg-blue-50 text-blue-700 rounded-lg">
                    <i class="fas fa-info-circle mr-3"></i>
                    Nessun paziente con trattamenti multipli trovato con i filtri selezionati.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Regime Analysis -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-100">
                <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-blue-600"></i>
                    Distribuzione per Regime Sanitario
                </h6>
            </div>
            <div class="p-6">
                <?php if (!empty($byRegime)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($byRegime as $regime): ?>
                    <div class="bg-gray-50 rounded-lg p-6 border-l-4 border-blue-500">
                        <h5 class="text-lg font-semibold text-gray-900 mb-3"><?= Html::encode($regime['regime_name']) ?></h5>
                        <div class="mb-3">
                            <span class="inline-flex px-3 py-2 text-sm font-medium bg-blue-100 text-blue-800 rounded-lg">
                                <?= $regime['patient_count'] ?> pazienti
                            </span>
                        </div>
                        <?php if (!empty($regime['avg_duration']) && $regime['avg_duration'] > 0): ?>
                        <div class="text-sm text-gray-600">
                            Durata media: <?= round($regime['avg_duration']) ?> giorni
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="flex items-center p-4 bg-blue-50 text-blue-700 rounded-lg">
                    <i class="fas fa-info-circle mr-3"></i>
                    Nessun dato disponibile per i regimi sanitari.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>