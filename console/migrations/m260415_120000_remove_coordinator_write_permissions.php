<?php

use yii\db\Migration;

/**
 * Rimuove i permessi di scrittura dal ruolo coordinator per renderlo read-only.
 * Permessi rimossi: update_appointment, manage_coordinator_groups
 */
class m260415_120000_remove_coordinator_write_permissions extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $role = $auth->getRole('coordinator');

        // Rimuovi update_appointment
        $updateAppointment = $auth->getPermission('update_appointment');
        if ($role && $updateAppointment) {
            $auth->removeChild($role, $updateAppointment);
            echo "Rimosso permesso 'update_appointment' dal ruolo 'coordinator'\n";
        }

        // Rimuovi manage_coordinator_groups
        $manageCoordinatorGroups = $auth->getPermission('manage_coordinator_groups');
        if ($role && $manageCoordinatorGroups) {
            $auth->removeChild($role, $manageCoordinatorGroups);
            echo "Rimosso permesso 'manage_coordinator_groups' dal ruolo 'coordinator'\n";
        }

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $role = $auth->getRole('coordinator');

        // Ripristina update_appointment
        $updateAppointment = $auth->getPermission('update_appointment');
        if ($role && $updateAppointment) {
            $auth->addChild($role, $updateAppointment);
            echo "Ripristinato permesso 'update_appointment' al ruolo 'coordinator'\n";
        }

        // Ripristina manage_coordinator_groups
        $manageCoordinatorGroups = $auth->getPermission('manage_coordinator_groups');
        if ($role && $manageCoordinatorGroups) {
            $auth->addChild($role, $manageCoordinatorGroups);
            echo "Ripristinato permesso 'manage_coordinator_groups' al ruolo 'coordinator'\n";
        }

        return true;
    }
}
