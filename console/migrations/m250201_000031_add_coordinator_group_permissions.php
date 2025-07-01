<?php

use yii\db\Migration;

/**
 * Migration per aggiungere i permessi di gestione gruppi coordinatori
 */
class m250201_000031_add_coordinator_group_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔐 Aggiunta permessi gestione gruppi coordinatori...\n\n";

        // Permessi per gestire gruppi di coordinatori
        $permissions = [
            'create_coordinator_group' => 'Creare gruppi di coordinatori',
            'view_coordinator_group' => 'Visualizzare gruppi di coordinatori',
            'update_coordinator_group' => 'Modificare gruppi di coordinatori',
            'delete_coordinator_group' => 'Eliminare gruppi di coordinatori',
            'view_own_group_therapists' => 'Visualizzare terapisti del proprio gruppo',
        ];

        // Crea i permessi
        foreach ($permissions as $name => $description) {
            if (!$auth->getPermission($name)) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "✓ Permesso '$name' creato\n";
            } else {
                echo "- Permesso '$name' già esistente\n";
            }
        }

        // Assegna permessi ai ruoli
        $adminRole = $auth->getRole('admin');
        $managerRole = $auth->getRole('manager');
        $coordinatorRole = $auth->getRole('coordinator');

        // Admin e Manager hanno accesso completo ai gruppi
        $adminManagerPermissions = ['create_coordinator_group', 'view_coordinator_group', 'update_coordinator_group', 'delete_coordinator_group'];
        
        foreach ([$adminRole, $managerRole] as $role) {
            if ($role) {
                foreach ($adminManagerPermissions as $permissionName) {
                    $permission = $auth->getPermission($permissionName);
                    if ($permission && !$auth->hasChild($role, $permission)) {
                        $auth->addChild($role, $permission);
                        echo "✓ '$permissionName' assegnato a '{$role->name}'\n";
                    }
                }
            }
        }

        // Coordinatore può visualizzare solo i propri terapisti
        if ($coordinatorRole) {
            $permission = $auth->getPermission('view_own_group_therapists');
            if ($permission && !$auth->hasChild($coordinatorRole, $permission)) {
                $auth->addChild($coordinatorRole, $permission);
                echo "✓ 'view_own_group_therapists' assegnato a 'coordinator'\n";
            }
        }

        echo "\n✅ Permessi gruppi coordinatori configurati!\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 Rimozione permessi gruppi coordinatori...\n";

        $permissions = ['create_coordinator_group', 'view_coordinator_group', 'update_coordinator_group', 'delete_coordinator_group', 'view_own_group_therapists'];
        
        foreach ($permissions as $name) {
            $permission = $auth->getPermission($name);
            if ($permission) {
                $auth->remove($permission);
                echo "✓ Permesso '$name' rimosso\n";
            }
        }

        echo "✅ Rimozione completata!\n";
    }
} 