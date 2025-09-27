<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $patient common\models\Patient */
/* @var $districts array */

$this->title = 'Nuovo Paziente';
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_form', [
    'patient' => $patient,
    'districts' => $districts,
    'province' => $province ?? [],
    'isUpdate' => false,
]) ?> 