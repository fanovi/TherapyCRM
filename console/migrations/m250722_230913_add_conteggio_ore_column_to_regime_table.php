<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%regime}}`.
 */
class m250722_230913_add_conteggio_ore_column_to_regime_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi la colonna
        $this->addColumn('{{%regime}}', 'conteggio_ore', "ENUM('weekly', 'monthly') NOT NULL DEFAULT 'weekly' AFTER `descrizione`");
        
        // Aggiorna il valore per ABA a 'monthly'
        $this->update('{{%regime}}', ['conteggio_ore' => 'monthly'], ['nome' => 'ABA']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%regime}}', 'conteggio_ore');
    }
}
