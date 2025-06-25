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

$this->title = 'Comunicazioni';
$this->params['breadcrumbs'][] = $this->title;

// Asset per JavaScript delle comunicazioni
$this->registerJsFile('@web/js/communications.js', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsVar('markReadUrl', Url::to(['communication/mark-read']));
$this->registerJsVar('markAllReadUrl', Url::to(['communication/mark-all-read']));
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

    <!-- Statistiche -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= $totalCount ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Totali</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= $unreadCount ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Non lette</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m4 0V9a2 2 0 011-1h4a2 2 0 011 1v12m-6 0h6"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= $internalCount ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Comunicazioni Sistema</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri -->
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtra per:</span>
            
            <?= Html::a('Tutte', ['communication/index', 'type' => 'all'], [
                'class' => 'px-3 py-1.5 text-sm rounded-lg border ' . 
                           ($currentType === 'all' ? 
                            'bg-brand-500 text-white border-brand-500' : 
                            'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
            ]) ?>
            
            <?= Html::a('Non lette', ['communication/index', 'type' => 'unread'], [
                'class' => 'px-3 py-1.5 text-sm rounded-lg border ' . 
                           ($currentType === 'unread' ? 
                            'bg-orange-500 text-white border-orange-500' : 
                            'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
            ]) ?>
            
            <?= Html::a('Sistema', ['communication/index', 'type' => 'internal_communication'], [
                'class' => 'px-3 py-1.5 text-sm rounded-lg border ' . 
                           ($currentType === 'internal_communication' ? 
                            'bg-green-500 text-white border-green-500' : 
                            'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700')
            ]) ?>
        </div>
    </div>

    <!-- Lista Comunicazioni -->
    <?php Pjax::begin(['id' => 'communications-pjax', 'enablePushState' => false]); ?>
    
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
    // Inizializza il sistema di comunicazioni
    if (typeof window.CommunicationSystem !== 'undefined') {
        window.CommunicationSystem.init();
    }
    
    // Refresh button
    document.getElementById('refresh-btn')?.addEventListener('click', function() {
        window.location.reload();
    });
});
</script> 