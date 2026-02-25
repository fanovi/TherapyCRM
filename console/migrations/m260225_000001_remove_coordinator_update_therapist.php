<?php

use yii\db\Migration;

/**
 * Rimuove il permesso update_therapist dal ruolo coordinator.
 * I coordinatori devono avere accesso in sola lettura ai dati dei terapisti.
 */
class m260225_000001_remove_coordinator_update_therapist extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $coordinatorRole = $auth->getRole('coordinator');
        $updateTherapist = $auth->getPermission('update_therapist');

        if ($coordinatorRole && $updateTherapist) {
            $auth->removeChild($coordinatorRole, $updateTherapist);
            echo "Rimosso permesso 'update_therapist' dal ruolo 'coordinator'\n";
        }

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $coordinatorRole = $auth->getRole('coordinator');
        $updateTherapist = $auth->getPermission('update_therapist');

        if ($coordinatorRole && $updateTherapist) {
            $auth->addChild($coordinatorRole, $updateTherapist);
            echo "Ripristinato permesso 'update_therapist' al ruolo 'coordinator'\n";
        }

        return true;
    }
}
