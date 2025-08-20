<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $currentType string */
/* @var $currentStatus string */

$message = 'Nessuna notifica trovata';
$icon = 'bell-slash';

// Personalizza il messaggio in base ai filtri
if ($currentType !== 'all' && $currentStatus !== 'all') {
    $typeLabels = \common\models\Notification::getTypeOptions();
    $typeName = $typeLabels[$currentType] ?? 'del tipo selezionato';
    
    $statusName = match($currentStatus) {
        'unread' => 'non lette',
        'read' => 'lette',
        'sent' => 'inviate',
        'unsent' => 'non inviate',
        default => 'con lo stato selezionato'
    };
    
    $message = "Nessuna notifica {$typeName} {$statusName} trovata.";
} elseif ($currentType !== 'all') {
    $typeLabels = \common\models\Notification::getTypeOptions();
    $typeName = $typeLabels[$currentType] ?? 'del tipo selezionato';
    $message = "Nessuna notifica {$typeName} trovata.";
} elseif ($currentStatus !== 'all') {
    $statusName = match($currentStatus) {
        'unread' => 'non lette',
        'read' => 'lette', 
        'sent' => 'inviate',
        'unsent' => 'non inviate',
        default => 'con lo stato selezionato'
    };
    $message = "Nessuna notifica {$statusName} trovata.";
}
?>

<div class="text-center py-12">
    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-800">
        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"></path>
        </svg>
    </div>
    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        <?= Html::encode($message) ?>
    </h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        <?php if ($currentType !== 'all' || $currentStatus !== 'all'): ?>
            Prova a modificare i filtri per visualizzare altre notifiche.
        <?php else: ?>
            Al momento non ci sono notifiche nel sistema.
        <?php endif; ?>
    </p>
    
    <?php if ($currentType !== 'all' || $currentStatus !== 'all'): ?>
        <div class="mt-6">
            <?= Html::a('Visualizza tutte le notifiche', ['notification/index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500'
            ]) ?>
        </div>
    <?php endif; ?>
</div>
