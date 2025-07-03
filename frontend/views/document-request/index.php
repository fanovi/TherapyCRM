<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\RequestStatus;

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
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
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
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/20">
                <svg class="fill-blue-600 dark:fill-blue-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Totali</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">
                        <?= $totalRequests ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Da Leggere -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/20">
                <svg class="fill-red-600 dark:fill-red-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Da Leggere</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">
                        <?= $unreadRequests ?>
                    </h4>
                </div>
                <?php if ($unreadRequests > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-red-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-red-600 dark:bg-red-500/15 dark:text-red-500">
                    <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
                    </svg>
                    Urgente
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- In Lavorazione -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-yellow-100 dark:bg-yellow-900/20">
                <svg class="fill-yellow-600 dark:fill-yellow-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">In Lavorazione</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">
                        <?= $inProgressRequests ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- Completate -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/20">
                <svg class="fill-green-600 dark:fill-green-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Completate</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">
                        <?= $completedRequests ?>
                    </h4>
                </div>
                <?php if ($totalRequests > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-green-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">
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
            <?= GridView::widget([
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
                                return $profile->first_name . ' ' . $profile->last_name;
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
                                return $model->patient->first_name . ' ' . $model->patient->last_name;
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
                            
                            return '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $colorClass . '">' . 
                                   Html::encode($model->getStatusLabel()) . '</span>';
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
                                // LOGICA SEMPLIFICATA:
                                // Ottieni il ruolo specifico dell'utente
                                $auth = Yii::$app->authManager;
                                $userRoles = array_keys($auth->getRolesByUser(Yii::$app->user->id));
                                $isAdmin = in_array('admin', $userRoles);
                                $isManager = in_array('manager', $userRoles);
                                
                                if ($isAdmin) {
                                    // === ADMIN ===
                                    if ($model->status == RequestStatus::STATUS_CONSEGNATO) {
                                        return '<span class="inline-flex items-center p-1.5 text-gray-400 cursor-not-allowed" title="Consegnato - Non modificabile">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </span>';
                                    }
                                    
                                    // Stati disponibili per admin (escluso quello corrente)
                                    $availableStatuses = [];
                                    if ($model->status != RequestStatus::STATUS_INVIATA) {
                                        $availableStatuses[RequestStatus::STATUS_INVIATA] = 'Inviata';
                                    }
                                    if ($model->status != RequestStatus::STATUS_PRESA_IN_CARICO) {
                                        $availableStatuses[RequestStatus::STATUS_PRESA_IN_CARICO] = 'Presa in carico';
                                    }
                                    if ($model->status != RequestStatus::STATUS_STAMPATO) {
                                        $availableStatuses[RequestStatus::STATUS_STAMPATO] = 'Stampato';
                                    }
                                    
                                    return Html::button(
                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>',
                                        [
                                            'title' => 'Cambia Stato',
                                            'class' => 'inline-flex items-center p-1.5 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition-colors',
                                            'onclick' => 'openStatusUpdateModal(' . $model->id . ', ' . json_encode($availableStatuses) . ')',
                                        ]
                                    );
                                    
                                } elseif ($isManager) {
                                    // === MANAGER ===
                                    if ($model->status == RequestStatus::STATUS_CONSEGNATO) {
                                        // Trova stato precedente
                                        $previousStatusHistory = \common\models\DocumentRequestStatusHistory::find()
                                            ->where(['document_request_id' => $model->id])
                                            ->andWhere(['to_status_id' => RequestStatus::STATUS_CONSEGNATO])
                                            ->orderBy(['created_at' => SORT_DESC])
                                            ->one();
                                            
                                        $previousStatus = $previousStatusHistory && $previousStatusHistory->from_status_id ? 
                                            $previousStatusHistory->from_status_id : RequestStatus::STATUS_STAMPATO;
                                            
                                        $previousStatusLabel = \common\models\DocumentRequest::getStatusLabels()[$previousStatus] ?? 'Stato precedente';
                                        
                                        // Mostra opzione per tornare allo stato precedente
                                        $availableStatuses = [$previousStatus => 'Torna a: ' . $previousStatusLabel];
                                        
                                        return Html::button(
                                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                            </svg>',
                                            [
                                                'title' => 'Torna a: ' . $previousStatusLabel,
                                                'class' => 'inline-flex items-center p-1.5 text-orange-600 hover:text-orange-900 hover:bg-orange-50 rounded-lg transition-colors',
                                                'onclick' => 'openStatusUpdateModal(' . $model->id . ', ' . json_encode($availableStatuses) . ')',
                                            ]
                                        );
                                    } else {
                                        // Mostra opzione per consegnare
                                        $availableStatuses = [RequestStatus::STATUS_CONSEGNATO => 'Consegnato'];
                                        
                                        return Html::button(
                                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>',
                                            [
                                                'title' => 'Segna come Consegnato',
                                                'class' => 'inline-flex items-center p-1.5 text-green-600 hover:text-green-900 hover:bg-green-50 rounded-lg transition-colors',
                                                'onclick' => 'openStatusUpdateModal(' . $model->id . ', ' . json_encode($availableStatuses) . ')',
                                            ]
                                        );
                                    }
                                }
                                
                                // Nessun permesso
                                return '<span class="inline-flex items-center p-1.5 text-gray-400 cursor-not-allowed" title="Non autorizzato">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </span>';
                            },
                        ],
                    ],
                ],
            ]); ?>
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

<!-- BEGIN STATUS UPDATE MODAL -->
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
        class="modal-dialog modal-dialog-scrollable modal-lg no-scrollbar relative flex w-full max-w-[500px] flex-col overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-8"
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
                <div class="space-y-3">
                    <template x-for="[statusId, statusName] in Object.entries(availableStatuses)" :key="statusId">
                        <div 
                            class="flex items-center p-4 border rounded-lg cursor-pointer transition-colors border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                            :class="{ 'bg-brand-50 border-brand-200 dark:bg-brand-900/20 dark:border-brand-800': selectedStatus == statusId }"
                            @click="selectStatus(statusId)"
                        >
                            <input 
                                type="radio" 
                                :id="'status_' + statusId" 
                                name="status" 
                                :value="statusId"
                                :checked="selectedStatus == statusId"
                                class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 dark:border-gray-600"
                                @change="selectStatus(statusId)"
                            >
                            <label 
                                :for="'status_' + statusId" 
                                class="ml-3 block text-sm font-medium text-gray-900 dark:text-white cursor-pointer flex-1"
                                x-text="statusName"
                            ></label>
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
            
            <div class="flex items-center gap-3 mt-6 modal-footer sm:justify-end">
                <button
                    type="button"
                    class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] sm:w-auto"
                    @click="closeModal()"
                >
                    Annulla
                </button>
                <button
                    type="button"
                    :disabled="isLoading || !selectedStatus"
                    @click="confirmUpdate()"
                    class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                >
                    <span x-show="!isLoading" class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Conferma
                    </span>
                    <span x-show="isLoading" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Aggiornamento...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- END STATUS UPDATE MODAL -->

<?php
// JavaScript semplificato per la nuova modale Alpine.js
$this->registerJs("
    // Funzione globale per aprire la modale
    window.openStatusUpdateModal = function(requestId, statuses) {
        // Dispatcha un evento personalizzato per Alpine.js
        window.dispatchEvent(new CustomEvent('open-status-modal', {
            detail: {
                requestId: requestId,
                statuses: statuses
            }
        }));
    };

", \yii\web\View::POS_END, 'document-request-status-modal');
?> 