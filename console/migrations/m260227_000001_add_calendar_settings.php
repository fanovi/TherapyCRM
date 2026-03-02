<?php

use yii\db\Migration;

class m260227_000001_add_calendar_settings extends Migration
{
    public function safeUp()
    {
        $now = time();
        $this->batchInsert('{{%system_setting}}', ['category', 'key', 'value', 'type', 'description', 'created_at', 'updated_at'], [
            ['calendar', 'patient_past_range_value', '3', 'integer', 'Quantità nel passato', $now, $now],
            ['calendar', 'patient_past_range_unit', 'months', 'string', 'Unità passato: months o days', $now, $now],
            ['calendar', 'patient_future_range_value', '3', 'integer', 'Quantità nel futuro', $now, $now],
            ['calendar', 'patient_future_range_unit', 'months', 'string', 'Unità futuro: months o days', $now, $now],
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%system_setting}}', ['category' => 'calendar']);
    }
}
