<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var common\models\Therapist $therapist */
/** @var array $specializations */

$this->title = 'Modifica Terapista: ' . $profile->last_name . ' ' . $profile->first_name;
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $profile->last_name . ' ' . $profile->first_name, 'url' => ['view', 'id' => $therapist->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<?= $this->render('_form', [
    'user' => $user,
    'profile' => $profile,
    'therapist' => $therapist,
    'specializations' => $specializations,
    'selectedSpecializationIds' => $selectedSpecializationIds ?? [],
    'primarySpecializationId' => $primarySpecializationId ?? null,
    'isUpdate' => true,
    'allPermissions' => $allPermissions ?? [],
    'rolePermissions' => $rolePermissions ?? [],
    'userDirectPermissions' => $userDirectPermissions ?? [],
    'categories' => $categories ?? [],
]) ?> 