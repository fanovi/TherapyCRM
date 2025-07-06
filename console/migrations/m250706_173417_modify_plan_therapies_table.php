<?php

use yii\db\Migration;

class m250706_173417_modify_plan_therapies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuovo la colonna health_regime
        $this->dropColumn('{{%plan_therapies}}', 'health_regime');

        // Aggiungo la colonna setting_id
        $this->addColumn('{{%plan_therapies}}', 'setting_id', $this->integer()->notNull());

        // Creo l'indice per la foreign key
        $this->createIndex(
            'idx-plan_therapies-setting_id',
            '{{%plan_therapies}}',
            'setting_id'
        );

        // Aggiungo la foreign key
        $this->addForeignKey(
            'fk-plan_therapies-setting_id',
            '{{%plan_therapies}}',
            'setting_id',
            '{{%setting}}',
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
        $this->dropForeignKey('fk-plan_therapies-setting_id', '{{%plan_therapies}}');

        // Rimuovo l'indice
        $this->dropIndex('idx-plan_therapies-setting_id', '{{%plan_therapies}}');

        // Rimuovo la colonna setting_id
        $this->dropColumn('{{%plan_therapies}}', 'setting_id');

        // Ripristino la colonna health_regime
        $this->addColumn('{{%plan_therapies}}', 'health_regime', $this->string());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250706_173417_modify_plan_therapies_table cannot be reverted.\n";

        return false;
    }
    */
}
