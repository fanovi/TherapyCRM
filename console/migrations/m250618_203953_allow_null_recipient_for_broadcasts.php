<?php

use yii\db\Migration;

class m250618_203953_allow_null_recipient_for_broadcasts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuovi il vincolo di chiave esterna
        $this->dropForeignKey('fk-notifications-recipient_user_id', 'notifications');
        
        // Modifica la colonna per permettere NULL
        $this->alterColumn('notifications', 'recipient_user_id', $this->integer()->null());
        
        // Ricrea il vincolo di chiave esterna con NULL permesso
        $this->addForeignKey(
            'fk-notifications-recipient_user_id',
            'notifications',
            'recipient_user_id',
            'users',
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
        // Rimuovi il vincolo di chiave esterna
        $this->dropForeignKey('fk-notifications-recipient_user_id', 'notifications');
        
        // Elimina le notifiche broadcast (recipient_user_id = NULL)
        $this->delete('notifications', ['recipient_user_id' => null]);
        
        // Ripristina la colonna come NOT NULL
        $this->alterColumn('notifications', 'recipient_user_id', $this->integer()->notNull());
        
        // Ricrea il vincolo di chiave esterna
        $this->addForeignKey(
            'fk-notifications-recipient_user_id',
            'notifications',
            'recipient_user_id',
            'users',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250618_203953_allow_null_recipient_for_broadcasts cannot be reverted.\n";

        return false;
    }
    */
}
