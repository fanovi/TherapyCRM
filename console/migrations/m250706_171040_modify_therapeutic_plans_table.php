<?php

use yii\db\Migration;

class m250706_171040_modify_therapeutic_plans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuovo la colonna status
        $this->dropColumn('{{%therapeutic_plans}}', 'status');

        // Aggiungo la colonna regime_id
        $this->addColumn('{{%therapeutic_plans}}', 'regime_id', $this->integer()->notNull());

        // Creo l'indice per la foreign key
        $this->createIndex(
            'idx-therapeutic_plans-regime_id',
            '{{%therapeutic_plans}}',
            'regime_id'
        );

        // Aggiungo la foreign key
        $this->addForeignKey(
            'fk-therapeutic_plans-regime_id',
            '{{%therapeutic_plans}}',
            'regime_id',
            '{{%regime}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovo la foreign key
        $this->dropForeignKey('fk-therapeutic_plans-regime_id', '{{%therapeutic_plans}}');

        // Rimuovo l'indice
        $this->dropIndex('idx-therapeutic_plans-regime_id', '{{%therapeutic_plans}}');

        // Rimuovo la colonna regime_id
        $this->dropColumn('{{%therapeutic_plans}}', 'regime_id');

        // Ripristino la colonna status
        $this->addColumn('{{%therapeutic_plans}}', 'status', $this->string());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250706_171040_modify_therapeutic_plans_table cannot be reverted.\n";

        return false;
    }
    */
}
