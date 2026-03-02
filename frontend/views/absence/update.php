<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Absence $model */
/** @var array $therapists */

$therapistName = $model->therapist && $model->therapist->user && $model->therapist->user->profile 
    ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
    : 'Terapista';

$this->title = 'Modifica Assenza: ' . $therapistName;
$this->params['breadcrumbs'][] = ['label' => 'Assenze', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $therapistName, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<?= $this->render('_form', [
    'model' => $model,
    'therapists' => $therapists,
    'isUpdate' => true,
]) ?>