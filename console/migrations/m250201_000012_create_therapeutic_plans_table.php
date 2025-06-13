<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%therapeutic_plans}}`.
 */
class m250201_000012_create_therapeutic_plans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%therapeutic_plans}}', [
            'id' => $this->primaryKey(),
            'patient_id' => $this->integer()->notNull(),
            'start_date' => $this->date()->notNull(),
            'duration_days' => $this->integer()->notNull(),
            'end_date' => 'DATE GENERATED ALWAYS AS (DATE_ADD(start_date, INTERVAL duration_days DAY)) STORED',
            'health_regime' => "ENUM('L11', 'L11DOM', 'L11PG', 'L11SEM', 'ABA', 'FKT', 'Private', 'PDOM', 'Other') NOT NULL",
            'status' => "ENUM('draft', 'active', 'expired', 'renewed') DEFAULT 'draft'",
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-therapeutic_plans-patient_id',
            '{{%therapeutic_plans}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );

        $this->addForeignKey(
            'fk-therapeutic_plans-created_by',
            '{{%therapeutic_plans}}',
            'created_by',
            '{{%users}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_status', '{{%therapeutic_plans}}', 'status');
        $this->createIndex('idx_dates', '{{%therapeutic_plans}}', ['start_date', 'end_date']);
        $this->createIndex('idx_patient', '{{%therapeutic_plans}}', 'patient_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-therapeutic_plans-created_by', '{{%therapeutic_plans}}');
        $this->dropForeignKey('fk-therapeutic_plans-patient_id', '{{%therapeutic_plans}}');
        
        $this->dropTable('{{%therapeutic_plans}}');
    }
} 