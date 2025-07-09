<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */

$this->title = 'Piano Terapeutico #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

<div class="therapeutic-plan-view">
    <!-- Header Section -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= Html::encode($this->title) ?></h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Dettagli del piano terapeutico
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <?php if (Yii::$app->user->can('update_therapeutic_plan')): ?>
                    <?= Html::a(
                        '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Modifica',
                        ['update', 'id' => $model->id],
                        [
                            'class' => 'inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2'
                        ]
                    ) ?>
                <?php endif; ?>
                
                <?php if (Yii::$app->user->can('delete_therapeutic_plan')): ?>
                    <?= Html::a(
                        '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Elimina',
                        ['delete', 'id' => $model->id],
                        [
                            'class' => 'inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                            'data-confirm' => 'Sei sicuro di voler eliminare questo piano terapeutico?',
                            'data-method' => 'post',
                        ]
                    ) ?>
                <?php endif; ?>
                
                <?= Html::a(
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Torna alla Lista',
                    ['index'],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2'
                    ]
                ) ?>
            </div>
        </div>
    </div>

    <!-- Status Alert -->
    <?php if ($model->isExpired()): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Piano Terapeutico Scaduto</h3>
                    <p class="mt-1 text-sm text-red-700">
                        Questo piano terapeutico è scaduto il <?= Yii::$app->formatter->asDate($model->end_date) ?>.
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Piano Terapeutico Attivo</h3>
                    <p class="mt-1 text-sm text-green-700">
                        Questo piano terapeutico è attivo<?= $model->end_date ? ' fino al ' . Yii::$app->formatter->asDate($model->end_date) : '' ?>.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Details Card -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Dettagli Piano Terapeutico</h3>
        </div>
        <div class="p-6">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table-auto w-full'],
                'template' => '<tr class="border-b border-gray-200 dark:border-gray-700"><td class="py-3 px-4 text-sm font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 w-1/3">{label}</td><td class="py-3 px-4 text-sm text-gray-700 dark:text-gray-300">{value}</td></tr>',
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'patient_id',
                        'label' => 'Paziente',
                        'value' => function($model) {
                            if ($model->patient) {
                                return $model->patient->fullName . ' (' . $model->patient->fiscal_code . ')';
                            }
                            return 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'regime_id',
                        'label' => 'Regime',
                        'value' => function($model) {
                            return $model->regime ? $model->regime->nome : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'value' => function($model) {
                            return $model->start_date ? Yii::$app->formatter->asDate($model->start_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'duration_days',
                        'label' => 'Durata',
                        'value' => function($model) {
                            return $model->getFormattedDuration();
                        }
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'value' => function($model) {
                            return $model->end_date ? Yii::$app->formatter->asDate($model->end_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'notes',
                        'label' => 'Note',
                        'value' => function($model) {
                            return $model->notes ? nl2br(Html::encode($model->notes)) : 'Nessuna nota';
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'created_by',
                        'label' => 'Creato da',
                        'value' => function($model) {
                            return $model->createdBy ? $model->createdBy->username : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Data Creazione',
                        'value' => function($model) {
                            return Yii::$app->formatter->asDatetime($model->created_at);
                        }
                    ],
                    [
                        'attribute' => 'updated_at',
                        'label' => 'Ultimo Aggiornamento',
                        'value' => function($model) {
                            return Yii::$app->formatter->asDatetime($model->updated_at);
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Related Information -->
    <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Terapie Assegnate</h3>
        </div>
        <div class="p-6">
            <?php if ($model->planTherapies): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($model->planTherapies as $therapy): ?>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-base font-medium text-gray-900 dark:text-white">
                                        <?= Html::encode($therapy->treatmentType->name) ?>
                                        <?php if ($therapy->is_group): ?>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                Gruppo
                                            </span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Ore settimanali:</span> <?= Html::encode($therapy->weekly_hours) ?>
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Setting:</span> <?= Html::encode($therapy->setting->nome) ?>
                                        </p>
                                        <?php if ($therapy->notes): ?>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                <span class="font-medium">Note:</span><br>
                                                <?= nl2br(Html::encode($therapy->notes)) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php
                                            $totalHours = $therapy->weekly_hours * ($model->duration_days / 7);
                                            echo sprintf("%.1f ore totali", $totalHours);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessuna terapia assegnata a questo piano.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Patient Info Section -->
    <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Informazioni Aggiuntive</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Patient Info -->
                <?php if ($model->patient): ?>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Informazioni Paziente</h4>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Nome:</span> <?= Html::encode($model->patient->fullName) ?>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Codice Fiscale:</span> <?= Html::encode($model->patient->fiscal_code) ?>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Data di Nascita:</span> 
                                <?= $model->patient->birth_date ? Yii::$app->formatter->asDate($model->patient->birth_date) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Regime Info -->
                <?php if ($model->regime): ?>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Informazioni Regime</h4>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Nome:</span> <?= Html::encode($model->regime->nome) ?>
                            </p>
                            <?php if ($model->regime->descrizione): ?>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Descrizione:</span> <?= Html::encode($model->regime->descrizione) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 