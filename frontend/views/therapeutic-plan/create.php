<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $patients array */
/* @var $regimes array */

$this->title = 'Crea Piano Terapeutico';
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="therapeutic-plan-create">
    <!-- Header Section -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= Html::encode($this->title) ?></h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Crea un nuovo piano terapeutico per un paziente
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <?= Html::a(
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Torna alla Lista',
                    ['index'],
                    [
                        'class' => 'inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2'
                    ]
                ) ?>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <?= $this->render('_form', [
                'model' => $model,
                'patients' => $patients,
                'regimes' => $regimes,
            ]) ?>
        </div>
    </div>
</div> 