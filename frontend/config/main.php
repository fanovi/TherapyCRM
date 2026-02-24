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
    'id' => 'sanluca-frontend',
    'name' => 'San Luca CGM',
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
                'statistics/dashboard' => 'statistics/index',
                'statistics/absences' => 'statistics/absences',
                'statistics/patients' => 'statistics/patients',
                'statistics/treatments' => 'statistics/treatments',
                'statistics/plans' => 'statistics/plans',
                'statistics/chart-data/<type>' => 'statistics/chart-data',
                'statistics/export/<type>' => 'statistics/export',
                // API routes
                'api/therapist/<id:\d+>' => 'api/therapist/view',
                'api/patient/<id:\d+>' => 'api/patient/view',
                // Password reset route
                'site/reset-password' => 'site/reset-password',
                'therapeutic-plan-manager/get-settings-list/<regimeId:\d+>' => 'therapeutic-plan-manager/get-settings-list',
                'therapeutic-plan/get-settings-list/<regimeId:\d+>' => 'therapeutic-plan/get-settings-list',
                // Manuale d'uso
                'manuale' => 'site/manuale',
                'manuale/gestionale' => 'site/manuale-gestionale',
                'manuale/app' => 'site/manuale-app',
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
