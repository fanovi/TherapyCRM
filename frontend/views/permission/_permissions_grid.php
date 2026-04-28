<?php

use yii\helpers\Html;

/** @var array $allPermissions */
/** @var array $rolePermissions */
/** @var array $userDirectPermissions */
/** @var array $categories */

$rolePermissions = $rolePermissions ?? [];
$userDirectPermissions = $userDirectPermissions ?? [];
$categories = $categories ?? [];

$totalPermissions = 0;
foreach ($categories as $perms) {
    $totalPermissions += count($perms);
}
?>

<!-- Sezione Permessi -->
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] permissions-section">
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

    <!-- Toolbar: search + filtri rapidi -->
    <div class="px-5 sm:px-6 pb-3 border-b border-gray-100 dark:border-gray-800">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <!-- Searchbar -->
            <div class="relative flex-1 lg:max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text"
                       id="permissions-grid-search"
                       autocomplete="off"
                       placeholder="Cerca per nome, codice o categoria (es. paziente, view_, calendar...)"
                       class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-10 text-sm text-gray-700 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-500">
                <button type="button"
                        id="permissions-grid-search-clear"
                        class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        title="Pulisci ricerca">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Filtri rapidi -->
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden permissions-grid-filter-group" role="group">
                    <button type="button" class="permissions-grid-filter-btn px-3 py-1.5 text-xs font-medium" data-filter="all">
                        Tutti
                    </button>
                    <button type="button" class="permissions-grid-filter-btn px-3 py-1.5 text-xs font-medium border-l border-gray-200 dark:border-gray-700" data-filter="role">
                        Ruolo
                    </button>
                    <button type="button" class="permissions-grid-filter-btn px-3 py-1.5 text-xs font-medium border-l border-gray-200 dark:border-gray-700" data-filter="extra">
                        Extra
                    </button>
                    <button type="button" class="permissions-grid-filter-btn px-3 py-1.5 text-xs font-medium border-l border-gray-200 dark:border-gray-700" data-filter="available">
                        Disponibili
                    </button>
                </div>

                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    <span id="permissions-grid-visible-count"><?= (int) $totalPermissions ?></span>
                    /
                    <span id="permissions-grid-total-count"><?= (int) $totalPermissions ?></span>
                    permessi
                </span>
            </div>
        </div>

        <!-- Stato vuoto (visibile solo quando non ci sono match) -->
        <div id="permissions-grid-empty" class="hidden mt-3 rounded-lg border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4 text-sm text-gray-500 dark:text-gray-400 text-center">
            Nessun permesso corrisponde ai criteri di ricerca.
        </div>
    </div>

    <div class="px-5 pb-5 sm:px-6 sm:pb-6 pt-4">
        <?php foreach ($categories as $category => $permissions): ?>
            <div class="mb-4 permissions-grid-category" data-category="<?= Html::encode(strtolower($category)) ?>">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2 pb-1 border-b border-gray-100 dark:border-gray-700 permissions-grid-category-title">
                    <?= Html::encode($category) ?>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                    <?php foreach ($permissions as $permission): ?>
                        <?php
                        $isFromRole = in_array($permission->name, $rolePermissions);
                        $isDirectExtra = in_array($permission->name, $userDirectPermissions);
                        $isChecked = $isFromRole || $isDirectExtra;
                        $label = $permission->description ?: ucfirst(str_replace('_', ' ', $permission->name));

                        if ($isFromRole) {
                            $itemType = 'role';
                        } elseif ($isDirectExtra) {
                            $itemType = 'extra';
                        } else {
                            $itemType = 'available';
                        }
                        ?>
                        <label class="permissions-grid-item flex items-start space-x-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 <?= $isFromRole ? 'cursor-default' : 'cursor-pointer' ?>"
                               data-type="<?= Html::encode($itemType) ?>"
                               data-name="<?= Html::encode(strtolower($permission->name)) ?>"
                               data-label="<?= Html::encode(strtolower($label)) ?>"
                               data-category="<?= Html::encode(strtolower($category)) ?>">
                            <?php if ($isFromRole): ?>
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
                                <span class="text-sm text-gray-800 dark:text-gray-200 permissions-grid-item-label">
                                    <?= Html::encode($label) ?>
                                </span>
                                <?php if ($isFromRole): ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">ruolo</span>
                                <?php elseif ($isDirectExtra): ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">extra</span>
                                <?php endif; ?>
                                <code class="block text-xs text-gray-400 dark:text-gray-500 permissions-grid-item-code"><?= Html::encode($permission->name) ?></code>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$css = <<<CSS
