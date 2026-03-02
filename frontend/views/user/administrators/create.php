<?php

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */

$this->title = 'Nuovo Amministratore';
$this->params['breadcrumbs'][] = ['label' => 'Amministratori', 'url' => ['administrators']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_form', [
    'user' => $user,
    'profile' => $profile,
    'isUpdate' => false,
    'allPermissions' => $allPermissions ?? [],
    'rolePermissions' => $rolePermissions ?? [],
    'userDirectPermissions' => $userDirectPermissions ?? [],
    'categories' => $categories ?? [],
]) ?> 