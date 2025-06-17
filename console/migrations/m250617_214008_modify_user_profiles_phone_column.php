<?php

use yii\db\Migration;

/**
 * Class m250617_214008_modify_user_profiles_phone_column
 */
class m250617_214008_modify_user_profiles_phone_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Modifica la colonna phone per supportare dati crittografati (fino a 255 caratteri)
        $this->alterColumn('{{%user_profiles}}', 'phone', $this->string(255));
        
        // Modifica anche la colonna address per supportare indirizzi crittografati più lunghi
        $this->alterColumn('{{%user_profiles}}', 'address', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ripristina le dimensioni originali
        $this->alterColumn('{{%user_profiles}}', 'phone', $this->string(20));
        $this->alterColumn('{{%user_profiles}}', 'address', $this->text());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250617_214008_modify_user_profiles_phone_column cannot be reverted.\n";

        return false;
    }
    */
}
