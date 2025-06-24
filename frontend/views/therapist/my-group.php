<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var frontend\models\TherapistSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\CoordinatorGroup $coordinatorGroup */

$this->title = 'I Miei Terapisti';
$this->params['breadcrumbs'][] = $this->title;
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
                <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">
                    Gruppo: <?= Html::encode($coordinatorGroup->name) ?>
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <?= $coordinatorGroup->description ? Html::encode($coordinatorGroup->description) : 'Visualizza e gestisci i terapisti del tuo gruppo.' ?>
                </p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    <?= $dataProvider->totalCount ?> terapist<?= $dataProvider->totalCount == 1 ? 'a' : 'i' ?> nel gruppo
                </p>
            </div>
        </div>
    </div>

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Terapisti del Gruppo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Lista dei terapisti assegnati al tuo gruppo di coordinamento.
            </p>
        </div>
        
        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovati ' . $dataProvider->totalCount . ' terapisti' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['my-group'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#my-group-grid-pjax"});'
                ]) ?>
            </div>
        </div>
        
        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'my-group-grid-pjax']); ?>
            
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
                        'attribute' => 'first_name',
                        'label' => 'Nome',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra nome...'],
                        'value' => function($model) {
                            return $model->user && $model->user->profile ? $model->user->profile->first_name : '';
                        }
                    ],
                    [
                        'attribute' => 'last_name',
                        'label' => 'Cognome',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra cognome...'],
                        'value' => function($model) {
                            return $model->user && $model->user->profile ? $model->user->profile->last_name : '';
                        }
                    ],
                    [
                        'attribute' => 'email',
                        'label' => 'Email',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra email...'],
                        'value' => function($model) {
                            return $model->user ? $model->user->email : '';
                        }
                    ],
                    [
                        'attribute' => 'specialization_name',
                        'label' => 'Specializzazione',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'specialization_name', 
                            \frontend\models\TherapistSearch::getSpecializationsList(), 
                            [
                                'prompt' => 'Tutte',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'value' => function($model) {
                            return $model->specialization ? $model->specialization->name : '';
                        }
                    ],
                    [
                        'label' => 'Ruolo nel Gruppo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'format' => 'raw',
                        'content' => function($model) use ($coordinatorGroup) {
                            $groupTherapist = \common\models\GroupTherapist::find()
                                ->where(['group_id' => $coordinatorGroup->id, 'therapist_id' => $model->id, 'is_active' => 1])
                                ->one();
                            
                            if ($groupTherapist) {
                                $roleClass = 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900';
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $roleClass . '">' . Html::encode($groupTherapist->getRoleLabel()) . '</span>';
                            }
                            
                            return '<span class="text-gray-400">N/D</span>';
                        }
                    ],
                    [
                        'attribute' => 'weekly_hours_contract',
                        'label' => 'Ore/Settimana',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[110px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'weekly_hours_contract', 
                            \frontend\models\TherapistSearch::getHoursList(), 
                            [
                                'prompt' => 'Tutte',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                    ],
                    [
                        'attribute' => 'calendar_color',
                        'label' => 'Colore',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-1 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => '#000000'],
                        'format' => 'raw',
                        'content' => function($model) {
                            $color = $model->calendar_color ?: '#6B7280';
                            return '<div class="flex items-center justify-center gap-2">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300" style="background-color: ' . Html::encode($color) . '"></div>
                                <span class="text-xs text-gray-500">' . Html::encode($color) . '</span>
                            </div>';
                        }
                    ],
                    [
                        'attribute' => 'is_active',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'is_active', 
                            \frontend\models\TherapistSearch::getStatusList(), 
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'format' => 'raw',
                        'content' => function($model) {
                            $statusClass = $model->is_active ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900' : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900';
                            $statusText = $model->is_active ? 'Attivo' : 'Inattivo';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $statusText . '</span>';
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[80px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'template' => '{view}',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            return ['/therapist/' . $action, 'id' => $model->id];
                        },
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', 
                                    $url, [
                                    'title' => 'Visualizza terapista',
                                    'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>
    <!-- Content End -->
</div> 