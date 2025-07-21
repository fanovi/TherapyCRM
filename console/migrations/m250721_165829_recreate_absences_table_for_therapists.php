<?php

use yii\db\Migration;

/**
 * Recreates the absences table to match the Absence model for therapist absences.
 * The existing table was designed for patient absences, but the model expects therapist absences.
 */
class m250721_165829_recreate_absences_table_for_therapists extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Drop the existing table that was designed for patient absences
        $this->dropTable('{{%absences}}');

        // Create the new table for therapist absences according to Absence model
        $this->createTable('{{%absences}}', [
            'id' => $this->primaryKey(),
            'therapist_id' => $this->integer()->notNull()->comment('ID del terapista assente'),
            'start_date' => $this->date()->notNull()->comment('Data inizio assenza'),
            'end_date' => $this->date()->notNull()->comment('Data fine assenza'),
            'type' => "ENUM('vacation', 'sick_leave', 'personal', 'training', 'other') NOT NULL DEFAULT 'other' COMMENT 'Tipo di assenza'",
            'reason' => $this->text()->comment('Motivo dell\'assenza'),
            'status' => "ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Stato dell\'assenza'",
            'approved_by' => $this->integer()->comment('ID utente che ha approvato'),
            'approved_at' => $this->dateTime()->comment('Data e ora di approvazione'),
            'notes' => $this->text()->comment('Note aggiuntive'),
            'created_by' => $this->integer()->notNull()->comment('ID utente che ha creato'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create foreign keys
        $this->addForeignKey(
            'fk-absences-therapist_id',
            '{{%absences}}',
            'therapist_id',
            '{{%therapists}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-absences-approved_by',
            '{{%absences}}',
            'approved_by',
            '{{%users}}',
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk-absences-created_by',
            '{{%absences}}',
            'created_by',
            '{{%users}}',
            'id'
        );

        // Create indexes for performance
        $this->createIndex('idx-absences-therapist_id', '{{%absences}}', 'therapist_id');
        $this->createIndex('idx-absences-status', '{{%absences}}', 'status');
        $this->createIndex('idx-absences-type', '{{%absences}}', 'type');
        $this->createIndex('idx-absences-dates', '{{%absences}}', ['start_date', 'end_date']);
        $this->createIndex('idx-absences-approved', '{{%absences}}', ['status', 'approved_at']);

        echo "Recreated absences table for therapist absences management\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop the new table
        $this->dropTable('{{%absences}}');

        // Recreate the old table structure for patient absences
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

        // Recreate old foreign keys
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

        // Recreate old indexes
        $this->createIndex('idx_patient', '{{%absences}}', 'patient_id');
        $this->createIndex('idx_justified', '{{%absences}}', 'is_justified');

        echo "Restored old absences table structure\n";
    }
} 