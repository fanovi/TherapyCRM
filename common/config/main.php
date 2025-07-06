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
            'appId' => '517b6b4d-1c8f-40cf-a814-34830eb24aca',
            'restApiKey' => 'os_v2_app_kf5wwti4r5am7kaugsbq5mskzicz5jhjntyuke4m3vdyeiwiwx7fxjcmzxolepvcevylcaqddtpsj57j2v3lszs34hesqvjpzfima4i',
        ],
        'notificationService' => [
            'class' => 'common\components\NotificationService',
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'dd/MM/yyyy',
            'datetimeFormat' => 'dd/MM/yyyy HH:mm',
            'decimalSeparator' => ',',
            'thousandSeparator' => '.',
            'currencyCode' => 'EUR',
            'defaultTimeZone' => 'Europe/Rome',
        ],
    ],
];
