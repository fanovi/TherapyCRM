<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use common\models\AuthAssignment;
use common\models\AuthItem;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $search string */
/* @var $roleFilter string|null */
/* @var $availableRoles array */
/* @var $currentSort string */

$this->title = 'Permessi Utente';

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Amministratore',
    'manager' => 'Manager',
    'coordinator' => 'Coordinatore',
];

$roleBadgeColors = [
    'super_admin' => 'bg-red-100 text-red-800',
    'admin' => 'bg-purple-100 text-purple-800',
    'manager' => 'bg-blue-100 text-blue-800',
    'coordinator' => 'bg-green-100 text-green-800',
];
?>
<div class="user-permissions-index">

    <h1 class="text-2xl font-semibold text-gray-900 mb-6"><?= Html::encode($this->title) ?></h1>

    <!-- Search & Filter -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <?= Html::beginForm(['index'], 'get', ['class' => 'flex flex-wrap gap-4 items-end']) ?>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Cerca</label>
            <?= Html::textInput('search', $search, [
                'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                'placeholder' => 'Nome, cognome o email...'
            ]) ?>
        </div>
        <div class="w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Ruolo</label>
            <?= Html::dropDownList('role', $roleFilter,
                array_merge(['' => 'Tutti i ruoli'], array_combine($availableRoles, array_map(function($r) use ($roleLabels) {
                    return $roleLabels[$r] ?? ucfirst($r);
                }, $availableRoles))),
                ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ) ?>
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-500 mb-1">Ordina per</label>
            <?= Html::dropDownList('sort', $currentSort, [
                'name' => 'Nome',
                'role' => 'Ruolo',
                'email' => 'Email',
            ], ['class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']) ?>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::submitButton('Filtra', ['class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700']) ?>
            <?= Html::a('Reset', ['index'], ['class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ruolo</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Permessi Extra</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
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
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-indigo-700">
                                        <?= strtoupper(substr($user->profile ? $user->profile->first_name : 'U', 0, 1)) ?><?= strtoupper(substr($user->profile ? $user->profile->last_name : '', 0, 1)) ?>
                                    </span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= Html::encode($user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username) ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= Html::encode($user->email) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php foreach ($roles as $role): ?>
                                <?php $badgeColor = $roleBadgeColors[$role] ?? 'bg-gray-100 text-gray-800'; ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeColor ?>">
                                    <?= Html::encode($roleLabels[$role] ?? ucfirst($role)) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($roles)): ?>
                                <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php if ($extraCount > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    <?= $extraCount ?> extra
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <?= Html::a('Gestisci', ['view', 'id' => $user->id], [
                                'class' => 'inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($dataProvider->getModels()) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            Nessun utente trovato.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <?= LinkPager::widget([
            'pagination' => $dataProvider->pagination,
        ]) ?>
    </div>

</div>
