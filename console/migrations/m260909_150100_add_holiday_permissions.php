<?php

use yii\db\Migration;

/**
 * Permessi del modulo giorni festivi (docs/PLAN_GIORNI_FESTIVI_2026-09-09.md).
 *
 * Governano solo l'anagrafica delle chiusure (/holiday). Il blocco degli
 * appuntamenti nei giorni chiusi NON e' soggetto a permesso: vale per chiunque,
 * come i conflitti terapista, e non esiste un permesso di bypass.
 *
 * Assegnati ai ruoli admin e super_admin; grazie al RBAC per-persona possono
 * poi essere concessi al singolo utente da /user/update senza cambiargli ruolo.
 */
class m260909_150100_add_holiday_permissions extends Migration
{
    private const PERMISSIONS = [
        'view_holiday' => 'Visualizzare i giorni festivi',
        'create_holiday' => 'Creare giorni festivi',
        'update_holiday' => 'Modificare giorni festivi',
        'delete_holiday' => 'Eliminare giorni festivi',
    ];
    private const ROLES = ['admin', 'super_admin'];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "📅 Creazione permessi giorni festivi...\n";
        $permissions = [];
        foreach (self::PERMISSIONS as $name => $description) {
            $permission = $auth->getPermission($name);
            if (!$permission) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "  ✓ Permesso '$name' creato\n";
            } else {
                echo "  - Permesso '$name' gia' esistente\n";
            }
            $permissions[] = $permission;
        }

        echo "\n🎭 Assegnazione ai ruoli...\n";
        foreach (self::ROLES as $roleName) {
            $role = $auth->getRole($roleName);
            if (!$role) {
                echo "  - Ruolo '$roleName' non trovato, salto\n";
                continue;
            }
            foreach ($permissions as $permission) {
                if (!$auth->hasChild($role, $permission)) {
                    $auth->addChild($role, $permission);
                    echo "  ✓ '{$permission->name}' assegnato a '$roleName'\n";
                } else {
                    echo "  - '{$permission->name}' gia' presente su '$roleName'\n";
                }
            }
        }

        echo "\n🗂  Registrazione in permission_metadata...\n";
        foreach (array_keys(self::PERMISSIONS) as $name) {
            $exists = (new \yii\db\Query())
                ->from('{{%permission_metadata}}')
                ->where(['permission_name' => $name])
                ->exists($this->db);
            if ($exists) {
                echo "  - $name gia' in permission_metadata\n";
                continue;
            }

            $this->insert('{{%permission_metadata}}', [
                'permission_name' => $name,
                'is_active' => 1,
                'notes' => 'Modulo giorni festivi.',
            ]);
        }

        echo "\n✅ Migrazione completata\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permission = $auth->getPermission($name);
            if ($permission) {
                // remove() elimina anche le relazioni in auth_item_child e auth_assignment
                $auth->remove($permission);
                echo "  ✓ Permesso '$name' rimosso\n";
            }
        }
        $this->delete('{{%permission_metadata}}', ['permission_name' => array_keys(self::PERMISSIONS)]);

        echo "\n✅ Rollback completato\n";

        return true;
    }
}
