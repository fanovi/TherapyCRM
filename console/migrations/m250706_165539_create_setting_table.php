<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%setting}}`.
 */
class m250706_165539_create_setting_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%setting}}', [
            'id' => $this->primaryKey(),
            'nome' => $this->string()->notNull(),
        ]);

        // Inserimento dei dati iniziali
        $this->batchInsert('{{%setting}}', 
            ['nome'],
            [
                ['Ambulatoriale'],
                ['Domiciliare'],
                ['Piccolo gruppo (PG)'],
                ['Ambulatoriale + PG'],
                ['Centro diurno'],
                ['Semiconvitto'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%setting}}');
    }
}
