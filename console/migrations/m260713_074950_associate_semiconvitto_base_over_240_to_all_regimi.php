<?php

use yii\db\Migration;

class m260713_074950_associate_semiconvitto_base_over_240_to_all_regimi extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Recupero l'ID del setting "Semiconvitto Base Over 240"
        $settingId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%setting}}')
            ->where(['nome' => 'Semiconvitto Base Over 240'])
            ->scalar($this->db);

        if (!$settingId) {
            throw new \yii\db\Exception('Setting "Semiconvitto Base Over 240" non trovato. Eseguire prima la migration di creazione del setting.');
        }

        // Recupero gli ID di tutti i regimi
        $regimeIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%regime}}')
            ->column($this->db);

        foreach ($regimeIds as $regimeId) {
            // Evito duplicati nel caso l'associazione esista già
            $exists = (new \yii\db\Query())
                ->from('{{%regime_setting}}')
                ->where(['regime_id' => $regimeId, 'setting_id' => $settingId])
                ->exists($this->db);

            if (!$exists) {
                $this->insert('{{%regime_setting}}', [
                    'regime_id' => $regimeId,
                    'setting_id' => $settingId,
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $settingId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%setting}}')
            ->where(['nome' => 'Semiconvitto Base Over 240'])
            ->scalar($this->db);

        if ($settingId) {
            $this->delete('{{%regime_setting}}', ['setting_id' => $settingId]);
        }
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260713_074950_associate_semiconvitto_base_over_240_to_all_regimi cannot be reverted.\n";

        return false;
    }
    */
}
