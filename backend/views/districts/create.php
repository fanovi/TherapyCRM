<?php

use yii\helpers\Html;
use common\models\District;

/* @var $this yii\web\View */
/* @var $model District */

$this->title = 'Nuovo Distretto';
$this->params['breadcrumbs'][] = ['label' => 'Distretti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="districts-create">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('Torna alla Lista', ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'
            ]) ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informazioni Distretto</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Inserisci i dati del nuovo distretto. I campi contrassegnati con * sono obbligatori.
            </p>
        </div>
        <div class="px-6 py-6">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>

    <!-- Informazioni aggiuntive -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mt-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Informazioni sui Distretti
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Il <strong>codice</strong> deve essere univoco e contenere solo lettere maiuscole e numeri</li>
                        <li>Il <strong>nome</strong> identifica il distretto in modo descrittivo</li>
                        <li>Il <strong>riferimento ASL</strong> è opzionale e indica l'ASL di competenza</li>
                        <li>Una volta creato, il distretto potrà essere associato ai pazienti</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
