<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\StatisticsAsset;
use frontend\widgets\StatsCard;
use frontend\widgets\ChartWidget;
use frontend\widgets\StatisticsFilter;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\TreatmentStatisticsSearch */
/* @var $ranking array */
/* @var $combinations array */
/* @var $bySettingType array */
/* @var $hoursDistribution array */
/* @var $searchResults array */
/* @var $treatmentOptions array */
/* @var $regimeOptions array */

$this->title = 'Analisi Trattamenti';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

StatisticsAsset::register($this);

$this->registerJs("
", \yii\web\View::POS_READY);

?>

<div class="treatment-statistics p-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
            <i class="fas fa-stethoscope text-blue-600"></i>
            Analisi Trattamenti
        </h1>
        <div class="flex gap-3">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-2"></i> Dashboard',
                ['index'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-2"></i> Esporta',
                ['export', 'type' => 'treatments'],
                [
                    'class' => 'inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600',
                    'data-method' => 'post'
                ]
            ) ?>
        </div>
    </div>

    <!-- Treatment Search/Filter -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-100">
                <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-search text-blue-600"></i>
                    Ricerca Combinazioni Trattamenti
                </h6>
            </div>
            <div class="p-6">
                <?php $form = ActiveForm::begin([
                    'method' => 'get',
                    'options' => ['id' => 'treatment-search-form']
                ]); ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div>
                        <?= $form->field($searchModel, 'treatmentIds', [
                            'options' => ['class' => 'mb-0'],
                            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
                        ])->dropDownList($treatmentOptions, [
                            'multiple' => true,
                            'prompt' => 'Seleziona trattamenti da analizzare...',
                            'size' => 8,
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[200px] max-h-[300px]'
                        ])->label('Trattamenti') ?>
                    </div>

                    <div>
                        <?= StatisticsFilter::widget([
                            'model' => $searchModel,
                            'form' => $form,
                            'title' => false,
                            'fields' => ['combinationMode'],
                            'collapsible' => false
                        ]) ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <?= $form->field($searchModel, 'dateFrom', [
                            'options' => ['class' => 'mb-0'],
                            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
                        ])->input('date', [
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
                        ])->label('Data da') ?>
                    </div>

                    <div>
                        <?= $form->field($searchModel, 'dateTo', [
                            'options' => ['class' => 'mb-0'],
                            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
                        ])->input('date', [
                            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
                        ])->label('Data a') ?>
                    </div>

                    <div class="flex items-center">
                        <?= $form->field($searchModel, 'includeInactive', [
                            'options' => ['class' => 'mb-0'],
                            'template' => '<div class="flex items-center space-x-2">{input}{label}</div>'
                        ])->checkbox([
                            'class' => 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500'
                        ])->label('Includi inattivi') ?>
                    </div>
                </div>

                <div class="flex justify-center gap-3">
                    <?= Html::submitButton('<i class="fas fa-search mr-2"></i> Analizza', [
                        'class' => 'inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600'
                    ]) ?>
                    <?= Html::a('<i class="fas fa-eraser mr-2"></i> Pulisci', ['treatments'], [
                        'class' => 'inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    <?php if (!empty($searchResults)): ?>
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow border-l-4 border-blue-500">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-chart-bar text-blue-600"></i>
                        Risultati Ricerca Combinazioni
                    </h6>
                </div>
                <div class="p-6">
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
                                <?php foreach ($searchResults as $result): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <strong class="text-gray-900">
                                                <?php
                                                // Gestisci diversi formati possibili di dati
                                                if (isset($result['first_name']) && isset($result['last_name'])) {
                                                    echo Html::encode($result['first_name'] . ' ' . $result['last_name']);
                                                } elseif (isset($result['patient_name'])) {
                                                    echo Html::encode($result['patient_name']);
                                                } elseif (isset($result['name'])) {
                                                    echo Html::encode($result['name']);
                                                } else {
                                                    echo 'N/D';
                                                }
                                                ?>
                                            </strong>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                <?= isset($result['treatment_count']) ? $result['treatment_count'] : 0 ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-sm text-gray-600">
                                                <?= Html::encode(isset($result['treatments']) ? $result['treatments'] : 'N/D') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <span class="inline-flex px-3 py-2 text-sm font-medium bg-blue-100 text-blue-800 rounded-lg">
                            Trovati <?= count($searchResults) ?> pazienti con la combinazione richiesta
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div>
            <?= StatsCard::widget([
                'title' => 'Tipi di Trattamento',
                'value' => count($ranking),
                'icon' => 'fas fa-list',
                'color' => 'primary',
                'footer' => 'Totale disponibili',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div>
            <?php
            $totalPatients = array_sum(array_column($ranking, 'patient_count'));
            ?>
            <?= StatsCard::widget([
                'title' => 'Pazienti Totali',
                'value' => $totalPatients,
                'icon' => 'fas fa-users',
                'color' => 'success',
                'footer' => 'Con almeno un trattamento',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div>
            <?php
            $totalHours = array_sum(array_column($ranking, 'total_weekly_hours'));
            ?>
            <?= StatsCard::widget([
                'title' => 'Ore Settimanali',
                'value' => $totalHours,
                'icon' => 'fas fa-clock',
                'color' => 'info',
                'footer' => 'Totale programmate',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div>
            <?= StatsCard::widget([
                'title' => 'Combinazioni Frequenti',
                'value' => count($combinations),
                'icon' => 'fas fa-layer-group',
                'color' => 'warning',
                'footer' => 'Trattamenti multipli',
                'valueFormat' => 'number'
            ]) ?>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2">
            <?= ChartWidget::widget([
                'title' => 'Ranking Trattamenti per Numero Pazienti',
                'type' => 'bar',
                'data' => [
                    'labels' => array_slice(array_column($ranking, 'name'), 0, 15),
                    'datasets' => [
                        [
                            'label' => 'Numero Pazienti',
                            'data' => array_slice(array_column($ranking, 'patient_count'), 0, 15),
                            'backgroundColor' => '#4e73df'
                        ]
                    ]
                ],
                'height' => 400,
                'options' => [
                    'indexAxis' => 'y',
                    'scales' => [
                        'x' => [
                            'beginAtZero' => true
                        ]
                    ]
                ]
            ]) ?>
        </div>

        <div>
            <?= ChartWidget::widget([
                'title' => 'Distribuzione Ore Settimanali',
                'type' => 'doughnut',
                'data' => [
                    'labels' => array_column($hoursDistribution, 'hours_range'),
                    'datasets' => [
                        [
                            'label' => 'Numero Terapie',
                            'data' => array_column($hoursDistribution, 'therapy_count'),
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
                'height' => 400
            ]) ?>
        </div>
    </div>

    <!-- Setting Type Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div>
            <div class="bg-white rounded-lg shadow h-full">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-users text-blue-600"></i>
                        Terapie Individuali vs Gruppo
                    </h6>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($bySettingType as $setting): ?>
                            <div class="text-center p-4 border border-gray-200 rounded-lg bg-gray-50">
                                <h4 class="text-lg font-semibold text-blue-600 mb-3"><?= $setting['setting_type'] ?></h4>
                                <div class="space-y-2">
                                    <div>
                                        <span class="inline-flex px-3 py-2 text-sm font-medium bg-blue-100 text-blue-800 rounded-lg">
                                            <?= $setting['therapy_count'] ?>
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">Terapie</div>
                                    </div>
                                    <div>
                                        <span class="inline-flex px-3 py-2 text-sm font-medium bg-green-100 text-green-800 rounded-lg">
                                            <?= $setting['patient_count'] ?>
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">Pazienti</div>
                                    </div>
                                    <div>
                                        <span class="inline-flex px-3 py-2 text-sm font-medium bg-blue-100 text-blue-800 rounded-lg">
                                            <?= round($setting['avg_hours'], 1) ?>h
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">Ore medie</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <?= ChartWidget::widget([
                'title' => 'Confronto Individuale vs Gruppo',
                'type' => 'bar',
                'data' => [
                    'labels' => ['Terapie', 'Pazienti', 'Ore Totali'],
                    'datasets' => [
                        [
                            'label' => $bySettingType[0]['setting_type'] ?? 'Gruppo',
                            'data' => [
                                $bySettingType[0]['therapy_count'] ?? 0,
                                $bySettingType[0]['patient_count'] ?? 0,
                                $bySettingType[0]['total_hours'] ?? 0
                            ],
                            'backgroundColor' => '#4e73df'
                        ],
                        [
                            'label' => $bySettingType[1]['setting_type'] ?? 'Individuale',
                            'data' => [
                                $bySettingType[1]['therapy_count'] ?? 0,
                                $bySettingType[1]['patient_count'] ?? 0,
                                $bySettingType[1]['total_hours'] ?? 0
                            ],
                            'backgroundColor' => '#1cc88a'
                        ]
                    ]
                ],
                'height' => 350
            ]) ?>
        </div>
    </div>

    <!-- Detailed Ranking Table -->
    <div class="mb-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-100">
                <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-table text-blue-600"></i>
                    Ranking Dettagliato Trattamenti
                </h6>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">#</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Trattamento</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Codice</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Pazienti</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Terapie</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Ore Settimanali</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">Ore Medie</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-700">% Pazienti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $index => $treatment): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <strong class="text-gray-900"><?= $index + 1 ?></strong>
                                    </td>
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
                                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            <?= $treatment['therapy_count'] ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="text-sm text-gray-600">
                                            <?= round($treatment['total_weekly_hours'] ?? 0, 1) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="text-sm text-gray-600">
                                            <?= round($treatment['avg_weekly_hours'] ?? 0, 1) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php
                                        $percentage = $totalPatients > 0 ? round($treatment['patient_count'] / $totalPatients * 100, 1) : 0;
                                        $badgeClass = $percentage >= 20 ? 'bg-green-100 text-green-800' : ($percentage >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800');
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
            </div>
        </div>
    </div>

    <!-- Frequent Combinations -->
    <?php if (!empty($combinations)): ?>
        <div class="mb-8">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-layer-group text-blue-600"></i>
                        Combinazioni di Trattamenti Più Frequenti
                    </h6>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">#</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Combinazione Trattamenti</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">N° Trattamenti</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">N° Pazienti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($combinations as $index => $combo): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <strong class="text-gray-900"><?= $index + 1 ?></strong>
                                        </td>
                                        <td class="py-3 px-4"><?= Html::encode($combo['combination']) ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                <?= $combo['treatment_count'] ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                <?= $combo['patient_count'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>