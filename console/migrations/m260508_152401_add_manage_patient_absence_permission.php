<?php

use yii\db\Migration;

/**
 * Aggiunge permesso manage_patient_absence per creare assenze paziente da gestionale.
 */
class m260508_152401_add_manage_patient_absence_permission extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission('manage_patient_absence');
        if (!$permission) {
            $permission = $auth->createPermission('manage_patient_absence');
            $permission->description = 'Crea/gestisci assenze paziente da gestionale';
            $auth->add($permission);
            echo "  Permesso 'manage_patient_absence' creato\n";
        }

        // Assegna a ruoli admin/manager (NON therapist: solo super_admin/manager dovrebbe poter creare assenze)
        $roles = ['admin', 'manager'];
        foreach ($roles as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role && $permission && !$auth->hasChild($role, $permission)) {
                $auth->addChild($role, $permission);
                echo "  'manage_patient_absence' assegnato a '$roleName'\n";
            }
        }

        // Aggiungi a permission_metadata come attivo
        $exists = (new \yii\db\Query())
            ->from('{{%permission_metadata}}')
            ->where(['permission_name' => 'manage_patient_absence'])
            ->exists();
        if (!$exists) {
            $this->insert('{{%permission_metadata}}', [
                'permission_name' => 'manage_patient_absence',
                'is_active' => 1,
            ]);
        }

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('manage_patient_absence');
        if ($permission) {
            $auth->remove($permission);
        }
        $this->delete('{{%permission_metadata}}', ['permission_name' => 'manage_patient_absence']);
        return true;
    }
}
