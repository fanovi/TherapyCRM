<?php

use yii\db\Migration;

/**
 * Aggiunge il permesso che consente al coordinatore di visualizzare i pazienti
 * dei terapisti del proprio gruppo di coordinamento.
 *
 * PERMESSI AGGIUNTI:
 * - view_own_group_patients: Visualizzare pazienti dei terapisti del proprio gruppo
 *
 * RUOLI INTERESSATI:
 * - coordinator: assegnato (abilita la voce sidebar "I Miei Pazienti",
 *                la pagina /patient/my-group e il filtro nella searchbar globale)
 *
 * Replica il pattern di `m250201_000031_add_coordinator_group_permissions`
 * (permesso `view_own_group_therapists`).
 */
class m260428_160135_add_view_own_group_patients_permission extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "Inizio creazione permesso view_own_group_patients...\n\n";

        $permission = $auth->getPermission('view_own_group_patients');
        if (!$permission) {
            $permission = $auth->createPermission('view_own_group_patients');
            $permission->description = 'Visualizzare pazienti dei terapisti del proprio gruppo';
            $auth->add($permission);
            echo "  Permesso 'view_own_group_patients' creato\n";
        } else {
            echo "  Permesso 'view_own_group_patients' gia' esistente\n";
        }

        echo "\nAssegnazione permesso ai ruoli...\n";

        $coordinatorRole = $auth->getRole('coordinator');
        if ($coordinatorRole && $permission && !$auth->hasChild($coordinatorRole, $permission)) {
            $auth->addChild($coordinatorRole, $permission);
            echo "  'view_own_group_patients' assegnato a 'coordinator'\n";
        }

        if ($this->db->schema->getTableSchema('{{%permission_metadata}}', true) !== null) {
            $exists = (new \yii\db\Query())
                ->from('{{%permission_metadata}}')
                ->where(['permission_name' => 'view_own_group_patients'])
                ->exists();

            if (!$exists) {
                $this->insert('{{%permission_metadata}}', [
                    'permission_name' => 'view_own_group_patients',
                    'is_active' => 1,
                    'notes' => 'Coordinatore: pazienti del proprio gruppo (sidebar "I Miei Pazienti" + searchbar filtrata).',
                ]);
                echo "  Metadata 'view_own_group_patients' (active=1) inserito\n";
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

        $permission = $auth->getPermission('view_own_group_patients');
        if ($permission) {
            $auth->remove($permission);
            echo "Permesso 'view_own_group_patients' rimosso\n";
        }

        return true;
    }
}
