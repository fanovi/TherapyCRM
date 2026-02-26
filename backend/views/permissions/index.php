<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use common\models\AuthItemChild;
use common\models\AuthAssignment;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $search string */

$this->title = 'Gestione Permessi';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="permissions-index">

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6"><?= Html::encode($this->title) ?></h1>

    <div class="flex flex-wrap gap-4 mb-6">
        <div class="flex-1">
            <?= Html::a('Nuovo Permesso', ['create'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
            ]) ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <?= Html::beginForm(['index'], 'get', ['class' => 'flex gap-4 items-end']) ?>
        <div class="flex-1">
            <?= Html::textInput('search', $search, [
                'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                'placeholder' => 'Cerca per nome o descrizione...'
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrizione</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ruoli</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Utenti Diretti</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($dataProvider->getModels() as $permission): ?>
                    <?php
                    $roleCount = AuthItemChild::find()->where(['child' => $permission->name])->count();
                    $userCount = AuthAssignment::find()->where(['item_name' => $permission->name])->count();
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?= Html::encode($permission->name) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                            <?= Html::encode($permission->description ?: '-') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                <?= $roleCount ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <?php if ($userCount > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <?= $userCount ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?= Html::a('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', ['update', 'name' => $permission->name], [
                                'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3 inline-block',
                                'title' => 'Modifica',
                            ]) ?>
                            <?php
                            $deleteConfirm = 'Sei sicuro di voler eliminare questo permesso?';
                            if ($roleCount > 0 || $userCount > 0) {
                                $deleteConfirm = "ATTENZIONE: Questo permesso è assegnato a {$roleCount} ruoli e {$userCount} utenti. Eliminandolo verrà rimosso da tutti. Continuare?";
                            }
                            ?>
                            <?= Html::a('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', ['delete', 'name' => $permission->name], [
                                'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-block',
                                'title' => 'Elimina',
                                'data' => [
                                    'confirm' => $deleteConfirm,
                                    'method' => 'post',
                                ],
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
