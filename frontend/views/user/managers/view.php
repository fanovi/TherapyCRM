<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\User $model */

$this->title = $model->profile->first_name . ' ' . $model->profile->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Manager', 'url' => ['managers']];
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/managers']) ?>">
                            Manager
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
                ['managers'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <?php if (Yii::$app->user->can('update_manager')): ?>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Modifica Manager', 
                    ['update-manager', 'id' => $model->id], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
            <?php endif; ?>
            
            <?php if (Yii::$app->user->can('delete_manager')): ?>
                <?php if ($model->status == 'active'): ?>
                    <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Disattiva', 
                        ['toggle-status-manager', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-error-500 border border-transparent rounded-lg hover:bg-error-500 focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2',
                        'data' => [
                            'confirm' => 'Sei sicuro di voler disattivare questo manager? Potrà essere riattivato in seguito.',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php else: ?>
                    <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Attiva', 
                        ['toggle-status-manager', 'id' => $model->id], [
                        'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-success-500 border border-transparent rounded-lg hover:bg-success-500 focus:outline-none focus:ring-2 focus:ring-success-500 focus:ring-offset-2',
                        'data' => [
                            'confirm' => 'Sei sicuro di voler attivare questo manager?',
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
            <?php if (Yii::$app->user->can('update_manager')): ?>
                <?= Html::a('<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                    ['update-manager', 'id' => $model->id], [
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
                        'attribute' => 'profile.first_name',
                        'label' => 'Nome',
                    ],
                    [
                        'attribute' => 'profile.last_name',
                        'label' => 'Cognome',
                    ],
                    [
                        'attribute' => 'email',
                        'label' => 'Email',
                    ],
                    [
                        'attribute' => 'profile.phone',
                        'label' => 'Telefono',
                    ],
                    [
                        'attribute' => 'profile.fiscal_code',
                        'label' => 'Codice Fiscale',
                    ],
                    [
                        'attribute' => 'profile.address',
                        'label' => 'Indirizzo',
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Informazioni Account -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-between">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Account
            </h3>
            <?php if (Yii::$app->user->can('update_manager')): ?>
                <?= Html::a('<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                    ['update-manager', 'id' => $model->id], [
                    'class' => 'inline-flex items-center p-1.5 text-gray-400 hover:text-brand-600 hover:bg-gray-100 rounded-lg transition-colors',
                    'title' => 'Modifica informazioni account'
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
                        'attribute' => 'username',
                        'label' => 'Username',
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'format' => 'raw',
                        'value' => function($model) {
                            $statusClass = $model->status == 'active' ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900' : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900';
                            $statusText = $model->status == 'active' ? 'Attivo' : 'Inattivo';
                            return '<span class="inline-flex px-3 py-1 text-sm font-medium rounded-full ' . $statusClass . '">' . $statusText . '</span>';
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Creato il',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'label' => 'Aggiornato il',
                        'format' => ['datetime', 'php:d/m/Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
