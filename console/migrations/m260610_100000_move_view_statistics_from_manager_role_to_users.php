<?php

use yii\db\Migration;

/**
 * Ticket #281: rende gestibile per singolo utente il permesso "Visualizzare
 * statistiche e dashboard" (view_statistics) per i manager.
 *
 * Il permesso viene tolto dal ruolo 'manager' e assegnato direttamente a tutti
 * gli utenti che oggi hanno quel ruolo: il comportamento resta invariato, ma da
 * questo momento il permesso puo' essere tolto/assegnato al singolo manager
 * dalla griglia permessi del suo form utente.
 *
 * Il ruolo 'admin' mantiene il permesso a livello di ruolo.
 */
class m260610_100000_move_view_statistics_from_manager_role_to_users extends Migration
{
    const PERMISSION = 'view_statistics';
    const ROLE = 'manager';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission(self::PERMISSION);
        $role = $auth->getRole(self::ROLE);

        if (!$permission || !$role) {
            echo "Permesso o ruolo non trovato, nessuna modifica.\n";
            return true;
        }

        // Assegna il permesso direttamente a tutti gli utenti con ruolo manager
        $managerUserIds = $auth->getUserIdsByRole(self::ROLE);
        $assigned = 0;

        foreach ($managerUserIds as $userId) {
            if (!$auth->getAssignment(self::PERMISSION, $userId)) {
                $auth->assign($permission, $userId);
                $assigned++;
            }
        }

        echo "Permesso '" . self::PERMISSION . "' assegnato direttamente a {$assigned} utenti manager.\n";

        // Rimuovi il permesso dal ruolo manager
        if ($auth->hasChild($role, $permission)) {
            $auth->removeChild($role, $permission);
            echo "Permesso '" . self::PERMISSION . "' rimosso dal ruolo '" . self::ROLE . "'.\n";
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission(self::PERMISSION);
        $role = $auth->getRole(self::ROLE);

        if (!$permission || !$role) {
            return true;
        }

        // Ripristina il permesso sul ruolo manager
        if (!$auth->hasChild($role, $permission)) {
            $auth->addChild($role, $permission);
        }

        // Rimuovi le assegnazioni dirette dagli utenti manager (tornano a
        // ereditarlo dal ruolo)
        $managerUserIds = $auth->getUserIdsByRole(self::ROLE);
        foreach ($managerUserIds as $userId) {
            if ($auth->getAssignment(self::PERMISSION, $userId)) {
                $auth->revoke($permission, $userId);
            }
        }

        return true;
    }
}
