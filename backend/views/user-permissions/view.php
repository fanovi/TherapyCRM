<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $userRoles array */
/* @var $rolePermissions array */
/* @var $directPermissions array */
/* @var $availablePermissions array */

$displayName = $user->profile ? $user->profile->first_name . ' ' . $user->profile->last_name : $user->username;
$this->title = 'Permessi: ' . $displayName;
$this->params['breadcrumbs'][] = ['label' => 'Permessi Utente', 'url' => ['index']];
$this->params['breadcrumbs'][] = $displayName;
?>
<div class="user-permissions-view">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= Html::encode($displayName) ?></h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                <?= Html::encode($user->email) ?>
                <?php if (!empty($userRoles)): ?>
                    &mdash; Ruolo:
                    <?php foreach ($userRoles as $role): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <?= Html::encode($role) ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </p>
        </div>
        <div>
            <?= Html::a('&larr; Torna alla lista', ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600',
            ]) ?>
        </div>
    </div>

    <!-- Sezione 1: Permessi dal ruolo -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Permessi dal Ruolo</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Questi permessi sono ereditati dal ruolo assegnato e non possono essere modificati da qui.</p>
        </div>
        <div class="px-6 py-4">
            <?php if (!empty($rolePermissions)): ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($rolePermissions as $permName => $fromRole): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300" title="Dal ruolo: <?= Html::encode($fromRole) ?>">
                            <?= Html::encode($permName) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-400">Nessun permesso ereditato dal ruolo.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sezione 2: Permessi extra diretti -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Permessi Extra</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Permessi assegnati direttamente a questo utente, indipendentemente dal ruolo.</p>
        </div>
        <div class="px-6 py-4" id="direct-permissions-list">
            <?php if (!empty($directPermissions)): ?>
                <?php foreach ($directPermissions as $permName): ?>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 direct-perm-row" data-permission="<?= Html::encode($permName) ?>">
                        <span class="text-sm text-gray-900 dark:text-gray-100"><?= Html::encode($permName) ?></span>
                        <button type="button"
                                class="remove-permission-btn inline-flex items-center px-2.5 py-1 border border-transparent rounded text-xs font-medium text-red-700 bg-red-100 hover:bg-red-200 dark:text-red-200 dark:bg-red-900 dark:hover:bg-red-800"
                                data-user-id="<?= $user->id ?>"
                                data-permission="<?= Html::encode($permName) ?>">
                            Rimuovi
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-400" id="no-extra-msg">Nessun permesso extra assegnato.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sezione 3: Aggiungi permesso -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Aggiungi Permesso</h2>
        </div>
        <div class="px-6 py-4">
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <select id="add-permission-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        <option value="">-- Seleziona un permesso --</option>
                        <?php foreach ($availablePermissions as $perm): ?>
                            <option value="<?= Html::encode($perm->name) ?>"><?= Html::encode($perm->name) ?><?= $perm->description ? ' - ' . Html::encode($perm->description) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="add-permission-btn"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Aggiungi
                </button>
            </div>
        </div>
    </div>

</div>

<?php
$addUrl = Url::to(['add-permission']);
$removeUrl = Url::to(['remove-permission']);
$userId = $user->id;
$csrf = Yii::$app->request->csrfToken;
$js = <<<JS
// Add permission
$('#add-permission-btn').on('click', function() {
    var select = $('#add-permission-select');
    var permissionName = select.val();
    if (!permissionName) {
        alert('Seleziona un permesso.');
        return;
    }

    var btn = $(this);
    btn.prop('disabled', true);

    $.ajax({
        url: '{$addUrl}',
        type: 'POST',
        data: {
            user_id: '{$userId}',
            permission: permissionName,
            _csrf: '{$csrf}'
        },
        success: function(response) {
            btn.prop('disabled', false);
            if (response.status === 'success') {
                // Remove "no extra" message
                $('#no-extra-msg').remove();

                // Add the row to the list
                var row = '<div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 direct-perm-row" data-permission="' + permissionName + '">' +
                    '<span class="text-sm text-gray-900 dark:text-gray-100">' + permissionName + '</span>' +
                    '<button type="button" class="remove-permission-btn inline-flex items-center px-2.5 py-1 border border-transparent rounded text-xs font-medium text-red-700 bg-red-100 hover:bg-red-200 dark:text-red-200 dark:bg-red-900 dark:hover:bg-red-800" data-user-id="{$userId}" data-permission="' + permissionName + '">Rimuovi</button>' +
                    '</div>';
                $('#direct-permissions-list').append(row);

                // Remove from dropdown
                select.find('option[value="' + permissionName + '"]').remove();
                select.val('');
            } else {
                alert(response.message || 'Errore durante l\'operazione');
            }
        },
        error: function() {
            btn.prop('disabled', false);
            alert('Errore durante l\'operazione');
        }
    });
});

// Remove permission
$(document).on('click', '.remove-permission-btn', function() {
    var btn = $(this);
    var permissionName = btn.data('permission');
    var userId = btn.data('user-id');

    if (!confirm('Rimuovere il permesso "' + permissionName + '"?')) {
        return;
    }

    btn.prop('disabled', true);

    $.ajax({
        url: '{$removeUrl}',
        type: 'POST',
        data: {
            user_id: userId,
            permission: permissionName,
            _csrf: '{$csrf}'
        },
        success: function(response) {
            if (response.status === 'success') {
                btn.closest('.direct-perm-row').remove();

                // Add back to dropdown
                var select = $('#add-permission-select');
                select.append('<option value="' + permissionName + '">' + permissionName + '</option>');

                // Show "no extra" message if empty
                if ($('.direct-perm-row').length === 0) {
                    $('#direct-permissions-list').append('<p class="text-sm text-gray-400" id="no-extra-msg">Nessun permesso extra assegnato.</p>');
                }
            } else {
                btn.prop('disabled', false);
                alert(response.message || 'Errore durante l\'operazione');
            }
        },
        error: function() {
            btn.prop('disabled', false);
            alert('Errore durante l\'operazione');
        }
    });
});
JS;
$this->registerJs($js);
?>
