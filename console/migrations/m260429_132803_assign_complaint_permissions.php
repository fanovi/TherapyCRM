<?php

use yii\db\Migration;

/**
 * Migration per creare e assegnare i permessi del modulo Reclami (Complaints)
 *
 * PERMESSI CREATI:
 * - view_complaints   : visualizzare l'elenco e il dettaglio dei reclami
 * - manage_complaints : gestire i reclami (azioni amministrative, es. eliminazione)
 *
 * RUOLI INTERESSATI (assegnazione di default):
 * - super_admin : view_complaints + manage_complaints
 * - admin       : view_complaints + manage_complaints
 */
class m260429_132803_assign_complaint_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "📣 Inizio creazione/assegnazione permessi modulo Reclami...\n\n";

        // Verifica che i ruoli esistano
        $superAdminRole = $auth->getRole('super_admin');
        $adminRole = $auth->getRole('admin');

        if (!$superAdminRole) {
            throw new \Exception('Ruolo super_admin non trovato. Applicare prima la migration che lo crea.');
        }

        if (!$adminRole) {
            throw new \Exception('Ruolo admin non trovato. Applicare prima la migration RBAC base.');
        }

        // Crea i permessi se non esistono
        $permissions = [
            'view_complaints'   => 'Visualizzare reclami - Può vedere lista e dettaglio dei reclami',
            'manage_complaints' => 'Gestire reclami - Può eseguire azioni amministrative sui reclami',
        ];

        foreach ($permissions as $name => $description) {
            $permission = $auth->getPermission($name);
            if (!$permission) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "✓ Permesso '$name' creato\n";
            } else {
                echo "- Permesso '$name' già esistente\n";
            }
        }

        // Assegnazione ai ruoli
        $rolesToAssign = [
            'super_admin' => $superAdminRole,
            'admin'       => $adminRole,
        ];

        foreach ($rolesToAssign as $roleName => $role) {
            echo "\n👤 Assegnazione permessi a '$roleName'...\n";
            foreach (array_keys($permissions) as $permName) {
                $permission = $auth->getPermission($permName);
                if ($permission && !$auth->hasChild($role, $permission)) {
                    $auth->addChild($role, $permission);
                    echo "  ✓ $permName assegnato a $roleName\n";
                } else {
                    echo "  - $permName già assegnato a $roleName\n";
                }
            }
        }

        echo "\n✅ Permessi modulo Reclami configurati correttamente.\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 Rimozione permessi modulo Reclami...\n";

        $superAdminRole = $auth->getRole('super_admin');
        $adminRole = $auth->getRole('admin');

        $permissionNames = ['view_complaints', 'manage_complaints'];

        // Rimuovi le assegnazioni dai ruoli
        foreach ([$superAdminRole, $adminRole] as $role) {
            if (!$role) {
                continue;
            }
            foreach ($permissionNames as $permName) {
                $permission = $auth->getPermission($permName);
                if ($permission && $auth->hasChild($role, $permission)) {
                    $auth->removeChild($role, $permission);
                    echo "✓ $permName rimosso da {$role->name}\n";
                }
            }
        }

        // Rimuovi i permessi
        foreach ($permissionNames as $permName) {
            $permission = $auth->getPermission($permName);
            if ($permission) {
                $auth->remove($permission);
                echo "✓ Permesso '$permName' rimosso\n";
            }
        }

        echo "✅ Rimozione completata.\n";

        return true;
    }
}
