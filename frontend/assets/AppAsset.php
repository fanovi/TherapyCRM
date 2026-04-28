<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        'css/style.css?v=1.0.4',
    ];

    public $js = [
        // SweetAlert2: modali moderne riusabili (caricato via CDN, no purging Tailwind).
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
        'js/bundle.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\widgets\ActiveFormAsset',
    ];
}
