<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $role common\models\AuthItem */
/* @var $groupedPermissions array */
/* @var $assignedPermissions array */

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Amministratore',
    'manager' => 'Manager',
    'coordinator' => 'Coordinatore',
    'therapist' => 'Terapista',
    'patient' => 'Paziente',
    'patient_family' => 'Familiare Paziente',
];

$this->title = 'Ruolo: ' . ($roleLabels[$role->name] ?? ucfirst($role->name));
?>
<div class="roles-view">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900"><?= Html::encode($roleLabels[$role->name] ?? ucfirst($role->name)) ?></h1>
            <?php if ($role->description): ?>
                <p class="mt-1 text-sm text-gray-500"><?= Html::encode($role->description) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-sm text-gray-400">
                Codice: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs"><?= Html::encode($role->name) ?></code>
            </p>
        </div>
        <?= Html::a('&larr; Torna alla lista', ['index'], [
            'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50',
        ]) ?>
    </div>

    <div class="mb-4">
        <span class="text-sm text-gray-500">
            Permessi assegnati: <strong id="assigned-count"><?= count($assignedPermissions) ?></strong>
        </span>
    </div>

    <?php foreach ($groupedPermissions as $category => $permissions): ?>
    <div class="bg-white rounded-lg shadow-sm mb-4">
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 rounded-t-lg">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider"><?= Html::encode($category) ?></h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-1">
                <?php foreach ($permissions as $permission): ?>
                    <?php $isAssigned = in_array($permission->name, $assignedPermissions); ?>
                    <label class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               class="permission-toggle mt-0.5 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                               data-role="<?= Html::encode($role->name) ?>"
                               data-permission="<?= Html::encode($permission->name) ?>"
                               data-description="<?= Html::encode($permission->description ?: ucfirst(str_replace('_', ' ', $permission->name))) ?>"
                               <?= $isAssigned ? 'checked' : '' ?>>
                        <div class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-gray-900">
                                <?= Html::encode($permission->description ?: ucfirst(str_replace('_', ' ', $permission->name))) ?>
                            </span>
                            <code class="text-xs text-gray-400"><?= Html::encode($permission->name) ?></code>
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
        <div id="reassign-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full z-10">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Ri-assegna permesso</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Stai rimuovendo <strong id="modal-perm-label"></strong> dal ruolo
                    <strong><?= Html::encode($roleLabels[$role->name] ?? $role->name) ?></strong>.
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
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg flex justify-end gap-3">
                <button type="button" id="modal-remove-only"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Rimuovi senza ri-assegnare
                </button>
                <button type="button" id="modal-remove-assign"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 opacity-50 cursor-not-allowed"
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

// Permission checkbox change
$(document).on('change', '.permission-toggle', function() {
    var cb = $(this);
    var role = cb.data('role');
    var permission = cb.data('permission');
    var description = cb.data('description');
    var assign = cb.is(':checked') ? 1 : 0;

    if (!assign) {
        // Removing: show modal
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

    // Adding: just toggle
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

// Modal events
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

// Remove only
$('#modal-remove-only').on('click', function() {
    doToggle('{$roleName}', _removingPermission, 0).done(function() {
        $('#reassign-modal').addClass('hidden');
        if (_removingCheckbox) _removingCheckbox.prop('disabled', false);
        _removingCheckbox = null;
        var c = parseInt($('#assigned-count').text());
        $('#assigned-count').text(c - 1);
    });
});

// Remove and assign
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
