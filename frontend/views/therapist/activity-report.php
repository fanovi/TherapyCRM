<?php

use common\models\Absence;
use common\models\Appointment;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\Therapist $therapist */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var yii\data\ActiveDataProvider $appointmentsProvider */
/** @var yii\data\ActiveDataProvider $absencesProvider */
/** @var yii\data\ActiveDataProvider $substitutionsProvider */
$this->title = 'Report Attività - ' . $therapist->user->profile->last_name . ' ' . $therapist->user->profile->first_name;
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $therapist->user->profile->last_name . ' ' . $therapist->user->profile->first_name, 'url' => ['view', 'id' => $therapist->id]];
$this->params['breadcrumbs'][] = 'Report Attività';

// CSS per date picker
$this->registerCss("
input[type='date'] {
    position: relative;
    background: white;
    cursor: pointer;
    min-height: 42px;
}

input[type='date']::-webkit-calendar-picker-indicator {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: auto;
    height: auto;
    color: transparent;
    background: transparent;
    cursor: pointer;
}

input[type='date']::-webkit-datetime-edit {
    padding: 0;
}

input[type='date']::-webkit-datetime-edit-fields-wrapper {
    padding: 0;
}

/* Dark mode support */
.dark input[type='date'] {
    background: rgb(55 65 81);
    color: white;
}
");
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Flash Messages -->
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200 dark:bg-red-900/20 dark:border-red-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        <?= Yii::$app->session->getFlash('error') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        <?= Yii::$app->session->getFlash('success') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    

    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                    <?= Html::encode($this->title) ?>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Specializzazione: <?= Html::encode($therapist->specialization->name) ?> | 
                    Ore Settimanali: <?= Html::encode($therapist->weekly_hours_contract) ?>
                </p>
            </div>
            <div>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> Torna alla Lista',
                        ['index'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Filtra per Periodo
                </h3>
                <div>
                    <?= Html::a('<svg class="mr-2 h-4 w-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg> Esporta Excel',
                            ['activity-report', 'id' => $therapist->id, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'export' => 'excel'], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors',
                        'style' => 'background-color: #16a34a !important;'
                    ]) ?>
                </div>
            </div>
            <form method="get" id="filter-form" class="flex flex-wrap gap-4 items-end">
                <input type="hidden" name="id" value="<?= Html::encode($therapist->id) ?>">
                <div class="flex-1 min-w-[200px]">
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Data Inizio
                    </label>
                    <div class="relative">
                        <input type="date" 
                               id="date_from" 
                               name="date_from" 
                               value="<?= Html::encode($dateFrom) ?>" 
                               placeholder="gg/mm/aaaa"
                               required
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2.5 pr-10">
                        <button type="button" onclick="document.getElementById('date_from').showPicker()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Data Fine
                    </label>
                    <div class="relative">
                        <input type="date" 
                               id="date_to" 
                               name="date_to" 
                               value="<?= Html::encode($dateTo) ?>" 
                               placeholder="gg/mm/aaaa"
                               required
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2.5 pr-10">
                        <button type="button" onclick="document.getElementById('date_to').showPicker()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Filtra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Period Info -->
    <div class="mb-6 rounded-lg bg-blue-50 p-4 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Visualizzazione attività dal <strong><?= date('d/m/Y', strtotime($dateFrom)) ?></strong> al <strong><?= date('d/m/Y', strtotime($dateTo)) ?></strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Section 1: Appuntamenti -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                📅 Appuntamenti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Totale: <?= $appointmentsProvider->totalCount ?> appuntamenti
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'appointments-pjax']); ?>
            
            <?= GridView::widget([
                'dataProvider' => $appointmentsProvider,
                'options' => ['class' => 'min-w-full'],
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                'columns' => [
                    [
                        'attribute' => 'appointment_datetime',
                        'label' => 'Data/Ora',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            return date('d/m/Y H:i', strtotime($model->appointment_datetime));
                        }
                    ],
                    [
                        'label' => 'Paziente',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            $patient = $model->getActualPatient();
                            return $patient ? ($patient->last_name . ' ' . $patient->first_name) : 'N/A';
                        }
                    ],
                    [
                        'label' => 'Tipo Trattamento',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            $treatmentType = $model->getActualTreatmentType();
                            return $treatmentType ? $treatmentType->name : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'duration_minutes',
                        'label' => 'Durata',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 text-center'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            return $model->duration_minutes . ' min';
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            $statusClasses = [
                                Appointment::STATUS_SCHEDULED => 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900',
                                Appointment::STATUS_COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900',
                                Appointment::STATUS_CANCELLED => 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900',
                                Appointment::STATUS_ABSENT_JUSTIFIED => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-200 dark:text-yellow-900',
                                Appointment::STATUS_ABSENT_NOT_JUSTIFIED => 'bg-orange-100 text-orange-800 dark:bg-orange-200 dark:text-orange-900',
                                Appointment::STATUS_THERAPIST_ABSENT => 'bg-purple-100 text-purple-800 dark:bg-purple-200 dark:text-purple-900',
                            ];
                            $class = $statusClasses[$model->status] ?? 'bg-gray-100 text-gray-800';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $model->getStatusLabel() . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'appointment_source',
                        'label' => 'Origine',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            $class = $model->isPrivate() ? 'bg-purple-100 text-purple-800 dark:bg-purple-200 dark:text-purple-900' : 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $model->getAppointmentSourceLabel() . '</span>';
                        }
                    ],
                    [
                        'label' => 'Setting',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            return $model->setting ? $model->setting->nome : 'N/A';
                        }
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>

    <!-- Section 2: Assenze -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                🏖️ Assenze
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Totale: <?= $absencesProvider->totalCount ?> assenze
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'absences-pjax']); ?>
            
            <?= GridView::widget([
                'dataProvider' => $absencesProvider,
                'options' => ['class' => 'min-w-full'],
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                'columns' => [
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            return date('d/m/Y', strtotime($model->start_date));
                        }
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            return date('d/m/Y', strtotime($model->end_date));
                        }
                    ],
                    [
                        'attribute' => 'type',
                        'label' => 'Tipo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[130px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            $typeClasses = [
                                Absence::TYPE_VACATION => 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900',
                                Absence::TYPE_SICK_LEAVE => 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900',
                                Absence::TYPE_PERSONAL => 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900',
                                Absence::TYPE_TRAINING => 'bg-purple-100 text-purple-800 dark:bg-purple-200 dark:text-purple-900',
                                Absence::TYPE_OTHER => 'bg-gray-100 text-gray-800 dark:bg-gray-200 dark:text-gray-900',
                            ];
                            $class = $typeClasses[$model->type] ?? 'bg-gray-100 text-gray-800';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $model->getTypeLabel() . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            $statusClasses = [
                                Absence::STATUS_PENDING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-200 dark:text-yellow-900',
                                Absence::STATUS_APPROVED => 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900',
                                Absence::STATUS_REJECTED => 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900',
                                Absence::STATUS_CANCELLED => 'bg-gray-100 text-gray-800 dark:bg-gray-200 dark:text-gray-900',
                            ];
                            $class = $statusClasses[$model->status] ?? 'bg-gray-100 text-gray-800';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $class . '">' . $model->getStatusLabel() . '</span>';
                        }
                    ],
                    [
                        'label' => 'Durata',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 text-center'],
                        'value' => function ($model) {
                            return $model->getDurationDays() . ' giorni';
                        }
                    ],
                    [
                        'attribute' => 'reason',
                        'label' => 'Motivo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            return $model->reason ?: '-';
                        }
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>

    <!-- Section 3: Sostituzioni -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                🔄 Sostituzioni
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Totale: <?= $substitutionsProvider->totalCount ?> sostituzioni
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'substitutions-pjax']); ?>
            
            <?= GridView::widget([
                'dataProvider' => $substitutionsProvider,
                'options' => ['class' => 'min-w-full'],
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                'columns' => [
                    [
                        'label' => 'Data Appuntamento',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'value' => function ($model) {
                            return date('d/m/Y H:i', strtotime($model->appointment->appointment_datetime));
                        }
                    ],
                    [
                        'label' => 'Terapista Originale',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            return $model->originalTherapist->user->profile->last_name . ' '
                                . $model->originalTherapist->user->profile->first_name;
                        }
                    ],
                    [
                        'label' => 'Terapista Sostituto',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            return $model->substituteTherapist->user->profile->last_name . ' '
                                . $model->substituteTherapist->user->profile->first_name;
                        }
                    ],
                    [
                        'label' => 'Ruolo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'format' => 'raw',
                        'value' => function ($model) use ($therapist) {
                            if ($model->original_therapist_id == $therapist->id) {
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-200 dark:text-orange-900">Sostituito</span>';
                            } else {
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900">Sostituto</span>';
                            }
                        }
                    ],
                    [
                        'attribute' => 'reason',
                        'label' => 'Motivo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'value' => function ($model) {
                            return $model->reason ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'substituted_at',
                        'label' => 'Sostituito il',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'format' => 'raw',
                        'value' => function ($model) {
                            return date('d/m/Y H:i', strtotime($model->substituted_at));
                        }
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<?php
// JavaScript per validazione date
$this->registerJs("
// Date picker validation
const dateFromInput = document.getElementById('date_from');
const dateToInput = document.getElementById('date_to');
const filterForm = document.getElementById('filter-form');

// Update date_to min attribute when date_from changes
dateFromInput.addEventListener('change', function() {
    dateToInput.min = this.value;
    
    // If date_to is less than date_from, update it
    if (dateToInput.value && dateToInput.value < this.value) {
        dateToInput.value = this.value;
    }
});

// Update date_from max attribute when date_to changes
dateToInput.addEventListener('change', function() {
    dateFromInput.max = this.value;
    
    // If date_from is greater than date_to, update it
    if (dateFromInput.value && dateFromInput.value > this.value) {
        dateFromInput.value = this.value;
    }
});

// Validate on form submit
filterForm.addEventListener('submit', function(e) {
    const dateFrom = new Date(dateFromInput.value);
    const dateTo = new Date(dateToInput.value);
    
    if (dateFrom > dateTo) {
        e.preventDefault();
        alert('La data di inizio non può essere successiva alla data di fine!');
        dateFromInput.focus();
        return false;
    }
    
    return true;
});

// Initialize min/max on page load
if (dateFromInput.value) {
    dateToInput.min = dateFromInput.value;
}
if (dateToInput.value) {
    dateFromInput.max = dateToInput.value;
}
", \yii\web\View::POS_READY);
?>

