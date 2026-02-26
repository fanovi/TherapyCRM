<?php

use yii\db\Migration;

class m260301_000002_create_trusted_devices_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%trusted_device}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'device_token' => $this->string(64)->notNull()->unique(),
            'device_name' => $this->string(255)->null(),
            'last_used_at' => $this->integer()->null(),
            'expires_at' => $this->integer()->notNull(),
            'is_revoked' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-trusted_device-user_id',
            '{{%trusted_device}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('idx-trusted_device-user_id', '{{%trusted_device}}', 'user_id');
        $this->createIndex('idx-trusted_device-device_token', '{{%trusted_device}}', 'device_token', true);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-trusted_device-user_id', '{{%trusted_device}}');
        $this->dropTable('{{%trusted_device}}');
    }
}
