<?php

use common\helpers\GridViewHelper;
use common\models\RequestStatus;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\DocumentRequestSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Richieste Documenti';
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/site/index']) ?>">
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
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6 md:gap-6">
        <?php
        $totalRequests = $dataProvider->getTotalCount();
        $unreadRequests = \common\models\DocumentRequest::find()
            ->where(['status' => RequestStatus::STATUS_INVIATA])
            ->count();
        $inProgressRequests = \common\models\DocumentRequest::find()
            ->where(['status' => RequestStatus::STATUS_PRESA_IN_CARICO])
            ->count();
        $completedRequests = \common\models\DocumentRequest::find()
            ->where(['status' => RequestStatus::STATUS_CONSEGNATO])
            ->count();
        ?>
        
        <!-- Totali -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                <svg class="fill-gray-800 dark:fill-white/90" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Totali</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $totalRequests ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Da Leggere -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-error-50 dark:bg-error-500/15">
                <svg class="text-error-600 dark:text-error-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                    <path d="M14 2v6h6"/>
                    <path d="M16 13H8"/>
                    <path d="M16 17H8"/>
                    <path d="M10 9H8"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Da Leggere</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $unreadRequests ?>
                    </h4>
                </div>
                <?php if ($unreadRequests > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                    <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
                    </svg>
                    Urgente
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- In Lavorazione -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-warning-50 dark:bg-warning-500/15">
                <svg class="text-warning-600 dark:text-warning-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">In Lavorazione</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $inProgressRequests ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Completate -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-success-50 dark:bg-success-500/15">
                <svg class="text-success-600 dark:text-success-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Completate</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $completedRequests ?>
                    </h4>
                </div>
                <?php if ($totalRequests > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                    <?= round(($completedRequests / $totalRequests) * 100, 1) ?>%
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] overflow-hidden">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Elenco Richieste
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci le richieste di documenti inviate dai pazienti
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?= GridView::widget(array_merge([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => [
                    'class' => 'grid-view',
                ],
                'tableOptions' => [
                    'class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400',
                ],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                'rowOptions' => function ($model, $key, $index, $grid) {
                    $statusRowColors = [
                        RequestStatus::STATUS_INVIATA => [
                            'class' => 'border-b dark:border-gray-700',
                            'style' => 'background-color: #fef2f2; transition: background-color 0.2s;'
                        ],
                        RequestStatus::STATUS_PRESA_IN_CARICO => [
                            'class' => 'border-b dark:border-gray-700',
                            'style' => 'background-color: #fefce8; transition: background-color 0.2s;'
                        ],
                        RequestStatus::STATUS_STAMPATO => [
                            'class' => 'border-b dark:border-gray-700',
                            'style' => 'background-color: #eff6ff; transition: background-color 0.2s;'
                        ],
                        RequestStatus::STATUS_CONSEGNATO => [
                            'class' => 'border-b dark:border-gray-700',
                            'style' => 'background-color: #f0fdf4; transition: background-color 0.2s;'
                        ],
                    ];

                    $defaultOptions = [
                        'class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600',
                        'style' => ''
                    ];

                    return $statusRowColors[$model->status] ?? $defaultOptions;
                },
                'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                'summary' => '<div class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">Visualizzando <b>{begin}-{end}</b> di <b>{totalCount}</b> richieste.</div>',
                'summaryOptions' => [
                    'class' => 'border-b border-gray-200 dark:border-gray-700',
                ],
                'pager' => [
                    'options' => [
                        'class' => 'px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-700',
                    ],
                    'linkOptions' => [
                        'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                    ],
                    'activePageCssClass' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-brand-500 rounded-md',
                    'disabledPageCssClass' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-600',
                ],
                'columns' => [
                    [
                        'attribute' => 'account_patient_name',
                        'label' => 'Richiedente',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'value' => function ($model) {
                            if ($model->accountPatient && $model->accountPatient->user && $model->accountPatient->user->profile) {
                                $profile = $model->accountPatient->user->profile;
                                return $profile->last_name . ' ' . $profile->first_name;
                            }
                            return 'N/A';
                        },
                        'filter' => Html::activeTextInput($searchModel, 'account_patient_name', [
                            'class' => 'block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                            'placeholder' => 'Nome richiedente...'
                        ]),
                    ],
                    [
                        'attribute' => 'patient_name',
                        'label' => 'Paziente',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'value' => function ($model) {
                            if ($model->patient) {
                                return $model->patient->last_name . ' ' . $model->patient->first_name;
                            }
                            return 'N/A';
                        },
                        'filter' => Html::activeTextInput($searchModel, 'patient_name', [
                            'class' => 'block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                            'placeholder' => 'Nome paziente...'
                        ]),
                    ],
                    [
                        'attribute' => 'request_type_name',
                        'label' => 'Tipo Richiesta',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'value' => function ($model) {
                            return $model->requestType ? $model->requestType->name : 'N/A';
                        },
                        'filter' => Html::activeTextInput($searchModel, 'request_type_name', [
                            'class' => 'block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                            'placeholder' => 'Tipo richiesta...'
                        ]),
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        'format' => 'raw',
                        'content' => function ($model) {
                            $statusColors = [
                                RequestStatus::STATUS_INVIATA => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                RequestStatus::STATUS_PRESA_IN_CARICO => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                RequestStatus::STATUS_STAMPATO => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                RequestStatus::STATUS_CONSEGNATO => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            ];

                            $colorClass = $statusColors[$model->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';

                            return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $colorClass . '">'
                                . Html::encode($model->getStatusLabel()) . '</span>';
                        },
                        'filter' => Html::activeDropDownList($searchModel, 'status',
                                ['' => 'Tutti gli stati'] + $searchModel->getStatusOptions(), [
                            'class' => 'block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                        ]),
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Data Creazione',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'],
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->created_at, 'php:d/m/Y H:i');
                        },
                        'filter' => Html::activeTextInput($searchModel, 'created_at', [
                            'class' => 'block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                            'placeholder' => 'Data...'
                        ]),
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider'],
                        'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2'],
                        'template' => '{view} {update-status}',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>',
                                    ['view', 'id' => $model->id],
                                    [
                                        'title' => 'Visualizza',
                                        'class' => 'inline-flex items-center p-1.5 text-brand-600 hover:text-brand-900 hover:bg-brand-50 rounded-lg transition-colors dark:text-brand-400 dark:hover:text-brand-300 dark:hover:bg-brand-900/20',
                                        'data-pjax' => '0',
                                    ]
                                );
                            },
                            'update-status' => function ($url, $model, $key) {
                                $user = Yii::$app->user;
                                $canChangeFreely = $user->can('change_document_request_status');
                                $canMarkDelivered = $user->can('mark_document_request_delivered');
                                $hasAnyPerm = $canChangeFreely || $canMarkDelivered;

                                if (!$hasAnyPerm) {
                                    return '<span class="inline-flex items-center p-1.5 text-gray-400 cursor-not-allowed" title="Non autorizzato">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </span>';
                                }

                                $isDelivered = $model->status == RequestStatus::STATUS_CONSEGNATO;

                                $previousStatus = null;
                                if ($isDelivered) {
                                    $previousStatusHistory = \common\models\DocumentRequestStatusHistory::find()
                                        ->where(['document_request_id' => $model->id])
                                        ->andWhere(['to_status_id' => RequestStatus::STATUS_CONSEGNATO])
                                        ->orderBy(['created_at' => SORT_DESC])
                                        ->one();
                                    $previousStatus = $previousStatusHistory && $previousStatusHistory->from_status_id
                                        ? $previousStatusHistory->from_status_id
                                        : RequestStatus::STATUS_STAMPATO;
                                }

                                // Lista completa stati: chi e' selezionabile vs solo visibile in grigio.
                                $statusList = [
                                    RequestStatus::STATUS_INVIATA => 'Inviata',
                                    RequestStatus::STATUS_PRESA_IN_CARICO => 'Presa in carico',
                                    RequestStatus::STATUS_STAMPATO => 'Stampato',
                                    RequestStatus::STATUS_CONSEGNATO => 'Consegnato',
                                ];
                                $allStatuses = [];
                                foreach ($statusList as $stId => $stLabel) {
                                    $isCurrent = $model->status == $stId;
                                    if ($isDelivered) {
                                        // In stato Consegnato: solo lo stato precedente e' selezionabile.
                                        $enabled = !$isCurrent && $canMarkDelivered && $stId == $previousStatus;
                                    } else {
                                        if ($isCurrent) {
                                            $enabled = false;
                                        } elseif ($stId == RequestStatus::STATUS_CONSEGNATO) {
                                            $enabled = $canMarkDelivered;
                                        } else {
                                            $enabled = $canChangeFreely;
                                        }
                                    }

                                    $allStatuses[] = [
                                        'id' => (string) $stId,
                                        'label' => $stId == $previousStatus && $isDelivered ? 'Torna a: ' . $stLabel : $stLabel,
                                        'enabled' => $enabled,
                                        'current' => $isCurrent,
                                    ];
                                }

                                return Html::button(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>',
                                    [
                                        'title' => 'Cambia stato',
                                        'class' => 'inline-flex items-center p-1.5 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition-colors',
                                        'onclick' => 'openStatusUpdateModal(' . $model->id . ', ' . json_encode($allStatuses) . ')',
                                    ]
                                );
                            },
                        ],
                    ],
                ],
            ], GridViewHelper::getGridViewConfig('richieste documenti'))); ?>
        </div>
    </div>
