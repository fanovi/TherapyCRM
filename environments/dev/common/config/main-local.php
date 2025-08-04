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
            // Configurazione Mailgun (commentata per ora)
            // 'transport' => [
            //     'dsn' => 'smtp://api:639225f51b67980f85eb58411a717409-812b35f5-9914033d@smtp.mailgun.org:587',
            // ],
            // Configurazione alternativa per test (usa il sandbox domain)
            // 'transport' => [
            //     'dsn' => 'smtp://api:639225f51b67980f85eb58411a717409-812b35f5-9914033d@smtp.mailgun.org:587',
            //     'options' => [
            //         'domain' => 'sandbox123456789.mailgun.org', // Sostituisci con il tuo sandbox domain
            //     ],
            // ],
            // Configurazione alternativa per altri provider:
            // Gmail con password app:
            // 'transport' => [
            //     'dsn' => 'gmail+smtp://your-email@gmail.com:your-app-password@default',
            // ],
            //
            // Outlook/Hotmail:
            // 'transport' => [
            //     'dsn' => 'smtp://your-email@outlook.com:your-password@smtp-mail.outlook.com:587',
            // ],
            //
            // SendGrid:
            // 'transport' => [
            //     'dsn' => 'sendgrid+smtp://your-api-key@default',
            // ],
            //
            // Mailgun:
            // 'transport' => [
            //     'dsn' => 'smtp://your-username:your-password@smtp.mailgun.org:587',
            // ],
        ],
    ],
];
