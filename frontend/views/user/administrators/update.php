<?php

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */

$this->title = 'Modifica Amministratore: ' . ($user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username);
$this->params['breadcrumbs'][] = ['label' => 'Amministratori', 'url' => ['administrators']];
$this->params['breadcrumbs'][] = ['label' => ($user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username), 'url' => ['view-administrator', 'id' => $user->id]];
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