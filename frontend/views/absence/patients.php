<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\PatientAbsenceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array|null $therapistsList */

$this->title = 'Assenze Pazienti';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            <?php if (Yii::$app->user->can('manage_patient_absence')): ?>
                <a href="<?= \yii\helpers\Url::to(['absence/create-patient-absence']) ?>"
                   class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                    + Crea assenza paziente
                </a>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Assenze Pazienti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Appuntamenti in cui il paziente risulta assente.
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovate ' . $dataProvider->totalCount . ' assenze' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['patients'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#patient-absence-grid-pjax"});'
                ]) ?>
            </div>
        </div>

        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'patient-absence-grid-pjax']); ?>

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
                        'attribute' => 'patient_name',
                        'label' => 'Paziente',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cerca paziente...'],
                        'value' => function($model) {
                            $patient = $model->getActualPatient();
                            return $patient ? $patient->getFullName() : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'date_from',
                        'label' => 'Data/Ora',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[160px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                        'value' => function($model) {
                            return Yii::$app->formatter->asDatetime($model->appointment_datetime, 'php:d/m/Y H:i');
                        }
                    ],
                    [
                        'attribute' => 'therapist_id',
                        'label' => 'Terapista',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'therapist_id',
                            isset($therapistsList) ? $therapistsList : \common\models\PatientAbsenceSearch::getTherapistsList(),
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'value' => function($model) {
                            return $model->therapist && $model->therapist->user && $model->therapist->user->profile
                                ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
                                : '';
                        }
                    ],
                    [
                        'attribute' => 'duration_minutes',
                        'label' => 'Durata',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                        'format' => 'raw',
                        'content' => function($model) {
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900">' . $model->duration_minutes . ' min</span>';
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'status',
                            \common\models\PatientAbsenceSearch::getStatusList(),
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'format' => 'raw',
                        'content' => function($model) {
                            $isJustified = $model->status === \common\models\Appointment::STATUS_ABSENT_JUSTIFIED;
                            $statusClass = $isJustified
                                ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900'
                                : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900';
                            $label = $isJustified ? 'Giustificata' : 'Non giustificata';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $label . '</span>';
                        }
                    ],
                    [
                        'label' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'format' => 'raw',
                        'content' => function($model) {
                            $appointmentDateTime = new \DateTime($model->appointment_datetime);
                            $now = new \DateTime();
                            $oneHourBefore = (clone $appointmentDateTime)->modify('-1 hour');

                            if ($now < $oneHourBefore) {
                                return Html::button('<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>Rimuovi', [
                                    'class' => 'inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-blue-900 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-800',
                                    'onclick' => 'removePatientAbsence(' . $model->id . ')',
                                ]);
                            }
                            return '<span class="inline-flex items-center gap-1 text-xs text-gray-400">'
                                . 'Non disponibile'
                                . '<span class="cursor-help text-gray-400 hover:text-gray-600" title="Rimozione disponibile solo fino a 1 ora prima dell\'inizio dell\'appuntamento. Questo appuntamento è già passato o troppo vicino.">'
                                . '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                                . '</span>'
                                . '</span>';
                        }
                    ],
                ],
            ]); ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<?php
$removeUrl = \yii\helpers\Url::to(['absence/remove-patient-absence']);
$js = <<<JS
function removePatientAbsence(id) {
    Swal.fire({
        title: 'Rimuovere l\\'assenza?',
        text: 'L\\'appuntamento verrà ripristinato come confermato.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Rimuovi',
        cancelButtonText: 'Annulla',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    }).then(function(res) {
        if (!res.isConfirmed) return;

        Swal.fire({
            title: 'Operazione in corso...',
            didOpen: function() { Swal.showLoading(); },
            showConfirmButton: false,
            allowOutsideClick: false,
        });

        $.ajax({
            url: '{$removeUrl}',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Assenza rimossa',
                        text: 'L\\'appuntamento è stato ripristinato.',
                        confirmButtonColor: '#3b82f6',
                    }).then(function() {
                        $.pjax.reload({container: '#patient-absence-grid-pjax'});
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operazione non riuscita',
                        text: response.error || 'Errore sconosciuto',
                        confirmButtonColor: '#dc2626',
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore di comunicazione',
                    text: 'Impossibile contattare il server. Riprova.',
                    confirmButtonColor: '#dc2626',
                });
            }
        });
    });
}
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
