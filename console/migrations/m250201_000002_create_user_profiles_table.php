<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_profiles}}`.
 */
class m250201_000002_create_user_profiles_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user_profiles}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'first_name' => $this->string(100)->notNull(),
            'last_name' => $this->string(100)->notNull(),
            'fiscal_code' => $this->string(16),
            'phone' => $this->string(20),
            'address' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign key verso users
        $this->addForeignKey(
            'fk-user_profiles-user_id',
            '{{%user_profiles}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );

        // Crea indici
        $this->createIndex('idx_user_id', '{{%user_profiles}}', 'user_id');
        $this->createIndex('idx_fiscal_code', '{{%user_profiles}}', 'fiscal_code');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key
        $this->dropForeignKey('fk-user_profiles-user_id', '{{%user_profiles}}');
        
        $this->dropTable('{{%user_profiles}}');
    }
} 