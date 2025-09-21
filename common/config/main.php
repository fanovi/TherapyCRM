<?php
return [
    'language' => 'it-IT',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'codiceFiscaleGenerator' => [
            'class' => 'common\components\CodiceFiscaleGenerator',
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'jwt' => [
            'class' => 'common\components\JwtComponent',
            'privateKeyPath' => dirname(__DIR__) . '/keys/private.key',
            'publicKeyPath' => dirname(__DIR__) . '/keys/public.key',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'oneSignal' => [
            'class' => 'common\components\OneSignalService',
            'appId' => '8ab64a7b-8b43-41b4-8444-18922a41a7fc',
            'restApiKey' => 'os_v2_app_rk3eu64lina3jbcedcjcuqnh7s55ycbimsaec2uukpprwqs4iyicqgswdpdoupi54te5skfcn2ojoqhnf7xd3urwfwpcvmmu5xvoggi',
        ],
        'notificationService' => [
            'class' => 'common\components\NotificationService',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'dd/MM/yyyy',
            'datetimeFormat' => 'dd/MM/yyyy HH:mm',
            'decimalSeparator' => ',',
            'thousandSeparator' => '.',
            'currencyCode' => 'EUR',
            'defaultTimeZone' => 'Europe/Rome',
            'locale' => 'it-IT',
        ],
    ],
];
