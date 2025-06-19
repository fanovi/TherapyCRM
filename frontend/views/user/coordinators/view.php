<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = $model->profile ? $model->profile->nome . ' ' . $model->profile->cognome : $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Coordinatori', 'url' => ['coordinators']];
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/coordinators']) ?>">
                            Coordinatori
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
        <?php if (Yii::$app->user->can('update_coordinator')): ?>
        <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Modifica', 
            ['update-coordinator', 'id' => $model->id], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
        ]) ?>
        <?php endif; ?>
        
        <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> Torna alla Lista', 
            ['coordinators'], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-700'
        ]) ?>
    </div>

    <!-- Coordinator Details Card -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dettagli Coordinatore
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni complete del coordinatore.
            </p>
        </div>
        
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->id) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Username</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->username) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->email) ?></dd>
                </div>
                
                <?php if ($model->profile): ?>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->profile->nome) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cognome</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($model->profile->cognome) ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefono</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->profile->telefono ? Html::encode($model->profile->telefono) : '-' ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data di Nascita</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->profile->data_nascita ? Html::encode($model->profile->data_nascita) : '-' ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sesso</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->profile->sesso ? ($model->profile->sesso == 'M' ? 'Maschio' : 'Femmina') : '-' ?>
                    </dd>
                </div>
                
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Indirizzo</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->profile->indirizzo ? Html::encode($model->profile->indirizzo) : 'Nessun indirizzo disponibile' ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Città</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->profile->citta ? Html::encode($model->profile->citta) : '-' ?>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">CAP</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white/90">
                        <?= $model->profile->cap ? Html::encode($model->profile->cap) : '-' ?>
                    </dd>
                </div>
                <?php endif; ?>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stato</dt>
                    <dd class="mt-1">
                        <?php if ($model->status == 10): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900">Attivo</span>
                        <?php else: ?>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900">Inattivo</span>
                        <?php endif; ?>
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