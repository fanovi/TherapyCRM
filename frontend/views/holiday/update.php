<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Holiday $model */
/** @var common\models\Appointment[] $conflictAppointments */

$this->title = 'Modifica: ' . $model->getOldAttribute('name');
$this->params['breadcrumbs'][] = ['label' => 'Giorni Festivi', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: <?= Html::encode(\yii\helpers\Json::encode($this->title)) ?> }">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <?= $this->render('_form', [
        'model' => $model,
        'conflictAppointments' => $conflictAppointments,
    ]) ?>
</div>
