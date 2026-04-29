<?php

use yii\db\Migration;

/**
 * Registra in permission_metadata i due permessi del flusso document-request
 * introdotti dopo la migration originale, marcandoli come attivi (is_active = 1).
 *
 * Sono gia' presenti in auth_item (creati da
 * m260429_133156_add_document_request_status_permissions) ma non avevano
 * ancora la riga in permission_metadata: di conseguenza non risultavano
 * filtrati dalle viste come orfani, ma erano anche fuori dalla mappa di
 * gestione "permessi attivi" che usiamo per /site/my-permissions e affini.
 *
 * Questa migration allinea il metadato senza toccare il RBAC.
 */
class m260429_151843_register_doc_request_perms_metadata extends Migration
{
    private const PERMISSIONS = [
        'change_document_request_status',
        'mark_document_request_delivered',
    ];

    public function safeUp()
    {
        foreach (self::PERMISSIONS as $perm) {
            $exists = (new \yii\db\Query())
                ->from('{{%permission_metadata}}')
                ->where(['permission_name' => $perm])
                ->exists($this->db);

            if ($exists) {
                echo "- $perm gia' in permission_metadata, salto\n";
                continue;
            }

            $authItemExists = (new \yii\db\Query())
                ->from('{{%auth_item}}')
                ->where(['name' => $perm, 'type' => 2])
                ->exists($this->db);

            if (!$authItemExists) {
                echo "! $perm non trovato in auth_item, salto (lanciare prima m260429_133156)\n";
                continue;
            }

            $this->insert('{{%permission_metadata}}', [
                'permission_name' => $perm,
                'is_active' => 1,
                'notes' => 'Permesso document-request workflow (post migration originale).',
            ]);
            echo "+ registrato $perm in permission_metadata (is_active = 1)\n";
        }
    }

    public function safeDown()
    {
        $this->delete('{{%permission_metadata}}', [
            'permission_name' => self::PERMISSIONS,
        ]);
    }
}
