<?php

use common\models\Notification;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $totalCount int */
/* @var $unreadCount int */
/* @var $sentCount int */
/* @var $unsentCount int */
/* @var $typeStats array */
/* @var $currentType string */
/* @var $currentStatus string */

$this->title = 'Notifiche';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/notifications.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsVar('apiStatsUrl', Url::to(['notification/stats-api']));

$filterChip = function (string $label, array $url, bool $active): string {
    $base = 'inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-colors';
    $on = 'bg-brand-500 text-white border-brand-500';
    $off = 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700';
    return Html::a(Html::encode($label), $url, [
        'class' => $base . ' ' . ($active ? $on : $off),
        'data-pjax' => '0',
    ]);
};
?>

<div class="mx-auto max-w-5xl p-4 md:p-6">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Notifiche</h2>
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
                <li class="text-sm text-gray-800 dark:text-white/90">Notifiche</li>
            </ol>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <!-- Totale -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                    <svg class="w-4 h-4 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Totale</span>
                    <p class="text-lg font-bold text-gray-800 dark:text-white/90"><?= (int) $totalCount ?></p>
                </div>
            </div>
        </div>

        <!-- Non lette -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-500/15">
                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Non lette</span>
                    <p class="text-lg font-bold text-gray-800 dark:text-white/90"><?= (int) $unreadCount ?></p>
                </div>
            </div>
        </div>

        <!-- Inviate -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/15">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Inviate</span>
                    <p class="text-lg font-bold text-gray-800 dark:text-white/90"><?= (int) $sentCount ?></p>
                </div>
            </div>
        </div>

        <!-- Non inviate -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Non inviate</span>
                    <p class="text-lg font-bold text-gray-800 dark:text-white/90"><?= (int) $unsentCount ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri -->
    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <!-- Filtri Stato -->
        <div class="mb-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase">Stato</p>
            <div class="flex flex-wrap gap-2">
                <?= $filterChip('Tutti', ['notification/index', 'type' => $currentType !== 'all' ? $currentType : null], $currentStatus === 'all') ?>
                <?= $filterChip('Non lette (' . $unreadCount . ')', ['notification/index', 'status' => 'unread', 'type' => $currentType !== 'all' ? $currentType : null], $currentStatus === 'unread') ?>
                <?= $filterChip('Lette', ['notification/index', 'status' => 'read', 'type' => $currentType !== 'all' ? $currentType : null], $currentStatus === 'read') ?>
            </div>
        </div>

        <!-- Filtri Tipo -->
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase">Tipo</p>
            <div class="flex flex-wrap gap-2">
                <?= $filterChip('Tutti', ['notification/index', 'status' => $currentStatus !== 'all' ? $currentStatus : null], $currentType === 'all') ?>
                <?php foreach (Notification::getTypeOptions() as $typeKey => $typeLabel): ?>
                    <?= $filterChip(
                        $typeLabel . ' (' . ($typeStats[$typeKey]['count'] ?? 0) . ')',
                        ['notification/index', 'type' => $typeKey, 'status' => $currentStatus !== 'all' ? $currentStatus : null],
                        $currentType === $typeKey
                    ) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Lista notifiche -->
    <?php Pjax::begin(['id' => 'notifications-pjax', 'enablePushState' => false]); ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['tag' => false],
        'itemView' => '_notification_item',
        'layout' => '<div class="space-y-3">{items}</div><div class="mt-6">{pager}</div>',
        'emptyText' => $this->render('_empty_state', [
            'currentType' => $currentType,
            'currentStatus' => $currentStatus
        ]),
        'pager' => [
            'class' => \yii\widgets\LinkPager::class,
            'options' => ['class' => 'flex items-center justify-center gap-1'],
            'linkOptions' => ['class' => 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'],
            'activePageCssClass' => 'bg-brand-500 text-white border-brand-500 hover:bg-brand-600',
            'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
            'prevPageLabel' => '&laquo;',
            'nextPageLabel' => '&raquo;',
            'maxButtonCount' => 5,
        ],
    ]) ?>

    <?php Pjax::end(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.NotificationSystem !== 'undefined') {
        window.NotificationSystem.init();
    }
});
</script>
