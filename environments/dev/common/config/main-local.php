<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=yii2advanced',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            // Configurazione Gmail SMTP per invio email reali
            'useFileTransport' => false,
            'transport' => [
                'dsn' => 'smtps://rispoli.mar2803@gmail.com:oxfa eavb dkic ldqv@smtp.gmail.com:465',
            ],
        ],
    ],
];
