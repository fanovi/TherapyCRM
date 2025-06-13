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
            ['D001', 'Distretto Centro', 'ASL Roma 1'],
            ['D002', 'Distretto Nord', 'ASL Roma 2'],
            ['D003', 'Distretto Sud', 'ASL Roma 3'],
            ['D004', 'Distretto Est', 'ASL Roma 4'],
            ['D005', 'Distretto Ovest', 'ASL Roma 5'],
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