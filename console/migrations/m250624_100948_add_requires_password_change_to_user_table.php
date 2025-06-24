<?php

use yii\db\Migration;

class m250624_100948_add_requires_password_change_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%users}}', 'requires_password_change', $this->boolean()->defaultValue(0)->notNull());
        echo "Added requires_password_change column to users table.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%users}}', 'requires_password_change');
        echo "Dropped requires_password_change column from users table.\n";
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250624_100948_add_requires_password_change_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
