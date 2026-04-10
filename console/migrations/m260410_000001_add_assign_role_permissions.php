<?php

use yii\db\Migration;

/**
 * Aggiunge il permesso assign_role_permissions (gestione permessi sui ruoli).
 * Solo super_admin può assegnare/rimuovere permessi dai ruoli.
 * manage_permissions resta per la gestione permessi sui singoli utenti.
 */
class m260410_000001_add_assign_role_permissions extends Migration
{
    public function safeUp()
    {
        // Insert the assign_role_permissions permission
        $this->insert('{{%auth_item}}', [
            'name' => 'assign_role_permissions',
            'type' => 2, // TYPE_PERMISSION
            'description' => 'Può assegnare e rimuovere permessi ai ruoli',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Assign to super_admin only
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'super_admin',
            'child' => 'assign_role_permissions',
        ]);

        // Add to permission_metadata
        $this->insert('{{%permission_metadata}}', [
            'permission_name' => 'assign_role_permissions',
            'is_active' => 1,
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%permission_metadata}}', [
            'permission_name' => 'assign_role_permissions',
        ]);

        $this->delete('{{%auth_item_child}}', [
            'parent' => 'super_admin',
            'child' => 'assign_role_permissions',
        ]);

        $this->delete('{{%auth_item}}', [
            'name' => 'assign_role_permissions',
        ]);
    }
}
