<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%districts}}`.
 */
class m250201_000003_create_districts_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%districts}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(10)->notNull()->unique(),
            'name' => $this->string(100)->notNull(),
            'asl_reference' => $this->string(100),
        ]);

        // Inserisci alcuni dati di esempio
        $this->batchInsert('{{%districts}}', ['code', 'name', 'asl_reference'], [
            ['65', 'ASL 65 Battipaglia', 'Battipaglia'],
            ['000', 'Altro', 'Altro'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%districts}}');
    }
} 