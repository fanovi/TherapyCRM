<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Therapist $model */

$this->title = $model->user->profile->first_name . ' ' . $model->user->profile->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['index']];
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/therapist/index']) ?>">
                            Terapisti
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
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <!-- Torna alla Lista -->
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Torna alla Lista', 
                ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <?php if (Yii::$app->user->can('update_therapist')): ?>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Modifica Terapista', 
                    ['update', 'id' => $model->id], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            <?php endif; ?>
            
            <?php if (Yii::$app->user->can('delete_therapist')): ?>
                <?php if ($model->is_active): ?>
                    <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Disattiva', 
                        ['toggle-status', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-error-500 border border-transparent rounded-lg hover:bg-error-500 focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2',
                        'data' => [
                            'confirm' => 'Sei sicuro di voler disattivare questo terapista? Potrà essere riattivato in seguito.',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php else: ?>
                    <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Attiva', 
                        ['toggle-status', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-success-500 border border-transparent rounded-lg hover:bg-success-500 focus:outline-none focus:ring-2 focus:ring-success-500 focus:ring-offset-2',
                        'data' => [
                            'confirm' => 'Sei sicuro di voler attivare questo terapista?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dati Personali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-between">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <?php if (Yii::$app->user->can('update_therapist')): ?>
                <?= Html::a('<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                    ['update', 'id' => $model->id], [
                    'class' => 'inline-flex items-center p-1.5 text-gray-400 hover:text-brand-600 hover:bg-gray-100 rounded-lg transition-colors',
                    'title' => 'Modifica dati personali'
                ]) ?>
            <?php endif; ?>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table-auto w-full text-sm'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800"><th class="px-5 py-4 text-left font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 w-1/4">{label}</th><td class="px-5 py-4 text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'user.profile.first_name',
                        'label' => 'Nome',
                    ],
                    [
                        'attribute' => 'user.profile.last_name',
                        'label' => 'Cognome',
                    ],
                    [
                        'attribute' => 'user.email',
                        'label' => 'Email',
                    ],
                    [
                        'attribute' => 'user.profile.phone',
                        'label' => 'Telefono',
                    ],
                    [
                        'attribute' => 'user.profile.fiscal_code',
                        'label' => 'Codice Fiscale',
                    ],
                    [
                        'attribute' => 'user.profile.address',
                        'label' => 'Indirizzo',
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Dati Professionali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-between">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Professionali
            </h3>
            <?php if (Yii::$app->user->can('update_therapist')): ?>
                <?= Html::a('<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                    ['update', 'id' => $model->id], [
                    'class' => 'inline-flex items-center p-1.5 text-gray-400 hover:text-brand-600 hover:bg-gray-100 rounded-lg transition-colors',
                    'title' => 'Modifica dati professionali'
                ]) ?>
            <?php endif; ?>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'table-auto w-full text-sm'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800"><th class="px-5 py-4 text-left font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 w-1/4">{label}</th><td class="px-5 py-4 text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'specialization.name',
                        'label' => 'Specializzazione',
                    ],
                    [
                        'attribute' => 'weekly_hours_contract',
                        'label' => 'Ore Settimanali Contratto',
                    ],
                    [
                        'attribute' => 'calendar_color',
                        'label' => 'Colore Calendario',
                        'format' => 'raw',
                        'value' => function($model) {
                            $color = $model->calendar_color ?: '#6B7280';
                            return '<div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300" style="background-color: ' . Html::encode($color) . '"></div>
                                <span>' . Html::encode($color) . '</span>
                            </div>';
                        }
                    ],
                    [
                        'attribute' => 'is_internal',
                        'label' => 'Tipo Terapista',
                        'format' => 'raw',
                        'value' => function($model) {
                            $typeClass = $model->is_internal ? 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900' : 'bg-orange-100 text-orange-800 dark:bg-orange-200 dark:text-orange-900';
                            $typeText = $model->is_internal ? 'Terapista Interno' : 'Consulente Esterno';
                            return '<span class="inline-flex px-3 py-1 text-sm font-medium rounded-full ' . $typeClass . '">' . $typeText . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'is_active',
                        'label' => 'Stato',
                        'format' => 'raw',
                        'value' => function($model) {
                            $statusClass = $model->is_active ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900' : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900';
                            $statusText = $model->is_active ? 'Attivo' : 'Inattivo';
                            return '<span class="inline-flex px-3 py-1 text-sm font-medium rounded-full ' . $statusClass . '">' . $statusText . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Creato il',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div> 