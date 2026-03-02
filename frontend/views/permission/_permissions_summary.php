<?php

use yii\helpers\Html;

/** @var array $rolePermissions */
/** @var array $userDirectPermissions */
/** @var array $categories */

$rolePermissions = $rolePermissions ?? [];
$userDirectPermissions = $userDirectPermissions ?? [];
$categories = $categories ?? [];

$hasAnyPermission = !empty($rolePermissions) || !empty($userDirectPermissions);
?>

<?php if ($hasAnyPermission): ?>
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mt-6">
    <div class="px-5 py-4 sm:px-6 sm:py-5">
        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
            Permessi
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Riepilogo dei permessi assegnati a questo utente.
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                ruolo
            </span>
            <span class="text-xs text-gray-400">= ereditato dal ruolo</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                extra
            </span>
            <span class="text-xs text-gray-400">= aggiunto direttamente</span>
        </div>
    </div>

    <div class="px-5 pb-5 sm:px-6 sm:pb-6">
        <?php foreach ($categories as $category => $permissions): ?>
            <?php
            // Only show categories that have at least one assigned permission
            $hasCategoryPermissions = false;
            foreach ($permissions as $permission) {
                if (in_array($permission->name, $rolePermissions) || in_array($permission->name, $userDirectPermissions)) {
                    $hasCategoryPermissions = true;
                    break;
                }
            }
            if (!$hasCategoryPermissions) continue;
            ?>
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2 pb-1 border-b border-gray-100 dark:border-gray-700">
                    <?= Html::encode($category) ?>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                    <?php foreach ($permissions as $permission): ?>
                        <?php
                        $isFromRole = in_array($permission->name, $rolePermissions);
                        $isDirectExtra = in_array($permission->name, $userDirectPermissions);
                        if (!$isFromRole && !$isDirectExtra) continue;
                        $label = $permission->description ?: ucfirst(str_replace('_', ' ', $permission->name));
                        ?>
                        <div class="flex items-start space-x-2 p-1.5 rounded">
                            <svg class="mt-0.5 h-4 w-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm text-gray-800 dark:text-gray-200">
                                    <?= Html::encode($label) ?>
                                </span>
                                <?php if ($isFromRole): ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">ruolo</span>
                                <?php else: ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">extra</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
