<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Notification;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */

// Determina l'icona e il colore in base al tipo
$typeConfig = [
    Notification::TYPE_INFO => ['icon' => 'information-circle', 'color' => 'blue'],
    Notification::TYPE_REMINDER => ['icon' => 'bell', 'color' => 'amber'],
    Notification::TYPE_DEADLINE => ['icon' => 'clock', 'color' => 'red'],
    Notification::TYPE_MANDATORY_READ => ['icon' => 'exclamation-triangle', 'color' => 'red'],
    Notification::TYPE_INTERNAL_COMMUNICATION => ['icon' => 'chat-bubble-left-right', 'color' => 'green'],
];

$config = $typeConfig[$model->notification_type] ?? $typeConfig[Notification::TYPE_INFO];
$isUnread = !$model->isRead();
?>

<div class="communication-item <?= $isUnread ? 'unread' : 'read' ?>" data-id="<?= $model->id ?>">
    <div class="bg-white dark:bg-gray-900 rounded-xl border <?= $isUnread ? 'border-brand-200 dark:border-brand-800' : 'border-gray-200 dark:border-gray-800' ?> p-4 hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-start space-x-4">
            <!-- Icona e indicatore non letta -->
            <div class="flex-shrink-0 relative">
                <div class="w-10 h-10 bg-<?= $config['color'] ?>-100 dark:bg-<?= $config['color'] ?>-900/20 rounded-lg flex items-center justify-center">
                    <!-- Icona SVG basata sul tipo -->
                    <?php if ($config['icon'] === 'information-circle'): ?>
                        <svg class="w-5 h-5 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php elseif ($config['icon'] === 'bell'): ?>
                        <svg class="w-5 h-5 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
                        </svg>
                    <?php elseif ($config['icon'] === 'clock'): ?>
                        <svg class="w-5 h-5 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php elseif ($config['icon'] === 'exclamation-triangle'): ?>
                        <svg class="w-5 h-5 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    <?php else: // chat-bubble-left-right ?>
                        <svg class="w-5 h-5 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                
                <!-- Indicatore non letta -->
                <?php if ($isUnread): ?>
                    <div class="absolute -top-1 -right-1 w-3 h-3 bg-brand-500 rounded-full border-2 border-white dark:border-gray-900"></div>
                <?php endif; ?>
            </div>

            <!-- Contenuto principale -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Titolo -->
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            <?= Html::encode($model->title) ?>
                        </h3>
                        
                        <!-- Badge tipo -->
                        <div class="mt-1 flex items-center space-x-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-<?= $config['color'] ?>-100 text-<?= $config['color'] ?>-800 dark:bg-<?= $config['color'] ?>-900/20 dark:text-<?= $config['color'] ?>-400">
                                <?= Html::encode(Notification::getTypeOptions()[$model->notification_type] ?? 'Comunicazione') ?>
                            </span>
                            
                            <?php if ($isUnread): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                                    Non letta
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Preview messaggio -->
                        <?php if ($model->message): ?>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                <?= Html::encode(mb_substr(strip_tags($model->message), 0, 120)) ?>
                                <?= mb_strlen($model->message) > 120 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Meta informazioni -->
                        <div class="mt-3 flex items-center text-xs text-gray-500 dark:text-gray-400 space-x-4">
                            <span class="flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <?= Yii::$app->formatter->asRelativeTime($model->created_at) ?>
                            </span>
                            
                            <?php if ($model->senderUser): ?>
                                <span class="flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <?= Html::encode($model->senderUser->username) ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($model->isRead()): ?>
                                <span class="flex items-center text-green-600 dark:text-green-400">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Letta <?= Yii::$app->formatter->asRelativeTime($model->read_at) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Azioni -->
                    <div class="flex items-center space-x-2 ml-4">
                        <?php if ($isUnread): ?>
                            <button
                                class="mark-read-btn inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/40"
                                data-id="<?= $model->id ?>">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Segna come letta
                            </button>
                        <?php endif; ?>
                        
                        <?= Html::a('Visualizza', ['communication/view', 'id' => $model->id], [
                            'class' => 'inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-brand-700 bg-brand-100 hover:bg-brand-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:bg-brand-900/20 dark:text-brand-400 dark:hover:bg-brand-900/40'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 