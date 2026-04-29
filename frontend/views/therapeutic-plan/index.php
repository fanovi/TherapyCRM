<?php

use common\helpers\GridViewHelper;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\TherapeuticPlanSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $regimes array */
/* @var $districts array */

$this->title = 'Piani Terapeutici';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <!-- Action Button -->
            <?php if (Yii::$app->user->can('create_therapeutic_plan')) : ?>
            <div>
                <?= Html::a(
                    '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Nuovo Piano Terapeutico',
                    ['create'],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                    ]
                ) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Piani Terapeutici
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci i piani terapeutici dei pazienti
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovati ' . $dataProvider->totalCount . ' piani terapeutici' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['index'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#therapeutic-plan-grid-pjax"});'
                ]) ?>
            </div>
        </div>

        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'therapeutic-plan-grid-pjax']); ?>
            
            <?= GridView::widget(array_merge([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => ['class' => 'min-w-full'],
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'ID...'],
                        'options' => ['style' => 'width: 80px;'],
                    ],
                    [
                        'attribute' => 'protocol_number',
                        'label' => 'N. Protocollo',
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Protocollo...'],
                        'content' => function ($model) {
                            if (!$model->protocol_number) {
                                return '<span class="text-sm text-gray-500 dark:text-gray-400">N/A</span>';
                            }
                            return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">'
                                . Html::encode($model->protocol_number) . '</span>';
                        },
                        'options' => ['style' => 'width: 140px;'],
                    ],
                    [
                        'attribute' => 'patientName',
                        'label' => 'Paziente',
                        'value' => function ($model) {
                            return $model->patient ? $model->patient->fullName : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Paziente...'],
                        'content' => function ($model) {
                            $patientName = $model->patient ? $model->patient->fullName : 'N/A';
                            return '<div class="text-sm font-medium text-gray-900 dark:text-white">' . Html::encode($patientName) . '</div>';
                        }
                    ],
                    [
                        'attribute' => 'regime_id',
                        'label' => 'Regime',
                        'value' => function ($model) {
                            return $model->regime ? $model->regime->nome : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => Html::activeDropDownList(
                            $searchModel,
                            'regime_id',
                            $regimes,
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'content' => function ($model) {
                            $regimeName = $model->regime ? $model->regime->nome : 'N/A';
                            return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">'
                                . Html::encode($regimeName) . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'district_id',
                        'label' => 'Distretto',
                        'value' => function ($model) {
                            return $model->district ? $model->district->getDropdownLabel() : 'N/A';
                        },
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => Html::activeDropDownList(
                            $searchModel,
                            'district_id',
                            $districts,
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'content' => function ($model) {
                            $districtName = $model->district ? $model->district->getDropdownLabel() : '-';
                            return '<span class="text-sm text-gray-900 dark:text-white">' . Html::encode($districtName) . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'value' => function ($model) {
                            return $model->start_date ? Yii::$app->formatter->asDate($model->start_date) : 'N/A';
                        },
                        'filter' => false,
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'],
                    ],
                    [
                        'attribute' => 'duration_days',
                        'label' => 'Durata',
                        'value' => function ($model) {
                            return $model->getFormattedDuration();
                        },
                        'filter' => false,
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'],
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'value' => function ($model) {
                            return $model->end_date ? Yii::$app->formatter->asDate($model->end_date) : 'N/A';
                        },
                        'filter' => false,
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'],
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => Html::activeDropDownList(
                            $searchModel,
                            'status',
                            [
                                'draft' => 'Bozza',
                                'pending' => 'In Attesa',
                                'active' => 'Attivo',
                                'suspended' => 'Sospeso',
                                'completed' => 'Completato',
                                'terminated' => 'Interrotto',
                                'expired' => 'Scaduto',
                            ],
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'format' => 'raw',
                        'value' => function ($model) {
                            $map = [
                                'draft'      => ['label' => 'Bozza',      'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'],
                                'pending'    => ['label' => 'In Attesa',  'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
                                'active'     => ['label' => 'Attivo',     'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
                                'suspended'  => ['label' => 'Sospeso',    'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
                                'completed'  => ['label' => 'Completato', 'class' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200'],
                                'terminated' => ['label' => 'Interrotto', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
                                'expired'    => ['label' => 'Scaduto',    'class' => 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-100'],
                            ];
                            $entry = $map[$model->status] ?? ['label' => $model->status, 'class' => 'bg-gray-100 text-gray-800'];
                            return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $entry['class'] . '">'
                                . Html::encode($entry['label']) . '</span>';
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[180px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-right'],
                        'template' => '{view} {update} {delete} {calendar-link}',
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
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                        'data-pjax' => '0'
                                    ]
                                );
                            },
                            'update' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('update_therapeutic_plan')) return '';
                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>',
                                    $url,
                                    [
                                        'title' => 'Modifica',
                                        'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20',
                                        'data-pjax' => '0'
                                    ]
                                );
                            },
                            'delete' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('delete_therapeutic_plan')) return '';
                                // Cambia l'URL per puntare all'azione delete-confirm
                                $confirmUrl = \yii\helpers\Url::to(['delete-confirm', 'id' => $model->id]);

                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>',
                                    $confirmUrl,
                                    [
                                        'title' => 'Elimina piano terapeutico',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20',
                                        'data-pjax' => '0'
                                    ]
                                );
                            },
                            'calendar-link' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('manage_calendar'))
                                    return '';
                                $url = ['calendar/' . $model->patient_id];
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
</svg>',
                                        $url, [
                                    'title' => 'Link al calendario',
                                    'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                ]);
                            },
                        ],
                    ],
                ],
            ], GridViewHelper::getGridViewConfig('piani terapeutici'))); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>
</div> 