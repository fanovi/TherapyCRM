<?php

use yii\db\Migration;

/**
 * Assegna il permesso `view_patient_absence` ai ruoli `super_admin` e `coordinator`.
 *
 * Contesto: la migration `m260226_120000_add_patient_absence_permission` aveva
 * assegnato il permesso solo ad admin/manager/therapist, lasciando fuori
 * super_admin e coordinator. Coordinator e' filtrato lato controller per vedere
 * solo le assenze dei pazienti dei propri terapisti (CoordinatorGroup).
 */
class m260429_184108_assign_view_patient_absence_to_super_admin_coordinator extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission('view_patient_absence');
        if (!$permission) {
            throw new \Exception("Permesso 'view_patient_absence' non trovato. Eseguire prima m260226_120000_add_patient_absence_permission.");
        }

        $rolesToAssign = ['super_admin', 'coordinator'];

        foreach ($rolesToAssign as $roleName) {
            $role = $auth->getRole($roleName);
            if (!$role) {
                echo "  - Ruolo '$roleName' non trovato, skip.\n";
                continue;
            }
            if ($auth->hasChild($role, $permission)) {
                echo "  - 'view_patient_absence' gia' assegnato a '$roleName'\n";
                continue;
            }
            $auth->addChild($role, $permission);
            echo "  + 'view_patient_absence' assegnato a '$roleName'\n";
        }

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('view_patient_absence');
        if (!$permission) {
            return true;
        }

        foreach (['super_admin', 'coordinator'] as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role && $auth->hasChild($role, $permission)) {
                $auth->removeChild($role, $permission);
                echo "  - 'view_patient_absence' rimosso da '$roleName'\n";
            }
        }

        return true;
    }
}
