<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Patient */

$this->title = $model->first_name . ' ' . $model->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
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
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <?php if (Yii::$app->user->can('update_patient')): ?>
        <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Modifica', 
            ['update', 'id' => $model->id], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
        ]) ?>
        <?php endif; ?>
        
        <?php if (Yii::$app->user->can('create_patient')): ?>
        <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg> Crea Credenziali', 
            ['create-credentials', 'id' => $model->id], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700'
        ]) ?>
        <?php endif; ?>
        
        <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> Torna alla Lista', 
            ['index'], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-700'
        ]) ?>
    </div>

    <!-- Patient Details Card -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dettagli Paziente
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni complete del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->id) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->first_name) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cognome</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->last_name) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Codice Fiscale</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->fiscal_code) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data di Nascita</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->birth_date) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Distretto</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->district ? Html::encode($model->district->name) : '-' ?>
                    </dd>
                </div>
                
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Note</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->notes ? Html::encode($model->notes) : 'Nessuna nota disponibile' ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creato il</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= Yii::$app->formatter->asDatetime($model->created_at) ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aggiornato il</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= Yii::$app->formatter->asDatetime($model->updated_at) ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div> 