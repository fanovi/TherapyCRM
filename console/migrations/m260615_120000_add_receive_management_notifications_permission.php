<?php

use yii\db\Migration;

/**
 * Migration per il permesso che abilita la ricezione delle notifiche direzionali.
 *
 * Le notifiche operative (es. assenza paziente segnalata) vengono inviate al
 * "management" tramite NotificationHelper::sendToManagement(). Fino ad ora i
 * destinatari erano risolti per RUOLO (solo 'manager' + 'admin'), escludendo i
 * 'super_admin' e ignorando le abilitazioni concesse al singolo utente
 * (RBAC per-persona). Questo permesso rende la regola esplicita e gestibile:
 *
 * - 'receive_management_notifications' viene assegnato ai ruoli admin, manager
 *   e super_admin;
 * - puo' inoltre essere assegnato direttamente a un singolo utente per
 *   abilitarlo alla ricezione senza cambiargli ruolo.
 *
 * La risoluzione dei destinatari avviene in NotificationHelper::getUserIdsByPermission()
 * che considera sia l'eredita' dal ruolo sia l'assegnazione diretta.
 */
class m260615_120000_add_receive_management_notifications_permission extends Migration
{
    private const PERMISSION = 'receive_management_notifications';
    private const ROLES = ['admin', 'manager', 'super_admin'];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔔 Creazione permesso '" . self::PERMISSION . "'...\n";

        $permission = $auth->getPermission(self::PERMISSION);
        if (!$permission) {
            $permission = $auth->createPermission(self::PERMISSION);
            $permission->description = 'Ricevere le notifiche direzionali (assenze, cancellazioni, ecc.)';
            $auth->add($permission);
            echo "  ✓ Permesso creato\n";
        } else {
            echo "  - Permesso gia' esistente\n";
        }

        echo "\n🎭 Assegnazione ai ruoli direzionali...\n";
        foreach (self::ROLES as $roleName) {
            $role = $auth->getRole($roleName);
            if (!$role) {
                echo "  - Ruolo '$roleName' non trovato, salto\n";
                continue;
            }
            if (!$auth->hasChild($role, $permission)) {
                $auth->addChild($role, $permission);
                echo "  ✓ '" . self::PERMISSION . "' assegnato a '$roleName'\n";
            } else {
                echo "  - '" . self::PERMISSION . "' gia' presente su '$roleName'\n";
            }
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

        $permission = $auth->getPermission(self::PERMISSION);
        if ($permission) {
            // remove() elimina anche le relazioni in auth_item_child e auth_assignment
            $auth->remove($permission);
            echo "  ✓ Permesso '" . self::PERMISSION . "' rimosso\n";
        }

        echo "\n✅ Rollback completato\n";

        return true;
    }
}
