<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%appointment_patterns}}`.
 */
class m250201_000014_create_appointment_patterns_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%appointment_patterns}}', [
            'id' => $this->primaryKey(),
            'plan_therapy_id' => $this->integer()->notNull(),
            'therapist_id' => $this->integer()->notNull(),
            'day_of_week' => $this->tinyInteger()->notNull(),
            'start_time' => $this->time()->notNull(),
            'duration_minutes' => $this->integer()->notNull(),
            'location_type' => "ENUM('office', 'home') DEFAULT 'office'",
            'valid_from' => $this->date()->notNull(),
            'valid_to' => $this->date()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Aggiungi constraint per day_of_week
        $this->execute('ALTER TABLE {{%appointment_patterns}} ADD CONSTRAINT chk_day_of_week CHECK (day_of_week BETWEEN 1 AND 7)');

        // Crea foreign keys
        $this->addForeignKey(
            'fk-appointment_patterns-plan_therapy_id',
            '{{%appointment_patterns}}',
            'plan_therapy_id',
            '{{%plan_therapies}}',
            'id'
        );

        $this->addForeignKey(
            'fk-appointment_patterns-therapist_id',
            '{{%appointment_patterns}}',
            'therapist_id',
            '{{%therapists}}',
            'id'
        );

        $this->addForeignKey(
            'fk-appointment_patterns-created_by',
            '{{%appointment_patterns}}',
            'created_by',
            '{{%users}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_therapy', '{{%appointment_patterns}}', 'plan_therapy_id');
        $this->createIndex('idx_therapist', '{{%appointment_patterns}}', 'therapist_id');
        $this->createIndex('idx_validity', '{{%appointment_patterns}}', ['valid_from', 'valid_to']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-appointment_patterns-created_by', '{{%appointment_patterns}}');
        $this->dropForeignKey('fk-appointment_patterns-therapist_id', '{{%appointment_patterns}}');
        $this->dropForeignKey('fk-appointment_patterns-plan_therapy_id', '{{%appointment_patterns}}');
        
        $this->dropTable('{{%appointment_patterns}}');
    }
} 