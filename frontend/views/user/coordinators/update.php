<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $profile common\models\UserProfile */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Modifica Coordinatore: ' . ($user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username);
$this->params['breadcrumbs'][] = ['label' => 'Coordinatori', 'url' => ['coordinators']];
$this->params['breadcrumbs'][] = ['label' => ($user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username), 'url' => ['view-coordinator', 'id' => $user->id]];
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