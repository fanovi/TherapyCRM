<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Notification;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */

$this->title = 'Notifica: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Notifiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Configurazione tipo
$typeConfig = [
    Notification::TYPE_INFO => ['icon' => 'information-circle', 'color' => 'blue'],
    Notification::TYPE_REMINDER => ['icon' => 'bell', 'color' => 'amber'],
    Notification::TYPE_DEADLINE => ['icon' => 'clock', 'color' => 'red'],
    Notification::TYPE_MANDATORY_READ => ['icon' => 'exclamation-triangle', 'color' => 'red'],
    Notification::TYPE_INTERNAL_COMMUNICATION => ['icon' => 'chat-bubble-left-right', 'color' => 'green'],
];

$config = $typeConfig[$model->notification_type] ?? $typeConfig[Notification::TYPE_INFO];
$isUnread = !$model->isRead();
$isSent = $model->isSent();
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                    Dettagli Notifica
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Visualizza i dettagli completi della notifica selezionata
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <?= Html::a('← Torna alle notifiche', ['notification/index'], [
                    'class' => 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        
        <!-- Header notifica -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-start space-x-4">
                <!-- Icona -->
                <div class="flex-shrink-0 relative">
                    <?php if ($isUnread): ?>
                        <div class="absolute -top-1 -left-1 w-3 h-3 bg-brand-500 rounded-full animate-pulse"></div>
                    <?php endif; ?>
                    
                    <div class="w-12 h-12 bg-<?= $config['color'] ?>-100 dark:bg-<?= $config['color'] ?>-900/20 rounded-lg flex items-center justify-center">
                        <?php if ($config['icon'] === 'information-circle'): ?>
                            <svg class="w-6 h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        <?php elseif ($config['icon'] === 'bell'): ?>
                            <svg class="w-6 h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
                            </svg>
                        <?php elseif ($config['icon'] === 'clock'): ?>
                            <svg class="w-6 h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        <?php elseif ($config['icon'] === 'exclamation-triangle'): ?>
                            <svg class="w-6 h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        <?php else: // chat-bubble-left-right ?>
                            <svg class="w-6 h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Titolo e badges -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <?= Html::encode($model->title) ?>
                        </h2>
                        
                        <!-- Badge tipo -->
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?= $config['color'] ?>-100 text-<?= $config['color'] ?>-800 dark:bg-<?= $config['color'] ?>-900/20 dark:text-<?= $config['color'] ?>-400">
                            <?= $model->getTypeOptions()[$model->notification_type] ?? 'Notifica' ?>
                        </span>
                        
                        <!-- Badge stato lettura -->
                        <?php if ($isUnread): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                                Non letta
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                Letta
                            </span>
                        <?php endif; ?>
                        
                        <!-- Badge stato invio -->
                        <?php if ($isSent): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                Inviata
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                Non inviata
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Informazioni utenti -->
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span><strong>Da:</strong> <?= $model->senderUser ? Html::encode($model->senderUser->username) : 'Sistema' ?></span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <span><strong>A:</strong> <?= $model->recipientUser ? Html::encode($model->recipientUser->username) : 'Utente' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messaggio -->
        <div class="px-6 py-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Messaggio</h3>
            <div class="prose dark:prose-invert max-w-none">
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 text-gray-700 dark:text-gray-300">
                    <?= nl2br(Html::encode($model->message)) ?>
                </div>
            </div>
        </div>

        <!-- Timeline eventi -->
        <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Timeline</h3>
            
            <div class="flow-root">
                <ul class="-mb-8">
                    <!-- Creazione -->
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Notifica <span class="font-medium text-gray-900 dark:text-white">creata</span>
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time datetime="<?= $model->created_at ?>"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Programmazione (se esiste) -->
                    <?php if ($model->isScheduled()): ?>
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Notifica <span class="font-medium text-gray-900 dark:text-white">programmata per l'invio</span>
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time datetime="<?= $model->scheduled_for ?>"><?= Yii::$app->formatter->asDatetime($model->scheduled_for) ?></time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>

                    <!-- Invio (se esiste) -->
                    <?php if ($model->isSent()): ?>
                    <li>
                        <div class="relative pb-8">
                            <?php if ($model->isRead()): ?>
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            <?php endif; ?>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Notifica <span class="font-medium text-gray-900 dark:text-white">inviata</span>
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time datetime="<?= $model->sent_at ?>"><?= Yii::$app->formatter->asDatetime($model->sent_at) ?></time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>

                    <!-- Lettura (se esiste) -->
                    <?php if ($model->isRead()): ?>
                    <li>
                        <div class="relative">
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Notifica <span class="font-medium text-gray-900 dark:text-white">letta dal destinatario</span>
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <time datetime="<?= $model->read_at ?>"><?= Yii::$app->formatter->asDatetime($model->read_at) ?></time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Dettagli tecnici -->
        <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Dettagli Tecnici</h3>
            
            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Notifica</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= $model->id ?></dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Richiede Conferma Lettura</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <?= $model->requires_read_confirmation ? 'Sì' : 'No' ?>
                    </dd>
                </div>
                
                <?php if ($model->isViewed()): ?>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Prima Visualizzazione</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <?= Yii::$app->formatter->asDatetime($model->viewed_at) ?>
                    </dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</div>
