<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */

$this->title = 'Modifica Manager: ' . $profile->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Manager', 'url' => ['managers']];
$this->params['breadcrumbs'][] = ['label' => $profile->getFullName(), 'url' => ['view-manager', 'id' => $user->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<?= $this->render('_form', [
    'user' => $user,
    'profile' => $profile,
    'isUpdate' => true,
    'allPermissions' => $allPermissions ?? [],
    'rolePermissions' => $rolePermissions ?? [],
    'userDirectPermissions' => $userDirectPermissions ?? [],
    'categories' => $categories ?? [],
]) ?>
