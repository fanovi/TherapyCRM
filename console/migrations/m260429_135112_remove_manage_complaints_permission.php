<?php

use yii\db\Migration;

/**
 * Migration per rimuovere il permesso 'manage_complaints'.
 *
 * Motivazione: il modulo Reclami lato web è di sola lettura. La creazione
 * dei reclami avviene solo da API mobile (autenticazione JWT, nessun RBAC).
 * Resta valido il solo permesso 'view_complaints'.
 */
class m260429_135112_remove_manage_complaints_permission extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission('manage_complaints');
        if ($permission) {
            $auth->remove($permission);
            echo "✓ Permesso 'manage_complaints' rimosso (assegnazioni ai ruoli rimosse a cascata)\n";
        } else {
            echo "- Permesso 'manage_complaints' non presente, nulla da fare\n";
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission('manage_complaints');
        if (!$permission) {
            $permission = $auth->createPermission('manage_complaints');
            $permission->description = 'Gestire reclami - Può eseguire azioni amministrative sui reclami';
            $auth->add($permission);
            echo "✓ Permesso 'manage_complaints' ricreato\n";
        }

        foreach (['super_admin', 'admin'] as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role && !$auth->hasChild($role, $permission)) {
                $auth->addChild($role, $permission);
                echo "  ✓ manage_complaints riassegnato a $roleName\n";
            }
        }

        return true;
    }
}
