<?php

use yii\db\Migration;

/**
 * Assegna manage_patient_absence al ruolo super_admin (mancante nella migration originale).
 */
class m260508_154025_assign_manage_patient_absence_to_super_admin extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $role = $auth->getRole('super_admin');
        $permission = $auth->getPermission('manage_patient_absence');

        if (!$role) {
            echo "  Ruolo 'super_admin' non trovato\n";
            return true;
        }
        if (!$permission) {
            echo "  Permesso 'manage_patient_absence' non trovato\n";
            return true;
        }

        if (!$auth->hasChild($role, $permission)) {
            $auth->addChild($role, $permission);
            echo "  'manage_patient_absence' assegnato a 'super_admin'\n";
        } else {
            echo "  super_admin ha gia' 'manage_patient_absence'\n";
        }

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole('super_admin');
        $permission = $auth->getPermission('manage_patient_absence');

        if ($role && $permission && $auth->hasChild($role, $permission)) {
            $auth->removeChild($role, $permission);
        }

        return true;
    }
}
