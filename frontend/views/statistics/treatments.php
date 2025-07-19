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
    Statistics.init();
", \yii\web\View::POS_READY);

?>

<div class="treatment-statistics">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-stethoscope mr-2"></i>
            Analisi Trattamenti
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-1"></i> Dashboard',
                ['index'],
                ['class' => 'btn btn-sm btn-secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-1"></i> Esporta',
                ['export', 'type' => 'treatments'],
                [
                    'class' => 'btn btn-sm btn-success',
                    'data-method' => 'post'
                ]
            ) ?>
        </div>
    </div>

    <!-- Treatment Search/Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-search mr-2"></i>
                        Ricerca Combinazioni Trattamenti
                    </h6>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'method' => 'get',
                        'options' => ['id' => 'treatment-search-form']
                    ]); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($searchModel, 'treatmentIds')->dropDownList($treatmentOptions, [
                                'multiple' => true,
                                'prompt' => 'Seleziona trattamenti da analizzare...',
                                'size' => 6
                            ])->label('Trattamenti') ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?= StatisticsFilter::widget([
                                'model' => $searchModel,
                                'form' => $form,
                                'title' => false,
                                'fields' => ['combinationMode'],
                                'collapsible' => false
                            ]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <?= $form->field($searchModel, 'dateFrom')->input('date') ?>
                        </div>
                        
                        <div class="col-md-4">
                            <?= $form->field($searchModel, 'dateTo')->input('date') ?>
                        </div>
                        
                        <div class="col-md-4">
                            <?= $form->field($searchModel, 'includeInactive')->checkbox() ?>
                        </div>
                    </div>

                    <div class="text-center">
                        <?= Html::submitButton('<i class="fas fa-search mr-1"></i> Analizza', [
                            'class' => 'btn btn-primary'
                        ]) ?>
                        <?= Html::a('<i class="fas fa-eraser mr-1"></i> Pulisci', ['treatments'], [
                            'class' => 'btn btn-outline-secondary ml-2'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    <?php if (!empty($searchResults)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-info">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Risultati Ricerca Combinazioni
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Paziente</th>
                                    <th class="text-center">N° Trattamenti</th>
                                    <th>Trattamenti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchResults as $result): ?>
                                <tr>
                                    <td>
                                        <strong><?= Html::encode($result['first_name'] . ' ' . $result['last_name']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $result['treatment_count'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= Html::encode($result['treatments']) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <span class="badge badge-info">
                            Trovati <?= count($searchResults) ?> pazienti con la combinazione richiesta
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'Tipi di Trattamento',
                'value' => count($ranking),
                'icon' => 'fas fa-list',
                'color' => 'primary',
                'footer' => 'Totale disponibili',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="row">
        <div class="col-lg-8 mb-4">
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

        <div class="col-lg-4 mb-4">
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
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                            ]
                        ]
                    ]
                ],
                'height' => 400
            ]) ?>
        </div>
    </div>

    <!-- Setting Type Analysis -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users mr-2"></i>
                        Terapie Individuali vs Gruppo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($bySettingType as $setting): ?>
                        <div class="col-6 text-center">
                            <div class="p-3 border rounded">
                                <h4 class="text-primary"><?= $setting['setting_type'] ?></h4>
                                <p class="mb-1">
                                    <span class="badge badge-primary badge-lg"><?= $setting['therapy_count'] ?></span>
                                    <small class="text-muted d-block">Terapie</small>
                                </p>
                                <p class="mb-1">
                                    <span class="badge badge-success badge-lg"><?= $setting['patient_count'] ?></span>
                                    <small class="text-muted d-block">Pazienti</small>
                                </p>
                                <p class="mb-0">
                                    <span class="badge badge-info"><?= round($setting['avg_hours'], 1) ?>h</span>
                                    <small class="text-muted d-block">Ore medie</small>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
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
                'height' => 300
            ]) ?>
        </div>
    </div>

    <!-- Detailed Ranking Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table mr-2"></i>
                        Ranking Dettagliato Trattamenti
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Trattamento</th>
                                    <th>Codice</th>
                                    <th class="text-center">Pazienti</th>
                                    <th class="text-center">Terapie</th>
                                    <th class="text-center">Ore Settimanali</th>
                                    <th class="text-center">Ore Medie</th>
                                    <th class="text-center">% Pazienti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ranking as $index => $treatment): ?>
                                <tr>
                                    <td><strong><?= $index + 1 ?></strong></td>
                                    <td><?= Html::encode($treatment['name']) ?></td>
                                    <td><span class="badge badge-secondary"><?= Html::encode($treatment['code']) ?></span></td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $treatment['patient_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $treatment['therapy_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= round($treatment['total_weekly_hours'] ?? 0, 1) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= round($treatment['avg_weekly_hours'] ?? 0, 1) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $percentage = $totalPatients > 0 ? round($treatment['patient_count'] / $totalPatients * 100, 1) : 0;
                                        $badgeClass = $percentage >= 20 ? 'success' : ($percentage >= 10 ? 'warning' : 'secondary');
                                        ?>
                                        <span class="badge badge-<?= $badgeClass ?>"><?= $percentage ?>%</span>
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

    <!-- Frequent Combinations -->
    <?php if (!empty($combinations)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-layer-group mr-2"></i>
                        Combinazioni di Trattamenti Più Frequenti
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Combinazione Trattamenti</th>
                                    <th class="text-center">N° Trattamenti</th>
                                    <th class="text-center">N° Pazienti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($combinations as $index => $combo): ?>
                                <tr>
                                    <td><strong><?= $index + 1 ?></strong></td>
                                    <td><?= Html::encode($combo['combination']) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $combo['treatment_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $combo['patient_count'] ?></span>
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
    <?php endif; ?>
</div> 