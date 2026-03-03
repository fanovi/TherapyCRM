<?php

use yii\db\Migration;

class m260303_000002_add_biometric_system_settings extends Migration
{
    public function safeUp()
    {
        $this->batchInsert('{{%system_setting}}', ['category', 'key', 'value', 'type', 'description', 'created_at', 'updated_at'], [
            ['biometric', 'enabled', '1', 'boolean', 'Abilita autenticazione biometrica', time(), time()],
            ['biometric', 'max_failed_attempts', '3', 'integer', 'Numero massimo di tentativi falliti prima di revocare il dispositivo', time(), time()],
            ['biometric', 'token_expiry_days', '90', 'integer', 'Giorni di validità del token biometrico', time(), time()],
            ['biometric', 'max_devices_per_user', '3', 'integer', 'Numero massimo di dispositivi biometrici per utente', time(), time()],
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%system_setting}}', ['category' => 'biometric']);
    }
}
