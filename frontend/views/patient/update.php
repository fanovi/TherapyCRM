<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $patient common\models\Patient */
/* @var $districts array */

$this->title = 'Modifica Paziente: ' . $patient->fullName;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $patient->fullName, 'url' => ['view', 'id' => $patient->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<?= $this->render('_form', [
    'patient' => $patient,
    'districts' => $districts,
    'province' => $province ?? [],
    'isUpdate' => true,
]) ?> 