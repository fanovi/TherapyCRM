<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Notification;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */

$this->title = 'Comunicazione: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Comunicazioni', 'url' => ['index']];
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

// JavaScript per azioni
$this->registerJs("
document.getElementById('mark-read-btn')?.addEventListener('click', function() {
    fetch('" . Url::to(['communication/mark-read', 'id' => $model->id]) . "', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});
");
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <?= Html::a('← Torna alle comunicazioni', ['communication/index'], [
                'class' => 'inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
            ]) ?>
        </div>
        
        <div class="flex items-center space-x-3">
            <?php if ($isUnread): ?>
                <button
                    id="mark-read-btn"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Segna come letta
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <!-- Header comunicazione -->
        <div class="border-b border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-start space-x-4">
                <!-- Icona -->
                <div class="flex-shrink-0 relative">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Indicatore non letta -->
                    <?php if ($isUnread): ?>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-brand-500 rounded-full border-2 border-white dark:border-gray-900"></div>
                    <?php endif; ?>
                </div>

                <!-- Contenuto header -->
                <div class="flex-1 min-w-0">
                    <!-- Titolo -->
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        <?= Html::encode($model->title) ?>
                    </h1>
                    
                    <!-- Badge e info -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?= $config['color'] ?>-100 text-<?= $config['color'] ?>-800 dark:bg-<?= $config['color'] ?>-900/20 dark:text-<?= $config['color'] ?>-400">
                            <?= Html::encode(Notification::getTypeOptions()[$model->notification_type] ?? 'Comunicazione') ?>
                        </span>
                        
                        <?php if ($isUnread): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                                Non letta
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Letta
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenuto messaggio -->
        <div class="p-6">
            <?php if ($model->message): ?>
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    <?= $model->message ?>
                </div>
            <?php else: ?>
                <div class="text-gray-500 dark:text-gray-400 italic">
                    Nessun contenuto aggiuntivo per questa comunicazione.
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer con metadati -->
        <div class="border-t border-gray-200 dark:border-gray-800 px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data creazione</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <?= Yii::$app->formatter->asDatetime($model->created_at) ?>
                    </dd>
                </div>
                
                <?php if ($model->senderUser): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mittente</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= Html::encode($model->senderUser->username) ?>
                        </dd>
                    </div>
                <?php endif; ?>
                
                <?php if ($model->isViewed()): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Prima visualizzazione</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= Yii::$app->formatter->asDatetime($model->viewed_at) ?>
                        </dd>
                    </div>
                <?php endif; ?>
                
                <?php if ($model->isRead()): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Letta il</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= Yii::$app->formatter->asDatetime($model->read_at) ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</div> 