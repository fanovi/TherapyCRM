<?php

use yii\helpers\Html;
use common\models\District;

/* @var $this yii\web\View */
/* @var $model District */

$this->title = 'Modifica Distretto: ' . $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Distretti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->getFullName(), 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>
<div class="districts-update">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= Html::encode($this->title) ?></h1>
        <div class="flex gap-2">
            <?= Html::a('Visualizza', ['view', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'
            ]) ?>
            <?= Html::a('Torna alla Lista', ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'
            ]) ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Modifica Informazioni</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Modifica i dati del distretto. I campi contrassegnati con * sono obbligatori.
            </p>
        </div>
        <div class="px-6 py-6">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>

    <!-- Avviso per pazienti associati -->
    <?php 
    $patientsCount = $model->getPatients()->count();
    if ($patientsCount > 0): 
    ?>
    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mt-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Attenzione - Pazienti Associati
                </h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    <p>
                        Questo distretto ha <strong><?= $patientsCount ?></strong> pazienti associati. 
                        Le modifiche al nome e al codice potrebbero influenzare i report e le statistiche esistenti.
                    </p>
                    <div class="mt-2">
                        <?= Html::a('Visualizza Pazienti Associati', ['/patients/index', 'district_id' => $model->id], [
                            'class' => 'font-medium text-yellow-800 dark:text-yellow-200 underline hover:text-yellow-900 dark:hover:text-yellow-100',
                            'target' => '_blank'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
