<?php

use yii\db\Migration;

class m260316_100000_add_last_login_at_to_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%users}}', 'last_login_at', $this->dateTime()->null()->after('password_changed_at'));

        // Popola con updated_at per utenti esistenti
        $this->execute('UPDATE {{%users}} SET last_login_at = FROM_UNIXTIME(updated_at) WHERE updated_at IS NOT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('{{%users}}', 'last_login_at');
    }
}
