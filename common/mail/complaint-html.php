<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Complaint $complaint */
/** @var common\models\Patient $patient */
/** @var common\models\User $account */

$complaint_link = Yii::$app->urlManager->createAbsoluteUrl(['complaint/view', 'id' => $complaint->id]);

?>
<div class="verify-email">
    <p>Salve,</p>

    <p>E' stato creato un nuovo reclamo dall'account <?= Html::encode($account->email) ?> per il paziente <?= Html::encode($patient->first_name) ?> <?= Html::encode($patient->last_name) ?> con il titolo <?= Html::encode($complaint->title) ?> e la descrizione <?= Html::encode($complaint->description) ?>.</p>

    <p>Per visualizzare il reclamo, clicca sul link seguente: <?= Html::a(Html::encode($complaint_link), $complaint_link) ?></p>

</div>
