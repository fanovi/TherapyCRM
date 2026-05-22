<?php

use common\helpers\GridViewHelper;
use common\models\District;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PatientSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Pazienti';
$this->params['breadcrumbs'][] = $this->title;

// Get districts for filter
$districts = District::getDropdownData();

// Registra il CSS per le notifiche e il JS (cache-bust via filemtime)
$this->registerJsVar('sendNotificationUrl', Url::to(['patient/send-notification']));
$patientNotifMtime = @filemtime(Yii::getAlias('@webroot/js/patient-notifications.js')) ?: time();
$this->registerJsFile('@web/js/patient-notifications.js?v=' . $patientNotifMtime, ['depends' => [\yii\web\JqueryAsset::class]]);

// Toggle filtro "Solo con appuntamenti privati" -> pjax reload preservando altri filtri.
$this->registerJs(<<<JS
(function() {
    var checkbox = document.getElementById('filter-has-private');
    if (!checkbox) return;
    checkbox.addEventListener('change', function() {
        var url = new URL(window.location.href);
        if (this.checked) {
            url.searchParams.set('PatientSearch[has_private_appointment]', '1');
        } else {
            url.searchParams.delete('PatientSearch[has_private_appointment]');
        }
        url.searchParams.delete('page');
        if (typeof jQuery !== 'undefined' && jQuery.pjax) {
            jQuery.pjax.reload({container: '#patients-pjax', url: url.toString(), timeout: 5000});
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', url.toString());
            }
        } else {
            window.location.href = url.toString();
        }
    });
})();
JS
, \yii\web\View::POS_END, 'filter-has-private-toggle');

// Errore eliminazione paziente -> Swal modale, senza redirect a dettaglio.
if (Yii::$app->session->hasFlash('patient_delete_error')) {
    $err = Yii::$app->session->getFlash('patient_delete_error');
    $title = is_array($err) ? ($err['title'] ?? 'Errore') : 'Errore';
    $html  = is_array($err) ? ($err['html']  ?? '') : (string) $err;
    $titleJs = json_encode($title);
    $htmlJs  = json_encode($html);
    $this->registerJs(<<<JS
if (typeof Swal !== 'undefined') {
    Swal.fire({
        icon: 'error',
        title: {$titleJs},
        html: {$htmlJs},
        confirmButtonText: 'Ok',
        confirmButtonColor: '#dc2626'
    });
}
JS
, \yii\web\View::POS_END, 'patient-delete-error-swal');
}
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
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Lista Pazienti
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Gestisci tutti i pazienti del sistema. Seleziona i pazienti per inviare notifiche.
                    </p>
                </div>
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center">
                        <input type="checkbox" id="filter-has-private"
                               <?= !empty($searchModel->has_private_appointment) ? 'checked' : '' ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="filter-has-private" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Solo con appuntamenti privati
                        </label>
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
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <!-- Horizontal scroll wrapper -->
            <div class="overflow-x-auto">
                <?php Pjax::begin([
                    'id' => 'patients-pjax',
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
                        // Colonna checkbox per selezione
                        [
                            'headerOptions' => ['class' => 'px-6 py-3 w-12'],
                            'contentOptions' => ['class' => 'px-6 py-4'],
                            'filter' => false,
                            'header' => '',
                            'content' => function ($model) {
                                return Html::checkbox('patient_ids[]', false, [
                                    'value' => $model->id,
                                    'class' => 'patient-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded'
                                ]);
                            },
                        ],
                        // [
                        //     'attribute' => 'id',
                        //     'headerOptions' => ['class' => 'px-6 py-3 min-w-[80px]'],
                        //     'contentOptions' => ['class' => 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'],
                        //     'filterOptions' => ['class' => 'px-2 py-2'],
                        //     'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'ID...'],
                        // ],
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
                            'filter' => false,  // No filter for birth date
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
                        // [
                        //     'attribute' => 'notes',
                        //     'label' => 'Note',
                        //     'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                        //     'contentOptions' => ['class' => 'px-6 py-4 max-w-xs truncate'],
                        //     'filter' => false, // No filter for notes
                        //     'value' => function($model) {
                        //         return $model->notes ? (strlen($model->notes) > 50 ? substr($model->notes, 0, 50) . '...' : $model->notes) : '-';
                        //     },
                        // ],
                        // [
                        //     'attribute' => 'created_at',
                        //     'label' => 'Creato il',
                        //     'headerOptions' => ['class' => 'px-6 py-3 min-w-[130px]'],
                        //     'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-xs text-gray-500'],
                        //     'filter' => false, // No filter for created date
                        //     'format' => ['date', 'php:d/m/Y'],
                        // ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Azioni',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[180px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-right'],
                            'template' => '<div class="flex items-center justify-end space-x-2">{view} {update} {credentials} {delete} {calendar-link}</div>',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                            $url, [
                                        'title' => 'Visualizza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('update_patient'))
                                        return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                                            $url, [
                                        'title' => 'Modifica',
                                        'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20'
                                    ]);
                                },
                                'credentials' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('create_patient'))
                                        return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>',
                                            ['create-credentials', 'id' => $model->id], [
                                        'title' => 'Crea Credenziali',
                                        'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20'
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('delete_patient'))
                                        return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                                            $url, [
                                        'title' => 'Elimina',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20',
                                        'data' => [
                                            'confirm' => 'Sei sicuro di voler eliminare questo paziente?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                },
                                'calendar-link' => function ($url, $model, $key) {
                                    if (!(Yii::$app->user->can('view_calendar') || Yii::$app->user->can('manage_calendar')))
                                        return '';
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
                ], GridViewHelper::getGridViewConfig('pazienti'))); ?>
                
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

