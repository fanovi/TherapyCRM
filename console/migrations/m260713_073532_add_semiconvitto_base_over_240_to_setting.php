<?php

use yii\db\Migration;

class m260713_073532_add_semiconvitto_base_over_240_to_setting extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('{{%setting}}', [
            'nome' => 'Semiconvitto Base Over 240',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%setting}}', ['nome' => 'Semiconvitto Base Over 240']);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260713_073532_add_semiconvitto_base_over_240_to_setting cannot be reverted.\n";

        return false;
    }
    */
}