.permissions-grid-filter-btn {
    background: transparent;
    color: #6b7280;
    transition: background-color .15s, color .15s;
}
.permissions-grid-filter-btn:hover {
    background-color: #f3f4f6;
    color: #111827;
}
.dark .permissions-grid-filter-btn:hover {
    background-color: #1f2937;
    color: #f9fafb;
}
.permissions-grid-filter-btn.is-active {
    background-color: #2563eb;
    color: #ffffff;
}
.permissions-grid-filter-btn.is-active:hover {
    background-color: #1d4ed8;
    color: #ffffff;
}
CSS;
$this->registerCss($css);

$js = <<<JS
(function () {
    var section = document.querySelector('.permissions-section');
    if (!section) return;

    var input = section.querySelector('#permissions-grid-search');
    var clearBtn = section.querySelector('#permissions-grid-search-clear');
    var filterBtns = section.querySelectorAll('.permissions-grid-filter-btn');
    var emptyState = section.querySelector('#permissions-grid-empty');
    var visibleCountEl = section.querySelector('#permissions-grid-visible-count');
    var items = section.querySelectorAll('.permissions-grid-item');
    var categories = section.querySelectorAll('.permissions-grid-category');

    var currentFilter = 'all';
    var currentQuery = '';

    function setActiveFilter(value) {
        currentFilter = value;
        for (var i = 0; i < filterBtns.length; i++) {
            if (filterBtns[i].getAttribute('data-filter') === value) {
                filterBtns[i].classList.add('is-active');
            } else {
                filterBtns[i].classList.remove('is-active');
            }
        }
    }

    function applyFilter() {
        var q = currentQuery;
        var visible = 0;

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var type = item.getAttribute('data-type') || 'available';
            var name = item.getAttribute('data-name') || '';
            var label = item.getAttribute('data-label') || '';
            var category = item.getAttribute('data-category') || '';

            var typeOk = (currentFilter === 'all') || (currentFilter === type);

            var queryOk = !q
                || name.indexOf(q) !== -1
                || label.indexOf(q) !== -1
                || category.indexOf(q) !== -1;

            if (typeOk && queryOk) {
                item.style.display = '';
                visible++;
            } else {
                item.style.display = 'none';
            }
        }

        // Hide empty categories
        for (var c = 0; c < categories.length; c++) {
            var cat = categories[c];
            var anyVisible = false;
            var labels = cat.querySelectorAll('.permissions-grid-item');
            for (var k = 0; k < labels.length; k++) {
                if (labels[k].style.display !== 'none') {
                    anyVisible = true;
                    break;
                }
            }
            cat.style.display = anyVisible ? '' : 'none';
        }

        if (visibleCountEl) visibleCountEl.textContent = visible;
        if (emptyState) {
            if (visible === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
        if (clearBtn) {
            if (q) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }
    }

    if (input) {
        input.addEventListener('input', function () {
            currentQuery = (this.value || '').toLowerCase().trim();
            applyFilter();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                this.value = '';
                currentQuery = '';
                applyFilter();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (input) {
                input.value = '';
                input.focus();
            }
            currentQuery = '';
            applyFilter();
        });
    }

    for (var b = 0; b < filterBtns.length; b++) {
        filterBtns[b].addEventListener('click', function () {
            setActiveFilter(this.getAttribute('data-filter'));
            applyFilter();
        });
    }

    setActiveFilter('all');
    applyFilter();
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
