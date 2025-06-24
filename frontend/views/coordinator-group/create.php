<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\CoordinatorGroup $model */
/** @var array $coordinators */

$this->title = 'Nuovo Gruppo Coordinatore';
$this->params['breadcrumbs'][] = ['label' => 'Gruppi Coordinatori', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Crea Nuovo Gruppo Coordinatore
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci i dettagli per creare un nuovo gruppo di terapisti.
            </p>
        </div>
        
        <div class="px-5 py-6 sm:px-6 sm:py-8">
            <?= $this->render('_form', [
                'model' => $model,
                'coordinators' => $coordinators,
            ]) ?>
        </div>
    </div>
    <!-- Content End -->
</div> 