<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%regime}}`.
 */
class m250706_165256_create_regime_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%regime}}', [
            'id' => $this->primaryKey(),
            'nome' => $this->string()->notNull(),
            'descrizione' => $this->text(),
        ]);

        // Inserimento dei dati iniziali
        $this->batchInsert('{{%regime}}', 
            ['nome', 'descrizione'],
            [
                ['L11', 'Regime L11'],
                ['ABA', 'Regime ABA'],
                ['Privato', 'Regime Privato'],
                ['FKT', 'Regime FKT'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%regime}}');
    }
}
