<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%absence_recoveries}}`.
 */
class m250201_000017_create_absence_recoveries_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%absence_recoveries}}', [
            'id' => $this->primaryKey(),
            'original_appointment_id' => $this->integer()->notNull(),
            'recovery_appointment_id' => $this->integer()->notNull(),
            'absence_reason' => "ENUM('family', 'health', 'organizational')",
            'managed_by' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-absence_recoveries-original_appointment_id',
            '{{%absence_recoveries}}',
            'original_appointment_id',
            '{{%appointments}}',
            'id'
        );

        $this->addForeignKey(
            'fk-absence_recoveries-recovery_appointment_id',
            '{{%absence_recoveries}}',
            'recovery_appointment_id',
            '{{%appointments}}',
            'id'
        );

        $this->addForeignKey(
            'fk-absence_recoveries-managed_by',
            '{{%absence_recoveries}}',
            'managed_by',
            '{{%users}}',
            'id'
        );

        // Crea unique constraint per recovery appointment
        $this->createIndex(
            'unique_recovery',
            '{{%absence_recoveries}}',
            'recovery_appointment_id',
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-absence_recoveries-managed_by', '{{%absence_recoveries}}');
        $this->dropForeignKey('fk-absence_recoveries-recovery_appointment_id', '{{%absence_recoveries}}');
        $this->dropForeignKey('fk-absence_recoveries-original_appointment_id', '{{%absence_recoveries}}');
        
        $this->dropTable('{{%absence_recoveries}}');
    }
} 