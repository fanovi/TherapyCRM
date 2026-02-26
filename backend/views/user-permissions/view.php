<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $userRoles array */
/* @var $rolePermissions array - [permName => fromRole] */
/* @var $directPermissions array */
/* @var $availablePermissions array */
/* @var $permDescriptions array - [permName => description] */

$displayName = $user->profile ? $user->profile->first_name . ' ' . $user->profile->last_name : $user->username;
$this->title = 'Permessi: ' . $displayName;

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Amministratore',
    'manager' => 'Manager',
    'coordinator' => 'Coordinatore',
];

$roleBadgeColors = [
    'super_admin' => 'bg-red-100 text-red-800',
    'admin' => 'bg-purple-100 text-purple-800',
    'manager' => 'bg-blue-100 text-blue-800',
    'coordinator' => 'bg-green-100 text-green-800',
];

/**
 * Converts a permission name like "view_therapist" to "Visualizza terapista"
 */
function humanizePermission($name, $description = null) {
    if ($description) {
        return $description;
    }
    // Fallback: convert snake_case to readable
    $label = str_replace('_', ' ', $name);
    return ucfirst($label);
}

// Group all permissions: merge role + direct, mark source
$allPermissions = [];
foreach ($rolePermissions as $permName => $fromRole) {
    $allPermissions[$permName] = [
        'source' => 'role',
        'role' => $fromRole,
        'description' => $permDescriptions[$permName] ?? null,
    ];
}
foreach ($directPermissions as $permName) {
    if (isset($allPermissions[$permName])) {
        // Already from role, mark as both
        $allPermissions[$permName]['source'] = 'both';
    } else {
        $allPermissions[$permName] = [
            'source' => 'direct',
            'role' => null,
            'description' => $permDescriptions[$permName] ?? null,
        ];
    }
}
ksort($allPermissions);
?>
<div class="user-permissions-view">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900"><?= Html::encode($displayName) ?></h1>
            <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                <?= Html::encode($user->email) ?>
                <?php foreach ($userRoles as $role): ?>
                    <?php $badgeColor = $roleBadgeColors[$role] ?? 'bg-gray-100 text-gray-800'; ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeColor ?>">
                        <?= Html::encode($roleLabels[$role] ?? ucfirst($role)) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?= Html::a('&larr; Torna alla lista', ['index'], [
            'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50',
        ]) ?>
    </div>

    <!-- Griglia completa permessi -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Tutti i Permessi</h2>
            <p class="text-sm text-gray-500">
                Totale: <?= count($allPermissions) ?> permessi
                (<?= count($rolePermissions) ?> dal ruolo, <?= count($directPermissions) ?> extra)
            </p>
        </div>

        <?php if (!empty($allPermissions)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permesso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codice</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Origine</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($allPermissions as $permName => $info): ?>
                        <tr class="hover:bg-gray-50 direct-perm-row" data-permission="<?= Html::encode($permName) ?>">
                            <td class="px-6 py-3 text-sm text-gray-900">
                                <?= Html::encode(humanizePermission($permName, $info['description'])) ?>
                            </td>
                            <td class="px-6 py-3">
                                <code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded"><?= Html::encode($permName) ?></code>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <?php if ($info['source'] === 'role' || $info['source'] === 'both'): ?>
                                    <?php $roleColor = $roleBadgeColors[$info['role']] ?? 'bg-gray-100 text-gray-800'; ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $roleColor ?>">
                                        <?= Html::encode($roleLabels[$info['role']] ?? ucfirst($info['role'])) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($info['source'] === 'direct' || $info['source'] === 'both'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        Extra
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <?php if ($info['source'] === 'direct' || $info['source'] === 'both'): ?>
                                    <button type="button"
                                            class="remove-permission-btn text-xs text-red-600 hover:text-red-800 font-medium"
                                            data-user-id="<?= $user->id ?>"
                                            data-permission="<?= Html::encode($permName) ?>">
                                        Rimuovi extra
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">dal ruolo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="px-6 py-8 text-center text-sm text-gray-500">
                Nessun permesso assegnato a questo utente.
            </div>
        <?php endif; ?>
    </div>

    <!-- Aggiungi permesso -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Aggiungi Permesso Extra</h2>
            <p class="text-sm text-gray-500">Assegna un permesso aggiuntivo direttamente a questo utente.</p>
        </div>
        <div class="px-6 py-4">
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <select id="add-permission-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">-- Seleziona un permesso --</option>
                        <?php foreach ($availablePermissions as $perm): ?>
                            <option value="<?= Html::encode($perm->name) ?>">
                                <?= Html::encode($perm->description ?: ucfirst(str_replace('_', ' ', $perm->name))) ?>
                                (<?= Html::encode($perm->name) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="add-permission-btn"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
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
        data: { user_id: '{$userId}', permission: permissionName, _csrf: '{$csrf}' },
        success: function(response) {
            btn.prop('disabled', false);
            if (response.status === 'success') {
                location.reload();
            } else {
                alert(response.message || 'Errore');
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
    if (!confirm('Rimuovere il permesso extra "' + permissionName + '"?')) return;
    btn.prop('disabled', true);

    $.ajax({
        url: '{$removeUrl}',
        type: 'POST',
        data: { user_id: '{$userId}', permission: permissionName, _csrf: '{$csrf}' },
        success: function(response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                btn.prop('disabled', false);
                alert(response.message || 'Errore');
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
