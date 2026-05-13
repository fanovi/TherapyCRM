<?php
// Carica params-local (NON committato) per chiavi ambiente-specifiche (es. OneSignal).
$params = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

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
            // Chiavi da common/config/params-local.php (non committato).
            'appId' => $params['oneSignal']['appId'] ?? '',
            'restApiKey' => $params['oneSignal']['restApiKey'] ?? '',
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
