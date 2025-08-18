<?php

use yii\db\Migration;

/**
 * Adds therapeutic plan expiry notification templates
 */
class m250201_000029_add_therapeutic_plan_expiry_templates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert(
            '{{%notification_templates}}',
            ['code', 'type', 'title_template', 'message_template', 'days_before', 'is_active'],
            [
                [
                    'PLAN_EXPIRING_90',
                    'reminder',
                    'Piano terapeutico in scadenza',
                    'Il piano terapeutico di {patient_name} scadrà tra {days_remaining} giorni (il {end_date}). Si consiglia di iniziare le procedure per il rinnovo.',
                    90,
                    1
                ],
                [
                    'PLAN_EXPIRING_60',
                    'reminder',
                    'Piano terapeutico in scadenza',
                    'Il piano terapeutico di {patient_name} scadrà tra {days_remaining} giorni (il {end_date}). È importante programmare il rinnovo.',
                    60,
                    1
                ],
                [
                    'PLAN_EXPIRING_30',
                    'deadline',
                    'Piano terapeutico in scadenza - ATTENZIONE',
                    'ATTENZIONE: Il piano terapeutico di {patient_name} scadrà tra {days_remaining} giorni (il {end_date}). È urgente procedere con il rinnovo.',
                    30,
                    1
                ],
                [
                    'PLAN_EXPIRING_15',
                    'deadline',
                    'Piano terapeutico in scadenza - URGENTE',
                    'URGENTE: Il piano terapeutico di {patient_name} scadrà tra soli {days_remaining} giorni (il {end_date}). Procedere immediatamente con il rinnovo per evitare interruzioni.',
                    15,
                    1
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%notification_templates}}', ['in', 'code', [
            'PLAN_EXPIRING_90',
            'PLAN_EXPIRING_60',
            'PLAN_EXPIRING_30',
            'PLAN_EXPIRING_15'
        ]]);
    }
}
