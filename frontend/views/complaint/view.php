<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Complaint */

$this->title = 'Reclamo #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Reclami', 'url' => ['index']];
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/complaint/index']) ?>">
                            Reclami
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
        <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Torna alla Lista',
                ['index'], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
        ]) ?>
        
        <?php if ($model->patient_id): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>Vedi Paziente',
                    ['/patient/view', 'id' => $model->patient_id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded-lg hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
            ]) ?>
        <?php endif; ?>
    </div>

    <!-- Informazioni Generali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Generali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dettagli generali del reclamo ricevuto.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'id',
                        'label' => 'ID Reclamo',
                        'value' => '#' . $model->id
                    ],
                    [
                        'attribute' => 'title',
                        'label' => 'Titolo',
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Data Reclamo',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Descrizione Reclamo -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Descrizione del Reclamo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dettagli completi del reclamo.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-4 sm:px-6 sm:py-5">
            <div class="text-sm text-gray-800 dark:text-white/90 whitespace-pre-line">
                <?= Html::encode($model->description) ?>
            </div>
        </div>
    </div>

    <!-- Informazioni Paziente -->
    <?php if ($model->patient): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Paziente Correlato
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Informazioni sul paziente associato a questo reclamo.
                    </p>
                </div>
                <?= Html::a('Visualizza Dettagli', ['/patient/view', 'id' => $model->patient->id], [
                    'class' => 'inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded-lg hover:bg-blue-50'
                ]) ?>
            </div>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model->patient,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'label' => 'Nome Completo',
                        'value' => $model->patient->first_name . ' ' . $model->patient->last_name
                    ],
                    [
                        'attribute' => 'fiscal_code',
                        'label' => 'Codice Fiscale',
                        'value' => function ($model) {
                            return $model->fiscal_code ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'birth_date',
                        'label' => 'Data di Nascita',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'attribute' => 'phone_number',
                        'label' => 'Telefono',
                        'value' => function ($model) {
                            return $model->phone_number ?: '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Informazioni Account -->
    <?php if ($model->account): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Account che ha Effettuato il Reclamo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni sull'utente che ha inviato il reclamo.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?php
            $accountData = [
                [
                    'label' => 'Email',
                    'value' => $model->account->email
                ],
            ];
            
            if ($model->account->profile) {
                array_unshift($accountData, [
                    'label' => 'Nome Completo',
                    'value' => $model->account->profile->first_name . ' ' . $model->account->profile->last_name
                ]);
                
                if ($model->account->profile->phone) {
                    $accountData[] = [
                        'attribute' => 'phone',
                        'label' => 'Telefono',
                        'value' => $model->account->profile->phone
                    ];
                }
            }
            ?>
            <?= DetailView::widget([
                'model' => $model->account,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => $accountData,
            ]) ?>
        </div>
    </div>
    <?php endif; ?>
</div>
