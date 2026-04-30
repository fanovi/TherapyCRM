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
            // Reset eventuale loader del pulsante submit (in caso di back/forward cache)
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
        'postedTherapies' => $postedTherapies ?? [], // Aggiungi questa linea
    ]) ?>
</div> 