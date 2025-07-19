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
    Statistics.init();
", \yii\web\View::POS_READY);

?>

<div class="patient-statistics">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users mr-2"></i>
            Analisi Pazienti
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a(
                '<i class="fas fa-arrow-left mr-1"></i> Dashboard',
                ['index'],
                ['class' => 'btn btn-sm btn-secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-download mr-1"></i> Esporta',
                ['export', 'type' => 'patients'],
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

    <!-- Demographics Summary -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'Età Media',
                'value' => round($demographics['age_stats']['avg_age'] ?? 0, 1),
                'icon' => 'fas fa-birthday-cake',
                'color' => 'primary',
                'footer' => 'Range: ' . ($demographics['age_stats']['min_age'] ?? 0) . '-' . ($demographics['age_stats']['max_age'] ?? 0),
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?= StatsCard::widget([
                'title' => 'Totale Pazienti',
                'value' => $demographics['age_stats']['total_patients'] ?? 0,
                'icon' => 'fas fa-users',
                'color' => 'success',
                'footer' => 'Nei filtri selezionati',
                'valueFormat' => 'number'
            ]) ?>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $multiTreatmentCount = count($multiTreatmentStats['patients'] ?? []);
            $totalPatients = $demographics['age_stats']['total_patients'] ?? 1;
            $multiTreatmentPerc = round($multiTreatmentCount / $totalPatients * 100, 1);
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

        <div class="col-xl-3 col-md-6 mb-4">
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
    <div class="row">
        <div class="col-lg-6 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Distribuzione per Età',
                'type' => 'bar',
                'data' => [
                    'labels' => array_column($demographics['age_groups'], 'age_group'),
                    'datasets' => [
                        [
                            'label' => 'Numero Pazienti',
                            'data' => array_column($demographics['age_groups'], 'count'),
                            'backgroundColor' => '#4e73df'
                        ]
                    ]
                ],
                'height' => 300
            ]) ?>
        </div>

        <div class="col-lg-6 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Distribuzione per Genere',
                'type' => 'doughnut',
                'data' => [
                    'labels' => array_column($demographics['gender_distribution'], 'gender_label'),
                    'datasets' => [
                        [
                            'label' => 'Numero Pazienti',
                            'data' => array_column($demographics['gender_distribution'], 'count'),
                            'backgroundColor' => ['#36b9cc', '#e74a3b', '#858796']
                        ]
                    ]
                ],
                'height' => 300
            ]) ?>
        </div>
    </div>

    <!-- Treatment Analysis -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-stethoscope mr-2"></i>
                        Pazienti per Trattamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Trattamento</th>
                                    <th>Codice</th>
                                    <th class="text-center">Pazienti</th>
                                    <th class="text-center">% del Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalPatients = $demographics['age_stats']['total_patients'] ?? 1;
                                foreach ($byTreatment as $treatment): 
                                ?>
                                <tr>
                                    <td><?= Html::encode($treatment['name']) ?></td>
                                    <td><span class="badge badge-secondary"><?= Html::encode($treatment['code']) ?></span></td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $treatment['patient_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $percentage = round($treatment['patient_count'] / $totalPatients * 100, 1);
                                        $badgeClass = $percentage >= 20 ? 'success' : ($percentage >= 10 ? 'warning' : 'info');
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

        <div class="col-lg-4 mb-4">
            <?= ChartWidget::widget([
                'title' => 'Top 10 Trattamenti',
                'type' => 'pie',
                'data' => [
                    'labels' => array_slice(array_column($byTreatment, 'name'), 0, 10),
                    'datasets' => [
                        [
                            'label' => 'Pazienti',
                            'data' => array_slice(array_column($byTreatment, 'patient_count'), 0, 10),
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
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-layer-group mr-2"></i>
                        Pazienti con Trattamenti Multipli (escluso ABA)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($multiTreatmentStats['patients'])): ?>
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
                                <?php foreach (array_slice($multiTreatmentStats['patients'], 0, 20) as $patient): ?>
                                <tr>
                                    <td>
                                        <strong><?= Html::encode($patient['patient_name']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $count = $patient['treatment_count'];
                                        $badgeClass = $count >= 4 ? 'danger' : ($count >= 3 ? 'warning' : 'success');
                                        ?>
                                        <span class="badge badge-<?= $badgeClass ?>"><?= $count ?></span>
                                    </td>
                                    <td>
                                        <small><?= Html::encode($patient['treatments']) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($multiTreatmentStats['patients']) > 20): ?>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Mostrati i primi 20 di <?= count($multiTreatmentStats['patients']) ?> pazienti con trattamenti multipli
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nessun paziente con trattamenti multipli trovato con i filtri selezionati.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Regime Analysis -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clipboard-list mr-2"></i>
                        Distribuzione per Regime Sanitario
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($byRegime as $regime): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= Html::encode($regime['regime_name']) ?></h5>
                                    <p class="card-text">
                                        <span class="badge badge-primary badge-lg"><?= $regime['patient_count'] ?> pazienti</span>
                                    </p>
                                    <?php if ($regime['avg_duration']): ?>
                                    <small class="text-muted">
                                        Durata media: <?= round($regime['avg_duration']) ?> giorni
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 