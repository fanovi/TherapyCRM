<?php

use yii\db\Migration;

/**
 * Revoca delle assenze terapista: dati di tracciabilita' su absences e permesso
 * revoke_absence, assegnato a chi oggi possiede create_absence (ruoli, escluso therapist,
 * e singoli utenti).
 */
class m261102_100000_add_absence_revocation extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%absences}}', 'cancelled_by', $this->integer()->null()->comment('ID utente che ha revocato')->after('approved_at'));
        $this->addColumn('{{%absences}}', 'cancelled_at', $this->dateTime()->null()->comment('Data e ora della revoca')->after('cancelled_by'));
        $this->addColumn('{{%absences}}', 'cancellation_reason', $this->text()->null()->comment('Motivo della revoca')->after('cancelled_at'));
        $this->addForeignKey('fk-absences-cancelled_by', '{{%absences}}', 'cancelled_by', '{{%users}}', 'id', 'SET NULL');

        $auth = Yii::$app->authManager;

        $permission = $auth->getPermission('revoke_absence');
        if (!$permission) {
            $permission = $auth->createPermission('revoke_absence');
            $permission->description = 'Revocare assenze terapista';
            $auth->add($permission);
        }

        // Ruoli o gruppi di permessi che contengono create_absence, tranne il ruolo
        // therapist: un terapista non deve poter revocare le assenze dei colleghi.
        $parents = (new \yii\db\Query())
            ->select('parent')
            ->from('{{%auth_item_child}}')
            ->where(['child' => 'create_absence'])
            ->andWhere(['<>', 'parent', 'therapist'])
            ->column();
        foreach ($parents as $parentName) {
            $parent = $auth->getRole($parentName) ?: $auth->getPermission($parentName);
            if ($parent && !$auth->hasChild($parent, $permission)) {
                $auth->addChild($parent, $permission);
                echo "  'revoke_absence' aggiunto a '{$parentName}'\n";
            }
        }

        // Utenti a cui create_absence e' assegnato direttamente.
        $userIds = (new \yii\db\Query())
            ->select('user_id')
            ->from('{{%auth_assignment}}')
            ->where(['item_name' => 'create_absence'])
            ->column();
        foreach ($userIds as $userId) {
            if (!$auth->getAssignment('revoke_absence', $userId)) {
                $auth->assign($permission, $userId);
                echo "  'revoke_absence' assegnato all'utente {$userId}\n";
            }
        }

        $exists = (new \yii\db\Query())
            ->from('{{%permission_metadata}}')
            ->where(['permission_name' => 'revoke_absence'])
            ->exists();
        if (!$exists) {
            $this->insert('{{%permission_metadata}}', [
                'permission_name' => 'revoke_absence',
                'is_active' => 1,
            ]);
        }

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('revoke_absence');
        if ($permission) {
            // Rimuove anche figli, assegnazioni e (via FK CASCADE) permission_metadata.
            $auth->remove($permission);
        }
        $this->delete('{{%permission_metadata}}', ['permission_name' => 'revoke_absence']);

        $this->dropForeignKey('fk-absences-cancelled_by', '{{%absences}}');
        $this->dropColumn('{{%absences}}', 'cancellation_reason');
        $this->dropColumn('{{%absences}}', 'cancelled_at');
        $this->dropColumn('{{%absences}}', 'cancelled_by');

        return true;
    }
}
