<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Patient */

$this->title = $model->fullName;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/index']) ?>">
                            Pazienti
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
        <?php if (Yii::$app->user->can('update_patient')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Modifica', 
                ['update', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        <?php endif; ?>
        
        <?php if (Yii::$app->user->can('create_patient')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>Crea Credenziali', 
                ['create-credentials', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-green-600 bg-white border border-green-300 rounded-lg hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2'
            ]) ?>
        <?php endif; ?>
        
        <?php if (Yii::$app->user->can('delete_patient')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>Elimina', 
                ['delete', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                'data' => [
                    'confirm' => 'Sei sicuro di voler eliminare questo paziente?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
    </div>

    <!-- Dati Personali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni anagrafiche del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'first_name',
                        'label' => 'Nome',
                    ],
                    [
                        'attribute' => 'last_name', 
                        'label' => 'Cognome',
                    ],
                    [
                        'attribute' => 'fiscal_code',
                        'label' => 'Codice Fiscale',
                        'value' => function($model) {
                            return $model->fiscal_code ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'birth_date',
                        'label' => 'Data di Nascita',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'label' => 'Età',
                        'value' => function($model) {
                            return $model->age ? $model->age . ' anni' : '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Informazioni Aggiuntive -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Aggiuntive
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dettagli supplementari del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'district.name',
                        'label' => 'Distretto',
                        'value' => function($model) {
                            return $model->district ? $model->district->name : '-';
                        }
                    ],
                    [
                        'attribute' => 'notes',
                        'label' => 'Note',
                        'value' => function($model) {
                            return $model->notes ?: '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Informazioni di Sistema -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni di Sistema
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Date di creazione e aggiornamento del paziente.
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