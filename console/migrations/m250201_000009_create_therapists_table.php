<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%therapists}}`.
 */
class m250201_000009_create_therapists_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%therapists}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'specialization_id' => $this->integer()->notNull(),
            'weekly_hours_contract' => $this->integer(),
            'calendar_color' => $this->string(7), // Per UI calendario
            'is_active' => $this->boolean()->defaultValue(true),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-therapists-user_id',
            '{{%therapists}}',
            'user_id',
            '{{%users}}',
            'id'
        );

        $this->addForeignKey(
            'fk-therapists-specialization_id',
            '{{%therapists}}',
            'specialization_id',
            '{{%specializations}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_active', '{{%therapists}}', 'is_active');
        $this->createIndex('idx_specialization', '{{%therapists}}', 'specialization_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-therapists-specialization_id', '{{%therapists}}');
        $this->dropForeignKey('fk-therapists-user_id', '{{%therapists}}');
        
        $this->dropTable('{{%therapists}}');
    }
} 