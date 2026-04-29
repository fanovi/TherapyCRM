<?php

use yii\db\Migration;

/**
 * Assegna `view_absence` al ruolo `coordinator` in modo che possa accedere a:
 * - /absence/index (Assenze Terapisti)
 * - /absence/daily (Riepilogo Giornaliero Terapisti)
 *
 * Il filtro per gruppo (CoordinatorGroup) e' gia' applicato lato controller.
 */
class m260429_185253_assign_view_absence_to_coordinator extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $coordinator = $auth->getRole('coordinator');
        if (!$coordinator) {
            echo "  - Ruolo 'coordinator' non trovato, skip.\n";
            return true;
        }

        $permission = $auth->getPermission('view_absence');
        if (!$permission) {
            throw new \Exception("Permesso 'view_absence' non trovato.");
        }

        if ($auth->hasChild($coordinator, $permission)) {
            echo "  - 'view_absence' gia' assegnato a 'coordinator'\n";
            return true;
        }

        $auth->addChild($coordinator, $permission);
        echo "  + 'view_absence' assegnato a 'coordinator'\n";

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $coordinator = $auth->getRole('coordinator');
        $permission = $auth->getPermission('view_absence');

        if ($coordinator && $permission && $auth->hasChild($coordinator, $permission)) {
            $auth->removeChild($coordinator, $permission);
            echo "  - 'view_absence' rimosso da 'coordinator'\n";
        }

        return true;
    }
}
