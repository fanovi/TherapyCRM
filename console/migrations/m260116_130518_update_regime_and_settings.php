<?php

use yii\db\Migration;

class m260116_130518_update_regime_and_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Update regime id 4 name from "FKT" to "FKT 377"
        $this->update('{{%regime}}', 
            ['nome' => 'FKT 377'], 
            ['id' => 4]
        );

        // 2. Clear regime_setting table
        $this->delete('{{%regime_setting}}');

        // 3. Reinsert relationships for Regime id 1 (L11)
        // Settings: 1, 4, 2, 5, 13, 6, 12
        $l11Settings = [1, 4, 2, 5, 13, 6, 12];
        foreach ($l11Settings as $settingId) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => 1,
                'setting_id' => $settingId
            ]);
        }

        // 4. Insert relationship for Regime id 4 (FKT 377)
        // Setting: 1
        $this->insert('{{%regime_setting}}', [
            'regime_id' => 4,
            'setting_id' => 1
        ]);

        // 5. Insert relationships for Regime id 2 (ABA)
        // Settings: 7, 8, 9
        $abaSettings = [7, 8, 9];
        foreach ($abaSettings as $settingId) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => 2,
                'setting_id' => $settingId
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert relationships: clear table first
        $this->delete('{{%regime_setting}}');

        // Restore original relationships for Regime id 1 (L11)
        // Original settings from dump: 1, 2, 4, 5, 6
        $originalL11Settings = [1, 2, 4, 5, 6];
        foreach ($originalL11Settings as $settingId) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => 1,
                'setting_id' => $settingId
            ]);
        }

        // Revert regime id 4 name from "FKT 377" back to "FKT"
        $this->update('{{%regime}}', 
            ['nome' => 'FKT'], 
            ['id' => 4]
        );
    }
}
