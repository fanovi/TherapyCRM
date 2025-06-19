<?php

use yii\db\Migration;

/**
 * Migration per creare i permessi di login platform_login e app_login
 * 
 * SPECIFICHE PERMESSI:
 * - platform_login: Permesso per accedere alla piattaforma web (admin, manager, coordinator)
 * - app_login: Permesso per accedere all'app mobile (therapist, patient, patient_family)
 */
class m250620_000000_create_login_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔐 Inizio creazione permessi di login...\n\n";

        // Crea i nuovi permessi
        echo "📋 Creazione permessi...\n";
        
        // Permesso per accesso alla piattaforma web
        $platformLoginPerm = $auth->getPermission('platform_login');
        if (!$platformLoginPerm) {
            $platformLoginPerm = $auth->createPermission('platform_login');
            $platformLoginPerm->description = 'Accesso alla piattaforma web';
            $auth->add($platformLoginPerm);
            echo "  ✓ Permesso 'platform_login' creato\n";
        } else {
            echo "  - Permesso 'platform_login' già esistente\n";
        }

        // Permesso per accesso all'app mobile
        $appLoginPerm = $auth->getPermission('app_login');
        if (!$appLoginPerm) {
            $appLoginPerm = $auth->createPermission('app_login');
            $appLoginPerm->description = 'Accesso all\'app mobile';
            $auth->add($appLoginPerm);
            echo "  ✓ Permesso 'app_login' creato\n";
        } else {
            echo "  - Permesso 'app_login' già esistente\n";
        }

        // Ottieni tutti i ruoli esistenti
        $adminRole = $auth->getRole('admin');
        $managerRole = $auth->getRole('manager');
        $coordinatorRole = $auth->getRole('coordinator');
        $therapistRole = $auth->getRole('therapist');
        $patientRole = $auth->getRole('patient');
        $patientFamilyRole = $auth->getRole('patient_family');

        // Verifica che i ruoli esistano
        $missingRoles = [];
        if (!$adminRole) $missingRoles[] = 'admin';
        if (!$coordinatorRole) $missingRoles[] = 'coordinator';
        if (!$therapistRole) $missingRoles[] = 'therapist';
        if (!$patientFamilyRole) $missingRoles[] = 'patient_family';

        if (!empty($missingRoles)) {
            throw new \Exception('Ruoli mancanti: ' . implode(', ', $missingRoles) . '. Verificare che la migration RBAC base sia stata applicata.');
        }

        echo "\n🏢 Assegnazione permesso 'platform_login' ai ruoli della piattaforma...\n";
        
        // ADMIN - accesso alla piattaforma
        if ($adminRole && !$auth->hasChild($adminRole, $platformLoginPerm)) {
            $auth->addChild($adminRole, $platformLoginPerm);
            echo "  ✓ admin\n";
        }

        // MANAGER - accesso alla piattaforma (se esiste)
        if ($managerRole && !$auth->hasChild($managerRole, $platformLoginPerm)) {
            $auth->addChild($managerRole, $platformLoginPerm);
            echo "  ✓ manager\n";
        }

        // COORDINATOR - accesso alla piattaforma
        if ($coordinatorRole && !$auth->hasChild($coordinatorRole, $platformLoginPerm)) {
            $auth->addChild($coordinatorRole, $platformLoginPerm);
            echo "  ✓ coordinator\n";
        }

        echo "\n📱 Assegnazione permesso 'app_login' ai ruoli dell'app...\n";
        
        // THERAPIST - accesso all'app
        if ($therapistRole && !$auth->hasChild($therapistRole, $appLoginPerm)) {
            $auth->addChild($therapistRole, $appLoginPerm);
            echo "  ✓ therapist\n";
        }

        // PATIENT - accesso all'app (se esiste)
        if ($patientRole && !$auth->hasChild($patientRole, $appLoginPerm)) {
            $auth->addChild($patientRole, $appLoginPerm);
            echo "  ✓ patient\n";
        }

        // PATIENT_FAMILY - accesso all'app
        if ($patientFamilyRole && !$auth->hasChild($patientFamilyRole, $appLoginPerm)) {
            $auth->addChild($patientFamilyRole, $appLoginPerm);
            echo "  ✓ patient_family\n";
        }

        echo "\n✅ Creazione e assegnazione permessi di login completata!\n";
        echo "\n📋 RIEPILOGO ASSEGNAZIONI:\n";
        echo "🏢 PLATFORM_LOGIN:\n";
        echo "   • admin - Accesso completo alla piattaforma\n";
        if ($managerRole) echo "   • manager - Accesso alla piattaforma per gestione\n";
        echo "   • coordinator - Accesso alla piattaforma per coordinamento\n";
        echo "\n📱 APP_LOGIN:\n";
        echo "   • therapist - Accesso all'app per terapisti\n";
        if ($patientRole) echo "   • patient - Accesso all'app per pazienti\n";
        echo "   • patient_family - Accesso all'app per familiari\n\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 Rimozione permessi di login...\n";

        // Ottieni i permessi
        $platformLoginPerm = $auth->getPermission('platform_login');
        $appLoginPerm = $auth->getPermission('app_login');

        // Rimuovi le assegnazioni dai ruoli
        $roles = ['admin', 'manager', 'coordinator', 'therapist', 'patient', 'patient_family'];
        
        foreach ($roles as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role) {
                // Rimuovi platform_login
                if ($platformLoginPerm && $auth->hasChild($role, $platformLoginPerm)) {
                    $auth->removeChild($role, $platformLoginPerm);
                    echo "  ✓ Rimosso 'platform_login' da '$roleName'\n";
                }
                
                // Rimuovi app_login
                if ($appLoginPerm && $auth->hasChild($role, $appLoginPerm)) {
                    $auth->removeChild($role, $appLoginPerm);
                    echo "  ✓ Rimosso 'app_login' da '$roleName'\n";
                }
            }
        }

        // Elimina i permessi
        if ($platformLoginPerm) {
            $auth->remove($platformLoginPerm);
            echo "  ✓ Permesso 'platform_login' eliminato\n";
        }

        if ($appLoginPerm) {
            $auth->remove($appLoginPerm);
            echo "  ✓ Permesso 'app_login' eliminato\n";
        }

        echo "✅ Rimozione permessi completata!\n";
    }
}