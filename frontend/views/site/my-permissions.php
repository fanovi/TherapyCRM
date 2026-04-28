<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $roles \yii\rbac\Role[] */
/* @var $rolePermissions array<string, \yii\rbac\Permission[]> */
/* @var $directPermissions \yii\rbac\Permission[] */

$this->title = 'I miei permessi';
$this->params['breadcrumbs'][] = $this->title;

$user = Yii::$app->user->identity;
$profileName = $user && $user->profile
    ? trim($user->profile->last_name . ' ' . $user->profile->first_name)
    : ($user->email ?? '');

$totalRolePerms = 0;
foreach ($rolePermissions as $perms) {
    $totalRolePerms += count($perms);
}
?>

<div class="mx-auto max-w-5xl p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">I miei permessi</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Vista in sola lettura di ruoli e permessi assegnati al tuo account.
            </p>
        </div>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90">I miei permessi</li>
            </ol>
        </nav>
    </div>

    <!-- Account info + counters -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    <?= Html::encode($profileName !== '' ? $profileName : ($user->email ?? '-')) ?>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?= Html::encode($user->email ?? '') ?>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                    <?= count($roles) ?> ruol<?= count($roles) === 1 ? 'o' : 'i' ?>
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <?= $totalRolePerms ?> permess<?= $totalRolePerms === 1 ? 'o' : 'i' ?> da ruolo
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    <?= count($directPermissions) ?> extra
                </span>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z"/>
                </svg>
            </span>
            <input type="text" id="permissionsSearch" autocomplete="off"
                   placeholder="Cerca per nome permesso o descrizione..."
                   class="w-full pl-10 pr-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        </div>
    </div>

    <!-- Roles + permissions -->
    <?php if (empty($roles)): ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-800 dark:bg-white/[0.03]">
            Nessun ruolo assegnato al tuo account.
        </div>
    <?php else: ?>
        <?php foreach ($roles as $roleName => $role): ?>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] mb-4 permissions-section"
                 data-section-name="<?= Html::encode(strtolower($roleName . ' ' . ($role->description ?? ''))) ?>">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-medium text-gray-800 dark:text-white/90 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            <?= Html::encode($roleName) ?>
                        </h3>
                        <?php if (!empty($role->description)): ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= Html::encode($role->description) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <?= count($rolePermissions[$roleName] ?? []) ?> permess<?= count($rolePermissions[$roleName] ?? []) === 1 ? 'o' : 'i' ?>
                    </span>
                </div>

                <?php $perms = $rolePermissions[$roleName] ?? []; ?>
                <?php if (empty($perms)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessun permesso da questo ruolo.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        <?php foreach ($perms as $permName => $perm): ?>
                            <div class="permission-item flex items-start gap-2 px-3 py-2 rounded-lg border border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"
                                 data-permission-name="<?= Html::encode(strtolower($permName . ' ' . ($perm->description ?? ''))) ?>">
                                <svg class="w-4 h-4 mt-0.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <div class="min-w-0">
                                    <p class="text-xs font-mono text-gray-800 dark:text-white/90 truncate" title="<?= Html::encode($permName) ?>">
                                        <?= Html::encode($permName) ?>
                                    </p>
                                    <?php if (!empty($perm->description)): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            <?= Html::encode($perm->description) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Direct (extra) permissions -->
    <?php if (!empty($directPermissions)): ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/30 p-5 dark:border-amber-800 dark:bg-amber-900/10 permissions-section"
             data-section-name="extra <?= Html::encode(strtolower(implode(' ', array_keys($directPermissions)))) ?>">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                        </svg>
                        Permessi extra (assegnati direttamente)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Permessi assegnati al tuo account oltre quelli derivanti dai ruoli.
                    </p>
                </div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    <?= count($directPermissions) ?>
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <?php foreach ($directPermissions as $permName => $perm): ?>
                    <div class="permission-item flex items-start gap-2 px-3 py-2 rounded-lg border border-amber-100 bg-white dark:border-amber-800 dark:bg-gray-800"
                         data-permission-name="<?= Html::encode(strtolower($permName . ' ' . ($perm->description ?? ''))) ?>">
                        <svg class="w-4 h-4 mt-0.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-xs font-mono text-gray-800 dark:text-white/90 truncate" title="<?= Html::encode($permName) ?>">
                                <?= Html::encode($permName) ?>
                            </p>
                            <?php if (!empty($perm->description)): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <?= Html::encode($perm->description) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <p id="permissionsNoResults" class="hidden mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Nessun permesso corrisponde alla ricerca.
    </p>
</div>

<?php $this->registerJs(<<<JS
(function () {
    var input = document.getElementById('permissionsSearch');
    var noResults = document.getElementById('permissionsNoResults');
    if (!input) { return; }

    function applyFilter() {
        var term = (input.value || '').trim().toLowerCase();
        var sections = document.querySelectorAll('.permissions-section');
        var anyVisible = false;

        sections.forEach(function (section) {
            var items = section.querySelectorAll('.permission-item');
            var visibleInSection = 0;
            var sectionName = section.getAttribute('data-section-name') || '';
            var sectionMatches = !term || sectionName.indexOf(term) !== -1;

            items.forEach(function (item) {
                var name = item.getAttribute('data-permission-name') || '';
                var match = !term || name.indexOf(term) !== -1 || sectionMatches;
                item.style.display = match ? '' : 'none';
                if (match) { visibleInSection++; }
            });

            // Mostra la sezione se ci sono item visibili o se il nome sezione matcha (senza item)
            if (visibleInSection > 0 || (sectionMatches && items.length === 0)) {
                section.style.display = '';
                anyVisible = true;
            } else {
                section.style.display = 'none';
            }
        });

        noResults.classList.toggle('hidden', anyVisible || !term);
    }

    var debounce = null;
    input.addEventListener('input', function () {
        if (debounce) { clearTimeout(debounce); }
        debounce = setTimeout(applyFilter, 100);
    });
})();
JS); ?>
