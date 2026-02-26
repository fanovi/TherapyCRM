<?php

use yii\db\Migration;

/**
 * Migration per aggiungere il permesso per visualizzare le assenze dei pazienti.
 *
 * PERMESSI AGGIUNTI:
 * - view_patient_absence: Visualizzare assenze pazienti (pagina /absence/patients)
 */
class m260226_120000_add_patient_absence_permission extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "Inizio creazione permesso view_patient_absence...\n\n";

        // === CREAZIONE PERMESSO ===
        $permission = $auth->getPermission('view_patient_absence');
        if (!$permission) {
            $permission = $auth->createPermission('view_patient_absence');
            $permission->description = 'Visualizzare assenze pazienti';
            $auth->add($permission);
            echo "  Permesso 'view_patient_absence' creato\n";
        } else {
            echo "  Permesso 'view_patient_absence' gia' esistente\n";
        }

        // === ASSEGNAZIONE PERMESSI AI RUOLI ===
        echo "\nAssegnazione permesso ai ruoli...\n";

        $roles = ['admin', 'manager', 'therapist'];

        foreach ($roles as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role && $permission && !$auth->hasChild($role, $permission)) {
                $auth->addChild($role, $permission);
                echo "  'view_patient_absence' assegnato a '$roleName'\n";
            }
        }

        echo "\nMigrazione completata con successo!\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission('view_patient_absence');
        if ($permission) {
            $auth->remove($permission);
            echo "Permesso 'view_patient_absence' rimosso\n";
        }

        return true;
    }
}
