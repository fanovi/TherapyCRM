<?php

use yii\db\Migration;

/**
 * Migration per assegnare i permessi RBAC ai ruoli secondo le specifiche del sistema
 * 
 * SPECIFICHE RUOLI:
 * - ADMIN: Accesso completo a tutto il sistema
 * - MANAGER: Gestione pazienti, terapisti, piani terapeutici, comunicazioni
 * - COORDINATOR: Gestione limitata dei terapisti assegnati, visualizzazione documenti
 * - THERAPIST: Visualizzazione pazienti assegnati, gestione appuntamenti
 * - PATIENT: Visualizzazione propri dati e appuntamenti
 */
class m250619_205539_assign_rbac_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔐 Inizio assegnazione permessi RBAC...\n\n";

        // Ottieni tutti i ruoli esistenti
        $adminRole = $auth->getRole('admin');
        $coordinatorRole = $auth->getRole('coordinator');
        $therapistRole = $auth->getRole('therapist');
        $patientRole = $auth->getRole('patient_family');

        // Crea il ruolo manager se non esiste
        $managerRole = $auth->getRole('manager');
        if (!$managerRole) {
            $managerRole = $auth->createRole('manager');
            $managerRole->description = 'Manager del sistema';
            $auth->add($managerRole);
            echo "✓ Ruolo 'manager' creato\n";
        }

        // Crea il ruolo patient se non esiste (oltre a patient_family)
        $patientRoleNew = $auth->getRole('patient');
        if (!$patientRoleNew) {
            $patientRoleNew = $auth->createRole('patient');
            $patientRoleNew->description = 'Paziente del sistema';
            $auth->add($patientRoleNew);
            echo "✓ Ruolo 'patient' creato\n";
        }

        if (!$adminRole || !$coordinatorRole || !$therapistRole || !$patientRole) {
            throw new \Exception('Uno o più ruoli base non trovati. Verificare che la migration RBAC sia stata applicata.');
        }

        // Crea i permessi mancanti
        $this->createMissingPermissions($auth);

        // ADMIN - Accesso completo a tutto (già configurato nella migration base, aggiungiamo solo quelli nuovi)
        echo "👑 Assegnazione permessi aggiuntivi ADMIN...\n";
        $adminPermissions = [
            // Usa i permessi esistenti
            'manage_users', 'manage_patients', 'manage_therapists', 'manage_appointments', 'view_reports', 'manage_plans',
            // Aggiungi permessi personalizzati
            'create_admin', 'view_admin', 'update_admin', 'delete_admin',
            'create_coordinator', 'view_coordinator', 'update_coordinator', 'delete_coordinator',
            'manage_system', 'view_statistics', 'manage_communications', 'manage_documents'
        ];

        foreach ($adminPermissions as $permission) {
            $perm = $auth->getPermission($permission);
            if ($perm && !$auth->hasChild($adminRole, $perm)) {
                $auth->addChild($adminRole, $perm);
                echo "  ✓ $permission\n";
            }
        }

        // MANAGER - Gestione pazienti e terapisti
        echo "\n👨‍💼 Assegnazione permessi MANAGER...\n";
        $managerPermissions = [
            // Gestione terapisti (CRUD completo)
            'create_therapist', 'view_therapist', 'update_therapist', 'delete_therapist',
            // Gestione pazienti (visualizzazione e modifica)
            'view_patient', 'update_patient',
            // Comunicazioni e documenti
            'manage_communications', 'manage_documents',
            // Statistiche
            'view_statistics'
        ];

        foreach ($managerPermissions as $permission) {
            $perm = $auth->getPermission($permission);
            if ($perm && !$auth->hasChild($managerRole, $perm)) {
                $auth->addChild($managerRole, $perm);
                echo "  ✓ $permission\n";
            }
        }

        // COORDINATOR - Gestione limitata terapisti
        echo "\n👨‍💼 Assegnazione permessi COORDINATOR...\n";
        $coordinatorPermissions = [
            // Gestione limitata terapisti (solo visualizzazione e modifica)
            'view_therapist', 'update_therapist',
            // Visualizzazione documenti
            'view_documents'
        ];

        foreach ($coordinatorPermissions as $permission) {
            $perm = $auth->getPermission($permission);
            if ($perm && !$auth->hasChild($coordinatorRole, $perm)) {
                $auth->addChild($coordinatorRole, $perm);
                echo "  ✓ $permission\n";
            }
        }

        // THERAPIST - Visualizzazione pazienti assegnati
        echo "\n👨‍⚕️ Assegnazione permessi THERAPIST...\n";
        $therapistPermissions = [
            // Visualizzazione pazienti assegnati
            'view_patient',
            // Gestione appuntamenti
            'manage_appointments'
        ];

        foreach ($therapistPermissions as $permission) {
            $perm = $auth->getPermission($permission);
            if ($perm && !$auth->hasChild($therapistRole, $perm)) {
                $auth->addChild($therapistRole, $perm);
                echo "  ✓ $permission\n";
            }
        }

        // PATIENT_FAMILY - Visualizzazione propri dati (ruolo esistente)
        echo "\n👶 Assegnazione permessi PATIENT_FAMILY...\n";
        $patientPermissions = [
            // Visualizzazione propri dati
            'view_own_data',
            // Visualizzazione propri appuntamenti
            'view_own_appointments'
        ];

        foreach ($patientPermissions as $permission) {
            $perm = $auth->getPermission($permission);
            if ($perm && !$auth->hasChild($patientRole, $perm)) {
                $auth->addChild($patientRole, $perm);
                echo "  ✓ $permission\n";
            }
        }

        // PATIENT - Stesso set di permessi per il nuovo ruolo
        echo "\n👶 Assegnazione permessi PATIENT (nuovo ruolo)...\n";
        foreach ($patientPermissions as $permission) {
            $perm = $auth->getPermission($permission);
            if ($perm && !$auth->hasChild($patientRoleNew, $perm)) {
                $auth->addChild($patientRoleNew, $perm);
                echo "  ✓ $permission\n";
            }
        }

        echo "\n✅ Assegnazione permessi RBAC completata!\n";
        echo "\n📋 RIEPILOGO PERMESSI:\n";
        echo "👑 ADMIN: Accesso completo al sistema\n";
        echo "👨‍💼 MANAGER: Gestione terapisti (CRUD) + pazienti (view/update) + comunicazioni\n";
        echo "👨‍💼 COORDINATOR: Gestione limitata terapisti (view/update) + visualizzazione documenti\n";
        echo "👨‍⚕️ THERAPIST: Visualizzazione pazienti assegnati + gestione appuntamenti\n";
        echo "👶 PATIENT: Visualizzazione propri dati e appuntamenti\n\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 Rimozione assegnazioni permessi RBAC...\n";

        // Ottieni tutti i ruoli
        $roles = ['admin', 'manager', 'coordinator', 'therapist', 'patient'];

        foreach ($roles as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role) {
                // Rimuovi tutti i permessi dal ruolo
                $children = $auth->getChildren($role->name);
                foreach ($children as $child) {
                    if ($child->type == \yii\rbac\Item::TYPE_PERMISSION) {
                        $auth->removeChild($role, $child);
                        echo "  ✓ Rimosso permesso '{$child->name}' dal ruolo '$roleName'\n";
                    }
                }
            }
        }

        echo "✅ Rimozione completata!\n";
    }

    /**
     * Crea i permessi mancanti
     */
    private function createMissingPermissions($auth)
    {
        $permissions = [
            // Permessi amministratori
            'create_admin' => 'Creare amministratori',
            'view_admin' => 'Visualizzare amministratori',
            'update_admin' => 'Modificare amministratori',
            'delete_admin' => 'Eliminare amministratori',
            
            // Permessi coordinatori
            'create_coordinator' => 'Creare coordinatori',
            'view_coordinator' => 'Visualizzare coordinatori',
            'update_coordinator' => 'Modificare coordinatori',
            'delete_coordinator' => 'Eliminare coordinatori',
            
            // Permessi specifici terapisti
            'create_therapist' => 'Creare terapisti',
            'view_therapist' => 'Visualizzare terapisti',
            'update_therapist' => 'Modificare terapisti',
            'delete_therapist' => 'Eliminare terapisti',
            
            // Permessi specifici pazienti
            'create_patient' => 'Creare pazienti',
            'view_patient' => 'Visualizzare pazienti',
            'update_patient' => 'Modificare pazienti',
            'delete_patient' => 'Eliminare pazienti',
            
            // Permessi sistema
            'manage_system' => 'Gestire sistema',
            'view_statistics' => 'Visualizzare statistiche',
            'manage_communications' => 'Gestire comunicazioni',
            'manage_documents' => 'Gestire documenti',
            'view_documents' => 'Visualizzare documenti',
            'view_own_data' => 'Visualizzare propri dati',
        ];

        foreach ($permissions as $name => $description) {
            if (!$auth->getPermission($name)) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "✓ Permesso '$name' creato\n";
            }
        }
    }
}
