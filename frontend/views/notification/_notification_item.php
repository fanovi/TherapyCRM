<?php

use common\models\Notification;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */

$typeLabels = $model->getTypeOptions();
$typeColors = [
    Notification::TYPE_INFO => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    Notification::TYPE_REMINDER => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    Notification::TYPE_DEADLINE => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    Notification::TYPE_MANDATORY_READ => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    Notification::TYPE_INTERNAL_COMMUNICATION => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
];
$typeBadgeClass = $typeColors[$model->notification_type] ?? $typeColors[Notification::TYPE_INFO];
$typeLabel = $typeLabels[$model->notification_type] ?? 'Notifica';
$isUnread = !$model->isRead();

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

$preview = mb_substr(strip_tags($model->message), 0, 140);
if (mb_strlen($model->message) > 140) { $preview .= '...'; }
?>

<div class="notification-item <?= $isUnread ? 'unread' : 'read' ?>" data-id="<?= (int) $model->id ?>">
    <div class="rounded-2xl border <?= $isUnread ? 'border-orange-200 bg-orange-50/30 dark:border-orange-800 dark:bg-orange-900/10' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]' ?> p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-colors">
        <div class="flex items-start gap-3">
            <!-- Indicatore non letta -->
            <div class="flex-shrink-0 mt-1.5">
                <span class="block w-2.5 h-2.5 rounded-full <?= $isUnread ? 'bg-orange-500' : 'bg-gray-300 dark:bg-gray-600' ?>"></span>
            </div>

            <div class="flex-1 min-w-0">
                <!-- Riga 1: titolo + badges -->
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                        <?= Html::encode($model->title) ?>
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $typeBadgeClass ?>">
                        <?= Html::encode($typeLabel) ?>
                    </span>
                    <?php if (!$model->isSent()): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            Non inviata
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Anteprima -->
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-2">
                    <?= Html::encode($preview) ?>
                </p>

                <!-- Meta -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>
                        <span class="text-gray-400 dark:text-gray-500">Da:</span>
                        <span class="text-gray-700 dark:text-gray-300"><?= Html::encode($senderName) ?></span>
                    </span>
                    <span>
                        <span class="text-gray-400 dark:text-gray-500">A:</span>
                        <span class="text-gray-700 dark:text-gray-300"><?= Html::encode($recipientName) ?></span>
                    </span>
                    <span title="<?= Yii::$app->formatter->asDatetime($model->created_at) ?>">
                        <?= Yii::$app->formatter->asRelativeTime($model->created_at) ?>
                    </span>
                </div>
            </div>

            <!-- Azione -->
            <div class="flex-shrink-0 self-center">
                <?= Html::a(
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
                    ['notification/view', 'id' => $model->id],
                    [
                        'title' => 'Visualizza dettagli',
                        'class' => 'inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:text-blue-900 hover:bg-blue-50 dark:text-blue-400 dark:hover:text-blue-300 dark:hover:bg-blue-900/20',
                        'data-pjax' => '0',
                    ]
                ) ?>
            </div>
        </div>
    </div>
</div>
