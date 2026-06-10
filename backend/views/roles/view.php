<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCss('
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
    cursor: pointer;
}
.toggle-switch.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.toggle-input {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}
.toggle-slider {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #d1d5db;
    border-radius: 20px;
    transition: background-color 0.2s ease;
}
.toggle-slider:before {
    content: "";
    position: absolute;
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.toggle-input:checked + .toggle-slider {
    background-color: #3b82f6;
}
.toggle-input:checked + .toggle-slider:before {
    transform: translateX(16px);
}
');

/** @var yii\web\View $this */
/** @var common\models\AuthItem $role */
/** @var array $groupedPermissions */
/** @var array $assignedPermissions */

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Amministratore',
    'manager' => 'Manager',
    'coordinator' => 'Coordinatore',
    'therapist' => 'Terapista',
    'patient' => 'Paziente',
    'patient_family' => 'Familiare Paziente',
];

$roleBadgeColors = [
    'super_admin' => 'bg-red-100 text-red-700',
    'admin' => 'bg-orange-100 text-orange-700',
    'manager' => 'bg-blue-100 text-blue-700',
    'coordinator' => 'bg-indigo-100 text-indigo-700',
    'therapist' => 'bg-green-100 text-green-700',
    'patient' => 'bg-teal-100 text-teal-700',
    'patient_family' => 'bg-cyan-100 text-cyan-700',
];

$categoryColors = [
    'bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500',
    'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-orange-500',
    'bg-teal-500', 'bg-pink-500', 'bg-lime-500', 'bg-fuchsia-500',
];

$roleLabel = $roleLabels[$role->name] ?? ucfirst($role->name);
$badgeColor = $roleBadgeColors[$role->name] ?? 'bg-gray-100 text-gray-700';
$this->title = 'Ruolo: ' . $roleLabel;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90"><?= Html::encode($roleLabel) ?></h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="<?= Url::to(['/site/index']) ?>">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="<?= Url::to(['/roles/index']) ?>">
                        Permessi Ruoli
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90"><?= Html::encode($roleLabel) ?></li>
            </ol>
        </nav>
    </div>

    <!-- Role Header Card -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-8">
        <div class="px-6 py-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <?= Html::a('<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>',
                    ['index'], [
                    'class' => 'inline-flex items-center justify-center w-9 h-9 text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors'
                ]) ?>
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <?= Html::encode($roleLabel) ?>
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeColor ?>">
                            <?= Html::encode($role->name) ?>
                        </span>
                    </div>
                    <?php if ($role->description): ?>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"><?= Html::encode($role->description) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Permessi attivi: <strong id="assigned-count" class="text-gray-900 dark:text-white"><?= count($assignedPermissions) ?></strong>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <div style="position:relative;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="permission-search"
                   placeholder="Cerca per nome permesso o area (es. Calendario, Assenze...)"
                   style="width:100%;padding:10px 16px 10px 40px;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;background:white;outline:none;color:#374151;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 2px rgba(59,130,246,0.15)'"
                   onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        </div>
    </div>

    <!-- Permissions by Category -->
    <?php
    $systemPermissions = ['platform_login', 'app_login', 'manage_system', 'manage_notifications'];
    $colorIndex = 0;
    ?>
    <?php foreach ($groupedPermissions as $category => $permissions): ?>
        <?php $dotColor = $categoryColors[$colorIndex % count($categoryColors)]; $colorIndex++; ?>
        <div class="mb-6 permission-category">
            <!-- Category Header -->
            <div class="flex items-center gap-3 mb-3 px-1">
                <span class="flex-shrink-0 w-3 h-3 rounded-full <?= $dotColor ?>"></span>
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider category-title"><?= Html::encode($category) ?></h4>
            </div>

            <!-- Permissions Grid -->
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 divide-gray-100 dark:divide-gray-800">
                    <?php foreach ($permissions as $i => $permission): ?>
                        <?php
                        $isAssigned = in_array($permission->name, $assignedPermissions);
                        $isSystem = in_array($permission->name, $systemPermissions);
                        $description = $permission->description ?: ucfirst(str_replace('_', ' ', $permission->name));
                        $isDisabled = $isSystem;
                        ?>
                        <div class="permission-row flex items-center justify-between px-5 py-3.5 <?= ($i >= 2) ? 'border-t border-gray-100 dark:border-gray-800' : '' ?> <?= $isSystem ? 'opacity-60' : '' ?>">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-50 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <span class="perm-name block text-sm font-medium text-gray-800 dark:text-white/90 truncate">
                                        <?= Html::encode($description) ?>
                                        <?php if ($isSystem): ?>
                                            <span class="text-xs text-amber-600 font-normal">(sistema)</span>
                                        <?php endif; ?>
                                    </span>
                                    <code class="perm-code text-xs text-gray-400"><?= Html::encode($permission->name) ?></code>
                                </div>
                            </div>
                            <label class="toggle-switch flex-shrink-0 ml-3 <?= $isDisabled ? 'disabled' : '' ?>">
                                <input type="checkbox"
                                       class="toggle-input <?= $isSystem ? '' : 'permission-toggle' ?>"
                                       data-role="<?= Html::encode($role->name) ?>"
                                       data-permission="<?= Html::encode($permission->name) ?>"
                                       data-description="<?= Html::encode($description) ?>"
                                       <?= $isAssigned ? 'checked' : '' ?>
                                       <?= $isDisabled ? 'disabled' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal ri-assegnazione -->
