<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Piani Terapeutici';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="therapeutic-plan-index">
    <!-- Header Section -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= Html::encode($this->title) ?></h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Gestisci i piani terapeutici dei pazienti
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <?= Html::a(
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nuovo Piano Terapeutico',
                    ['create'],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                    ]
                ) ?>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <?php Pjax::begin(); ?>
            
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => '{items}{pager}',
                'tableOptions' => [
                    'class' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700'
                ],
                'options' => [
                    'class' => 'overflow-hidden'
                ],
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white'],
                        'options' => ['style' => 'width: 80px;'],
                    ],
                    [
                        'attribute' => 'patient_id',
                        'label' => 'Paziente',
                        'value' => function($model) {
                            return $model->patient ? $model->patient->fullName : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'content' => function($model) {
                            $patientName = $model->patient ? $model->patient->fullName : 'N/A';
                            return '<div class="text-sm font-medium text-gray-900 dark:text-white">' . Html::encode($patientName) . '</div>';
                        }
                    ],
                    [
                        'attribute' => 'regime_id',
                        'label' => 'Regime',
                        'value' => function($model) {
                            return $model->regime ? $model->regime->nome : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'content' => function($model) {
                            $regimeName = $model->regime ? $model->regime->nome : 'N/A';
                            return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . 
                                   Html::encode($regimeName) . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'value' => function($model) {
                            return $model->start_date ? Yii::$app->formatter->asDate($model->start_date) : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'],
                    ],
                    [
                        'attribute' => 'duration_days',
                        'label' => 'Durata',
                        'value' => function($model) {
                            return $model->getFormattedDuration();
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'],
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'value' => function($model) {
                            return $model->end_date ? Yii::$app->formatter->asDate($model->end_date) : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'content' => function($model) {
                            if (!$model->end_date) {
                                return '<span class="text-sm text-gray-500 dark:text-gray-400">N/A</span>';
                            }
                            
                            $isExpired = $model->isExpired();
                            $badgeClass = $isExpired ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                            
                            return '<div class="text-sm text-gray-900 dark:text-white">' . 
                                   Yii::$app->formatter->asDate($model->end_date) . 
                                   '</div><span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $badgeClass . '">' .
                                   ($isExpired ? 'Scaduto' : 'Attivo') . '</span>';
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium'],
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>',
                                    $url,
                                    [
                                        'title' => 'Visualizza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3',
                                        'data-pjax' => '0'
                                    ]
                                );
                            },
                            'update' => function ($url, $model, $key) {
                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>',
                                    $url,
                                    [
                                        'title' => 'Modifica',
                                        'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3',
                                        'data-pjax' => '0'
                                    ]
                                );
                            },
                            'delete' => function ($url, $model, $key) {
                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>',
                                    $url,
                                    [
                                        'title' => 'Elimina',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
                                        'data-confirm' => 'Sei sicuro di voler eliminare questo piano terapeutico?',
                                        'data-method' => 'post',
                                        'data-pjax' => '0'
                                    ]
                                );
                            },
                        ],
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>
</div> 