<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notifications}}`.
 */
class m250201_000021_create_notifications_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notifications}}', [
            'id' => $this->primaryKey(),
            'recipient_user_id' => $this->integer()->notNull(),
            'sender_user_id' => $this->integer(),
            'notification_type' => "ENUM('info', 'reminder', 'deadline', 'mandatory_read')",
            'title' => $this->string(255)->notNull(),
            'message' => $this->text(),
            'requires_read_confirmation' => $this->boolean()->defaultValue(false),
            'read_at' => $this->dateTime(),
            'viewed_at' => $this->timestamp()->null(),
            'scheduled_for' => $this->dateTime(),
            'sent_at' => $this->dateTime(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-notifications-recipient_user_id',
            '{{%notifications}}',
            'recipient_user_id',
            '{{%users}}',
            'id'
        );

        $this->addForeignKey(
            'fk-notifications-sender_user_id',
            '{{%notifications}}',
            'sender_user_id',
            '{{%users}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_recipient_unread', '{{%notifications}}', ['recipient_user_id', 'read_at']);
        $this->createIndex('idx_scheduled', '{{%notifications}}', 'scheduled_for');
        
        // Indici per viewed_at (integrati dalla migrazione m240101_120000)
        $this->createIndex('idx-notifications-viewed_at', '{{%notifications}}', 'viewed_at');
        $this->createIndex(
            'idx-notifications-blocking-status',
            '{{%notifications}}',
            ['recipient_user_id', 'requires_read_confirmation', 'read_at', 'viewed_at']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-notifications-sender_user_id', '{{%notifications}}');
        $this->dropForeignKey('fk-notifications-recipient_user_id', '{{%notifications}}');
        
        $this->dropTable('{{%notifications}}');
    }
} 