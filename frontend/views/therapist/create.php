<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var common\models\Therapist $therapist */
/** @var array $specializations */

$this->title = 'Nuovo Terapista';
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_form', [
    'user' => $user,
    'profile' => $profile,
    'therapist' => $therapist,
    'specializations' => $specializations,
    'selectedSpecializationIds' => $selectedSpecializationIds ?? [],
    'primarySpecializationId' => $primarySpecializationId ?? null,
    'isUpdate' => false,
    'allPermissions' => $allPermissions ?? [],
    'rolePermissions' => $rolePermissions ?? [],
    'userDirectPermissions' => $userDirectPermissions ?? [],
    'categories' => $categories ?? [],
]) ?>