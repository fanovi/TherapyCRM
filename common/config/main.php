<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'jwt' => [
            'class' => 'common\components\JwtComponent',
            'privateKeyPath' => dirname(__DIR__) .  '/keys/private.key',
            'publicKeyPath' => dirname(__DIR__) . '/keys/public.key',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'oneSignal' => [
            'class' => 'common\components\OneSignalService',
            'appId' => '', // Configura con il tuo OneSignal App ID
            'restApiKey' => '', // Configura con la tua OneSignal REST API Key
        ],
        'notificationService' => [
            'class' => 'common\components\NotificationService',
        ],
    ],
];
