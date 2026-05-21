<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use frontend\widgets\AlertBanner;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */

$this->title = 'Piano Terapeutico #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
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
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/therapeutic-plan/index']) ?>">
                            Piani Terapeutici
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
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
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <!-- Torna alla Lista -->
            <?= Html::a(
                '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Torna alla Lista',
                ['index'],
                [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]
            ) ?>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <?php if (Yii::$app->user->can('update_therapeutic_plan') && $model->status !== 'terminated'): ?>
                <?= Html::a(
                    '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Modifica Piano',
                    ['update', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                    ]
                ) ?>
            <?php endif; ?>

            <?php if (Yii::$app->user->can('delete_therapeutic_plan')): ?>
                <?= Html::a(
                    '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>Elimina',
                    ['delete', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-error-500 border border-transparent rounded-lg hover:bg-error-500 focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2',
                        'data' => [
                            'confirm' => 'Sei sicuro di voler eliminare questo piano terapeutico?',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Alert -->
    <?php if ($model->status === 'suspended'): ?>
        <?php
        $msg = 'Questo piano è stato sospeso dal ' . Yii::$app->formatter->asDate($model->suspension_date) . '.';
        if ($model->suspension_reason) {
            $msg .= '<br>Motivo: ' . Html::encode($model->suspension_reason);
        }
        echo AlertBanner::widget([
            'variant' => 'warning',
            'title' => 'Piano Terapeutico Sospeso',
            'message' => $msg,
            'rawMessage' => true,
        ]);
        ?>
    <?php elseif ($model->status === 'expired'): ?>
        <?= AlertBanner::widget([
            'variant' => 'danger',
            'title' => 'Piano Terapeutico Scaduto',
            'message' => 'Questo piano terapeutico è scaduto il ' . Yii::$app->formatter->asDate($model->end_date) . '.',
        ]) ?>
    <?php elseif ($model->status === 'terminated'): ?>
        <?php
        $msg = 'Questo piano è stato interrotto'
            . ($model->termination_date ? ' il ' . Yii::$app->formatter->asDate($model->termination_date) : '')
            . '.';
        if ($model->termination_reason) {
            $msg .= '<br>Motivo: ' . Html::encode($model->termination_reason);
        }
        echo AlertBanner::widget([
            'variant' => 'danger',
            'title' => 'Piano Terapeutico Interrotto',
            'message' => $msg,
            'rawMessage' => true,
        ]);
        ?>
    <?php elseif ($model->status === 'active'): ?>
        <?= AlertBanner::widget([
            'variant' => 'success',
            'title' => 'Piano Terapeutico Attivo',
            'message' => 'Questo piano terapeutico è attivo' . ($model->end_date ? ' fino al ' . Yii::$app->formatter->asDate($model->end_date) : '') . '.',
        ]) ?>
    <?php elseif ($model->status === 'pending'): ?>
        <?= AlertBanner::widget([
            'variant' => 'info',
            'title' => 'Piano Terapeutico In Attesa',
            'message' => 'Il piano inizierà il ' . Yii::$app->formatter->asDate($model->start_date) . '.',
        ]) ?>
    <?php endif; ?>

    <!-- Dati Piano Terapeutico -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-between">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Piano Terapeutico
            </h3>
            <?php if (Yii::$app->user->can('update_therapeutic_plan') && $model->status !== 'terminated'): ?>
                <?= Html::a(
                    '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                    ['update', 'id' => $model->id],
                    [
                        'class' => 'inline-flex items-center p-1.5 text-gray-400 hover:text-brand-600 hover:bg-gray-100 rounded-lg transition-colors',
                        'title' => 'Modifica piano terapeutico'
                    ]
                ) ?>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table-auto w-full text-sm'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800"><th class="px-5 py-4 text-left font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 w-1/4">{label}</th><td class="px-5 py-4 text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    // 'id',
                    [
                        'attribute' => 'district_id',
                        'label' => 'Distretto',
                        'value' => function ($model) {
                            return $model->district ? $model->district->dropdownLabel : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'value' => function ($model) {
                            return $model->getStatusLabel();
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'patient_id',
                        'label' => 'Paziente',
                        'value' => function ($model) {
                            if ($model->patient) {
                                $patientName = Html::encode($model->patient->fullName);
                                $fiscalCode = Html::encode($model->patient->fiscal_code);
                                $patientLink = Html::a("$patientName ($fiscalCode)", ['/patient/view', 'id' => $model->patient->id], [
                                    'class' => 'text-brand-600 hover:text-brand-800 font-medium transition-colors duration-200',
                                    'title' => 'Visualizza dettagli paziente'
                                ]);
                                return $patientLink;
                            }
                            return 'N/A';
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'regime_id',
                        'label' => 'Regime',
                        'value' => function ($model) {
                            return $model->regime ? $model->regime->nome : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'approval_date',
                        'label' => 'Data Approvazione',
                        'value' => function ($model) {
                            return $model->approval_date ? Yii::$app->formatter->asDate($model->approval_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'protocol_number',
                        'label' => 'Numero Protocollo',
                        'value' => function ($model) {
                            return $model->protocol_number ? Html::encode($model->protocol_number) : 'N/A';
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'value' => function ($model) {
                            return $model->start_date ? Yii::$app->formatter->asDate($model->start_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'duration_days',
                        'label' => 'Durata',
                        'value' => function ($model) {
                            return $model->getFormattedDuration();
                        }
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'value' => function ($model) {
                            return $model->end_date ? Yii::$app->formatter->asDate($model->end_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'suspension_date',
                        'label' => 'Data Sospensione',
                        'visible' => $model->status === 'suspended',
                        'value' => function ($model) {
                            return $model->suspension_date ? Yii::$app->formatter->asDate($model->suspension_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'suspension_reason',
                        'label' => 'Motivo Sospensione',
                        'visible' => $model->status === 'suspended',
                        'value' => function ($model) {
                            return $model->suspension_reason ? nl2br(Html::encode($model->suspension_reason)) : 'N/A';
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'termination_date',
                        'label' => 'Data Interruzione',
                        'visible' => $model->status === 'terminated',
                        'value' => function ($model) {
                            return $model->termination_date ? Yii::$app->formatter->asDate($model->termination_date) : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'termination_reason',
                        'label' => 'Motivo Interruzione',
                        'visible' => $model->status === 'terminated',
                        'value' => function ($model) {
                            return $model->termination_reason ? nl2br(Html::encode($model->termination_reason)) : 'N/A';
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'notes',
                        'label' => 'Note',
                        'value' => function ($model) {
                            return $model->notes ? nl2br(Html::encode($model->notes)) : 'Nessuna nota';
                        },
                        'format' => 'raw'
                    ],
                    [
                        'attribute' => 'created_by',
                        'label' => 'Creato da',
                        'value' => function ($model) {
                            return $model->createdBy ? $model->createdBy->username : 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Data Creazione',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->created_at);
                        }
                    ],
                    [
                        'attribute' => 'updated_at',
                        'label' => 'Ultimo Aggiornamento',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asDatetime($model->updated_at);
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Terapie Assegnate -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Terapie Assegnate
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Elenco delle terapie associate a questo piano.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 p-5">
            <?php if ($model->planTherapies): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($model->planTherapies as $therapy): ?>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-base font-medium text-gray-900 dark:text-white/90">
                                        <?= Html::encode($therapy->treatmentType->name) ?>
                                        <?php if ($therapy->is_group): ?>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                Gruppo
                                            </span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Ore <?= $model->regime->nome !== 'ABA' ? 'Settimanali' : 'Mensili' ?>:</span> <?= Html::encode($therapy->weekly_hours) ?>
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Setting:</span> <?= Html::encode($therapy->setting->nome) ?>
                                        </p>
                                        <?php if ($therapy->notes): ?>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                <span class="font-medium">Note:</span><br>
                                                <?= nl2br(Html::encode($therapy->notes)) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessuna terapia assegnata a questo piano.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informazioni Aggiuntive -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Aggiuntive
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dettagli del paziente e del regime associato.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Patient Info -->
                <?php if ($model->patient): ?>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white/90 mb-3">Informazioni Paziente</h4>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Nome:</span>
                                <?= Html::a(Html::encode($model->patient->fullName), ['/patient/view', 'id' => $model->patient->id], [
                                    'class' => 'text-brand-600 hover:text-brand-800 font-medium transition-colors duration-200 ml-1',
                                    'title' => 'Visualizza dettagli paziente'
                                ]) ?>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Codice Fiscale:</span> <?= Html::encode($model->patient->fiscal_code) ?>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Data di Nascita:</span>
                                <?= $model->patient->birth_date ? Yii::$app->formatter->asDate($model->patient->birth_date) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Regime Info -->
                <?php if ($model->regime): ?>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white/90 mb-3">Informazioni Regime</h4>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Nome:</span> <?= Html::encode($model->regime->nome) ?>
                            </p>
                            <?php if ($model->regime->descrizione): ?>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Descrizione:</span> <?= Html::encode($model->regime->descrizione) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>