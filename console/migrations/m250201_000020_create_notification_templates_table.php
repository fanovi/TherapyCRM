<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notification_templates}}`.
 */
class m250201_000020_create_notification_templates_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notification_templates}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'type' => $this->string(50),
            'title_template' => $this->string(255),
            'message_template' => $this->text(),
            'days_before' => $this->integer(),
            'is_active' => $this->boolean()->defaultValue(true),
        ]);

        // Inserisci alcuni template di esempio
        $this->batchInsert('{{%notification_templates}}', ['code', 'type', 'title_template', 'message_template', 'days_before'], [
            ['PLAN_EXPIRING', 'reminder', 'Piano terapeutico in scadenza', 'Il piano terapeutico del paziente {patient_name} scadrà il {end_date}', 7],
            ['VISIT_REMINDER', 'reminder', 'Promemoria visita specialistica', 'Ricorda la visita specialistica di {visit_type} prevista per il {visit_date}', 1],
            ['ABSENCE_THRESHOLD', 'info', 'Soglia assenze superata', 'Il paziente {patient_name} ha superato la soglia di assenze ({percentage}%)', null],
            ['APPOINTMENT_CANCELLED', 'info', 'Appuntamento annullato', 'L\'appuntamento del {date} alle {time} è stato annullato', null],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%notification_templates}}');
    }
} 