</div>

<!-- Status Row Colors CSS -->
<style>
/* Hover effects for status rows */
tr[style*="background-color: #fef2f2"]:hover {
    background-color: #fee2e2 !important;
}
tr[style*="background-color: #fefce8"]:hover {
    background-color: #fef3c7 !important;
}
tr[style*="background-color: #eff6ff"]:hover {
    background-color: #dbeafe !important;
}
tr[style*="background-color: #f0fdf4"]:hover {
    background-color: #dcfce7 !important;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    tr[style*="background-color: #fef2f2"] {
        background-color: rgba(127, 29, 29, 0.1) !important;
    }
    tr[style*="background-color: #fefce8"] {
        background-color: rgba(133, 77, 14, 0.1) !important;
    }
    tr[style*="background-color: #eff6ff"] {
        background-color: rgba(30, 58, 138, 0.1) !important;
    }
    tr[style*="background-color: #f0fdf4"] {
        background-color: rgba(20, 83, 45, 0.1) !important;
    }
    
    tr[style*="background-color: #fef2f2"]:hover {
        background-color: rgba(127, 29, 29, 0.2) !important;
    }
    tr[style*="background-color: #fefce8"]:hover {
        background-color: rgba(133, 77, 14, 0.2) !important;
    }
    tr[style*="background-color: #eff6ff"]:hover {
        background-color: rgba(30, 58, 138, 0.2) !important;
    }
    tr[style*="background-color: #f0fdf4"]:hover {
        background-color: rgba(20, 83, 45, 0.2) !important;
    }
}
</style>

