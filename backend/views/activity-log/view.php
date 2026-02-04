<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\ActivityLog $model */

$this->title = 'Log Attività #' . $model->id;

/**
 * Helper function to decrypt sensitive fields
 */
$decryptValue = function($field, $value) {
    $sensitiveFields = ['phone', 'address'];

    if (!in_array($field, $sensitiveFields) || empty($value)) {
        return $value;
    }

    try {
        $encryptionKey = Yii::$app->params['encryptionKey'];
        $decoded = base64_decode($value, true);
        if ($decoded !== false) {
            $decrypted = Yii::$app->security->decryptByKey($decoded, $encryptionKey);
            if ($decrypted !== false) {
                return $decrypted;
            }
        }
    } catch (\Exception $e) {
        // If decryption fails, return original value (might not be encrypted)
    }

    return $value;
};
$this->params['breadcrumbs'][] = ['label' => 'Log Attività', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Dettaglio #' . $model->id;
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <div class="flex gap-2">
                <?= Html::a('<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>Torna alla lista', 
                    ['index'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Informazioni principali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Log
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dettagli dell'attività registrata nel sistema
            </p>
        </div>
        
        <div class="p-0">
            <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Log</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">#<?= $model->id ?></dd>
                </div>
                
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data e Ora</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                        <?= Yii::$app->formatter->asDatetime($model->created_at, 'php:d/m/Y H:i:s') ?>
                    </dd>
                </div>
                
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Utente</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                        <?= $model->user ? Html::encode($model->user->username) : '<span class="text-gray-400">N/A</span>' ?>
                    </dd>
                </div>
                
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Azione</dt>
                    <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                        <?php
                        $actions = [
                            'create' => ['label' => 'Creazione', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'],
                            'update' => ['label' => 'Modifica', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'],
                            'delete' => ['label' => 'Eliminazione', 'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400']
                        ];
                        $actionInfo = $actions[$model->action] ?? ['label' => $model->action, 'class' => 'bg-gray-100 text-gray-800'];
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $actionInfo['class'] ?>">
                            <?= $actionInfo['label'] ?>
                        </span>
                    </dd>
                </div>
                
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Entità</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                        <?= Html::encode($model->entity_name) ?>
                        <?php if ($model->entity_id): ?>
                            <span class="text-gray-500">(ID: <?= $model->entity_id ?>)</span>
                        <?php endif; ?>
                    </dd>
                </div>
                
                <?php if ($model->parent_log_id): ?>
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Log Principale</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                        <?= Html::a('Visualizza Log #' . $model->parent_log_id, 
                            ['view', 'id' => $model->parent_log_id], 
                            ['class' => 'text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300']) ?>
                    </dd>
                </div>
                <?php endif; ?>
                
                <div class="px-5 py-3 sm:px-6 sm:py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Indirizzo IP</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                        <code class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">
                            <?= Html::encode($model->ip_address) ?>
                        </code>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Dettagli modifiche -->
    <?php if ($model->action === 'update'): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Modifiche Effettuate
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Confronto tra i valori precedenti e quelli nuovi
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Campo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valore Precedente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nuovo Valore</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php
                    $oldValues = json_decode($model->old_values, true) ?: [];
                    $newValues = json_decode($model->new_values, true) ?: [];
                    $allFields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                    sort($allFields);
                    
                    foreach ($allFields as $field):
                        $oldValue = isset($oldValues[$field]) ? $decryptValue($field, $oldValues[$field]) : '';
                        $newValue = isset($newValues[$field]) ? $decryptValue($field, $newValues[$field]) : '';

                        if ($oldValue !== $newValue):
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?= Html::encode($field) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div class="max-w-xs overflow-hidden">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    <?= Html::encode(is_array($oldValue) ? json_encode($oldValue) : $oldValue) ?: '<vuoto>' ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div class="max-w-xs overflow-hidden">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <?= Html::encode(is_array($newValue) ? json_encode($newValue) : $newValue) ?: '<vuoto>' ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Valori per creazione -->
    <?php if ($model->action === 'create'): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Valori Iniziali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dati inseriti durante la creazione
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Campo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valore</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php
                    $newValues = json_decode($model->new_values, true) ?: [];
                    ksort($newValues);
                    
                    foreach ($newValues as $field => $value):
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?= Html::encode($field) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div class="max-w-md overflow-hidden">
                                <?= Html::encode(is_array($value) ? json_encode($value) : $decryptValue($field, $value)) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Valori per eliminazione -->
    <?php if ($model->action === 'delete'): ?>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Valori Eliminati
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dati presenti prima dell'eliminazione
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Campo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valore</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php
                    $oldValues = json_decode($model->old_values, true) ?: [];
                    ksort($oldValues);
                    
                    foreach ($oldValues as $field => $value):
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?= Html::encode($field) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            <div class="max-w-md overflow-hidden">
                                <span class="text-red-600 dark:text-red-400">
                                    <?= Html::encode(is_array($value) ? json_encode($value) : $decryptValue($field, $value)) ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Log correlati -->
    <?php if ($model->parent_log_id === null): ?>
        <?php
        $childLogs = \common\models\ActivityLog::find()
            ->where(['parent_log_id' => $model->id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        
        if (!empty($childLogs)):
        ?>
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Log Correlati
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Azioni collegate a questa operazione principale
                </p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data/Ora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entità</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Azione</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($childLogs as $log): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                #<?= $log->id ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= Yii::$app->formatter->asDatetime($log->created_at, 'php:d/m/Y H:i:s') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?= Html::encode($log->entity_name) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php
                                $actionInfo = $actions[$log->action] ?? ['label' => $log->action, 'class' => 'bg-gray-100 text-gray-800'];
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $actionInfo['class'] ?>">
                                    <?= $actionInfo['label'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?= Html::a('Dettagli', ['view', 'id' => $log->id], [
                                    'class' => 'text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300'
                                ]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>