<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var frontend\models\TherapistSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Terapisti';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <!-- Action Button -->
            <?php if (Yii::$app->user->can('create_therapist')): ?>
            <div>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Nuovo Terapista', 
                    ['create'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Terapisti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci i terapisti del sistema.
            </p>
        </div>
        
        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(); ?>
            
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
                        'attribute' => 'weekly_hours_contract',
                        'label' => 'Ore/Settimana',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[110px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => false, // No filter for hours
                    ],
                    [
                        'attribute' => 'calendar_color',
                        'label' => 'Colore',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[80px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => false, // No filter for color
                        'format' => 'raw',
                        'content' => function($model) {
                            $color = $model->calendar_color ?: '#6B7280';
                            return '<div class="w-6 h-6 rounded-full border-2 border-gray-300 mx-auto" style="background-color: ' . Html::encode($color) . '"></div>';
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
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'template' => '{view} {update} {toggle-status}',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            return [$action, 'id' => $model->id];
                        },
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', 
                                    $url, [
                                    'title' => 'Visualizza',
                                    'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3'
                                ]);
                            },
                            'update' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('update_therapist')) return '';
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                                    $url, [
                                    'title' => 'Modifica',
                                    'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3'
                                ]);
                            },
                            'toggle-status' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('delete_therapist')) return '';
                                
                                // Build the correct URL for toggle-status action
                                $toggleUrl = ['toggle-status', 'id' => $model->id];
                                
                                if ($model->is_active) {
                                    // Show deactivate button for active therapists
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 
                                        $toggleUrl, [
                                        'title' => 'Disattiva',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
                                        'data' => [
                                            'confirm' => 'Sei sicuro di voler disattivare questo terapista? Potrà essere riattivato in seguito.',
                                            'method' => 'post',
                                        ],
                                    ]);
                                } else {
                                    // Show activate button for inactive therapists
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>', 
                                        $toggleUrl, [
                                        'title' => 'Attiva',
                                        'class' => 'text-success-600 hover:text-success-700 dark:text-success-500 dark:hover:text-success-600',
                                        'data' => [
                                            'confirm' => 'Sei sicuro di voler attivare questo terapista?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                }
                            },
                        ],
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>