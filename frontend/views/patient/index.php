<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;
use common\models\District;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PatientSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Pazienti';
$this->params['breadcrumbs'][] = $this->title;

// Get districts for filter
$districts = ArrayHelper::map(District::find()->all(), 'id', 'name');

// Registra il CSS per le notifiche e il JS
use yii\helpers\Url;

$this->registerJsVar('sendNotificationUrl', Url::to(['patient/send-notification']));
$this->registerJsFile('@web/js/patient-notifications.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <!-- Action Button -->
            <?php if (Yii::$app->user->can('create_patient')): ?>
            <div>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Nuovo Paziente', 
                    ['create'], [
                    'class' => 'inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600'
                ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Barra azioni notifiche (nascosta di default) -->
    <div id="notification-actions-bar" class="hidden items-center justify-between mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-700">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
            </svg>
            <span class="text-blue-800 dark:text-blue-200">
                <span id="selected-patients-count">0</span> pazienti selezionati
            </span>
        </div>
        <div class="flex items-center space-x-3">
            <button id="send-notifications-btn" 
                    class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white transition rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
                </svg>
                Invia Notifica
            </button>
        </div>
    </div>

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Lista Pazienti
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Gestisci tutti i pazienti del sistema. Seleziona i pazienti per inviare notifiche.
                    </p>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="select-all-patients" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="select-all-patients" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        Seleziona tutto
                    </label>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <!-- Horizontal scroll wrapper -->
            <div class="overflow-x-auto">
                <?php Pjax::begin([
                    'id' => 'patients-pjax',
                    'enablePushState' => false,
                ]); ?>
                
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                    'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                    'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                    'columns' => [
                        // Colonna checkbox per selezione
                        [
                            'headerOptions' => ['class' => 'px-6 py-3 w-12'],
                            'contentOptions' => ['class' => 'px-6 py-4'],
                            'filter' => false,
                            'header' => '',
                            'content' => function($model) {
                                return Html::checkbox('patient_ids[]', false, [
                                    'value' => $model->id,
                                    'class' => 'patient-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded'
                                ]);
                            },
                        ],
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[80px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'ID...'],
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
                            'attribute' => 'last_name',
                            'label' => 'Cognome',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cognome...'],
                        ],
                        [
                            'attribute' => 'fiscal_code',
                            'label' => 'Codice Fiscale',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[160px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap font-mono text-xs'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Codice fiscale...'],
                            'value' => function($model) {
                                return $model->fiscal_code ?: '-';
                            }
                        ],
                        [
                            'attribute' => 'birth_date',
                            'label' => 'Data di Nascita',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[130px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filter' => false, // No filter for birth date
                            'format' => ['date', 'php:d/m/Y'],
                        ],
                        [
                            'label' => 'Età',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[80px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-center'],
                            'filter' => false, // No filter for age
                            'value' => function($model) {
                                return $model->age ? $model->age . ' anni' : '-';
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
                            'value' => function($model) {
                                return $model->district ? $model->district->name : '-';
                            }
                        ],
                        [
                            'attribute' => 'notes',
                            'label' => 'Note',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 max-w-xs truncate'],
                            'filter' => false, // No filter for notes
                            'value' => function($model) {
                                return $model->notes ? (strlen($model->notes) > 50 ? substr($model->notes, 0, 50) . '...' : $model->notes) : '-';
                            },
                        ],
                        [
                            'attribute' => 'created_at',
                            'label' => 'Creato il',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[130px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-xs text-gray-500'],
                            'filter' => false, // No filter for created date
                            'format' => ['date', 'php:d/m/Y'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Azioni',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'template' => '{view} {update} {credentials} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', 
                                        $url, [
                                        'title' => 'Visualizza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('update_patient')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                                        $url, [
                                        'title' => 'Modifica',
                                        'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3'
                                    ]);
                                },
                                'credentials' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('create_patient')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>', 
                                        ['create-credentials', 'id' => $model->id], [
                                        'title' => 'Crea Credenziali',
                                        'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3'
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('delete_patient')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', 
                                        $url, [
                                        'title' => 'Elimina',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
                                        'data' => [
                                            'confirm' => 'Sei sicuro di voler eliminare questo paziente?',
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
</div>

<!-- Modal Notifiche -->
<div x-data="{
    showModal: false,
    selectedCount: 0,
    title: '',
    message: '',
    isLoading: false,
    errors: '',
    success: '',
    
    openModal(count) {
        this.selectedCount = count;
        this.showModal = true;
        this.resetMessages();
        this.$nextTick(() => {
            this.$refs.titleInput?.focus();
        });
    },
    
    closeModal() {
        this.showModal = false;
        this.resetForm();
    },
    
    resetForm() {
        this.title = '';
        this.message = '';
        this.resetMessages();
    },
    
    resetMessages() {
        this.errors = '';
        this.success = '';
    }
}" 
     x-show="showModal" 
     x-cloak
     @open-modal.window="openModal($event.detail.count)"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
     
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="closeModal()"></div>
    
    <!-- Modal Content -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto dark:bg-gray-800"
             @click.stop>
             
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Invia Notifica
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="px-6 py-4">
                <!-- Info -->
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-700">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        La notifica sarà inviata a tutti gli account collegati ai <strong><span x-text="selectedCount"></span> pazienti selezionati</strong>.
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="window.sendPatientNotifications()" class="space-y-4">
                    <div>
                        <label for="notification-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Titolo Notifica *
                        </label>
                        <input type="text" 
                               x-ref="titleInput"
                               x-model="title"
                               id="notification-title" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="Inserisci il titolo..."
                               maxlength="100"
                               required>
                    </div>

                    <div>
                        <label for="notification-message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Messaggio *
                        </label>
                        <textarea x-model="message"
                                  id="notification-message" 
                                  rows="4"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  placeholder="Inserisci il messaggio..."
                                  maxlength="500"
                                  required></textarea>
                    </div>
                </form>

                <!-- Messages -->
                <div x-show="errors" x-cloak class="mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-md p-3 dark:bg-red-900/20 dark:border-red-700">
                        <p class="text-sm text-red-800 dark:text-red-200" x-text="errors"></p>
                    </div>
                </div>
                
                <div x-show="success" x-cloak class="mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-md p-3 dark:bg-green-900/20 dark:border-green-700">
                        <p class="text-sm text-green-800 dark:text-green-200" x-text="success"></p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 dark:border-gray-700 dark:bg-gray-800 flex justify-end space-x-3">
                <button type="button" 
                        @click="showModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    Annulla
                </button>
                <button type="button" 
                        :disabled="isLoading || !title.trim() || !message.trim()"
                        @click="window.sendPatientNotifications()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isLoading">Invia Notifica</span>
                    <span x-show="isLoading">Invio...</span>
                </button>
            </div>
        </div>
    </div>
</div> 