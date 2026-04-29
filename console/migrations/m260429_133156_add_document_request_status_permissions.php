<?php

use yii\db\Migration;

/**
 * Permessi dedicati al cambio stato delle richieste documenti.
 *
 * - change_document_request_status: cambio stato libero (Inviata,
 *   Presa in carico, Stampato). Tipico ruolo admin.
 * - mark_document_request_delivered: marcatura Consegnato e
 *   ripristino allo stato precedente. Tipico ruolo manager (e
 *   anche admin per coerenza).
 *
 * Sostituisce il check hard-coded sui ruoli admin/manager nel
 * DocumentRequestController e nella view index.php.
 */
class m260429_133156_add_document_request_status_permissions extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $permissions = [
            'change_document_request_status' => 'Cambiare stato richiesta documento (libero)',
            'mark_document_request_delivered' => 'Marcare richiesta come Consegnato e ripristinare',
        ];

        foreach ($permissions as $name => $description) {
            $perm = $auth->getPermission($name);
            if (!$perm) {
                $perm = $auth->createPermission($name);
                $perm->description = $description;
                $auth->add($perm);
                echo "+ creato permesso $name\n";
            } else {
                echo "- $name gia esistente\n";
            }
        }

        $changeStatus = $auth->getPermission('change_document_request_status');
        $markDelivered = $auth->getPermission('mark_document_request_delivered');

        // Assegnamenti: admin e super_admin hanno entrambi.
        $rolesFullAccess = ['admin', 'super_admin'];
        foreach ($rolesFullAccess as $roleName) {
            $role = $auth->getRole($roleName);
            if (!$role) {
                echo "- ruolo $roleName non trovato, salto\n";
                continue;
            }
            foreach ([$changeStatus, $markDelivered] as $perm) {
                if (!$auth->hasChild($role, $perm)) {
                    $auth->addChild($role, $perm);
                    echo "+ $roleName <- {$perm->name}\n";
                }
            }
        }

        // Manager: solo mark_document_request_delivered.
        $manager = $auth->getRole('manager');
        if ($manager && !$auth->hasChild($manager, $markDelivered)) {
            $auth->addChild($manager, $markDelivered);
            echo "+ manager <- mark_document_request_delivered\n";
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        foreach (['change_document_request_status', 'mark_document_request_delivered'] as $name) {
            $perm = $auth->getPermission($name);
            if ($perm) {
                $auth->remove($perm);
                echo "- rimosso $name\n";
            }
        }
        return true;
    }
}
