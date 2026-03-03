<?php

use yii\db\Migration;

class m260303_000001_create_biometric_device_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%biometric_device}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'biometric_token' => $this->string(128)->notNull()->unique(),
            'device_name' => $this->string(255)->null(),
            'platform' => $this->string(20)->null(),
            'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'failed_attempts' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'last_used_at' => $this->integer()->null(),
            'expires_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-biometric_device-user_id',
            '{{%biometric_device}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('idx-biometric_device-user_id', '{{%biometric_device}}', 'user_id');
        $this->createIndex('idx-biometric_device-biometric_token', '{{%biometric_device}}', 'biometric_token', true);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-biometric_device-user_id', '{{%biometric_device}}');
        $this->dropTable('{{%biometric_device}}');
    }
}
