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

$this->title = 'Modifica Piano Terapeutico #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Piani Terapeutici', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => '#' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<div class="therapeutic-plan-update">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= Html::encode($this->title) ?></h1>
    </div>

    <div id="form-alert-container">
        <?= Alert::widget() ?>
    </div>
    <?php
    $this->registerJs(<<<JS
        (function() {
            var container = document.getElementById('form-alert-container');
            if (!container) return;
            var alertEl = container.querySelector('[role="alert"]');
            if (alertEl) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                alertEl.classList.add('ring-2', 'ring-red-400');
                setTimeout(function() {
                    alertEl.classList.remove('ring-2', 'ring-red-400');
                }, 2500);
            }
            var btn = document.getElementById('submit-btn');
            if (btn) {
                btn.disabled = false;
            }
        })();
JS);
    ?>

    <?= $this->render('_form', [
        'model' => $model,
        'therapyModel' => $therapyModel,
        'patients' => $patients,
        'regimes' => $regimes,
        'districts' => $districts,
        'treatmentTypes' => $treatmentTypes,
        'settings' => $settings,
        'postedTherapies' => $postedTherapies, // Aggiungi questa riga!
    ]) ?>
</div>