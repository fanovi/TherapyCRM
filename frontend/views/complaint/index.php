<?php

use common\helpers\GridViewHelper;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\ComplaintSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reclami';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName"></li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6 md:gap-6">
        <?php
        $totalComplaints = $dataProvider->getTotalCount();
        $thisMonthComplaints = \common\models\Complaint::find()
            ->where(['>=', 'created_at', date('Y-m-01 00:00:00')])
            ->count();
        $lastMonthComplaints = \common\models\Complaint::find()
            ->where(['>=', 'created_at', date('Y-m-01 00:00:00', strtotime('-1 month'))])
            ->andWhere(['<', 'created_at', date('Y-m-01 00:00:00')])
            ->count();
        ?>
        
        <!-- Totali -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-7 9h-2V5h2v6zm0 4h-2v-2h2v2z"/>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Totali</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $totalComplaints ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Questo Mese -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-warning-50 dark:bg-warning-500/15">
                <svg class="text-warning-600 dark:text-warning-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Questo Mese</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $thisMonthComplaints ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Mese Scorso -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/15">
                <svg class="text-blue-600 dark:text-blue-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Mese Scorso</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $lastMonthComplaints ?>
                    </h4>
                </div>
                <?php if ($lastMonthComplaints > 0 && $thisMonthComplaints > $lastMonthComplaints): ?>
                <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                    <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
                    </svg>
                    +<?= round((($thisMonthComplaints - $lastMonthComplaints) / $lastMonthComplaints) * 100) ?>%
                </span>
                <?php elseif ($lastMonthComplaints > 0 && $thisMonthComplaints < $lastMonthComplaints): ?>
                <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                    <svg class="fill-current rotate-180" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
                    </svg>
                    -<?= round((($lastMonthComplaints - $thisMonthComplaints) / $lastMonthComplaints) * 100) ?>%
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Reclami
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci tutti i reclami ricevuti, dal più recente al più vecchio.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <!-- Horizontal scroll wrapper -->
            <div class="overflow-x-auto">
                <?php Pjax::begin([
                    'id' => 'complaints-pjax',
                    'enablePushState' => false,
                ]); ?>
                
                <?= GridView::widget(array_merge([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                    'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                    'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[80px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'ID...'],
                        ],
                        [
                            'attribute' => 'title',
                            'label' => 'Titolo',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-6 py-4'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Titolo...'],
                            'value' => function ($model) {
                                return strlen($model->title) > 50 ? substr($model->title, 0, 50) . '...' : $model->title;
                            }
                        ],
                        [
                            'attribute' => 'patient_name',
                            'label' => 'Paziente',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Paziente...'],
                            'value' => function ($model) {
                                return $model->patient ? $model->patient->last_name . ' ' . $model->patient->first_name : '-';
                            }
                        ],
                        [
                            'attribute' => 'account_name',
                            'label' => 'Account',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Account...'],
                            'value' => function ($model) {
                                if ($model->account && $model->account->profile) {
                                    return $model->account->profile->last_name . ' ' . $model->account->profile->first_name;
                                }
                                return $model->account ? $model->account->email : '-';
                            }
                        ],
                        [
                            'attribute' => 'created_at',
                            'label' => 'Data',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => '<div class="flex flex-col gap-1">' .
                                Html::activeTextInput($searchModel, 'date_from', [
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                    'placeholder' => 'Da (YYYY-MM-DD)...',
                                    'type' => 'date'
                                ]) .
                                Html::activeTextInput($searchModel, 'date_to', [
                                    'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                    'placeholder' => 'A (YYYY-MM-DD)...',
                                    'type' => 'date'
                                ]) .
                                '</div>',
                            'format' => ['date', 'php:d/m/Y H:i'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Azioni',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-right'],
                            'template' => '<div class="flex items-center justify-end space-x-2">{view}</div>',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                            $url, [
                                        'title' => 'Visualizza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20'
                                    ]);
                                },
                            ],
                        ],
                    ],
                ], GridViewHelper::getGridViewConfig('reclami'))); ?>
                
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>