<div id="reassign-modal" class="hidden" style="position:fixed;inset:0;z-index:99999;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem;">
        <div id="reassign-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99999;"></div>
        <div style="position:relative;background:white;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:480px;width:100%;z-index:100000;">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Ri-assegna permesso</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Stai rimuovendo <strong id="modal-perm-label"></strong> dal ruolo
                    <strong><?= Html::encode($roleLabel) ?></strong>.
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    Vuoi assegnare questo permesso direttamente ad alcuni utenti con questo ruolo?
                </p>
            </div>
            <div class="px-6 py-4 max-h-80 overflow-y-auto" id="modal-users-container">
                <div id="modal-loading" class="text-center py-4 text-sm text-gray-500">Caricamento utenti...</div>
                <div id="modal-empty" class="text-center py-4 text-sm text-gray-500 hidden">Nessun utente attivo con questo ruolo.</div>
                <div id="modal-users-list" class="hidden">
                    <div class="mb-3 flex items-center gap-2">
                        <button type="button" id="modal-select-all" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Seleziona tutti</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" id="modal-select-none" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Deseleziona tutti</button>
                    </div>
                    <div id="modal-users-checkboxes"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" id="modal-remove-only"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Rimuovi senza ri-assegnare
                </button>
                <button type="button" id="modal-remove-assign"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 opacity-50 cursor-not-allowed"
                        disabled>
                    Ri-assegna ai selezionati (<span id="modal-selected-count">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$toggleUrl = Url::to(['toggle-permission']);
$getUsersUrl = Url::to(['get-role-users']);
$assignToUsersUrl = Url::to(['assign-permission-to-users']);
$csrf = Yii::$app->request->csrfToken;
$roleName = $role->name;

$js = <<<JS
var _removingPermission = '';
var _removingCheckbox = null;

function openModal() { $('#reassign-modal').removeClass('hidden'); }
function closeModal() {
    $('#reassign-modal').addClass('hidden');
    if (_removingCheckbox) {
        _removingCheckbox.prop('checked', true).prop('disabled', false);
    }
}

function updateSelectedCount() {
    var count = $('#modal-users-checkboxes input:checked').length;
    $('#modal-selected-count').text(count);
    if (count > 0) {
        $('#modal-remove-assign').removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
    } else {
        $('#modal-remove-assign').addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
    }
}

function doToggle(role, permission, assign) {
    return $.ajax({
        url: '{$toggleUrl}',
        type: 'POST',
        data: { role: role, permission: permission, assign: assign, _csrf: '{$csrf}' }
    });
}

$(document).on('change', '.permission-toggle', function() {
    var cb = $(this);
    var role = cb.data('role');
    var permission = cb.data('permission');
    var description = cb.data('description');
    var assign = cb.is(':checked') ? 1 : 0;

    if (!assign) {
        cb.prop('disabled', true);
        _removingPermission = permission;
        _removingCheckbox = cb;

        $('#modal-perm-label').text(description);
        $('#modal-loading').show();
        $('#modal-empty').addClass('hidden');
        $('#modal-users-list').addClass('hidden');
        $('#modal-users-checkboxes').empty();
        openModal();

        $.get('{$getUsersUrl}', { role: role, permission: permission }, function(data) {
            $('#modal-loading').hide();
            var users = data.users || [];
            if (users.length === 0) {
                $('#modal-empty').removeClass('hidden');
            } else {
                var html = '';
                for (var i = 0; i < users.length; i++) {
                    var u = users[i];
                    html += '<label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer">' +
                        '<input type="checkbox" class="modal-user-cb h-4 w-4 text-indigo-600 border-gray-300 rounded" value="' + u.id + '"' + (u.hasPermission ? ' disabled checked' : '') + '>' +
                        '<div class="ml-3"><span class="text-sm font-medium text-gray-900">' + u.name + '</span>' +
                        '<span class="text-xs text-gray-500 ml-1">(' + u.email + ')</span>' +
                        (u.hasPermission ? '<span class="ml-1 text-xs text-green-600">(ha gia\' il permesso)</span>' : '') +
                        '</div></label>';
                }
                $('#modal-users-checkboxes').html(html);
                $('#modal-users-list').removeClass('hidden');
                updateSelectedCount();
            }
        });
        return;
    }

    cb.prop('disabled', true);
    doToggle(role, permission, 1).done(function(response) {
        cb.prop('disabled', false);
        if (response.status !== 'success') {
            cb.prop('checked', false);
            alert(response.message || 'Errore');
        } else {
            var c = parseInt($('#assigned-count').text());
            $('#assigned-count').text(c + 1);
        }
    }).fail(function() {
        cb.prop('disabled', false);
        cb.prop('checked', false);
        alert('Errore di rete');
    });
});

$('#reassign-overlay').on('click', closeModal);
$(document).on('change', '.modal-user-cb', updateSelectedCount);

$('#modal-select-all').on('click', function() {
    $('#modal-users-checkboxes input:not(:disabled)').prop('checked', true);
    updateSelectedCount();
});
$('#modal-select-none').on('click', function() {
    $('#modal-users-checkboxes input:not(:disabled)').prop('checked', false);
    updateSelectedCount();
});

$('#modal-remove-only').on('click', function() {
    doToggle('{$roleName}', _removingPermission, 0).done(function() {
        $('#reassign-modal').addClass('hidden');
        if (_removingCheckbox) _removingCheckbox.prop('disabled', false);
        _removingCheckbox = null;
        var c = parseInt($('#assigned-count').text());
        $('#assigned-count').text(c - 1);
    });
});

$('#modal-remove-assign').on('click', function() {
    var selectedIds = [];
    $('#modal-users-checkboxes input:checked:not(:disabled)').each(function() {
        selectedIds.push($(this).val());
    });

    doToggle('{$roleName}', _removingPermission, 0).done(function() {
        if (selectedIds.length > 0) {
            $.ajax({
                url: '{$assignToUsersUrl}',
                type: 'POST',
                data: { permission: _removingPermission, user_ids: selectedIds, _csrf: '{$csrf}' }
            });
        }
        $('#reassign-modal').addClass('hidden');
        if (_removingCheckbox) _removingCheckbox.prop('disabled', false);
        _removingCheckbox = null;
        var c = parseInt($('#assigned-count').text());
        $('#assigned-count').text(c - 1);
    });
});

// Search filter
$('#permission-search').on('input', function() {
    var query = $(this).val().toLowerCase().trim();

    $('.permission-category').each(function() {
        var categoryName = $(this).find('.category-title').text().toLowerCase();
        var categoryMatch = categoryName.indexOf(query) !== -1;
        var visibleCount = 0;

        $(this).find('.permission-row').each(function() {
            var permName = $(this).find('.perm-name').text().toLowerCase();
            var permCode = $(this).find('.perm-code').text().toLowerCase();

            if (!query || categoryMatch || permName.indexOf(query) !== -1 || permCode.indexOf(query) !== -1) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0 && !categoryMatch) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
});
JS;
$this->registerJs($js);
?>
