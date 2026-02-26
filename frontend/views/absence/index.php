<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\AbsenceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\PatientAbsenceSearch $patientSearchModel */
/** @var yii\data\ActiveDataProvider $patientDataProvider */

$this->title = 'Assenze';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6" x-data="{ activeTab: 'therapists' }">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90"><?= Html::encode($this->title) ?></h2>

        <!-- Action Button (only for therapist tab) -->
        <?php if (Yii::$app->user->can('create_absence')): ?>
        <div x-show="activeTab === 'therapists'">
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Nuova Assenza',
                ['create'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab Bar -->
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-4" aria-label="Tabs">
            <button
                @click="activeTab = 'therapists'"
                :class="activeTab === 'therapists'
                    ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-3 px-4 text-sm font-medium transition-colors"
            >
                Assenze Terapisti
                <span class="ml-1.5 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <?= $dataProvider->totalCount ?>
                </span>
            </button>
            <button
                @click="activeTab = 'patients'"
                :class="activeTab === 'patients'
                    ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-3 px-4 text-sm font-medium transition-colors"
            >
                Assenze Pazienti
                <span class="ml-1.5 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <?= $patientDataProvider->totalCount ?>
                </span>
            </button>
        </nav>
    </div>

    <!-- Tab 1: Assenze Terapisti -->
    <div x-show="activeTab === 'therapists'" x-cloak>
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Lista Assenze Terapisti
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gestisci le assenze dei terapisti.
                </p>
            </div>

            <!-- Filter Controls -->
            <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <?= 'Trovate ' . $dataProvider->totalCount . ' assenze' ?>
                </div>
                <div class="flex gap-2">
                    <?= Html::a('Reset Filtri', ['index'], [
                        'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]) ?>
                    <?= Html::button('Aggiorna', [
                        'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                        'onclick' => '$.pjax.reload({container:"#absence-grid-pjax"});'
                    ]) ?>
                </div>
            </div>

            <!-- Scrollable Table Container -->
            <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
                <?php Pjax::begin(['id' => 'absence-grid-pjax']); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'options' => ['class' => 'min-w-full'],
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0'],
                    'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                    'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                    'columns' => [
                        [
                            'attribute' => 'therapist_name',
                            'label' => 'Terapista',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'therapist_id',
                                \common\models\AbsenceSearch::getTherapistsList(),
                                [
                                    'prompt' => 'Tutti',
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]
                            ),
                            'value' => function($model) {
                                return $model->therapist && $model->therapist->user && $model->therapist->user->profile
                                    ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
                                    : '';
                            }
                        ],
                        [
                            'attribute' => 'start_date',
                            'label' => 'Data Inizio',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                            'format' => ['date', 'php:d/m/Y'],
                        ],
                        [
                            'attribute' => 'end_date',
                            'label' => 'Data Fine',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                            'format' => ['date', 'php:d/m/Y'],
                        ],
                        [
                            'attribute' => 'type',
                            'label' => 'Tipo',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'type',
                                \common\models\AbsenceSearch::getTypesList(),
                                [
                                    'prompt' => 'Tutti',
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]
                            ),
                            'value' => function($model) {
                                return $model->getTypeLabel();
                            }
                        ],
                        [
                            'label' => 'Durata',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                            'format' => 'raw',
                            'content' => function($model) {
                                $days = $model->getDurationDays();
                                $badgeClass = $days > 7 ? 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900' : 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900';
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $badgeClass . '">' . $days . ' giorni</span>';
                            }
                        ],
                        [
                            'attribute' => 'reason',
                            'label' => 'Motivo',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-4 py-4'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra motivo...'],
                            'value' => function($model) {
                                return $model->reason ? \yii\helpers\StringHelper::truncate($model->reason, 50) : '-';
                            }
                        ],
                        [
                            'attribute' => 'status',
                            'label' => 'Stato',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'status',
                                \common\models\AbsenceSearch::getStatusList(),
                                [
                                    'prompt' => 'Tutti',
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]
                            ),
                            'format' => 'raw',
                            'content' => function($model) {
                                $statusClass = $model->isApproved() ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-200 dark:text-yellow-900';
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $model->getStatusLabel() . '</span>';
                            }
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Azioni',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'template' => '{view} {update} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                        $url, [
                                        'title' => 'Visualizza assenza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                        'data-bs-toggle' => 'tooltip',
                                        'data-bs-placement' => 'top'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('update_absence')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                                        $url, [
                                        'title' => 'Modifica assenza',
                                        'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20',
                                        'data-bs-toggle' => 'tooltip',
                                        'data-bs-placement' => 'top'
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('delete_absence')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                                        $url, [
                                        'title' => 'Elimina assenza',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20',
                                        'data-bs-toggle' => 'tooltip',
                                        'data-bs-placement' => 'top',
                                        'data' => [
                                            'confirm' => 'Sei sicuro di voler eliminare questa assenza?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>

                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>

    <!-- Tab 2: Assenze Pazienti -->
    <div x-show="activeTab === 'patients'" x-cloak>
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Lista Assenze Pazienti
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Appuntamenti in cui il paziente risulta assente.
                </p>
            </div>

            <!-- Filter Controls -->
            <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <?= 'Trovate ' . $patientDataProvider->totalCount . ' assenze' ?>
                </div>
                <div class="flex gap-2">
                    <?= Html::a('Reset Filtri', ['index'], [
                        'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]) ?>
                    <?= Html::button('Aggiorna', [
                        'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                        'onclick' => '$.pjax.reload({container:"#patient-absence-grid-pjax"});'
                    ]) ?>
                </div>
            </div>

            <!-- Scrollable Table Container -->
            <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
                <?php Pjax::begin(['id' => 'patient-absence-grid-pjax']); ?>

                <?= GridView::widget([
                    'dataProvider' => $patientDataProvider,
                    'filterModel' => $patientSearchModel,
                    'options' => ['class' => 'min-w-full'],
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0'],
                    'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                    'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                    'columns' => [
                        [
                            'attribute' => 'patient_name',
                            'label' => 'Paziente',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cerca paziente...'],
                            'value' => function($model) {
                                $patient = $model->getActualPatient();
                                return $patient ? $patient->getFullName() : 'N/A';
                            }
                        ],
                        [
                            'attribute' => 'date_from',
                            'label' => 'Data/Ora',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[160px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                            'value' => function($model) {
                                return Yii::$app->formatter->asDatetime($model->appointment_datetime, 'php:d/m/Y H:i');
                            }
                        ],
                        [
                            'attribute' => 'therapist_id',
                            'label' => 'Terapista',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => \yii\helpers\Html::activeDropDownList($patientSearchModel, 'therapist_id',
                                \common\models\PatientAbsenceSearch::getTherapistsList(),
                                [
                                    'prompt' => 'Tutti',
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]
                            ),
                            'value' => function($model) {
                                return $model->therapist && $model->therapist->user && $model->therapist->user->profile
                                    ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
                                    : '';
                            }
                        ],
                        [
                            'attribute' => 'duration_minutes',
                            'label' => 'Durata',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                            'format' => 'raw',
                            'content' => function($model) {
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900">' . $model->duration_minutes . ' min</span>';
                            }
                        ],
                        [
                            'attribute' => 'status',
                            'label' => 'Stato',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => \yii\helpers\Html::activeDropDownList($patientSearchModel, 'status',
                                \common\models\PatientAbsenceSearch::getStatusList(),
                                [
                                    'prompt' => 'Tutti',
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]
                            ),
                            'format' => 'raw',
                            'content' => function($model) {
                                $isJustified = $model->status === \common\models\Appointment::STATUS_ABSENT_JUSTIFIED;
                                $statusClass = $isJustified
                                    ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900'
                                    : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900';
                                $label = $isJustified ? 'Giustificata' : 'Non giustificata';
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $label . '</span>';
                            }
                        ],
                    ],
                ]); ?>

                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>
