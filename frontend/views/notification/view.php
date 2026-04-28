<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Notification;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */

$this->title = 'Notifica: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Notifiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Mappa tipi -> badge color + label.
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
?>

<div class="mx-auto max-w-3xl p-4 md:p-6">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Dettaglio Notifica</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/notification/index']) ?>">
                        Notifiche
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90">Notifica #<?= (int) $model->id ?></li>
            </ol>
        </nav>
    </div>

    <!-- Card Informazioni -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] mb-4">
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $typeBadgeClass ?>">
                <?= Html::encode($typeLabel) ?>
            </span>
            <?php if ($model->isRead()): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Letta
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300">
                    Non letta
                </span>
            <?php endif; ?>
            <?php if ($model->isSent()): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                    Inviata
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    Non inviata
                </span>
            <?php endif; ?>
            <?php if ($model->requires_read_confirmation): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                    Conferma richiesta
                </span>
            <?php endif; ?>
        </div>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white/90 mb-3">
            <?= Html::encode($model->title) ?>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">Da</p>
                <p class="text-gray-800 dark:text-gray-200"><?= Html::encode($senderName) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase font-medium text-gray-500 dark:text-gray-400 mb-1">A</p>
                <p class="text-gray-800 dark:text-gray-200"><?= Html::encode($recipientName) ?></p>
            </div>
        </div>
    </div>

    <!-- Card Messaggio -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] mb-4">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 mb-3">Messaggio</h4>
        <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">
            <?= nl2br(Html::encode($model->message)) ?>
        </div>
    </div>

    <!-- Card Cronologia -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] mb-4">
        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 mb-4">Cronologia</h4>
        <ul class="space-y-3 text-sm">
            <li class="flex items-start gap-3">
                <span class="mt-0.5 w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                <div class="flex-1 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-gray-700 dark:text-gray-300">Creata</span>
                    <time class="text-xs text-gray-500 dark:text-gray-400" datetime="<?= Html::encode($model->created_at) ?>">
                        <?= Yii::$app->formatter->asDatetime($model->created_at) ?>
                    </time>
                </div>
            </li>
            <?php if ($model->isScheduled()): ?>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                    <div class="flex-1 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-gray-700 dark:text-gray-300">Programmata per l'invio</span>
                        <time class="text-xs text-gray-500 dark:text-gray-400" datetime="<?= Html::encode($model->scheduled_for) ?>">
                            <?= Yii::$app->formatter->asDatetime($model->scheduled_for) ?>
                        </time>
                    </div>
                </li>
            <?php endif; ?>
            <?php if ($model->isSent()): ?>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                    <div class="flex-1 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-gray-700 dark:text-gray-300">Inviata</span>
                        <time class="text-xs text-gray-500 dark:text-gray-400" datetime="<?= Html::encode($model->sent_at) ?>">
                            <?= Yii::$app->formatter->asDatetime($model->sent_at) ?>
                        </time>
                    </div>
                </li>
            <?php endif; ?>
            <?php if ($model->isViewed()): ?>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 w-2 h-2 rounded-full bg-purple-500 flex-shrink-0"></span>
                    <div class="flex-1 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-gray-700 dark:text-gray-300">Prima visualizzazione</span>
                        <time class="text-xs text-gray-500 dark:text-gray-400" datetime="<?= Html::encode($model->viewed_at) ?>">
                            <?= Yii::$app->formatter->asDatetime($model->viewed_at) ?>
                        </time>
                    </div>
                </li>
            <?php endif; ?>
            <?php if ($model->isRead()): ?>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <div class="flex-1 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-gray-700 dark:text-gray-300">Letta dal destinatario</span>
                        <time class="text-xs text-gray-500 dark:text-gray-400" datetime="<?= Html::encode($model->read_at) ?>">
                            <?= Yii::$app->formatter->asDatetime($model->read_at) ?>
                        </time>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Footer azioni -->
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span>ID notifica: #<?= (int) $model->id ?></span>
        <?= Html::a('Torna alle notifiche', ['index'], [
            'class' => 'inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700',
            'data-pjax' => '0',
        ]) ?>
    </div>
</div>
