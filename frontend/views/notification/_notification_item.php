<?php

use common\models\Notification;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */

$typeConfig = [
    Notification::TYPE_INFO => ['icon' => 'information-circle', 'color' => 'blue'],
    Notification::TYPE_REMINDER => ['icon' => 'bell', 'color' => 'amber'],
    Notification::TYPE_DEADLINE => ['icon' => 'clock', 'color' => 'red'],
    Notification::TYPE_MANDATORY_READ => ['icon' => 'exclamation-triangle', 'color' => 'red'],
    Notification::TYPE_INTERNAL_COMMUNICATION => ['icon' => 'chat-bubble-left-right', 'color' => 'green'],
];

$config = $typeConfig[$model->notification_type] ?? $typeConfig[Notification::TYPE_INFO];
$isUnread = !$model->isRead();
$typeLabel = $model->getTypeOptions()[$model->notification_type] ?? 'Notifica';

$displayUserName = function ($user) {
    if (!$user) {
        return 'Sistema';
    }
    if ($user->profile && ($user->profile->last_name || $user->profile->first_name)) {
        return trim($user->profile->last_name . ' ' . $user->profile->first_name);
    }
    return $user->email ?: $user->username;
};
$senderName = $displayUserName($model->senderUser);
$recipientName = $displayUserName($model->recipientUser);

// Card non lette: sfondo evidenziato + ring brand + bordo colorato sinistra.
// Card lette: bianco neutro + opacity ridotta.
if ($isUnread) {
    $cardClass = 'bg-orange-50 dark:bg-orange-900/15 ring-1 ring-brand-200 dark:ring-brand-800 border-gray-200 dark:border-gray-800';
    $cardStyle = 'box-shadow: inset 4px 0 0 0 #f97316;';
} else {
    $cardClass = 'bg-white dark:bg-white/[0.03] border-gray-200 dark:border-gray-800 opacity-90';
    $cardStyle = 'box-shadow: inset 4px 0 0 0 #d1d5db;';
}
?>

<div class="notification-item <?= $isUnread ? 'unread' : 'read' ?>" data-id="<?= $model->id ?>">
    <div class="rounded-xl border <?= $cardClass ?> p-4 sm:p-6 hover:shadow-md transition-shadow duration-200"
         style="<?= $cardStyle ?>">
        <div class="flex items-start space-x-4">
            <!-- Icona tipo + indicatore non letta -->
            <div class="flex-shrink-0 relative">
                <?php if ($isUnread): ?>
                    <div class="absolute -top-1 -left-1 w-3 h-3 bg-brand-500 rounded-full animate-pulse" title="Non letta"></div>
                <?php endif; ?>

                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-<?= $config['color'] ?>-100 dark:bg-<?= $config['color'] ?>-900/20 rounded-lg flex items-center justify-center">
                    <?php if ($config['icon'] === 'information-circle'): ?>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    <?php elseif ($config['icon'] === 'bell'): ?>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"/>
                        </svg>
                    <?php elseif ($config['icon'] === 'clock'): ?>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    <?php elseif ($config['icon'] === 'exclamation-triangle'): ?>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-<?= $config['color'] ?>-600 dark:text-<?= $config['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4">
                    <div class="flex-1 min-w-0">
                        <!-- Titolo + badge -->
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <h3 class="text-base sm:text-lg <?= $isUnread ? 'font-bold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300' ?> truncate">
                                <?= Html::encode($model->title) ?>
                            </h3>

                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $config['color'] ?>-100 text-<?= $config['color'] ?>-800 dark:bg-<?= $config['color'] ?>-900/20 dark:text-<?= $config['color'] ?>-400">
                                <?= Html::encode($typeLabel) ?>
                            </span>

                            <?php if ($isUnread): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                                    Non letta
                                </span>
                            <?php endif; ?>

                            <?php if (!$model->isSent()): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    Non inviata
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Preview messaggio -->
                        <p class="text-sm <?= $isUnread ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-500' ?> line-clamp-2 mb-3">
                            <?= Html::encode(mb_substr(strip_tags($model->message), 0, 150)) ?><?= mb_strlen($model->message) > 150 ? '...' : '' ?>
                        </p>

                        <!-- Meta -->
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-gray-400">Da:</span>
                                <span class="text-gray-700 dark:text-gray-300"><?= Html::encode($senderName) ?></span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-7a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-gray-400">A:</span>
                                <span class="text-gray-700 dark:text-gray-300"><?= Html::encode($recipientName) ?></span>
                            </span>
                            <span class="inline-flex items-center gap-1" title="<?= Yii::$app->formatter->asDatetime($model->created_at) ?>">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?= Yii::$app->formatter->asRelativeTime($model->created_at) ?>
                            </span>
                            <?php if ($model->isRead()): ?>
                                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400" title="Letta il <?= Yii::$app->formatter->asDatetime($model->read_at) ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Letta <?= Yii::$app->formatter->asRelativeTime($model->read_at) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Azione -->
                    <div class="flex-shrink-0 self-center">
                        <?= Html::a(
                            'Visualizza',
                            ['notification/view', 'id' => $model->id],
                            [
                                'class' => 'inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg text-brand-700 bg-brand-100 border border-brand-200 hover:bg-brand-200 dark:bg-brand-900/20 dark:text-brand-400 dark:border-brand-800 dark:hover:bg-brand-900/40 transition-colors duration-200',
                                'data-pjax' => '0',
                            ]
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
