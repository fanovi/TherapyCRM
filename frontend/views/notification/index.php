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

$this->title = 'Notifiche Sistema';
$this->params['breadcrumbs'][] = $this->title;

// Asset per JavaScript delle notifiche
$this->registerJsFile('@web/js/notifications.js', ['depends' => [\yii\web\JqueryAsset::class]]);

// Registra gli URL per gli endpoint API
$this->registerJsVar('apiStatsUrl', Url::to(['notification/stats-api']));
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb e Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        
        <!-- Titolo principale -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                Notifiche Sistema
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Visualizza e gestisci tutte le notifiche inviate dal sistema ai pazienti
            </p>
        </div>

        <!-- Azioni header -->
        <div class="flex items-center gap-3">
            <button 
                id="refresh-btn"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Aggiorna
            </button>
        </div>
    </div>

    <!-- Filtri -->
    <div class="mb-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
        <div class="flex flex-wrap gap-4">
            <!-- Filtri per tipo -->
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo:</span>
                <?= Html::a('Tutti', ['notification/index'], [
                    'class' => 'px-2 py-1 text-xs rounded-md transition-colors '
                        . ($currentType === 'all' ? 'text-gray-800 dark:text-gray-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'),
                    'style' => $currentType === 'all' ? 'background-color: var(--color-brand-50);' : ''
                ]) ?>
                
                <?php foreach (Notification::getTypeOptions() as $typeKey => $typeLabel): ?>
                    <?= Html::a($typeLabel . ' (' . ($typeStats[$typeKey]['count'] ?? 0) . ')',
                            ['notification/index', 'type' => $typeKey], [
                        'class' => 'px-2 py-1 text-xs rounded-md transition-colors '
                            . ($currentType === $typeKey ? 'text-gray-800 dark:text-gray-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'),
                        'style' => $currentType === $typeKey ? 'background-color: var(--color-brand-50);' : ''
                    ]) ?>
                <?php endforeach; ?>
            </div>
            
            <div class="border-l border-gray-300 dark:border-gray-600"></div>
            
            <!-- Filtri per stato -->
            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stato:</span>
                <?= Html::a('Tutti', ['notification/index', 'type' => $currentType !== 'all' ? $currentType : null], [
                    'class' => 'px-2 py-1 text-xs rounded-md transition-colors '
                        . ($currentStatus === 'all' ? 'text-gray-800 dark:text-gray-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'),
                    'style' => $currentStatus === 'all' ? 'background-color: var(--color-brand-50);' : ''
                ]) ?>
                
                <?= Html::a('Non lette (' . $unreadCount . ')',
                        ['notification/index', 'status' => 'unread', 'type' => $currentType !== 'all' ? $currentType : null], [
                    'class' => 'px-2 py-1 text-xs rounded-md transition-colors '
                        . ($currentStatus === 'unread' ? 'text-gray-800 dark:text-gray-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'),
                    'style' => $currentStatus === 'unread' ? 'background-color: var(--color-brand-50);' : ''
                ]) ?>
                
                <?= Html::a('Lette',
                        ['notification/index', 'status' => 'read', 'type' => $currentType !== 'all' ? $currentType : null], [
                    'class' => 'px-2 py-1 text-xs rounded-md transition-colors '
                        . ($currentStatus === 'read' ? 'text-gray-800 dark:text-gray-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'),
                    'style' => $currentStatus === 'read' ? 'background-color: var(--color-brand-50);' : ''
                ]) ?>
                


            </div>
        </div>
    </div>

    <!-- Statistiche -->
    <div class="mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Totale -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V8h-5l5-5 5 5h-5v9z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Totale</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $totalCount ?></p>
                    </div>
                </div>
            </div>

            <!-- Non lette -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Non lette</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $unreadCount ?></p>
                    </div>
                </div>
            </div>




        </div>
    </div>

    <!-- Lista Notifiche -->
    <?php Pjax::begin(['id' => 'notifications-pjax', 'enablePushState' => false]); ?>
    
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'item'],
        'itemView' => '_notification_item',
        'layout' => '<div class="space-y-4">{items}</div>{pager}',
        'emptyText' => $this->render('_empty_state', [
            'currentType' => $currentType,
            'currentStatus' => $currentStatus
        ]),
        'pager' => [
            'class' => \yii\widgets\LinkPager::class,
            'options' => ['class' => 'flex justify-center mt-6'],
            'linkOptions' => ['class' => 'px-3 py-2 mx-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors'],
            'activePageCssClass' => 'bg-brand-500 text-white border-brand-500 hover:bg-brand-600',
            'disabledPageCssClass' => 'text-gray-300 cursor-not-allowed',
            'maxButtonCount' => 7,
        ],
    ]) ?>
    
    <?php Pjax::end(); ?>
</div>

<!-- Script per gestione AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    if (typeof window.NotificationSystem !== 'undefined') {
        window.NotificationSystem.init();
    }
    
    // Refresh button
    document.getElementById('refresh-btn')?.addEventListener('click', function() {
        window.location.reload();
    });
});
</script>
