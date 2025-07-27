<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $appointmentCount int */

$this->title = 'Conferma Eliminazione';
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-2xl p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            <?= Html::encode($this->title) ?>
        </h2>
    </div>

    <!-- Warning Card -->
    <div class="rounded-2xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
        <div class="px-6 py-6">
            <!-- Warning Icon -->
            <div class="flex items-center mb-4">
                <svg class="w-8 h-8 text-red-600 dark:text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <h3 class="text-lg font-medium text-red-800 dark:text-red-200">
                    Attenzione: Azione Irreversibile
                </h3>
            </div>

            <!-- Warning Message -->
            <div class="text-red-700 dark:text-red-300 mb-6">
                <p class="mb-3">
                    Stai per eliminare il piano terapeutico <strong><?= Html::encode($model->patient ? $model->patient->fullName : 'N/A') ?></strong>.
                </p>
                <p class="mb-3">
                    <strong>Questo piano terapeutico ha <?= $appointmentCount ?> appuntament<?= $appointmentCount === 1 ? 'o' : 'i' ?> collegat<?= $appointmentCount === 1 ? 'o' : 'i' ?>.</strong>
                </p>
                <p class="mb-3">
                    L'eliminazione comporterà:
                </p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li>Eliminazione definitiva del piano terapeutico</li>
                    <li>Eliminazione di tutti i <?= $appointmentCount ?> appuntament<?= $appointmentCount === 1 ? 'o' : 'i' ?> collegat<?= $appointmentCount === 1 ? 'o' : 'i' ?></li>
                    <li><strong>Questa azione NON può essere annullata</strong></li>
                </ul>
            </div>

            <!-- Plan Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-6 border border-red-200 dark:border-red-700">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">Dettagli Piano Terapeutico:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">ID:</span>
                        <span class="ml-2 text-gray-900 dark:text-white"><?= Html::encode($model->id) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Paziente:</span>
                        <span class="ml-2 text-gray-900 dark:text-white"><?= Html::encode($model->patient ? $model->patient->fullName : 'N/A') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Regime:</span>
                        <span class="ml-2 text-gray-900 dark:text-white"><?= Html::encode($model->regime ? $model->regime->nome : 'N/A') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Data Inizio:</span>
                        <span class="ml-2 text-gray-900 dark:text-white"><?= $model->start_date ? Yii::$app->formatter->asDate($model->start_date) : 'N/A' ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                <?php $form = ActiveForm::begin([
                    'action' => ['delete', 'id' => $model->id],
                    'method' => 'post'
                ]); ?>
                
                <!-- Hidden field for confirmation -->
                <?= Html::hiddenInput('confirm_delete', 'yes') ?>
                
                <!-- Delete Button -->
                <?= Html::submitButton(
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>Conferma Eliminazione',
                    [
                        'class' => 'inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-error-500 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                    ]
                ) ?>
                
                <?php ActiveForm::end(); ?>
                
                <!-- Cancel Button -->
                <?= Html::a(
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>Annulla',
                    ['index'],
                    [
                        'class' => 'inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]
                ) ?>
            </div>
        </div>
    </div>
</div>