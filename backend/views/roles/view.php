<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $role common\models\AuthItem */
/* @var $groupedPermissions array */
/* @var $assignedPermissions array */

$this->title = 'Ruolo: ' . $role->name;
$this->params['breadcrumbs'][] = ['label' => 'Gestione Ruoli', 'url' => ['index']];
$this->params['breadcrumbs'][] = $role->name;
?>
<div class="roles-view">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= Html::encode($role->name) ?></h1>
            <?php if ($role->description): ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= Html::encode($role->description) ?></p>
            <?php endif; ?>
        </div>
        <div>
            <?= Html::a('&larr; Torna alla lista', ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600',
            ]) ?>
        </div>
    </div>

    <div class="mb-4">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            Permessi assegnati: <strong id="assigned-count"><?= count($assignedPermissions) ?></strong>
        </span>
    </div>

    <?php foreach ($groupedPermissions as $category => $permissions): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-4">
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 rounded-t-lg">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider"><?= Html::encode($category) ?></h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($permissions as $permission): ?>
                    <?php $isAssigned = in_array($permission->name, $assignedPermissions); ?>
                    <label class="flex items-start space-x-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                        <input type="checkbox"
                               class="permission-toggle mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                               data-role="<?= Html::encode($role->name) ?>"
                               data-permission="<?= Html::encode($permission->name) ?>"
                               <?= $isAssigned ? 'checked' : '' ?>>
                        <div class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100"><?= Html::encode($permission->name) ?></span>
                            <?php if ($permission->description): ?>
                                <span class="block text-xs text-gray-500 dark:text-gray-400"><?= Html::encode($permission->description) ?></span>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<?php
$toggleUrl = Url::to(['toggle-permission']);
$csrf = Yii::$app->request->csrfToken;
$js = <<<JS
$(document).on('change', '.permission-toggle', function() {
    var checkbox = $(this);
    var roleName = checkbox.data('role');
    var permissionName = checkbox.data('permission');
    var assign = checkbox.is(':checked') ? 1 : 0;

    checkbox.prop('disabled', true);

    $.ajax({
        url: '{$toggleUrl}',
        type: 'POST',
        data: {
            role: roleName,
            permission: permissionName,
            assign: assign,
            _csrf: '{$csrf}'
        },
        success: function(response) {
            checkbox.prop('disabled', false);
            if (response.status !== 'success') {
                checkbox.prop('checked', !assign);
                alert(response.message || 'Errore durante l\'operazione');
            } else {
                var count = parseInt($('#assigned-count').text());
                $('#assigned-count').text(assign ? count + 1 : count - 1);
            }
        },
        error: function() {
            checkbox.prop('disabled', false);
            checkbox.prop('checked', !assign);
            alert('Errore durante l\'operazione');
        }
    });
});
JS;
$this->registerJs($js);
?>
