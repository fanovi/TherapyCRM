<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%plan_therapies}}`.
 */
class m250201_000013_create_plan_therapies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%plan_therapies}}', [
            'id' => $this->primaryKey(),
            'therapeutic_plan_id' => $this->integer()->notNull(),
            'treatment_type_id' => $this->integer()->notNull(),
            'weekly_hours' => $this->decimal(4, 2)->notNull(),
            'is_group' => $this->boolean()->defaultValue(false),
            'notes' => $this->text(),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-plan_therapies-therapeutic_plan_id',
            '{{%plan_therapies}}',
            'therapeutic_plan_id',
            '{{%therapeutic_plans}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-plan_therapies-treatment_type_id',
            '{{%plan_therapies}}',
            'treatment_type_id',
            '{{%treatment_types}}',
            'id'
        );

        // Crea indice
        $this->createIndex('idx_plan', '{{%plan_therapies}}', 'therapeutic_plan_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-plan_therapies-treatment_type_id', '{{%plan_therapies}}');
        $this->dropForeignKey('fk-plan_therapies-therapeutic_plan_id', '{{%plan_therapies}}');
        
        $this->dropTable('{{%plan_therapies}}');
    }
} 