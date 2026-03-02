<?php

use yii\helpers\Html;
use yii\helpers\Url;

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

$roleLabel = $roleLabels[$role->name] ?? ucfirst($role->name);
$this->title = 'Ruolo: ' . $roleLabel;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($roleLabel) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>

            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/permission/roles']) ?>">
                            Permessi Ruoli
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName"></li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Action Buttons -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Torna alla Lista',
                ['roles'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Permessi assegnati: <strong id="assigned-count" class="text-gray-800 dark:text-white"><?= count($assignedPermissions) ?></strong>
        </div>
    </div>

    <!-- Role Info -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                <?= Html::encode($roleLabel) ?>
            </h3>
            <?php if ($role->description): ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= Html::encode($role->description) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                Codice: <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-xs"><?= Html::encode($role->name) ?></code>
            </p>
        </div>
    </div>

    <!-- Permissions Grid -->
    <?php foreach ($groupedPermissions as $category => $permissions): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-4">
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-900/50 rounded-t-2xl border-b border-gray-100 dark:border-gray-800">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider"><?= Html::encode($category) ?></h4>
        </div>
        <div class="px-5 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-1">
                <?php foreach ($permissions as $permission): ?>
                    <?php $isAssigned = in_array($permission->name, $assignedPermissions); ?>
                    <label class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                        <input type="checkbox"
                               class="permission-toggle mt-0.5 h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500"
                               data-role="<?= Html::encode($role->name) ?>"
                               data-permission="<?= Html::encode($permission->name) ?>"
                               data-description="<?= Html::encode($permission->description ?: ucfirst(str_replace('_', ' ', $permission->name))) ?>"
                               <?= $isAssigned ? 'checked' : '' ?>>
                        <div class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white/90">
                                <?= Html::encode($permission->description ?: ucfirst(str_replace('_', ' ', $permission->name))) ?>
                            </span>
                            <code class="text-xs text-gray-400 dark:text-gray-500"><?= Html::encode($permission->name) ?></code>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal ri-assegnazione -->
<div id="reassign-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div id="reassign-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full z-10">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Ri-assegna permesso</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Stai rimuovendo <strong id="modal-perm-label"></strong> dal ruolo
                    <strong><?= Html::encode($roleLabel) ?></strong>.
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Vuoi assegnare questo permesso direttamente ad alcuni utenti con questo ruolo?
                </p>
            </div>
            <div class="px-6 py-4 max-h-80 overflow-y-auto" id="modal-users-container">
                <div id="modal-loading" class="text-center py-4 text-sm text-gray-500">Caricamento utenti...</div>
                <div id="modal-empty" class="text-center py-4 text-sm text-gray-500 hidden">Nessun utente attivo con questo ruolo.</div>
                <div id="modal-users-list" class="hidden">
                    <div class="mb-3 flex items-center gap-2">
                        <button type="button" id="modal-select-all" class="text-xs text-brand-600 hover:text-brand-800 font-medium">Seleziona tutti</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" id="modal-select-none" class="text-xs text-brand-600 hover:text-brand-800 font-medium">Deseleziona tutti</button>
                    </div>
                    <div id="modal-users-checkboxes"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl flex justify-end gap-3">
                <button type="button" id="modal-remove-only"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                    Rimuovi senza ri-assegnare
                </button>
                <button type="button" id="modal-remove-assign"
                        class="px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 opacity-50 cursor-not-allowed"
                        disabled>
                    Ri-assegna ai selezionati (<span id="modal-selected-count">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$toggleUrl = Url::to(['toggle-role-permission']);
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
                    html += '<label class="flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">' +
                        '<input type="checkbox" class="modal-user-cb h-4 w-4 text-brand-600 border-gray-300 rounded" value="' + u.id + '"' + (u.hasPermission ? ' disabled checked' : '') + '>' +
                        '<div class="ml-3"><span class="text-sm font-medium text-gray-900 dark:text-white">' + u.name + '</span>' +
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
JS;
$this->registerJs($js);
?>
