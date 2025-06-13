<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%document_requests}}`.
 */
class m250201_000024_create_document_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%document_requests}}', [
            'id' => $this->primaryKey(),
            'patient_id' => $this->integer()->notNull(),
            'requested_by' => $this->integer()->notNull(),
            'document_type' => "ENUM('attendance_generic', 'attendance_school', 'attendance_inps')",
            'status' => "ENUM('pending', 'processing', 'completed') DEFAULT 'pending'",
            'completed_at' => $this->dateTime(),
            'completed_by' => $this->integer(),
            'notes' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-document_requests-patient_id',
            '{{%document_requests}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );

        $this->addForeignKey(
            'fk-document_requests-requested_by',
            '{{%document_requests}}',
            'requested_by',
            '{{%users}}',
            'id'
        );

        $this->addForeignKey(
            'fk-document_requests-completed_by',
            '{{%document_requests}}',
            'completed_by',
            '{{%users}}',
            'id'
        );

        // Crea indice per status
        $this->createIndex('idx_status', '{{%document_requests}}', 'status');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-document_requests-completed_by', '{{%document_requests}}');
        $this->dropForeignKey('fk-document_requests-requested_by', '{{%document_requests}}');
        $this->dropForeignKey('fk-document_requests-patient_id', '{{%document_requests}}');
        
        $this->dropTable('{{%document_requests}}');
    }
} 