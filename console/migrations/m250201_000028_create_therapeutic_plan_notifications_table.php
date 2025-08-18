<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%therapeutic_plan_notifications}}`.
 */
class m250201_000028_create_therapeutic_plan_notifications_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%therapeutic_plan_notifications}}', [
            'id' => $this->primaryKey(),
            'therapeutic_plan_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'days_before' => $this->integer()->notNull(),
            'notification_id' => $this->integer()->null(),
            'sent_at' => $this->datetime()->notNull(),
            'created_at' => $this->datetime()->notNull(),
        ]);

        // Indici
        $this->createIndex(
            '{{%idx-therapeutic_plan_notifications-therapeutic_plan_id}}',
            '{{%therapeutic_plan_notifications}}',
            'therapeutic_plan_id'
        );

        $this->createIndex(
            '{{%idx-therapeutic_plan_notifications-user_id}}',
            '{{%therapeutic_plan_notifications}}',
            'user_id'
        );

        $this->createIndex(
            '{{%idx-therapeutic_plan_notifications-notification_id}}',
            '{{%therapeutic_plan_notifications}}',
            'notification_id'
        );

        // Indice unico per evitare duplicati
        $this->createIndex(
            '{{%idx-therapeutic_plan_notifications-unique}}',
            '{{%therapeutic_plan_notifications}}',
            ['therapeutic_plan_id', 'user_id', 'days_before'],
            true
        );

        // Foreign keys
        $this->addForeignKey(
            '{{%fk-therapeutic_plan_notifications-therapeutic_plan_id}}',
            '{{%therapeutic_plan_notifications}}',
            'therapeutic_plan_id',
            '{{%therapeutic_plans}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%fk-therapeutic_plan_notifications-user_id}}',
            '{{%therapeutic_plan_notifications}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%fk-therapeutic_plan_notifications-notification_id}}',
            '{{%therapeutic_plan_notifications}}',
            'notification_id',
            '{{%notifications}}',
            'id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign keys
        $this->dropForeignKey(
            '{{%fk-therapeutic_plan_notifications-therapeutic_plan_id}}',
            '{{%therapeutic_plan_notifications}}'
        );

        $this->dropForeignKey(
            '{{%fk-therapeutic_plan_notifications-user_id}}',
            '{{%therapeutic_plan_notifications}}'
        );

        $this->dropForeignKey(
            '{{%fk-therapeutic_plan_notifications-notification_id}}',
            '{{%therapeutic_plan_notifications}}'
        );

        $this->dropTable('{{%therapeutic_plan_notifications}}');
    }
}
