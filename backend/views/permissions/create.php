<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\AuthItem */

$this->title = 'Nuovo Permesso';
$this->params['breadcrumbs'][] = ['label' => 'Gestione Permessi', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="permission-create">

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6"><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
