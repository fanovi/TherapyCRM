<?php

use yii\db\Migration;

/**
 * Migration per aggiungere i permessi per la gestione delle notifiche del sistema
 * 
 * PERMESSI AGGIUNTI:
 * - view_notifications: Visualizzare notifiche inviate dal sistema
 * - manage_notifications: Gestire notifiche del sistema (riservato per future funzionalità)
 */
class m250901_120000_add_notification_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔔 Inizio creazione permessi per notifiche...\n\n";

        // === CREAZIONE PERMESSI ===
        echo "📋 Creazione permessi notifiche...\n";
        
        $permissions = [
            'view_notifications' => 'Visualizzare notifiche del sistema',
            'manage_notifications' => 'Gestire notifiche del sistema (futuro)',
        ];

        $createdCount = 0;
        $existingCount = 0;

        foreach ($permissions as $name => $description) {
            $permission = $auth->getPermission($name);
            if (!$permission) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "  ✓ Permesso '$name' creato\n";
                $createdCount++;
            } else {
                echo "  - Permesso '$name' già esistente\n";
                $existingCount++;
            }
        }

        echo "\nPermessi creati: $createdCount\n";
        echo "Permessi esistenti: $existingCount\n";

        // === ASSEGNAZIONE PERMESSI AI RUOLI ===
        echo "\n🎭 Assegnazione permessi ai ruoli...\n";

        // Ottieni i ruoli
        $adminRole = $auth->getRole('admin');
        $managerRole = $auth->getRole('manager');
        $coordinatorRole = $auth->getRole('coordinator');

        $viewNotificationsPerm = $auth->getPermission('view_notifications');
        $manageNotificationsPerm = $auth->getPermission('manage_notifications');

        $assignedCount = 0;

        // ADMIN - Tutti i permessi
        if ($adminRole && $viewNotificationsPerm) {
            if (!$auth->hasChild($adminRole, $viewNotificationsPerm)) {
                $auth->addChild($adminRole, $viewNotificationsPerm);
                echo "  ✓ 'view_notifications' assegnato ad 'admin'\n";
                $assignedCount++;
            }
        }

        if ($adminRole && $manageNotificationsPerm) {
            if (!$auth->hasChild($adminRole, $manageNotificationsPerm)) {
                $auth->addChild($adminRole, $manageNotificationsPerm);
                echo "  ✓ 'manage_notifications' assegnato ad 'admin'\n";
                $assignedCount++;
            }
        }

        // MANAGER - Solo visualizzazione
        if ($managerRole && $viewNotificationsPerm) {
            if (!$auth->hasChild($managerRole, $viewNotificationsPerm)) {
                $auth->addChild($managerRole, $viewNotificationsPerm);
                echo "  ✓ 'view_notifications' assegnato a 'manager'\n";
                $assignedCount++;
            }
        }

        // COORDINATOR - Solo visualizzazione
        if ($coordinatorRole && $viewNotificationsPerm) {
            if (!$auth->hasChild($coordinatorRole, $viewNotificationsPerm)) {
                $auth->addChild($coordinatorRole, $viewNotificationsPerm);
                echo "  ✓ 'view_notifications' assegnato a 'coordinator'\n";
                $assignedCount++;
            }
        }

        echo "\nAssegnazioni create: $assignedCount\n";

        echo "\n✅ Migrazione permessi notifiche completata con successo!\n";
        echo "🔔 Gli utenti con ruoli admin, manager e coordinator possono ora accedere alle notifiche\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🗑️ Rimozione permessi notifiche...\n";

        // Rimuovi i permessi
        $permissions = ['view_notifications', 'manage_notifications'];
        
        foreach ($permissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission) {
                $auth->remove($permission);
                echo "  ✓ Permesso '$permissionName' rimosso\n";
            }
        }

        echo "\n✅ Rollback completato\n";

        return true;
    }
}
