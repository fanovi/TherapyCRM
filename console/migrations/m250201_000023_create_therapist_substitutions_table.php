<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%therapist_substitutions}}`.
 */
class m250201_000023_create_therapist_substitutions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%therapist_substitutions}}', [
            'id' => $this->primaryKey(),
            'appointment_id' => $this->integer()->notNull(),
            'original_therapist_id' => $this->integer()->notNull(),
            'substitute_therapist_id' => $this->integer()->notNull(),
            'reason' => $this->text(),
            'substituted_by' => $this->integer()->notNull(),
            'substituted_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-therapist_substitutions-appointment_id',
            '{{%therapist_substitutions}}',
            'appointment_id',
            '{{%appointments}}',
            'id'
        );

        $this->addForeignKey(
            'fk-therapist_substitutions-original_therapist_id',
            '{{%therapist_substitutions}}',
            'original_therapist_id',
            '{{%therapists}}',
            'id'
        );

        $this->addForeignKey(
            'fk-therapist_substitutions-substitute_therapist_id',
            '{{%therapist_substitutions}}',
            'substitute_therapist_id',
            '{{%therapists}}',
            'id'
        );

        $this->addForeignKey(
            'fk-therapist_substitutions-substituted_by',
            '{{%therapist_substitutions}}',
            'substituted_by',
            '{{%users}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-therapist_substitutions-substituted_by', '{{%therapist_substitutions}}');
        $this->dropForeignKey('fk-therapist_substitutions-substitute_therapist_id', '{{%therapist_substitutions}}');
        $this->dropForeignKey('fk-therapist_substitutions-original_therapist_id', '{{%therapist_substitutions}}');
        $this->dropForeignKey('fk-therapist_substitutions-appointment_id', '{{%therapist_substitutions}}');
        
        $this->dropTable('{{%therapist_substitutions}}');
    }
} 