<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */

$this->title = 'Nuovo Manager';
$this->params['breadcrumbs'][] = ['label' => 'Manager', 'url' => ['managers']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_form', [
    'user' => $user,
    'profile' => $profile,
    'isUpdate' => false,
]) ?>
