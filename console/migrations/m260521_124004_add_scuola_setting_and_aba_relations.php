<?php

use yii\db\Migration;

/**
 * Aggiunge il setting "Scuola" e crea le relazioni per il regime ABA (id=2):
 *   - lega ABA al nuovo setting Scuola
 *   - replica per ABA tutte le relazioni gia' esistenti per L11 (regime id=1)
 *     che non sono ancora presenti.
 */
class m260521_124004_add_scuola_setting_and_aba_relations extends Migration
{
    const REGIME_L11_ID = 1;
    const REGIME_ABA_ID = 2;
    const SETTING_NAME = 'Scuola';

    public function safeUp()
    {
        $db = $this->db;

        // 1) Crea il setting "Scuola" se non esiste
        $scuolaId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%setting}}')
            ->where(['nome' => self::SETTING_NAME])
            ->scalar($db);

        if (!$scuolaId) {
            $this->insert('{{%setting}}', ['nome' => self::SETTING_NAME]);
            $scuolaId = (int) $db->getLastInsertID();
        } else {
            $scuolaId = (int) $scuolaId;
        }

        // 2) Lega ABA -> Scuola (se non gia' esistente)
        $exists = (new \yii\db\Query())
            ->from('{{%regime_setting}}')
            ->where(['regime_id' => self::REGIME_ABA_ID, 'setting_id' => $scuolaId])
            ->exists($db);
        if (!$exists) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => self::REGIME_ABA_ID,
                'setting_id' => $scuolaId,
            ]);
        }

        // 3) Replica per ABA tutte le relazioni di L11 mancanti
        $l11SettingIds = (new \yii\db\Query())
            ->select('setting_id')
            ->from('{{%regime_setting}}')
            ->where(['regime_id' => self::REGIME_L11_ID])
            ->column($db);

        $abaSettingIds = (new \yii\db\Query())
            ->select('setting_id')
            ->from('{{%regime_setting}}')
            ->where(['regime_id' => self::REGIME_ABA_ID])
            ->column($db);

        $missing = array_diff($l11SettingIds, $abaSettingIds);
        foreach ($missing as $settingId) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => self::REGIME_ABA_ID,
                'setting_id' => (int) $settingId,
            ]);
        }
    }

    public function safeDown()
    {
        $scuolaId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%setting}}')
            ->where(['nome' => self::SETTING_NAME])
            ->scalar($this->db);

        // Rimuove la sola relazione ABA -> Scuola (le altre potrebbero essere
        // legittime in qualsiasi caso, non e' sicuro distinguerle dalle preesistenti).
        if ($scuolaId) {
            $this->delete('{{%regime_setting}}', [
                'regime_id' => self::REGIME_ABA_ID,
                'setting_id' => (int) $scuolaId,
            ]);
            $this->delete('{{%setting}}', ['id' => (int) $scuolaId]);
        }
    }
}
