<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;
use common\models\District;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PatientSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Pazienti';
$this->params['breadcrumbs'][] = $this->title;

// Get districts for filter
$districts = ArrayHelper::map(District::find()->all(), 'id', 'name');

// Registra il CSS per le notifiche e il JS
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
    <div id="notification-actions-bar" class="hidden items-center justify-between mb-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl dark:bg-white/[0.03] dark:border-gray-800">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-brand-500 mr-2 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
            </svg>
            <span class="text-gray-800 dark:text-white/90 font-medium">
                <span id="selected-patients-count">0</span> pazienti selezionati
            </span>
        </div>
        <div class="flex items-center space-x-3">
            <button id="send-notifications-btn" 
                    class="flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
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
                            'label' => 'Data di Nascita (Età)',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filter' => false, // No filter for birth date
                            'value' => function($model) {
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
                            'template' => '{view} {update} {credentials} {delete} {calendar-link}',
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
                                'calendar-link' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('manage_calendar')) return '';
                                    $url = ['calendar/' . $model->id];
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
                ]); ?>
                
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

<!-- BEGIN MODAL -->
<div
    class="fixed inset-0 items-center justify-center hidden p-5 overflow-y-auto modal z-99999"
    id="notificationModal"
>
    <div
        class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"
    ></div>
    <div
        class="modal-dialog modal-dialog-scrollable modal-lg no-scrollbar relative flex w-full max-w-[600px] flex-col overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-8"
        x-data="{
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
                document.getElementById('notificationModal').classList.remove('hidden');
                document.getElementById('notificationModal').classList.add('flex');
                this.$nextTick(() => {
                    this.$refs.titleInput?.focus();
                });
            },
            
            closeModal() {
                this.showModal = false;
                this.resetForm();
                document.getElementById('notificationModal').classList.add('hidden');
                document.getElementById('notificationModal').classList.remove('flex');
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
        @open-modal.window="openModal($event.detail.count)"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </div>
                    <h5
                        class="font-semibold text-gray-800 modal-title text-theme-xl dark:text-white/90 lg:text-2xl"
                    >
                        Invia Notifica
                    </h5>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Invia una notifica informativa a tutti gli account collegati ai pazienti selezionati
                </p>
            </div>
            
            <div class="mt-6 modal-body">
                <!-- Info Alert -->
                <div class="mb-6 rounded-lg border border-brand-200 bg-brand-50 p-4 dark:border-brand-800 dark:bg-brand-900/20">
                    <div class="flex items-start">
                        <svg class="mr-3 mt-0.5 h-5 w-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm">
                            <p class="font-medium text-brand-800 dark:text-brand-200">
                                <span x-text="selectedCount"></span> pazienti selezionati
                            </p>
                            <p class="mt-1 text-brand-700 dark:text-brand-300">
                                La notifica sarà inviata a tutti gli account collegati ai pazienti selezionati
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-6">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                        >
                            Titolo Notifica *
                        </label>
                        <input
                            x-ref="titleInput"
                            x-model="title"
                            type="text"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                            placeholder="Inserisci il titolo della notifica..."
                            maxlength="100"
                            required
                        />
                    </div>

                    <div class="mb-6">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                        >
                            Messaggio *
                        </label>
                        <textarea
                            x-model="message"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                            placeholder="Inserisci il messaggio della notifica..."
                            maxlength="500"
                            required
                        ></textarea>
                    </div>
                </div>

                <!-- Messages -->
                <div x-show="errors" x-cloak class="mb-4">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <div class="flex items-start">
                            <svg class="mr-3 mt-0.5 h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-red-800 dark:text-red-200" x-text="errors"></p>
                        </div>
                    </div>
                </div>
                
                <div x-show="success" x-cloak class="mb-4">
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
                    id="send-notification-modal-btn"
                    :disabled="isLoading || !title.trim() || !message.trim()"
                    @click="window.sendPatientNotifications()"
                    class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                >
                    <span x-show="!isLoading" class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Invia Notifica
                    </span>
                    <span x-show="isLoading" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Invio in corso...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- END MODAL --> 