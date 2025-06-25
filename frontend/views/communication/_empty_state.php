<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $currentType string */

$messages = [
    'all' => [
        'title' => 'Nessuna comunicazione',
        'subtitle' => 'Al momento non hai ricevuto alcuna comunicazione.',
        'icon' => 'chat-bubble-left-right'
    ],
    'unread' => [
        'title' => 'Nessuna comunicazione non letta',
        'subtitle' => 'Tutte le comunicazioni sono state lette.',
        'icon' => 'check-circle'
    ],
    'internal_communication' => [
        'title' => 'Nessuna comunicazione di sistema',
        'subtitle' => 'Non ci sono comunicazioni generate dal sistema.',
        'icon' => 'cog'
    ]
];

$message = $messages[$currentType] ?? $messages['all'];
?>

<div class="flex flex-col items-center justify-center py-12 px-4">
    <div class="text-center">
        <!-- Icona -->
        <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
            <?php if ($message['icon'] === 'chat-bubble-left-right'): ?>
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
            <?php elseif ($message['icon'] === 'check-circle'): ?>
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            <?php else: // cog ?>
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            <?php endif; ?>
        </div>

        <!-- Titolo -->
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            <?= Html::encode($message['title']) ?>
        </h3>

        <!-- Sottotitolo -->
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm">
            <?= Html::encode($message['subtitle']) ?>
        </p>

        <!-- Azioni -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php if ($currentType !== 'all'): ?>
                <?= Html::a('Visualizza tutte', ['communication/index', 'type' => 'all'], [
                    'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
            <?php endif; ?>
            
            <button
                onclick="window.location.reload()"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Aggiorna
            </button>
        </div>
    </div>
</div> 