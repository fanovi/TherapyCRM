<?php

use yii\db\Migration;

class m250921_102350_create_provincia_comune_tables extends Migration
{
    public function safeUp()
    {
        // Tabella provincia
        $this->createTable('provincia', [
            'id' => $this->primaryKey(),
            'nome' => $this->string(255)->notNull()->unique(),
            'sigla' => $this->string(2)->notNull()->unique(),
        ]);

        // Tabella comune
        $this->createTable('comune', [
            'id' => $this->primaryKey(),
            'nome' => $this->string(255)->notNull(),
            'provincia_id' => $this->integer()->notNull(),
            'codice_catasto' => $this->string(4)->notNull()->unique(),
        ]);

        // Indici
        $this->createIndex('idx-comune-provincia_id', 'comune', 'provincia_id');
        $this->createIndex('idx-comune-codice_catasto', 'comune', 'codice_catasto');

        // Foreign key
        $this->addForeignKey(
            'fk-comune-provincia_id',
            'comune',
            'provincia_id',
            'provincia',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-comune-provincia_id', 'comune');
        $this->dropTable('comune');
        $this->dropTable('provincia');
    }
}
