<?php

use \yii\web\Request;

$baseUrl = str_replace('/frontend/web', '', (new Request)->getBaseUrl());

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
            'baseUrl' => $baseUrl,
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
            'loginUrl' => ['site/login'],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Route per il calendar
                'calendar' => 'calendar/index',
                'calendar/<id_patient:\d+>' => 'calendar/index',
                'calendar/therapist/<id_therapist:\d+>' => 'calendar/index',
                
                // Route per le statistiche
                'statistics' => 'statistics/index',
                'statistics/absences' => 'statistics/absence-stats',
                'statistics/patients' => 'statistics/patient-stats',
                'statistics/treatments' => 'statistics/treatment-stats',
                'statistics/regimes' => 'statistics/regime-stats',
                
                // API routes
                'api/therapist/<id:\d+>' => 'api/therapist/view',
                'api/patient/<id:\d+>' => 'api/patient/view',
            ],
        ],
    ],
    'as beforeRequest' => [
        'class' => 'yii\filters\AccessControl',
        'rules' => [
            [
                'actions' => [],
                'allow' => true,
            ],
            [
                'controllers' => ['therapeutic-plan-manager'],
                'allow' => true,
            ],
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ],
    ],
    'params' => $params,
];
