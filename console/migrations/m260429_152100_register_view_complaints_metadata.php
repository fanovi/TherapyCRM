<?php

use yii\db\Migration;

/**
 * Registra in permission_metadata il permesso view_complaints (modulo Reclami)
 * marcandolo come attivo (is_active = 1).
 *
 * Il permesso e' gia' presente in auth_item (creato da
 * m260429_132803_assign_complaint_permissions) ed e' realmente cablato nel
 * codice (ComplaintController, sidebar, controllo accessi). Mancava solo
 * la riga in permission_metadata che lo classifica come "permesso reale e
 * attivo" per le viste di gestione (/site/my-permissions, /permission/roles,
 * /permission/view-role).
 */
class m260429_152100_register_view_complaints_metadata extends Migration
{
    private const PERMISSION = 'view_complaints';

    public function safeUp()
    {
        $exists = (new \yii\db\Query())
            ->from('{{%permission_metadata}}')
            ->where(['permission_name' => self::PERMISSION])
            ->exists($this->db);

        if ($exists) {
            echo "- " . self::PERMISSION . " gia' in permission_metadata, salto\n";
            return;
        }

        $authItemExists = (new \yii\db\Query())
            ->from('{{%auth_item}}')
            ->where(['name' => self::PERMISSION, 'type' => 2])
            ->exists($this->db);

        if (!$authItemExists) {
            echo "! " . self::PERMISSION . " non trovato in auth_item, salto (lanciare prima m260429_132803)\n";
            return;
        }

        $this->insert('{{%permission_metadata}}', [
            'permission_name' => self::PERMISSION,
            'is_active' => 1,
            'notes' => 'Permesso modulo Reclami (post migration originale).',
        ]);
        echo "+ registrato " . self::PERMISSION . " in permission_metadata (is_active = 1)\n";
    }

    public function safeDown()
    {
        $this->delete('{{%permission_metadata}}', [
            'permission_name' => self::PERMISSION,
        ]);
    }
}
