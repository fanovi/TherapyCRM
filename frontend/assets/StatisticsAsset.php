<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle per le pagine delle statistiche
 */
class StatisticsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/statistics.css',
    ];
    
    public $js = [
        'js/statistics.js',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'frontend\assets\AppAsset',
    ];

    public $jsOptions = [
        'position' => \yii\web\View::POS_END
    ];
} 