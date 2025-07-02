<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    
    // Configurazione Activity Log
    'activityLog' => [
        // Entità considerate critiche (inviano notifiche)
        'criticalEntities' => [
            'User',
            'Patient', 
            'Therapist',
            'TherapeuticPlan',
            'Appointment',
        ],
        
        // Azioni considerate critiche (inviano notifiche)
        'criticalActions' => [
            'delete', // Tutte le eliminazioni sono critiche
        ],
        
        // Email per le notifiche di azioni critiche
        'notificationEmails' => [
            'admin@therapycrm.com',
            'security@therapycrm.com',
        ],
        
        // Configurazione pulizia automatica
        'cleanup' => [
            'enabled' => true,
            'retentionMonths' => 12, // Mantieni log per 12 mesi
            'schedule' => '0 2 1 * *', // Primo giorno del mese alle 2:00 AM
        ],
        
        // Configurazione esportazione
        'export' => [
            'maxRecords' => 50000, // Massimo numero di record per export
            'formats' => ['csv', 'xlsx'], // Formati supportati
        ],
        
        // Configurazione widget
        'widget' => [
            'defaultLimit' => 10,
            'maxLimit' => 50,
        ],
        
        // Configurazione avanzata
        'advanced' => [
            'logUnchangedAttributes' => false, // Non loggare campi non modificati
            'excludeIpAddresses' => [], // IP da escludere dal logging
            'enableUserAgentTracking' => true,
            'enableGeoLocation' => false, // Per future implementazioni
            'compressOldLogs' => false, // Per future implementazioni
        ],
        
        // Configurazione sicurezza
        'security' => [
            'enableEncryption' => false, // Per future implementazioni
            'hashSensitiveData' => true,
            'redactedFields' => [ // Campi da oscurare nei log
                'password',
                'password_hash',
                'auth_key',
                'access_token',
                'verification_token',
                'password_reset_token',
            ],
        ],
    ],
];
