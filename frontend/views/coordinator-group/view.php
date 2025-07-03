<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\CoordinatorGroup $model */
/** @var common\models\Therapist[] $therapists */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Gruppi Coordinatori', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <!-- Action Buttons -->
            <div class="flex gap-2">
                <?php if (Yii::$app->user->can('update_coordinator_group')): ?>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Modifica', 
                    ['update', 'id' => $model->id], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]) ?>
                <?php endif; ?>
                
                <?php if (Yii::$app->user->can('delete_coordinator_group')): ?>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Elimina', 
                    ['delete', 'id' => $model->id], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                    'onclick' => 'return confirm("Sei sicuro di voler eliminare questo gruppo coordinatore?")'
                ]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="space-y-6">
        <!-- Group Details -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Dettagli Gruppo
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Informazioni generali del gruppo coordinatore.
                </p>
            </div>
            
            <div class="px-5 py-6 sm:px-6 sm:py-8">
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => ['class' => 'space-y-4'],
                    'template' => '<div class="flex flex-col sm:flex-row sm:justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1 sm:mb-0 sm:w-1/3">{label}</dt><dd class="text-sm text-gray-900 dark:text-white sm:w-2/3">{value}</dd></div>',
                    'attributes' => [
                        'name:text:Nome Gruppo',
                        [
                            'label' => 'Coordinatore',
                            'value' => function($model) {
                                if ($model->coordinator && $model->coordinator->profile) {
                                    return $model->coordinator->profile->first_name . ' ' . $model->coordinator->profile->last_name . ' (' . $model->coordinator->email . ')';
                                }
                                return $model->coordinator ? $model->coordinator->email : 'N/D';
                            }
                        ],
                        [
                            'label' => 'Numero Terapisti',
                            'value' => function($model) {
                                $count = $model->getTherapistCount();
                                return $count . ' terapist' . ($count == 1 ? 'a' : 'i') . ' assegnat' . ($count == 1 ? 'a' : 'i');
                            }
                        ],

                        [
                            'label' => 'Creato il',
                            'value' => function($model) {
                                return $model->created_at ? date('d/m/Y \a\l\l\e H:i', strtotime($model->created_at)) : 'N/D';
                            }
                        ],
                        [
                            'label' => 'Aggiornato il',
                            'value' => function($model) {
                                return $model->updated_at ? date('d/m/Y \a\l\l\e H:i', strtotime($model->updated_at)) : 'N/D';
                            }
                        ],
                    ],
                ]) ?>
            </div>
        </div>
        
        <!-- Therapists List -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-200 dark:border-gray-800">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                            Terapisti Assegnati
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Lista dei terapisti che fanno parte di questo gruppo.
                        </p>
                    </div>

                </div>
            </div>
            
            <div class="px-5 py-6 sm:px-6 sm:py-8">
                <?php if (empty($therapists)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nessun terapista assegnato</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Inizia assegnando terapisti a questo gruppo.</p>
                        <?php if (Yii::$app->user->can('update_coordinator_group')): ?>
                        <div class="mt-6">
                            <?= Html::a('Modifica Gruppo', ['update', 'id' => $model->id], [
                                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2'
                            ]) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($therapists as $therapist): ?>
                            <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-gray-400 dark:border-gray-600 dark:bg-gray-800">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-full" style="background-color: <?= Html::encode($therapist->calendar_color ?: '#6B7280') ?>"></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?= Html::encode($therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name) ?>
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <?= Html::encode($therapist->specialization->name ?? 'N/D') ?>
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                            <?= Html::encode($therapist->user->email) ?>
                                        </p>
                                        
                                        <!-- Data di assegnazione -->
                                        <?php 
                                        $groupTherapist = $therapist->getGroupTherapists()
                                            ->where(['group_id' => $model->id])
                                            ->andWhere(['IS', 'assigned_to', null])
                                            ->one();
                                        ?>
                                        <?php if ($groupTherapist): ?>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Assegnato il: <?= date('d/m/Y', strtotime($groupTherapist->assigned_from)) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Content End -->
</div> 