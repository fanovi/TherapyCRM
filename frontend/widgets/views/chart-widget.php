<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $widget frontend\widgets\ChartWidget */
/* @var $widgetId string */
/* @var $chartId string */
/* @var $loaderId string */
?>
<div <?= Html::renderTagAttributes($widget->containerOptions) ?>>
    <!-- Header -->
    <?php if ($widget->title !== false): ?>
    <div class="px-4 pt-4 pb-2 flex items-center justify-between border-b border-gray-100">
        <h6 class="text-base font-semibold text-gray-700">
            <?= Html::encode($widget->title) ?>
        </h6>
        <?php if ($widget->ajaxUrl): ?>
        <button type="button" class="ml-2 text-blue-500 hover:text-blue-700 focus:outline-none" onclick="window.reloadChart_<?= $widgetId ?>()" title="Ricarica grafico">
            <i class="fas fa-sync-alt"></i>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <!-- Body -->
    <div class="relative p-4">
        <?php if ($widget->showLoading && $widget->ajaxUrl): ?>
        <div id="<?= $loaderId ?>" class="absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-80 z-10" style="display: none;">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
            <p class="mt-2 text-gray-400 text-sm">Caricamento dati del grafico...</p>
        </div>
        <?php endif; ?>
        <div class="w-full" style="height: <?= $widget->height ?>px;">
            <canvas id="<?= $chartId ?>" width="400" height="<?= $widget->height ?>"></canvas>
        </div>
    </div>
</div> 