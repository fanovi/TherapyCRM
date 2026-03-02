<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Absence $model */

$therapistName = $model->therapist && $model->therapist->user && $model->therapist->user->profile 
    ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
    : 'Terapista';

$this->title = 'Assenza: ' . $therapistName;
$this->params['breadcrumbs'][] = ['label' => 'Assenze', 'url' => ['index']];
$this->params['breadcrumbs'][] = $therapistName;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <div class="flex gap-2">
                <?php if (Yii::$app->user->can('create_absence')): ?>
                    <?= Html::a('Modifica', ['update', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                    ]) ?>
                    <?= Html::a('Elimina', ['delete', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-error-500 border border-transparent rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                        'data' => [
                            'confirm' => 'Sei sicuro di voler eliminare questa assenza?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php endif; ?>
                <?= Html::a('Torna alla lista', ['index'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dettagli Assenza
            </h3>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table detail-view'],
                'template' => '<tr><th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">{label}</th><td class="px-5 py-3 text-sm text-gray-900 dark:text-gray-100">{value}</td></tr>',
                'attributes' => [
                    [
                        'label' => 'Terapista',
                        'value' => function($model) {
                            return $model->therapist && $model->therapist->user && $model->therapist->user->profile 
                                ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
                                : '-';
                        },
                    ],
                    [
                        'attribute' => 'start_date',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'attribute' => 'end_date',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'label' => 'Durata',
                        'value' => function($model) {
                            return $model->getDurationDays() . ' giorni';
                        },
                    ],
                    [
                        'attribute' => 'type',
                        'value' => function($model) {
                            return $model->getTypeLabel();
                        },
                    ],
                    'reason:ntext',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            $statusClass = $model->isApproved() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $model->getStatusLabel() . '</span>';
                        },
                    ],
                    [
                        'label' => 'Approvato da',
                        'value' => function($model) {
                            return $model->approvedBy && $model->approvedBy->profile 
                                ? $model->approvedBy->profile->getFullName()
                                : '-';
                        },
                    ],
                    [
                        'attribute' => 'approved_at',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                        'visible' => !empty($model->approved_at),
                    ],
                    'notes:ntext',
                    [
                        'label' => 'Creato da',
                        'value' => function($model) {
                            return $model->createdBy && $model->createdBy->profile 
                                ? $model->createdBy->profile->getFullName()
                                : '-';
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>