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
/* @var $q string */

$this->title = 'Notifiche';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/notifications.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsVar('apiStatsUrl', Url::to(['notification/stats-api']));

$q = isset($q) ? $q : '';
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            <?= Html::encode($this->title) ?>
        </h2>

        <div class="flex items-center gap-3">
            <button
                id="refresh-btn"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Aggiorna
            </button>
        </div>
    </div>

    <!-- Filtri/Tab orizzontali -->
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Tab stato -->
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stato:</span>
                    <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                        <?= Html::a('Tutte', ['notification/index', 'type' => $currentType !== 'all' ? $currentType : null, 'q' => $q ?: null], [
                            'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 ' .
                                ($currentStatus === 'all' ?
                                    'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400 shadow-sm' :
                                    'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-700/50')
                        ]) ?>

                        <?= Html::a('Non lette' . ($unreadCount > 0 ? ' (' . $unreadCount . ')' : ''), ['notification/index', 'status' => 'unread', 'type' => $currentType !== 'all' ? $currentType : null, 'q' => $q ?: null], [
                            'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 ' .
                                ($currentStatus === 'unread' ?
                                    'bg-white dark:bg-gray-700 text-orange-600 dark:text-orange-400 shadow-sm' :
                                    'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-700/50')
                        ]) ?>

                        <?= Html::a('Lette', ['notification/index', 'status' => 'read', 'type' => $currentType !== 'all' ? $currentType : null, 'q' => $q ?: null], [
                            'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 ' .
                                ($currentStatus === 'read' ?
                                    'bg-white dark:bg-gray-700 text-green-600 dark:text-green-400 shadow-sm' :
                                    'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-700/50')
                        ]) ?>
                    </div>
                </div>

                <!-- Contatori -->
                <div class="flex items-center space-x-3 ml-auto">
                    <div class="flex items-center space-x-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Totale:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                            <?= (int) $totalCount ?>
                        </span>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <div class="flex items-center space-x-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Non lette:</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                                <?= (int) $unreadCount ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab tipo -->
            <div class="mt-4 flex flex-wrap items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-2">Tipo:</span>
                <?php
                $chipBase = 'inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full border transition-colors';
                $chipOn = 'bg-brand-500 text-white border-brand-500';
                $chipOff = 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700';
                ?>
                <?= Html::a('Tutti', ['notification/index', 'status' => $currentStatus !== 'all' ? $currentStatus : null, 'q' => $q ?: null], [
                    'class' => $chipBase . ' ' . ($currentType === 'all' ? $chipOn : $chipOff),
                    'data-pjax' => '0',
                ]) ?>
                <?php foreach (Notification::getTypeOptions() as $typeKey => $typeLabel): ?>
                    <?= Html::a(
                        $typeLabel . ' (' . ($typeStats[$typeKey]['count'] ?? 0) . ')',
                        ['notification/index', 'type' => $typeKey, 'status' => $currentStatus !== 'all' ? $currentStatus : null, 'q' => $q ?: null],
                        [
                            'class' => $chipBase . ' ' . ($currentType === $typeKey ? $chipOn : $chipOff),
                            'data-pjax' => '0',
                        ]
                    ) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Search testo -->
    <div class="mb-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none" style="padding-left: 12px;">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16.65 16.65A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6z"></path>
                </svg>
            </div>
            <input
                type="search"
                id="notif-search"
                value="<?= Html::encode($q) ?>"
                autocomplete="off"
                placeholder="Cerca nel testo (min 3 caratteri)..."
                style="padding-left: 40px; padding-right: 40px;"
                class="w-full py-2.5 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
            >
            <button
                type="button"
                id="notif-search-clear"
                style="padding-right: 12px;"
                class="absolute inset-y-0 right-0 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 <?= $q === '' ? 'hidden' : '' ?>"
                title="Cancella ricerca">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div id="notif-search-spinner" style="right: 32px; padding-right: 8px;" class="absolute inset-y-0 hidden items-center text-gray-400">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Lista notifiche -->
    <?php Pjax::begin(['id' => 'notifications-pjax', 'enablePushState' => true, 'timeout' => 8000]); ?>

    <?php if ($q !== ''): ?>
        <div class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Filtro attivo: "<span class="font-medium text-gray-700 dark:text-gray-300"><?= Html::encode($q) ?></span>"
            · <?= $dataProvider->getTotalCount() ?> risultati
        </div>
    <?php endif; ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['tag' => false],
        'itemView' => '_notification_item',
        'layout' => '<div class="space-y-4">{items}</div><div class="mt-6">{pager}</div>',
        'emptyText' => $this->render('_empty_state', [
            'currentType' => $currentType,
            'currentStatus' => $currentStatus,
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

    document.getElementById('refresh-btn')?.addEventListener('click', function() {
        window.location.reload();
    });

    // Search testo: debounce 350ms, min 3 char, Pjax submit
    (function() {
        const input = document.getElementById('notif-search');
        const clearBtn = document.getElementById('notif-search-clear');
        const spinner = document.getElementById('notif-search-spinner');
        const pjaxId = 'notifications-pjax';
        if (!input) return;

        let debounceTimer = null;
        let lastSent = input.value.trim();

        function currentParams() {
            return new URLSearchParams(window.location.search);
        }

        function buildUrl(q) {
            const params = currentParams();
            if (q) params.set('q', q); else params.delete('q');
            const qs = params.toString();
            return window.location.pathname + (qs ? '?' + qs : '');
        }

        function doSearch(q) {
            if (q === lastSent) return;
            lastSent = q;
            spinner?.classList.remove('hidden');
            spinner?.classList.add('flex');
            if (typeof $.pjax !== 'undefined') {
                $.pjax({
                    url: buildUrl(q),
                    container: '#' + pjaxId,
                    push: true,
                    timeout: 8000,
                    scrollTo: false,
                });
            } else {
                window.location.href = buildUrl(q);
            }
        }

        function onInput() {
            const v = input.value.trim();
            clearBtn?.classList.toggle('hidden', v === '');
            clearTimeout(debounceTimer);
            if (v === '' || v.length >= 3) {
                debounceTimer = setTimeout(() => doSearch(v), 350);
            }
        }

        input.addEventListener('input', onInput);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                input.value = '';
                onInput();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                const v = input.value.trim();
                if (v === '' || v.length >= 3) doSearch(v);
            }
        });

        clearBtn?.addEventListener('click', () => {
            input.value = '';
            clearBtn.classList.add('hidden');
            doSearch('');
            input.focus();
        });

        $(document).on('pjax:end', '#' + pjaxId, function() {
            spinner?.classList.add('hidden');
            spinner?.classList.remove('flex');
        });
    })();
});
</script>
