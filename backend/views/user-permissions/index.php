<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use common\models\AuthAssignment;
use common\models\AuthItem;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $search string */

$this->title = 'Permessi Utente';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-permissions-index">

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6"><?= Html::encode($this->title) ?></h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <?= Html::beginForm(['index'], 'get', ['class' => 'flex gap-4 items-end']) ?>
        <div class="flex-1">
            <?= Html::textInput('search', $search, [
                'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                'placeholder' => 'Cerca per nome, cognome o email...'
            ]) ?>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::submitButton('Cerca', ['class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>
            <?= Html::a('Reset', ['index'], ['class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ruolo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permessi Extra</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($dataProvider->getModels() as $user): ?>
                    <?php
                    $roleAssignments = AuthAssignment::find()
                        ->joinWith('item')
                        ->where(['user_id' => (string)$user->id])
                        ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_ROLE])
                        ->all();
                    $roles = array_map(function ($a) { return $a->item_name; }, $roleAssignments);

                    $extraCount = AuthAssignment::find()
                        ->joinWith('item')
                        ->where(['user_id' => (string)$user->id])
                        ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
                        ->count();
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?= Html::encode($user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            <?= Html::encode($user->email) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <?php foreach ($roles as $role): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <?= Html::encode($role) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($roles)): ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <?php if ($extraCount > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <?= $extraCount ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?= Html::a('Gestisci', ['view', 'id' => $user->id], [
                                'class' => 'inline-flex items-center px-3 py-1.5 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <?= LinkPager::widget([
            'pagination' => $dataProvider->pagination,
            'options' => ['class' => 'flex justify-center space-x-1'],
            'linkOptions' => ['class' => 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'],
            'activePageCssClass' => 'bg-blue-600 text-white border-blue-600',
            'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
        ]) ?>
    </div>

</div>
