<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Absence $model */
/** @var array $therapists */

$this->title = 'Nuova Assenza';
$this->params['breadcrumbs'][] = ['label' => 'Assenze', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_form', [
    'model' => $model,
    'therapists' => $therapists,
    'isUpdate' => false,
]) ?>