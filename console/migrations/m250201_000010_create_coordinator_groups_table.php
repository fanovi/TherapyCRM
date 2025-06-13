<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%coordinator_groups}}`.
 */
class m250201_000010_create_coordinator_groups_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%coordinator_groups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'coordinator_user_id' => $this->integer()->notNull(),
            'is_active' => $this->boolean()->defaultValue(true),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign key verso users
        $this->addForeignKey(
            'fk-coordinator_groups-coordinator_user_id',
            '{{%coordinator_groups}}',
            'coordinator_user_id',
            '{{%users}}',
            'id'
        );

        // Crea indice
        $this->createIndex('idx_coordinator', '{{%coordinator_groups}}', 'coordinator_user_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key
        $this->dropForeignKey('fk-coordinator_groups-coordinator_user_id', '{{%coordinator_groups}}');
        
        $this->dropTable('{{%coordinator_groups}}');
    }
} 