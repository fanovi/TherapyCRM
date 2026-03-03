<?php

use yii\db\Migration;

/**
 * Rimuove il permesso app_login dai ruoli che non devono accedere all'app mobile.
 *
 * Dopo questa migration, app_login rimane SOLO a:
 * - therapist
 * - patient
 *
 * Viene rimosso da:
 * - super_admin
 * - admin
 * - patient_family
 */
class m260303_100000_restrict_app_login_permission extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $appLogin = $auth->getPermission('app_login');

        if (!$appLogin) {
            echo "Permesso 'app_login' non trovato, nulla da fare.\n";
            return true;
        }

        $rolesToRemove = ['super_admin', 'admin', 'patient_family'];

        foreach ($rolesToRemove as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role && $auth->hasChild($role, $appLogin)) {
                $auth->removeChild($role, $appLogin);
                echo "✓ Rimosso 'app_login' dal ruolo '$roleName'\n";
            } else {
                echo "- Ruolo '$roleName' non ha 'app_login' o non esiste\n";
            }
        }

        echo "\n✅ Permesso 'app_login' ora assegnato solo a: therapist, patient\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $appLogin = $auth->getPermission('app_login');

        if (!$appLogin) {
            echo "Permesso 'app_login' non trovato.\n";
            return true;
        }

        $rolesToRestore = ['super_admin', 'admin', 'patient_family'];

        foreach ($rolesToRestore as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role && !$auth->hasChild($role, $appLogin)) {
                $auth->addChild($role, $appLogin);
                echo "✓ Ripristinato 'app_login' al ruolo '$roleName'\n";
            }
        }

        echo "\n✅ Rollback completato.\n";

        return true;
    }
}
