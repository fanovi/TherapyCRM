<?php

use common\helpers\GridViewHelper;
use common\models\District;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var frontend\models\PatientSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\CoordinatorGroup $coordinatorGroup */
/** @var int $therapistCount */
/** @var array $patientTherapistsMap mappa patient_id => [['id','name'], ...] */

$this->title = 'I Miei Pazienti';
$this->params['breadcrumbs'][] = $this->title;

$districts = District::getOptionsForSelect();
$patientTherapistsMap = isset($patientTherapistsMap) && is_array($patientTherapistsMap)
    ? $patientTherapistsMap
    : [];
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Group Info -->
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.5C8.96243 3.5 6.5 5.96243 6.5 9C6.5 12.0376 8.96243 14.5 12 14.5C15.0376 14.5 17.5 12.0376 17.5 9C17.5 5.96243 15.0376 3.5 12 3.5ZM5 9C5 5.13401 8.13401 2 12 2C15.866 2 19 5.13401 19 9C19 12.866 15.866 16 12 16C8.13401 16 5 12.866 5 9ZM12 18.5C7.30558 18.5 3.5 20.3431 3.5 22.5C3.5 23.3284 4.17157 24 5 24H19C19.8284 24 20.5 23.3284 20.5 22.5C20.5 20.3431 16.6944 18.5 12 18.5ZM2 22.5C2 19.1863 6.47715 17 12 17C17.5228 17 22 19.1863 22 22.5C22 24.1569 20.6569 25.5 19 25.5H5C3.34315 25.5 2 24.1569 2 22.5Z"/>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">
                    Gruppo: <?= Html::encode($coordinatorGroup->name) ?>
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    Pazienti assegnati ai terapisti del tuo gruppo di coordinamento.
                </p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    <?= (int) $therapistCount ?> terapist<?= $therapistCount == 1 ? 'a' : 'i' ?> nel gruppo
                    &middot;
                    <?= $dataProvider->totalCount ?> paziente<?= $dataProvider->totalCount == 1 ? '' : 'i' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Pazienti del Gruppo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Lista dei pazienti che hanno almeno un appuntamento (non cancellato) con uno dei tuoi terapisti.
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovati ' . $dataProvider->totalCount . ' pazienti' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['my-group'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#my-group-patients-pjax"});'
                ]) ?>
            </div>
        </div>

        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'my-group-patients-pjax', 'enablePushState' => false]); ?>

            <?= GridView::widget(array_merge([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0'],
                'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                'columns' => [
                    [
                        'attribute' => 'last_name',
                        'label' => 'Cognome',
                        'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cognome...'],
                    ],
                    [
                        'attribute' => 'first_name',
                        'label' => 'Nome',
                        'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Nome...'],
                    ],
                    [
                        'label' => 'Terapista del gruppo',
                        'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-6 py-4 align-top'],
                        'filter' => false,
                        'format' => 'raw',
                        'value' => function ($model) use ($patientTherapistsMap) {
                            $list = $patientTherapistsMap[$model->id] ?? [];
                            if (empty($list)) {
                                return '<span class="text-gray-400 italic text-xs">-</span>';
                            }
                            $canViewTherapist = Yii::$app->user->can('view_therapist');
                            $items = [];
                            foreach ($list as $t) {
                                $name = Html::encode($t['name']);
                                $badge = '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800">'
                                    . '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
                                    . $name
                                    . '</span>';
                                if ($canViewTherapist) {
                                    $items[] = Html::a($badge, ['/therapist/view', 'id' => $t['id']], [
                                        'data-pjax' => '0',
                                        'title' => 'Apri scheda terapista',
                                        'class' => 'hover:opacity-80',
                                    ]);
                                } else {
                                    $items[] = $badge;
                                }
                            }
                            return '<div class="flex flex-wrap gap-1.5">' . implode('', $items) . '</div>';
                        },
                    ],
                    [
                        'attribute' => 'fiscal_code',
                        'label' => 'Codice Fiscale',
                        'headerOptions' => ['class' => 'px-6 py-3 min-w-[160px]'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap font-mono text-xs'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Codice fiscale...'],
                        'value' => function ($model) {
                            return $model->fiscal_code ?: '-';
                        }
                    ],
                    [
                        'label' => 'Data di Nascita (Età)',
                        'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'filter' => false,
                        'value' => function ($model) {
                            if (!$model->birth_date) {
                                return '-';
                            }
                            $birthDate = Yii::$app->formatter->asDate($model->birth_date, 'php:d/m/Y');
                            $age = $model->age ? " ({$model->age} anni)" : '';
                            return $birthDate . $age;
                        }
                    ],
                    [
                        'attribute' => 'district_id',
                        'label' => 'Distretto',
                        'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => Html::activeDropDownList(
                            $searchModel,
                            'district_id',
                            $districts,
                            [
                                'prompt' => 'Tutti i distretti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'value' => function ($model) {
                            return $model->district ? $model->district->getDropdownLabel() : '-';
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-right'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'template' => '<div class="flex items-center justify-end space-x-2">{view} {calendar-link}</div>',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            if ($action === 'calendar-link') {
                                return ['/calendar/' . $model->id];
                            }
                            return ['/patient/' . $action, 'id' => $model->id];
                        },
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('view_patient')) {
                                    return '';
                                }
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                    $url, [
                                    'title' => 'Visualizza paziente',
                                    'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-2 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                ]);
                            },
                            'calendar-link' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('view_calendar')) {
                                    return '';
                                }
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                                    $url, [
                                    'title' => 'Calendario paziente',
                                    'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20',
                                ]);
                            },
                        ],
                    ],
                ],
            ], GridViewHelper::getGridViewConfig('pazienti'))); ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
    <!-- Content End -->
</div>
