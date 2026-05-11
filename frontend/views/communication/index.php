<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use common\models\Notification;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $totalCount int */
/* @var $unreadCount int */
/* @var $internalCount int */
/* @var $currentType string */
/* @var $q string */

$this->title = 'Comunicazioni';
$this->params['breadcrumbs'][] = $this->title;

// Asset per JavaScript delle comunicazioni
$this->registerJsFile('@web/js/communications.js', ['depends' => [\yii\web\JqueryAsset::class]]);

// Registra gli URL per i nuovi endpoint API
$this->registerJsVar('apiMarkReadUrl', Url::to(['communication/mark-read-api']));
$this->registerJsVar('apiStatsUrl', Url::to(['communication/stats-api']));
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb e Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            <?= Html::encode($this->title) ?>
        </h2>
        
        <!-- Azioni -->
        <div class="flex items-center gap-3">
            <?php if ($unreadCount > 0): ?>
                <button
                    id="mark-all-read-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Segna tutte come lette
                </button>
            <?php endif; ?>
            
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

    <!-- Filtri/Tab - Layout orizzontale fisso -->
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <!-- Mobile: Stack verticale -->
            <!-- <div class="block sm:hidden">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">Filtra per:</label>
                <div class="space-y-2">
                    <?= Html::a('Tutte (' . $totalCount . ')', ['communication/index', 'type' => 'all'], [
                        'class' => 'w-full justify-center inline-flex items-center px-4 py-3 text-sm font-medium rounded-lg border ' . 
                                   ($currentType === 'all' ? 
                                    'bg-brand-500 text-white border-brand-500' : 
                                    'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
                    ]) ?>
                    
                    <?= Html::a('Non lette (' . $unreadCount . ')', ['communication/index', 'type' => 'unread'], [
                        'class' => 'w-full justify-center inline-flex items-center px-4 py-3 text-sm font-medium rounded-lg border ' . 
                                   ($currentType === 'unread' ? 
                                    'bg-orange-500 text-white border-orange-500' : 
                                    'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
                    ]) ?>
                    
                    <?= Html::a('Sistema (' . $internalCount . ')', ['communication/index', 'type' => 'internal_communication'], [
                        'class' => 'w-full justify-center inline-flex items-center px-4 py-3 text-sm font-medium rounded-lg border ' . 
                                   ($currentType === 'internal_communication' ? 
                                    'bg-green-500 text-white border-green-500' : 
                                    'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
                    ]) ?>
                </div>
            </div> -->

            <!-- Desktop: Tab orizzontali -->
            <div class="hidden sm:block">
                <div class="flex items-center space-x-1">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mr-4">Filtra per:</span>
                    
                    <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                        <?= Html::a('Tutte', ['communication/index', 'type' => 'all', 'q' => $q ?: null], [
                            'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 ' . 
                                       ($currentType === 'all' ? 
                                        'bg-white dark:bg-gray-700 text-brand-600 dark:text-brand-400 shadow-sm' : 
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-700/50')
                        ]) ?>
                        
                        <?= Html::a('Non lette', ['communication/index', 'type' => 'unread', 'q' => $q ?: null], [
                            'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 ' . 
                                       ($currentType === 'unread' ? 
                                        'bg-white dark:bg-gray-700 text-orange-600 dark:text-orange-400 shadow-sm' : 
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-700/50')
                        ]) ?>
                        
                        <?= Html::a('Sistema', ['communication/index', 'type' => 'internal_communication', 'q' => $q ?: null], [
                            'class' => 'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 ' . 
                                       ($currentType === 'internal_communication' ? 
                                        'bg-white dark:bg-gray-700 text-green-600 dark:text-green-400 shadow-sm' : 
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-700/50')
                        ]) ?>
                    </div>
                    
                    <!-- Contatori -->
                    <div class="flex items-center space-x-3 ml-6">
                        <div class="flex items-center space-x-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Totali:</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                <?= $totalCount ?>
                            </span>
                        </div>
                        <?php if ($unreadCount > 0): ?>
                            <div class="flex items-center space-x-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Non lette:</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                                    <?= $unreadCount ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($internalCount > 0): ?>
                            <div class="flex items-center space-x-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Sistema:</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    <?= $internalCount ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search testo -->
    <div class="mb-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16.65 16.65A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6z"></path>
                </svg>
            </div>
            <input
                type="search"
                id="comm-search"
                value="<?= Html::encode($q) ?>"
                autocomplete="off"
                placeholder="Cerca nel testo (min 3 caratteri)..."
                class="w-full pl-10 pr-10 py-2.5 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
            >
            <button
                type="button"
                id="comm-search-clear"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 <?= $q === '' ? 'hidden' : '' ?>"
                title="Cancella ricerca">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div id="comm-search-spinner" class="absolute inset-y-0 right-8 pr-2 hidden items-center text-gray-400">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Lista Comunicazioni -->
    <?php Pjax::begin(['id' => 'communications-pjax', 'enablePushState' => true, 'timeout' => 8000]); ?>

    <?php if ($q !== ''): ?>
        <div class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Filtro attivo: "<span class="font-medium text-gray-700 dark:text-gray-300"><?= Html::encode($q) ?></span>"
            · <?= $dataProvider->getTotalCount() ?> risultati
        </div>
    <?php endif; ?>
    
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'item'],
        'itemView' => '_communication_item',
        'layout' => '<div class="space-y-4">{items}</div>{pager}',
        'emptyText' => $this->render('_empty_state', ['currentType' => $currentType]),
        'pager' => [
            'class' => \yii\widgets\LinkPager::class,
            'options' => ['class' => 'flex justify-center mt-6'],
            'linkOptions' => ['class' => 'px-3 py-2 mx-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300'],
            'activePageCssClass' => 'bg-brand-500 text-white border-brand-500 hover:bg-brand-600',
            'disabledPageCssClass' => 'text-gray-300 cursor-not-allowed',
            'maxButtonCount' => 5,
        ],
    ]) ?>
    
    <?php Pjax::end(); ?>
</div>

<!-- Script per gestione AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.CommunicationSystem !== 'undefined') {
        window.CommunicationSystem.init();
    }

    document.getElementById('refresh-btn')?.addEventListener('click', function() {
        window.location.reload();
    });

    // Search testo: debounce 350ms, min 3 char, Pjax submit
    (function() {
        const input = document.getElementById('comm-search');
        const clearBtn = document.getElementById('comm-search-clear');
        const spinner = document.getElementById('comm-search-spinner');
        const pjaxId = 'communications-pjax';
        if (!input) return;

        let debounceTimer = null;
        let lastSent = input.value.trim();

        function currentType() {
            const params = new URLSearchParams(window.location.search);
            return params.get('type') || 'all';
        }

        function buildUrl(q) {
            const params = new URLSearchParams();
            const type = currentType();
            if (type && type !== 'all') params.set('type', type);
            if (q) params.set('q', q);
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
            // accetta 0 (reset filtro) o >=3 caratteri
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

        // Spinner off al termine Pjax + refocus
        $(document).on('pjax:end', '#' + pjaxId, function() {
            spinner?.classList.add('hidden');
            spinner?.classList.remove('flex');
            // Restore focus mantenendo posizione caret
            const fresh = document.getElementById('comm-search');
            if (fresh && document.activeElement !== fresh) {
                // niente: pjax preserva DOM esterno, l'input non viene rimpiazzato
            }
        });
    })();
});
</script> 