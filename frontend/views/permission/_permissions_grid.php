<?php

use yii\helpers\Html;

/** @var array $allPermissions */
/** @var array $rolePermissions */
/** @var array $userDirectPermissions */
/** @var array $categories */

$rolePermissions = $rolePermissions ?? [];
$userDirectPermissions = $userDirectPermissions ?? [];
$categories = $categories ?? [];
?>

<!-- Sezione Permessi -->
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="px-5 py-4 sm:px-6 sm:py-5">
        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
            Permessi
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            I permessi con badge "ruolo" sono ereditati dal ruolo e non modificabili. Puoi aggiungere permessi extra selezionando quelli non assegnati.
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                ruolo
            </span>
            <span class="text-xs text-gray-400">= dal ruolo (non modificabile)</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                extra
            </span>
            <span class="text-xs text-gray-400">= aggiunto direttamente</span>
        </div>
    </div>

    <div class="px-5 pb-5 sm:px-6 sm:pb-6">
        <?php foreach ($categories as $category => $permissions): ?>
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2 pb-1 border-b border-gray-100 dark:border-gray-700">
                    <?= Html::encode($category) ?>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                    <?php foreach ($permissions as $permission): ?>
                        <?php
                        $isFromRole = in_array($permission->name, $rolePermissions);
                        $isDirectExtra = in_array($permission->name, $userDirectPermissions);
                        $isChecked = $isFromRole || $isDirectExtra;
                        $label = $permission->description ?: ucfirst(str_replace('_', ' ', $permission->name));
                        ?>
                        <label class="flex items-start space-x-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 <?= $isFromRole ? 'cursor-default' : 'cursor-pointer' ?>">
                            <?php if ($isFromRole): ?>
                                <!-- Permesso dal ruolo: checkbox disabilitato + hidden per non inviarlo -->
                                <input type="checkbox"
                                       class="mt-0.5 h-4 w-4 text-gray-400 border-gray-300 rounded cursor-default"
                                       checked
                                       disabled>
                            <?php else: ?>
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="<?= Html::encode($permission->name) ?>"
                                       class="mt-0.5 h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                       <?= $isDirectExtra ? 'checked' : '' ?>>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm text-gray-800 dark:text-gray-200">
                                    <?= Html::encode($label) ?>
                                </span>
                                <?php if ($isFromRole): ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">ruolo</span>
                                <?php elseif ($isDirectExtra): ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">extra</span>
                                <?php endif; ?>
                                <code class="block text-xs text-gray-400 dark:text-gray-500"><?= Html::encode($permission->name) ?></code>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
