<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%appointments}}`.
 */
class m250201_000015_create_appointments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%appointments}}', [
            'id' => $this->primaryKey(),
            'pattern_id' => $this->integer(),
            'plan_therapy_id' => $this->integer()->notNull(),
            'therapist_id' => $this->integer()->notNull(),
            'patient_id' => $this->integer()->notNull(), // Denormalizzato per performance
            'appointment_datetime' => $this->dateTime()->notNull(),
            'duration_minutes' => $this->integer()->notNull(),
            'location_type' => "ENUM('office', 'home') DEFAULT 'office'",
            'status' => "ENUM('scheduled', 'completed', 'absent_justified', 'absent_not_justified', 'cancelled') DEFAULT 'scheduled'",
            'original_therapist_id' => $this->integer(), // Per tracciare sostituzioni
            'notes' => $this->text(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-appointments-pattern_id',
            '{{%appointments}}',
            'pattern_id',
            '{{%appointment_patterns}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-appointments-plan_therapy_id',
            '{{%appointments}}',
            'plan_therapy_id',
            '{{%plan_therapies}}',
            'id'
        );

        $this->addForeignKey(
            'fk-appointments-therapist_id',
            '{{%appointments}}',
            'therapist_id',
            '{{%therapists}}',
            'id'
        );

        $this->addForeignKey(
            'fk-appointments-patient_id',
            '{{%appointments}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );

        $this->addForeignKey(
            'fk-appointments-original_therapist_id',
            '{{%appointments}}',
            'original_therapist_id',
            '{{%therapists}}',
            'id'
        );

        $this->addForeignKey(
            'fk-appointments-created_by',
            '{{%appointments}}',
            'created_by',
            '{{%users}}',
            'id'
        );

        // Crea indici ottimizzati per performance
        $this->createIndex('idx_therapist_date', '{{%appointments}}', ['therapist_id', 'appointment_datetime']);
        $this->createIndex('idx_patient_date', '{{%appointments}}', ['patient_id', 'appointment_datetime']);
        $this->createIndex('idx_status_date', '{{%appointments}}', ['status', 'appointment_datetime']);
        $this->createIndex('idx_pattern', '{{%appointments}}', 'pattern_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-appointments-created_by', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-original_therapist_id', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-patient_id', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-therapist_id', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-plan_therapy_id', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-pattern_id', '{{%appointments}}');
        
        $this->dropTable('{{%appointments}}');
    }
}