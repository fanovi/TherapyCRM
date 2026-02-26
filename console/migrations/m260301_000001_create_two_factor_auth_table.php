<?php

use yii\db\Migration;

class m260301_000001_create_two_factor_auth_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_two_factor_auth}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull()->unique(),
            'is_enabled' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'preferred_method' => $this->string(10)->notNull()->defaultValue('email'),
            'totp_secret' => $this->string(255)->null(),
            'totp_confirmed_at' => $this->integer()->null(),
            'email_otp_code' => $this->string(255)->null(),
            'email_otp_expires_at' => $this->integer()->null(),
            'email_otp_attempts' => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-user_two_factor_auth-user_id',
            '{{%user_two_factor_auth}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex('idx-user_two_factor_auth-user_id', '{{%user_two_factor_auth}}', 'user_id', true);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-user_two_factor_auth-user_id', '{{%user_two_factor_auth}}');
        $this->dropTable('{{%user_two_factor_auth}}');
    }
}
