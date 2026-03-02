<?php

use yii\db\Migration;

class m260227_000002_add_manage_permissions_permission extends Migration
{
    public function safeUp()
    {
        // Insert the manage_permissions permission
        $this->insert('{{%auth_item}}', [
            'name' => 'manage_permissions',
            'type' => 2, // TYPE_PERMISSION
            'description' => 'Gestione permessi ruoli e utenti',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Assign to super_admin
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'super_admin',
            'child' => 'manage_permissions',
        ]);

        // Assign to admin
        $this->insert('{{%auth_item_child}}', [
            'parent' => 'admin',
            'child' => 'manage_permissions',
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%auth_item_child}}', [
            'parent' => 'admin',
            'child' => 'manage_permissions',
        ]);

        $this->delete('{{%auth_item_child}}', [
            'parent' => 'super_admin',
            'child' => 'manage_permissions',
        ]);

        $this->delete('{{%auth_item}}', [
            'name' => 'manage_permissions',
        ]);
    }
}