<?php /* MODALE STATUS UPDATE: implementata su SweetAlert2, vedi script in fondo. */ ?>
<?php /*
<div
    class="fixed inset-0 items-center justify-center hidden p-5 overflow-y-auto modal z-99999"
    id="statusUpdateModal"
    x-data="{
            showModal: false,
            currentRequestId: null,
            selectedStatus: null,
            availableStatuses: {},
            isLoading: false,
            errors: '',
            success: '',
            
            openModal(requestId, statuses) {
                this.currentRequestId = requestId;
                this.selectedStatus = null;
                this.availableStatuses = statuses;
                this.showModal = true;
                this.resetMessages();
                document.getElementById('statusUpdateModal').classList.remove('hidden');
                document.getElementById('statusUpdateModal').classList.add('flex');
            },
            
            closeModal() {
                this.showModal = false;
                this.resetForm();
                document.getElementById('statusUpdateModal').classList.add('hidden');
                document.getElementById('statusUpdateModal').classList.remove('flex');
            },
            
            resetForm() {
                this.currentRequestId = null;
                this.selectedStatus = null;
                this.availableStatuses = {};
                this.resetMessages();
            },
            
            resetMessages() {
                this.errors = '';
                this.success = '';
            },
            
            selectStatus(statusId) {
                this.selectedStatus = statusId;
            },
            
            async confirmUpdate() {
                if (!this.currentRequestId || !this.selectedStatus) return;
                
                this.isLoading = true;
                this.resetMessages();
                
                try {
                    const response = await fetch('<?= \yii\helpers\Url::to(['update-status']) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            id: this.currentRequestId,
                            status: this.selectedStatus
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Risposta non valida dal server: ' + text.substring(0, 100));
                    }
                    
                    if (data.success) {
                        this.success = 'Stato aggiornato con successo!';
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.errors = data.message || 'Errore sconosciuto durante l\'aggiornamento';
                    }
                } catch (error) {
                    this.errors = 'Errore durante l\'aggiornamento dello stato: ' + error.message;
                } finally {
                    this.isLoading = false;
                }
            }
        }"
    @open-status-modal.window="openModal($event.detail.requestId, $event.detail.statuses)"
>
    <div
        class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"
        @click="closeModal()"
    ></div>
    <div
        class="modal-dialog modal-dialog-scrollable no-scrollbar relative mx-4 flex w-full max-w-md flex-col overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900 sm:mx-0 sm:max-w-lg"
    >
        <!-- close btn -->
        <button
            class="modal-close-btn absolute right-5 top-5 z-999 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300 sm:h-11 sm:w-11"
            @click="closeModal()"
        >
            <svg
                class="fill-current"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z"
                    fill=""
                />
            </svg>
        </button>

        <div
            class="flex flex-col px-2 overflow-y-auto modal-content custom-scrollbar"
        >
            <div class="modal-header">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-500/10">
                        <svg class="h-5 w-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <h5
                        class="font-semibold text-gray-800 modal-title text-theme-xl dark:text-white/90 lg:text-2xl"
                    >
                        Aggiorna Stato Richiesta
                    </h5>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Seleziona il nuovo stato per questa richiesta documento. L'operazione verrà registrata nella cronologia.
                </p>
            </div>
            
            <div class="mt-6 modal-body">
                <!-- Status Options -->
                <div class="space-y-6">
                    <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Seleziona il nuovo stato:</h6>
                    <template x-for="[statusId, statusName] in Object.entries(availableStatuses)" :key="statusId">
                        <div 
                            class="flex items-center p-5 border-2 rounded-xl cursor-pointer transition-all duration-200 border-gray-200 hover:bg-gray-50 hover:border-gray-300 dark:border-gray-700 dark:hover:bg-gray-800 dark:hover:border-gray-600"
                            :class="{ 
                                'bg-brand-50 border-brand-300 ring-2 ring-brand-100 dark:bg-brand-900/30 dark:border-brand-700 dark:ring-brand-800/50': selectedStatus == statusId,
                                'transform hover:scale-[1.02]': selectedStatus != statusId 
                            }"
                            @click="selectStatus(statusId)"
                        >
                            <input 
                                type="radio" 
                                :id="'status_' + statusId" 
                                name="status" 
                                :value="statusId"
                                :checked="selectedStatus == statusId"
                                class="h-5 w-5 text-brand-600 focus:ring-brand-500 focus:ring-offset-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                @change="selectStatus(statusId)"
                            >
                            <label 
                                :for="'status_' + statusId" 
                                class="ml-4 block text-sm font-medium text-gray-900 dark:text-white cursor-pointer flex-1 select-none"
                                x-text="statusName"
                            ></label>
                            <!-- Checkmark icon for selected status -->
                            <div x-show="selectedStatus == statusId" class="ml-2">
                                <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Messages -->
                <div x-show="errors" x-cloak class="mt-4">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <div class="flex items-start">
                            <svg class="mr-3 mt-0.5 h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-red-800 dark:text-red-200" x-text="errors"></p>
                        </div>
                    </div>
                </div>
                
                <div x-show="success" x-cloak class="mt-4">
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                        <div class="flex items-start">
                            <svg class="mr-3 mt-0.5 h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-green-800 dark:text-green-200" x-text="success"></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col gap-3 mt-8 modal-footer sm:flex-row sm:justify-end sm:gap-3">
                <button
                    type="button"
                    class="order-2 flex w-full justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-400 sm:order-1 sm:w-auto"
                    @click="closeModal()"
                >
                    Annulla
                </button>
                <button
                    type="button"
                    :disabled="isLoading || !selectedStatus"
                    @click="confirmUpdate()"
                    class="order-1 flex w-full justify-center rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-brand-600 dark:hover:bg-brand-700 sm:order-2 sm:w-auto"
                >
                    <span x-show="!isLoading" class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Conferma Aggiornamento
                    </span>
                    <span x-show="isLoading" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Aggiornamento in corso...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
*/ ?>

