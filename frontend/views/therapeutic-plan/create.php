<?php

use yii\helpers\Html;
use common\widgets\Alert;

/* @var $this yii\web\View */
/* @var $model common\models\TherapeuticPlan */
/* @var $therapyModel common\models\PlanTherapy */
/* @var $patients array */
/* @var $regimes array */
/* @var $districts array */
/* @var $treatmentTypes array */
/* @var $settings array */
/* @var $postedTherapies array */

$this->title = 'Crea Piano Terapeutico';
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mx-auto max-w-4xl p-4 md:p-6 therapeutic-plan-create">
    <?= Alert::widget() ?>
    <?= $this->render('_form', [
        'model' => $model,
        'therapyModel' => $therapyModel,
        'patients' => $patients,
        'regimes' => $regimes,
        'districts' => $districts,
        'treatmentTypes' => $treatmentTypes,
        'settings' => $settings,
        'postedTherapies' => $postedTherapies ?? [], // Aggiungi questa linea
    ]) ?>
</div> 