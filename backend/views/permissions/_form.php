<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\AuthItem */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="permission-form">

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'space-y-6'],
        ]); ?>

        <?php if ($model->isNewRecord): ?>
            <?= $form->field($model, 'name', [
                'options' => ['class' => ''],
                'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300'],
                'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
            ])->textInput([
                'maxlength' => true,
                'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                'placeholder' => 'es. manage_reports',
            ])->hint('Usa snake_case. Non potrà essere modificato dopo la creazione.', ['class' => 'mt-1 text-xs text-gray-500']) ?>
        <?php else: ?>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2"><?= Html::encode($model->name) ?></p>
            </div>
        <?php endif; ?>

        <?= $form->field($model, 'description', [
            'options' => ['class' => ''],
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300'],
            'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
        ])->textarea([
            'rows' => 3,
            'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
            'placeholder' => 'Descrizione del permesso...',
        ]) ?>

        <div class="flex items-center gap-4 pt-4">
            <?= Html::submitButton($model->isNewRecord ? 'Crea Permesso' : 'Salva Modifiche', [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
            ]) ?>
            <?= Html::a('Annulla', ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600',
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
