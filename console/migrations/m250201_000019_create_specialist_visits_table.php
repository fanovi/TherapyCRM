<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%specialist_visits}}`.
 */
class m250201_000019_create_specialist_visits_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%specialist_visits}}', [
            'id' => $this->primaryKey(),
            'therapeutic_plan_id' => $this->integer()->notNull(),
            'visit_type' => "ENUM('phoniatric', 'physiatric', 'npi', 'renewal_project', 'other')",
            'scheduled_datetime' => $this->dateTime(),
            'status' => "ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled'",
            'notes' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign key
        $this->addForeignKey(
            'fk-specialist_visits-therapeutic_plan_id',
            '{{%specialist_visits}}',
            'therapeutic_plan_id',
            '{{%therapeutic_plans}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_plan', '{{%specialist_visits}}', 'therapeutic_plan_id');
        $this->createIndex('idx_status', '{{%specialist_visits}}', 'status');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key
        $this->dropForeignKey('fk-specialist_visits-therapeutic_plan_id', '{{%specialist_visits}}');
        
        $this->dropTable('{{%specialist_visits}}');
    }
} 