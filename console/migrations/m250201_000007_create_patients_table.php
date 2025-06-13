<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%patients}}`.
 */
class m250201_000007_create_patients_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%patients}}', [
            'id' => $this->primaryKey(),
            'first_name' => $this->string(100)->notNull(),
            'last_name' => $this->string(100)->notNull(),
            'birth_date' => $this->date()->notNull(),
            'fiscal_code' => $this->string(16),
            'district_id' => $this->integer(),
            'notes' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign key verso districts
        $this->addForeignKey(
            'fk-patients-district_id',
            '{{%patients}}',
            'district_id',
            '{{%districts}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_fiscal_code', '{{%patients}}', 'fiscal_code');
        $this->createIndex('idx_name', '{{%patients}}', ['last_name', 'first_name']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key
        $this->dropForeignKey('fk-patients-district_id', '{{%patients}}');
        
        $this->dropTable('{{%patients}}');
    }
} 