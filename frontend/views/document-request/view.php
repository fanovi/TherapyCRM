<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\RequestStatus;

/* @var $this yii\web\View */
/* @var $model common\models\DocumentRequest */

$this->title = 'Richiesta Documento';
$this->params['breadcrumbs'][] = ['label' => 'Richieste Documenti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-6xl p-4 md:p-6">
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
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/document-request/index']) ?>">
                            Richieste Documenti
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

    <!-- Action Buttons -->
    <div class="mb-6 flex flex-wrap gap-3">
        <?= Html::a(
            '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>Torna alla Lista',
            ['index'],
            ['class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700']
        ) ?>
        
        <?php
        // LOGICA SEMPLIFICATA:
        // Ottieni il ruolo specifico dell'utente
        $auth = Yii::$app->authManager;
        $userRoles = array_keys($auth->getRolesByUser(Yii::$app->user->id));
        $isAdmin = in_array('admin', $userRoles);
        $isManager = in_array('manager', $userRoles);
        
        if ($isAdmin):
            // === ADMIN ===
            if ($model->status == RequestStatus::STATUS_CONSEGNATO):
        ?>
                <div class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 border border-gray-300 rounded-lg">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Richiesta Consegnata - Non Modificabile
                </div>
        <?php
            else:
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
        ?>
                <button onclick="openStatusUpdateModal(<?= $model->id ?>, <?= Html::encode(json_encode($availableStatuses)) ?>)" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Cambia Stato
                </button>
        <?php
            endif;
        elseif ($isManager):
            // === MANAGER ===
            if ($model->status == RequestStatus::STATUS_CONSEGNATO):
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
        ?>
                <button onclick="openStatusUpdateModal(<?= $model->id ?>, <?= Html::encode(json_encode($availableStatuses)) ?>)" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-orange-500 border border-transparent rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                    Torna a: <?= $previousStatusLabel ?>
                </button>
        <?php
            else:
                // Mostra opzione per consegnare
                $availableStatuses = [RequestStatus::STATUS_CONSEGNATO => 'Consegnato'];
        ?>
                <button onclick="openStatusUpdateModal(<?= $model->id ?>, <?= Html::encode(json_encode($availableStatuses)) ?>)" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-500 border border-transparent rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Segna come Consegnato
                </button>
        <?php
            endif;
        endif;
        ?>
    </div>

    <!-- Status Badge -->
    <div class="mb-6">
        <?php
        $statusColors = [
            RequestStatus::STATUS_INVIATA => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            RequestStatus::STATUS_PRESA_IN_CARICO => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            RequestStatus::STATUS_STAMPATO => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            RequestStatus::STATUS_CONSEGNATO => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ];
        
        $colorClass = $statusColors[$model->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
        ?>
        
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stato:</span>
            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $colorClass ?>">
                <?= Html::encode($model->getStatusLabel()) ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - 2/3 -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Dati Richiesta -->
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Dati Richiesta
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Informazioni principali della richiesta di documento.
                    </p>
                </div>
                
                <div class="border-t border-gray-100 dark:border-gray-800">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'w-full'],
                        'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                        'attributes' => [
                            [
                                'attribute' => 'account_patient_id',
                                'label' => 'Richiedente',
                                'value' => function ($model) {
                                    if ($model->accountPatient && $model->accountPatient->user && $model->accountPatient->user->profile) {
                                        $profile = $model->accountPatient->user->profile;
                                        return $profile->first_name . ' ' . $profile->last_name;
                                    }
                                    return 'N/A';
                                },
                            ],
                            [
                                'attribute' => 'patient_id',
                                'label' => 'Paziente',
                                'value' => function ($model) {
                                    if ($model->patient) {
                                        return $model->patient->first_name . ' ' . $model->patient->last_name;
                                    }
                                    return 'N/A';
                                },
                            ],
                            [
                                'attribute' => 'request_type_id',
                                'label' => 'Tipo Richiesta',
                                'value' => function ($model) {
                                    return $model->requestType ? $model->requestType->name : 'N/A';
                                },
                            ],
                            [
                                'attribute' => 'created_at',
                                'label' => 'Data Richiesta',
                                'value' => function ($model) {
                                    return Yii::$app->formatter->asDatetime($model->created_at, 'php:d/m/Y H:i');
                                },
                            ],
                        ],
                    ]) ?>
                </div>
            </div>

            <!-- Dettagli Terapia -->
            <?php if ($model->therapeutic_plan_id || $model->therapy_id): ?>
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Dettagli Terapia
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Informazioni sul piano terapeutico e terapia associata.
                    </p>
                </div>
                
                <div class="border-t border-gray-100 dark:border-gray-800">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'w-full'],
                        'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                        'attributes' => [
                            [
                                'attribute' => 'therapeutic_plan_id',
                                'label' => 'Piano Terapeutico',
                                'value' => function ($model) {
                                    return $model->therapeuticPlan ? 
                                           'Piano #' . $model->therapeuticPlan->id : 'N/A';
                                },
                                'visible' => $model->therapeutic_plan_id,
                            ],
                            [
                                'attribute' => 'therapy_id',
                                'label' => 'Terapia',
                                'value' => function ($model) {
                                    return $model->therapy ? 
                                           $model->therapy->name : 'N/A';
                                },
                                'visible' => $model->therapy_id,
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Note -->
            <?php if ($model->notes): ?>
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Note
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Note aggiuntive alla richiesta.
                    </p>
                </div>
                
                <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-4 sm:px-6">
                    <div class="text-sm text-gray-800 dark:text-white/90">
                        <?= Html::encode($model->notes) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - 1/3 -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Storico Stati -->
            <?php if ($model->statusHistory): ?>
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Storico Stati
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Cronologia dei cambiamenti di stato.
                    </p>
                </div>
                
                <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-4 sm:px-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <?php foreach ($model->statusHistory as $index => $history): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($index < count($model->statusHistory) - 1): ?>
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <?php
                                            $iconColors = [
                                                RequestStatus::STATUS_INVIATA => 'bg-red-400',
                                                RequestStatus::STATUS_PRESA_IN_CARICO => 'bg-yellow-400',
                                                RequestStatus::STATUS_STAMPATO => 'bg-blue-400',
                                                RequestStatus::STATUS_CONSEGNATO => 'bg-green-400',
                                            ];
                                            $iconColor = $iconColors[$history->to_status_id] ?? 'bg-gray-400';
                                            ?>
                                            <span class="h-8 w-8 rounded-full <?= $iconColor ?> flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        
                                        <div class="min-w-0 flex-1 pt-1.5">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    <?php if ($history->from_status_id): ?>
                                                        <?= Html::encode($history->toStatus->name) ?>
                                                    <?php else: ?>
                                                        Richiesta creata
                                                    <?php endif; ?>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    <?php if ($history->changedByUser): ?>
                                                        da <?= $history->changedByUser->profile ? 
                                                               Html::encode($history->changedByUser->profile->first_name . ' ' . $history->changedByUser->profile->last_name) :
                                                               Html::encode($history->changedByUser->username) ?>
                                                    <?php else: ?>
                                                        Sistema
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                <?= Yii::$app->formatter->asDatetime($history->created_at, 'php:d/m/Y H:i') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informazioni di Sistema -->
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Informazioni di Sistema
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Date di creazione e aggiornamento.
                    </p>
                </div>
                
                <div class="border-t border-gray-100 dark:border-gray-800">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'w-full'],
                        'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                        'attributes' => [
                            [
                                'attribute' => 'created_at',
                                'label' => 'Creato il',
                                'format' => ['date', 'php:d/m/Y H:i'],
                            ],
                            [
                                'attribute' => 'updated_at',
                                'label' => 'Aggiornato il',
                                'format' => ['date', 'php:d/m/Y H:i'],
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

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