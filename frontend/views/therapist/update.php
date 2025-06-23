<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var common\models\Therapist $therapist */
/** @var array $specializations */

$this->title = 'Modifica Terapista: ' . $profile->first_name . ' ' . $profile->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $profile->first_name . ' ' . $profile->last_name, 'url' => ['view', 'id' => $therapist->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<?= $this->render('_form', [
    'user' => $user,
    'profile' => $profile,
    'therapist' => $therapist,
    'specializations' => $specializations,
    'isUpdate' => true,
]) ?> 