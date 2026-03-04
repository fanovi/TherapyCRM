<?php

use yii\db\Migration;

class m260304_101651_add_password_changed_at_to_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%users}}', 'password_changed_at', $this->dateTime()->null()->after('password_hash'));

        // Popola con updated_at per utenti esistenti
        $this->execute('UPDATE {{%users}} SET password_changed_at = FROM_UNIXTIME(updated_at) WHERE updated_at IS NOT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('{{%users}}', 'password_changed_at');
    }
}
