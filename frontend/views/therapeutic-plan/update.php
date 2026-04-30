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

    <?php
    $errorFlashes = [];
    foreach (['error', 'danger'] as $key) {
        if (Yii::$app->session->hasFlash($key)) {
            $val = Yii::$app->session->getFlash($key, null, true);
            foreach ((array) $val as $msg) {
                $errorFlashes[] = $msg;
            }
        }
    }
    ?>
    <?= Alert::widget() ?>
    <?php if (!empty($errorFlashes)): ?>
        <?php
        $errorJson = \yii\helpers\Json::htmlEncode($errorFlashes);
        $this->registerJs(<<<JS
            (function() {
                var errors = {$errorJson};
                if (!errors || !errors.length) return;
                var html = errors.length === 1
                    ? errors[0]
                    : '<ul style="text-align:left;margin:0;padding-left:1.2em;">' + errors.map(function(e){ return '<li>' + e + '</li>'; }).join('') + '</ul>';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore di validazione',
                        html: html,
                        confirmButtonText: 'Ho capito',
                        confirmButtonColor: '#dc2626'
                    });
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    alert(errors.join('\\n'));
                }
                var btn = document.getElementById('submit-btn');
                if (btn) btn.disabled = false;
            })();
JS);
        ?>
    <?php endif; ?>

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