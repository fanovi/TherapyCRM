<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $therapyModel common\models\PlanTherapy */
/* @var $patients array */
/* @var $regimes array */
/* @var $treatmentTypes array */
/* @var $settings array */
/* @var $postedTherapies array */

$this->title = 'Crea Piano Terapeutico';
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="therapeutic-plan-create">
    <?= $this->render('_form', [
        'model' => $model,
        'therapyModel' => $therapyModel,
        'patients' => $patients,
        'regimes' => $regimes,
        'treatmentTypes' => $treatmentTypes,
        'settings' => $settings,
        'postedTherapies' => $postedTherapies ?? [], // Aggiungi questa linea
    ]) ?>
</div> 