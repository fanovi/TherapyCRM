<?php

use yii\db\Migration;

/**
 * Aggiunge il setting "Regime misto" (interno) e lo lega al regime ABA.
 *
 * Il regime ABA e' individuato per nome, con lo stesso criterio di
 * TherapeuticPlan::isABARegime(), perche' l'id varia tra i vari database.
 */
class m260925_100000_add_regime_misti_setting extends Migration
{
    const SETTING_NAME = 'Regime misto';

    public function safeUp()
    {
        $db = $this->db;

        $abaRegimeId = $this->findAbaRegimeId();

        // 1) Crea il setting se non esiste
        $settingId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%setting}}')
            ->where(['nome' => self::SETTING_NAME])
            ->scalar($db);

        if (!$settingId) {
            $this->insert('{{%setting}}', [
                'nome' => self::SETTING_NAME,
                'location_type' => 'internal',
            ]);
            $settingId = (int) $db->getLastInsertID();
        } else {
            $settingId = (int) $settingId;
        }

        // 2) Lega ABA -> Regime misto (se non gia' esistente)
        $exists = (new \yii\db\Query())
            ->from('{{%regime_setting}}')
            ->where(['regime_id' => $abaRegimeId, 'setting_id' => $settingId])
            ->exists($db);
        if (!$exists) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => $abaRegimeId,
                'setting_id' => $settingId,
            ]);
        }
    }

    public function safeDown()
    {
        $settingId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%setting}}')
            ->where(['nome' => self::SETTING_NAME])
            ->scalar($this->db);

        if ($settingId) {
            $this->delete('{{%regime_setting}}', ['setting_id' => (int) $settingId]);
            $this->delete('{{%setting}}', ['id' => (int) $settingId]);
        }
    }

    /**
     * Id del regime ABA: il nome deve contenere "ABA" e deve esserci un solo
     * regime che corrisponde, altrimenti la migration si ferma.
     */
    private function findAbaRegimeId()
    {
        $ids = (new \yii\db\Query())
            ->select('id')
            ->from('{{%regime}}')
            ->where(['like', 'nome', 'ABA'])
            ->column($this->db);

        if (count($ids) !== 1) {
            throw new \yii\base\Exception(
                'Atteso un solo regime ABA, trovati: ' . count($ids)
            );
        }

        return (int) $ids[0];
    }
}
