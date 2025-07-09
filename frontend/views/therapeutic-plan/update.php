<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $therapyModel common\models\PlanTherapy */
/* @var $patients array */
/* @var $regimes array */
/* @var $treatmentTypes array */
/* @var $settings array */

$this->title = 'Modifica Piano Terapeutico #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => '#' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<div class="therapeutic-plan-update">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= Html::encode($this->title) ?></h1>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'therapyModel' => $therapyModel,
        'patients' => $patients,
        'regimes' => $regimes,
        'treatmentTypes' => $treatmentTypes,
        'settings' => $settings,
    ]) ?>
</div> 