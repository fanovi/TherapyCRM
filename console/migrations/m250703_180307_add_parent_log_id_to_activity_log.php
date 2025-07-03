<?php

use yii\db\Migration;

/**
 * Aggiunge la colonna parent_log_id alla tabella activity_log per supportare il logging gerarchico
 */
class m250703_180307_add_parent_log_id_to_activity_log extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%activity_log}}', 'parent_log_id', $this->integer()->null()->after('user_agent'));
        
        // Aggiunge foreign key
        $this->addForeignKey(
            'fk-activity_log-parent_log_id',
            '{{%activity_log}}',
            'parent_log_id',
            '{{%activity_log}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Crea indice
        $this->createIndex('idx-activity_log-parent_log_id', '{{%activity_log}}', 'parent_log_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-activity_log-parent_log_id', '{{%activity_log}}');
        $this->dropIndex('idx-activity_log-parent_log_id', '{{%activity_log}}');
        $this->dropColumn('{{%activity_log}}', 'parent_log_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250703_180307_add_parent_log_id_to_activity_log cannot be reverted.\n";

        return false;
    }
    */
}
