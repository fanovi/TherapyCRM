<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/**
 * Widget per grafici con Chart.js (Tailwind CSS)
 */
class ChartWidget extends Widget
{
    public $title;
    public $type = 'line';
    public $data = [];
    public $ajaxUrl;
    public $height = 400;
    public $options = [];
    public $showLoading = true;
    public $containerOptions = [];
    protected $widgetId;

    public function init()
    {
        parent::init();
        if (!$this->title && $this->title !== false) {
            throw new \InvalidArgumentException('Il parametro "title" è obbligatorio');
        }
        if (!$this->data && !$this->ajaxUrl) {
            throw new \InvalidArgumentException('È necessario specificare "data" o "ajaxUrl"');
        }
        $this->widgetId = $this->getId();
        Html::addCssClass($this->containerOptions, 'chart-widget bg-white rounded-lg shadow mb-6');
    }

    public function run()
    {
        $this->registerAssets();
        return $this->renderTemplate('chart-widget', [
            'widget' => $this,
            'widgetId' => $this->widgetId,
            'chartId' => 'chart-' . $this->widgetId,
            'loaderId' => 'loader-' . $this->widgetId,
        ]);
    }

    protected function registerAssets()
    {
        $view = $this->getView();
        $this->registerChartScript();
    }

    protected function registerChartScript()
    {
        $view = $this->getView();
        $chartId = 'chart-' . $this->widgetId;
        $loaderId = 'loader-' . $this->widgetId;
        $chartOptions = $this->getChartOptions();
        $chartOptionsJson = Json::encode($chartOptions);
        if ($this->ajaxUrl) {
            $ajaxUrl = $this->ajaxUrl;
            $script = "(function() {var chartCanvas = document.getElementById('{$chartId}');var loader = document.getElementById('{$loaderId}');var chart = null;function loadChartData() {if (loader) loader.style.display = 'block';$.ajax({url: '{$ajaxUrl}',type: 'GET',dataType: 'json',success: function(response) {if (loader) loader.style.display = 'none';if (response.success && response.data) {var config = {$chartOptionsJson};config.data = response.data;if (chart) {chart.destroy();}chart = new Chart(chartCanvas, config);} else {console.error('Errore nei dati del grafico:', response.message || 'Dati non validi');}},error: function(xhr, status, error) {if (loader) loader.style.display = 'none';console.error('Errore AJAX nel caricamento del grafico:', error);}});}loadChartData();window.reloadChart_{$this->widgetId} = loadChartData;})();";
        } else {
            $chartData = Json::encode($this->data);
            $script = "(function() {var chartCanvas = document.getElementById('{$chartId}');var config = {$chartOptionsJson};config.data = {$chartData};var chart = new Chart(chartCanvas, config);})();";
        }
        $view->registerJs($script, View::POS_READY);
    }

    protected function getChartOptions()
    {
        $defaultOptions = [
            'type' => $this->type,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top'
                    ],
                    'title' => [
                        'display' => false
                    ]
                ]
            ]
        ];
        switch ($this->type) {
            case 'line':
            case 'bar':
                $defaultOptions['options']['scales'] = [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0]
                    ]
                ];
                break;
            case 'pie':
            case 'doughnut':
                $defaultOptions['options']['plugins']['legend']['position'] = 'right';
                break;
            case 'heatmap':
                $defaultOptions['type'] = 'matrix';
                $defaultOptions['options'] = [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['display' => false]
                    ],
                    'scales' => [
                        'x' => ['type' => 'linear', 'position' => 'bottom'],
                        'y' => ['type' => 'linear']
                    ]
                ];
                break;
        }
        return array_merge_recursive($defaultOptions, $this->options);
    }

    protected function renderTemplate($view, $params = [])
    {
        return $this->renderFile(__DIR__ . '/views/' . $view . '.php', $params);
    }

    public static function getSupportedTypes()
    {
        return [
            'line' => 'Linea',
            'bar' => 'Barre',
            'pie' => 'Torta',
            'doughnut' => 'Ciambella',
            'heatmap' => 'Heatmap'
        ];
    }
} 