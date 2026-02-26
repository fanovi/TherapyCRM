<?php

use yii\db\Migration;

class m260301_000003_create_system_settings_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%system_setting}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(50)->notNull(),
            'key' => $this->string(100)->notNull(),
            'value' => $this->text()->null(),
            'type' => $this->string(20)->notNull()->defaultValue('string'),
            'description' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-system_setting-category-key', '{{%system_setting}}', ['category', 'key'], true);

        // Insert default 2FA settings
        $now = time();
        $this->batchInsert('{{%system_setting}}', ['category', 'key', 'value', 'type', 'description', 'created_at', 'updated_at'], [
            ['2fa', 'enabled', '0', 'boolean', '2FA globale on/off', $now, $now],
            ['2fa', 'remember_device_enabled', '0', 'boolean', '"Ricorda dispositivo" on/off', $now, $now],
            ['2fa', 'remember_device_days', '30', 'integer', 'Giorni validità dispositivo', $now, $now],
            ['2fa', 'email_otp_expiry_seconds', '300', 'integer', 'Scadenza OTP email (secondi)', $now, $now],
            ['2fa', 'max_otp_attempts', '5', 'integer', 'Max tentativi OTP', $now, $now],
            ['2fa', 'totp_issuer_name', 'TherapyCRM', 'string', 'Nome nell\'app authenticator', $now, $now],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%system_setting}}');
    }
}