<?php
// Modale di aggiornamento stato in SweetAlert2.
$updateUrl = \yii\helpers\Url::to(['update-status']);
$jsUpdateUrl = json_encode($updateUrl);
$jsCsrfParam = json_encode(Yii::$app->request->csrfParam);
$this->registerJs(<<<JS
window.openStatusUpdateModal = function (requestId, statuses) {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 non caricato.');
        return;
    }
    if (!statuses || statuses.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Nessuna azione disponibile',
            text: 'Non hai i permessi per modificare lo stato di questa richiesta.',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    var anyEnabled = false;
    var radios = statuses.map(function (s) {
        var label = escapeHtml(s.label);
        var idStr = escapeHtml(String(s.id));
        var bg, border, color, cursor;
        if (s.current) {
            bg = '#eff6ff'; border = '#bfdbfe'; color = '#1d4ed8'; cursor = 'not-allowed';
        } else if (s.enabled) {
            bg = '#ffffff'; border = '#e5e7eb'; color = '#111827'; cursor = 'pointer';
            anyEnabled = true;
        } else {
            bg = '#f9fafb'; border = '#f3f4f6'; color = '#9ca3af'; cursor = 'not-allowed';
        }
        var disabledAttr = s.enabled ? '' : 'disabled';
        var note = '';
        if (s.current) { note = ' <span style="font-size:11px;color:#1d4ed8;font-style:italic;">(stato attuale)</span>'; }
        else if (!s.enabled) { note = ' <span style="font-size:11px;color:#9ca3af;font-style:italic;">(non disponibile)</span>'; }

        return '<label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid ' + border
            + ';border-radius:8px;cursor:' + cursor + ';margin-bottom:6px;background:' + bg
            + ';color:' + color + ';">'
            + '<input type="radio" name="swal-status" value="' + idStr + '" ' + disabledAttr + ' style="margin:0;">'
            + '<span style="font-size:14px;">' + label + note + '</span>'
            + '</label>';
    }).join('');

    Swal.fire({
        title: 'Aggiorna stato richiesta',
        html: '<div style="text-align:left;">'
            + '<p style="font-size:13px;color:#6b7280;margin:0 0 12px 0;">Seleziona il nuovo stato. L\\'operazione viene registrata nella cronologia.</p>'
            + radios
            + (!anyEnabled ? '<p style="font-size:12px;color:#dc2626;margin-top:8px;">Nessuno stato selezionabile con i permessi attuali.</p>' : '')
            + '</div>',
        showCancelButton: true,
        confirmButtonText: 'Conferma',
        cancelButtonText: 'Annulla',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        showConfirmButton: anyEnabled,
        didOpen: function () {
            var firstEnabled = Swal.getHtmlContainer().querySelector('input[name="swal-status"]:not([disabled])');
            if (firstEnabled) { firstEnabled.checked = true; }
        },
        preConfirm: function () {
            var checked = Swal.getHtmlContainer().querySelector('input[name="swal-status"]:checked');
            if (!checked) {
                Swal.showValidationMessage('Seleziona uno stato.');
                return false;
            }
            return checked.value;
        }
    }).then(function (result) {
        if (!result.isConfirmed) { return; }
        var newStatus = result.value;
        var formData = new URLSearchParams();
        formData.append('id', requestId);
        formData.append('status', newStatus);
        formData.append({$jsCsrfParam}, jQuery('meta[name=csrf-token]').attr('content') || '');

        Swal.fire({
            title: 'Aggiornamento in corso...',
            didOpen: function () { Swal.showLoading(); },
            allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false
        });

        fetch({$jsUpdateUrl}, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData.toString()
        })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
            if (data && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Stato aggiornato',
                    timer: 1200,
                    showConfirmButton: false
                }).then(function () { window.location.reload(); });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: (data && data.message) ? data.message : 'Errore sconosciuto.',
                    confirmButtonColor: '#2563eb'
                });
            }
        })
        .catch(function (err) {
            Swal.fire({
                icon: 'error',
                title: 'Errore di rete',
                text: err && err.message ? err.message : 'Impossibile contattare il server.',
                confirmButtonColor: '#2563eb'
            });
        });
    });
};
JS, \yii\web\View::POS_END, 'document-request-status-modal');
?> 