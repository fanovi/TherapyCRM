<?php

use yii\db\Migration;

class m260204_133225_add_super_admin_role extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔐 Creazione ruolo super_admin con tutti i permessi...\n\n";

        // Crea il ruolo super_admin se non esiste
        $superAdminRole = $auth->getRole('super_admin');
        if (!$superAdminRole) {
            $superAdminRole = $auth->createRole('super_admin');
            $superAdminRole->description = 'Super Amministratore - Accesso completo a tutte le funzionalità';
            $auth->add($superAdminRole);
            echo "✓ Ruolo 'super_admin' creato\n";
        } else {
            echo "- Ruolo 'super_admin' già esistente\n";
        }

        // Recupera tutti i permessi esistenti nel DB (type 2 = permission in auth_item)
        $permissions = $auth->getPermissions();
        $assignedCount = 0;

        foreach ($permissions as $permissionName => $permission) {
            if (!$auth->hasChild($superAdminRole, $permission)) {
                $auth->addChild($superAdminRole, $permission);
                $assignedCount++;
            }
        }

        echo "✓ Permessi assegnati a super_admin: $assignedCount (totale permessi nel sistema: " . count($permissions) . ")\n\n";
        echo "✅ Ruolo super_admin configurato correttamente.\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 Rimozione ruolo super_admin...\n";

        $superAdminRole = $auth->getRole('super_admin');
        if ($superAdminRole) {
            // Rimuovi prima tutte le assegnazioni figlio (permessi)
            $auth->removeChildren($superAdminRole);
            $auth->remove($superAdminRole);
            echo "✓ Ruolo 'super_admin' e relativi permessi rimossi\n";
        } else {
            echo "- Ruolo 'super_admin' non trovato\n";
        }

        echo "✅ Rollback completato.\n";

        return true;
    }
}