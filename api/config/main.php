<?php

use \yii\web\Request;
use \yii\web\Response;

$baseUrl = str_replace('/api/web', '/api', (new Request)->getBaseUrl());

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'api\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-api',
            'baseUrl' => $baseUrl,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
            'enableCookieValidation' => false,
            'acceptableContentTypes' => ['application/json' => 'application/json']
        ],
        'response' => [
            'format' => Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-api', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the api
            'name' => 'advanced-api',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'error/index',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET search/user' => 'search/user',
                // Notification routes
                'GET notifications' => 'notification/index',
                'GET notifications/<id:\d+>' => 'notification/view',
                'GET notifications/unread' => 'notification/unread',
                'GET notifications/has-blocking' => 'notification/has-blocking',
                'GET notifications/blocking' => 'notification/blocking',
                'POST notifications/<id:\d+>/mark-read' => 'notification/mark-read',
                'POST notifications/<id:\d+>/confirm-read' => 'notification/confirm-read',
                'POST notifications/<id:\d+>/mark-viewed' => 'notification/mark-viewed',
                'POST notifications/send' => 'notification/send',
                'POST notifications/send-template' => 'notification/send-template',
                'POST notifications/broadcast' => 'notification/broadcast',
                'POST notifications/create-test' => 'notification/create-test',
                // Request routes
                'GET requests/types' => 'requests/types',
                'GET requests' => 'requests/index',
                'GET requests/<id:\d+>' => 'requests/view',
                'GET requests/<id>' => 'requests/view',  // Cattura ID non numerici per gestione errori
                'POST requests' => 'requests/create',
                // Swagger routes
                'GET swagger' => 'swagger/index',
                'GET swagger/json' => 'swagger/json',
                // Auth routes
                'POST auth/login' => 'auth/login',
                'POST auth/logout' => 'auth/logout',
                'GET auth/verify' => 'auth/verify',
                // Calendar routes
                'POST calendar/patient-appointments' => 'calendar/patient-appointments',
                'POST calendar/patient-marked-dates' => 'calendar/patient-marked-dates',
                'POST calendar/patient-upcoming-appointments' => 'calendar/patient-upcoming-appointments',
                'POST calendar/cancel-appointment' => 'calendar/cancel-appointment',
                'POST calendar/therapist-appointments' => 'calendar/therapist-appointments',
                'POST calendar/therapist-marked-dates' => 'calendar/therapist-marked-dates',
                'POST calendar/mark-patient-absent' => 'calendar/mark-patient-absent',
                // Altre route qui...
            ],
        ],
    ],
    'params' => $params,
];
