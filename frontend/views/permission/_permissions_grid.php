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
    <div style="padding: 0 1.5rem 1rem 1.5rem; border-bottom: 1px solid #f3f4f6;" class="dark:!border-gray-800">
        <div style="display: flex; flex-direction: column; gap: 0.75rem;" class="lg:!flex-row lg:!items-center lg:!justify-between">
            <!-- Searchbar -->
            <div style="position: relative; flex: 1 1 auto; min-width: 0;" class="lg:!max-w-md">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; line-height: 0;">
                    <svg style="width: 16px; height: 16px; color: #9ca3af; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text"
                       id="permissions-grid-search"
                       autocomplete="off"
                       placeholder="Cerca per nome, codice o categoria (es. paziente, view_, calendar...)"
                       style="width: 100%; padding: 10px 40px 10px 40px; font-size: 14px; line-height: 1.4; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #374151; outline: none; box-sizing: border-box;"
                       onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 2px rgba(59,130,246,0.15)'"
                       onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                <button type="button"
                        id="permissions-grid-search-clear"
                        style="display: none; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #9ca3af; line-height: 0;"
                        title="Pulisci ricerca">
                    <svg style="width: 16px; height: 16px; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                <!-- Filtri rapidi -->
                <div style="display: inline-flex; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;" role="group">
                    <button type="button" class="permissions-grid-filter-btn" data-filter="all">Tutti</button>
                    <button type="button" class="permissions-grid-filter-btn" data-filter="role" style="border-left: 1px solid #e5e7eb;">Ruolo</button>
                    <button type="button" class="permissions-grid-filter-btn" data-filter="extra" style="border-left: 1px solid #e5e7eb;">Extra</button>
                    <button type="button" class="permissions-grid-filter-btn" data-filter="available" style="border-left: 1px solid #e5e7eb;">Disponibili</button>
                </div>

                <span style="font-size: 12px; color: #6b7280; white-space: nowrap;">
                    <span id="permissions-grid-visible-count" style="font-weight: 600; color: #111827;"><?= (int) $totalPermissions ?></span>
                    /
                    <span id="permissions-grid-total-count"><?= (int) $totalPermissions ?></span>
                    permessi
                </span>
            </div>
        </div>

        <!-- Stato vuoto (visibile solo quando non ci sono match) -->
        <div id="permissions-grid-empty" style="display: none; margin-top: 12px; padding: 16px; border: 1px dashed #e5e7eb; border-radius: 8px; background: #f9fafb; color: #6b7280; font-size: 14px; text-align: center;">
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
                        <label class="permissions-grid-item flex items-center gap-3 px-2 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 <?= $isFromRole ? 'cursor-default' : 'cursor-pointer' ?>"
                               data-type="<?= Html::encode($itemType) ?>"
                               data-name="<?= Html::encode(strtolower($permission->name)) ?>"
                               data-label="<?= Html::encode(strtolower($label)) ?>"
                               data-category="<?= Html::encode(strtolower($category)) ?>">
                            <?php if ($isFromRole): ?>
                                <input type="checkbox"
                                       class="shrink-0 h-4 w-4 text-gray-400 border-gray-300 rounded cursor-default"
                                       checked
                                       disabled>
                            <?php else: ?>
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="<?= Html::encode($permission->name) ?>"
                                       class="shrink-0 h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                                       <?= $isDirectExtra ? 'checked' : '' ?>>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0 flex items-center gap-2">
                                <span class="text-sm text-gray-800 dark:text-gray-200 permissions-grid-item-label truncate">
                                    <?= Html::encode($label) ?>
                                </span>
                                <?php if ($isFromRole): ?>
                                    <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">ruolo</span>
                                <?php elseif ($isDirectExtra): ?>
                                    <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">extra</span>
                                <?php endif; ?>
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
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.2;
    border: 0;
    cursor: pointer;
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
            emptyState.style.display = (visible === 0) ? 'block' : 'none';
        }
        if (clearBtn) {
            clearBtn.style.display = q ? 'block' : 'none';
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
