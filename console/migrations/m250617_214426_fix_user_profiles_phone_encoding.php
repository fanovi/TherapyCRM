<?php

use yii\db\Migration;

/**
 * Class m250617_214426_fix_user_profiles_phone_encoding
 */
class m250617_214426_fix_user_profiles_phone_encoding extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Cambia la colonna phone da VARCHAR a TEXT per supportare dati binari crittografati
        $this->alterColumn('{{%user_profiles}}', 'phone', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ripristina VARCHAR(255) 
        $this->alterColumn('{{%user_profiles}}', 'phone', $this->string(255));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250617_214426_fix_user_profiles_phone_encoding cannot be reverted.\n";

        return false;
    }
    */
}
