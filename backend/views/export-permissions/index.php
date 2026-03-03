<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $roleNames array */
/* @var $permissions array */
/* @var $rolePermMap array */
/* @var $roleCounts array */
/* @var $userRows array */

$this->title = 'Esporta Permessi RBAC';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="export-permissions-index">

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6"><?= Html::encode($this->title) ?></h1>

    <!-- Pulsanti Download -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <a href="<?= Url::to(['roles']) ?>"
           class="flex items-center justify-center gap-3 px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <div class="text-left">
                <div class="font-semibold">Scarica Permessi per Ruolo</div>
                <div class="text-sm text-blue-200">Matrice permessi x ruoli (CSV)</div>
            </div>
        </a>

        <a href="<?= Url::to(['users']) ?>"
           class="flex items-center justify-center gap-3 px-6 py-4 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <div class="text-left">
                <div class="font-semibold">Scarica Permessi per Utente</div>
                <div class="text-sm text-green-200">Permessi effettivi per ogni utente (CSV)</div>
            </div>
        </a>
    </div>

    <!-- Riepilogo Ruoli -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Riepilogo Ruoli</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <?php foreach ($roleNames as $role): ?>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300"><?= Html::encode($role) ?></div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1"><?= $roleCounts[$role] ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">permessi</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Anteprima Matrice Permessi x Ruoli -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Matrice Permessi x Ruoli</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($permissions) ?> permessi, <?= count($roleNames) ?> ruoli</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">Permesso</th>
                        <?php foreach ($roleNames as $role): ?>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?= Html::encode($role) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($permissions as $perm): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800 z-10" title="<?= Html::encode($perm['description'] ?? '') ?>">
                                <?= Html::encode($perm['name']) ?>
                            </td>
                            <?php foreach ($roleNames as $role): ?>
                                <td class="px-3 py-2 text-center">
                                    <?php if (isset($rolePermMap[$role][$perm['name']])): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-bold">&#10003;</span>
                                    <?php else: ?>
                                        <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-gray-100 sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">TOTALE</td>
                        <?php foreach ($roleNames as $role): ?>
                            <td class="px-3 py-3 text-center text-sm font-bold text-gray-900 dark:text-gray-100"><?= $roleCounts[$role] ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Anteprima Utenti -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Riepilogo Permessi per Utente</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($userRows) ?> utenti attivi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Utente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ruoli</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Da Ruolo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Diretti</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Totale</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($userRows as $uRow): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400"><?= $uRow['id'] ?></td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100"><?= Html::encode($uRow['lastName'] . ' ' . $uRow['firstName']) ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?= Html::encode($uRow['email']) ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <?php foreach ($uRow['roles'] as $r): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 mr-1 mb-1"><?= Html::encode($r) ?></span>
                                <?php endforeach; ?>
                                <?php if (empty($uRow['roles'])): ?>
                                    <span class="text-gray-400 text-xs">Nessuno</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100"><?= $uRow['fromRoleCount'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($uRow['directCount'] > 0): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"><?= $uRow['directCount'] ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-gray-100"><?= $uRow['fromRoleCount'] + $uRow['directCount'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
