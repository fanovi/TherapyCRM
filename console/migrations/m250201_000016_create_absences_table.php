<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%absences}}`.
 */
class m250201_000016_create_absences_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%absences}}', [
            'id' => $this->primaryKey(),
            'appointment_id' => $this->integer()->notNull(),
            'patient_id' => $this->integer()->notNull(),
            'absence_date' => $this->dateTime()->notNull(),
            'reason' => "ENUM('family', 'health', 'organizational', 'other')",
            'is_justified' => $this->boolean()->defaultValue(false),
            'is_communicated' => $this->boolean()->defaultValue(false),
            'communicated_by' => $this->integer(),
            'communicated_at' => $this->dateTime(),
            'notes' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-absences-appointment_id',
            '{{%absences}}',
            'appointment_id',
            '{{%appointments}}',
            'id'
        );

        $this->addForeignKey(
            'fk-absences-patient_id',
            '{{%absences}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );

        $this->addForeignKey(
            'fk-absences-communicated_by',
            '{{%absences}}',
            'communicated_by',
            '{{%users}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_patient', '{{%absences}}', 'patient_id');
        $this->createIndex('idx_justified', '{{%absences}}', 'is_justified');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-absences-communicated_by', '{{%absences}}');
        $this->dropForeignKey('fk-absences-patient_id', '{{%absences}}');
        $this->dropForeignKey('fk-absences-appointment_id', '{{%absences}}');
        
        $this->dropTable('{{%absences}}');
    }
} 