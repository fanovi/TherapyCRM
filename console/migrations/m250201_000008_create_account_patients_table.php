<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%account_patients}}`.
 */
class m250201_000008_create_account_patients_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%account_patients}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'patient_id' => $this->integer()->notNull(),
            'has_parental_authority' => $this->boolean()->defaultValue(false),
            'relationship_type' => "ENUM('self', 'parent', 'tutor', 'other')",
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-account_patients-user_id',
            '{{%account_patients}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-account_patients-patient_id',
            '{{%account_patients}}',
            'patient_id',
            '{{%patients}}',
            'id',
            'CASCADE'
        );

        // Crea unique constraint
        $this->createIndex(
            'unique_user_patient',
            '{{%account_patients}}',
            ['user_id', 'patient_id'],
            true
        );

        // Crea indice per ricerca per paziente
        $this->createIndex('idx_patient', '{{%account_patients}}', 'patient_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-account_patients-patient_id', '{{%account_patients}}');
        $this->dropForeignKey('fk-account_patients-user_id', '{{%account_patients}}');
        
        $this->dropTable('{{%account_patients}}');
    }
} 