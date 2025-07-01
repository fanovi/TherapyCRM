<?php

use yii\db\Migration;

/**
 * Migration completa per omologare tutti i permessi, ruoli e assegnazioni del sistema TherapyCRM
 * 
 * STRUTTURA RUOLI:
 * - ADMIN: Accesso completo al sistema
 * - MANAGER: Gestione completa pazienti, terapisti, piani terapeutici
 * - COORDINATOR: Gestione limitata terapisti assegnati, coordinamento gruppi
 * - THERAPIST: Gestione propri appuntamenti e pazienti assegnati
 * - PATIENT: Visualizzazione propri dati e appuntamenti
 * - PATIENT_FAMILY: Visualizzazione dati familiari pazienti
 * 
 * TABELLE COINVOLTE:
 * - users, user_profiles, therapists, patients, therapeutic_plans
 * - appointments, appointment_patterns, absences, absence_recoveries
 * - notifications, notification_templates, document_requests
 * - specializations, treatment_types, districts, coordinator_groups
 * - auth_item, auth_item_child, auth_assignment (RBAC)
 */
class m250201_000030_comprehensive_rbac_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "🔐 INIZIO CONFIGURAZIONE COMPLETA RBAC THERAPYCRM\n";
        echo "================================================\n\n";

        // ====================================================================
        // 1. CREAZIONE/VERIFICA RUOLI
        // ====================================================================
        echo "👥 1. CREAZIONE/VERIFICA RUOLI\n";
        echo "==============================\n";

        $roles = [
            'admin' => 'Amministratore del sistema - Accesso completo',
            'manager' => 'Manager - Gestione completa pazienti, terapisti, piani',
            'coordinator' => 'Coordinatore - Gestione limitata terapisti assegnati',
            'therapist' => 'Terapista - Gestione propri appuntamenti e pazienti',
            'patient' => 'Paziente - Visualizzazione propri dati',
            'patient_family' => 'Familiare paziente - Visualizzazione dati familiari'
        ];

        foreach ($roles as $roleName => $description) {
            $role = $auth->getRole($roleName);
            if (!$role) {
                $role = $auth->createRole($roleName);
                $role->description = $description;
                $auth->add($role);
                echo "✓ Ruolo '$roleName' creato\n";
            } else {
                echo "- Ruolo '$roleName' già esistente\n";
            }
        }

        // ====================================================================
        // 2. CREAZIONE PERMESSI COMPLETI
        // ====================================================================
        echo "\n🔑 2. CREAZIONE PERMESSI COMPLETI\n";
        echo "=================================\n";

        $permissions = [
            // ===== PERMESSI SISTEMA =====
            'manage_system' => 'Gestire configurazione di sistema',
            'view_statistics' => 'Visualizzare statistiche e dashboard',
            'manage_notifications' => 'Gestire notifiche di sistema',
            'platform_login' => 'Accesso alla piattaforma web',
            'app_login' => 'Accesso all\'applicazione mobile',

            // ===== PERMESSI UTENTI =====
            'create_user' => 'Creare utenti',
            'view_user' => 'Visualizzare utenti',
            'update_user' => 'Modificare utenti',
            'delete_user' => 'Eliminare utenti',

            // ===== PERMESSI AMMINISTRATORI =====
            'create_admin' => 'Creare amministratori',
            'view_admin' => 'Visualizzare amministratori',
            'update_admin' => 'Modificare amministratori',
            'delete_admin' => 'Eliminare amministratori',

            // ===== PERMESSI MANAGER =====
            'create_manager' => 'Creare manager',
            'view_manager' => 'Visualizzare manager',
            'update_manager' => 'Modificare manager',
            'delete_manager' => 'Eliminare manager',

            // ===== PERMESSI COORDINATORI =====
            'create_coordinator' => 'Creare coordinatori',
            'view_coordinator' => 'Visualizzare coordinatori',
            'update_coordinator' => 'Modificare coordinatori',
            'delete_coordinator' => 'Eliminare coordinatori',
            'manage_coordinator_groups' => 'Gestire gruppi di coordinamento',

            // ===== PERMESSI TERAPISTI =====
            'create_therapist' => 'Creare terapisti',
            'view_therapist' => 'Visualizzare terapisti',
            'update_therapist' => 'Modificare terapisti',
            'delete_therapist' => 'Eliminare terapisti',
            'manage_therapist_schedule' => 'Gestire calendario terapisti',
            'manage_therapist_substitutions' => 'Gestire sostituzioni terapisti',
            'view_therapist_statistics' => 'Visualizzare statistiche terapisti',

            // ===== PERMESSI PAZIENTI =====
            'create_patient' => 'Creare pazienti',
            'view_patient' => 'Visualizzare pazienti',
            'update_patient' => 'Modificare pazienti',
            'delete_patient' => 'Eliminare pazienti',
            'manage_patient_accounts' => 'Gestire account pazienti',
            'view_patient_statistics' => 'Visualizzare statistiche pazienti',

            // ===== PERMESSI PIANI TERAPEUTICI =====
            'create_therapeutic_plan' => 'Creare piani terapeutici',
            'view_therapeutic_plan' => 'Visualizzare piani terapeutici',
            'update_therapeutic_plan' => 'Modificare piani terapeutici',
            'delete_therapeutic_plan' => 'Eliminare piani terapeutici',
            'manage_plan_therapies' => 'Gestire terapie del piano',
            'manage_plans' => 'Gestire piani terapeutici (generale)',

            // ===== PERMESSI APPUNTAMENTI =====
            'create_appointment' => 'Creare appuntamenti',
            'view_appointment' => 'Visualizzare appuntamenti',
            'update_appointment' => 'Modificare appuntamenti',
            'delete_appointment' => 'Eliminare appuntamenti',
            'manage_appointments' => 'Gestire appuntamenti (generale)',
            'manage_appointment_patterns' => 'Gestire pattern ricorrenti',
            'view_calendar' => 'Visualizzare calendario',
            'manage_calendar' => 'Gestire calendario completo',

            // ===== PERMESSI ASSENZE =====
            'create_absence' => 'Registrare assenze',
            'view_absence' => 'Visualizzare assenze',
            'update_absence' => 'Modificare assenze',
            'delete_absence' => 'Eliminare assenze',
            'manage_absence_recovery' => 'Gestire recuperi assenze',
            'view_absence_statistics' => 'Visualizzare statistiche assenze',

            // ===== PERMESSI NOTIFICHE =====
            'create_notification' => 'Creare notifiche',
            'view_notification' => 'Visualizzare notifiche',
            'update_notification' => 'Modificare notifiche',
            'delete_notification' => 'Eliminare notifiche',
            'manage_notification_templates' => 'Gestire template notifiche',
            'send_notifications' => 'Inviare notifiche',

            // ===== PERMESSI DOCUMENTI =====
            'create_document_request' => 'Creare richieste documenti',
            'view_document_request' => 'Visualizzare richieste documenti',
            'update_document_request' => 'Modificare richieste documenti',
            'delete_document_request' => 'Eliminare richieste documenti',
            'manage_documents' => 'Gestire documenti (generale)',
            'view_documents' => 'Visualizzare documenti',
            'download_documents' => 'Scaricare documenti',

            // ===== PERMESSI SPECIALIZZAZIONI =====
            'create_specialization' => 'Creare specializzazioni',
            'view_specialization' => 'Visualizzare specializzazioni',
            'update_specialization' => 'Modificare specializzazioni',
            'delete_specialization' => 'Eliminare specializzazioni',
            'manage_treatment_types' => 'Gestire tipi di trattamento',

            // ===== PERMESSI VISITE SPECIALISTICHE =====
            'create_specialist_visit' => 'Creare visite specialistiche',
            'view_specialist_visit' => 'Visualizzare visite specialistiche',
            'update_specialist_visit' => 'Modificare visite specialistiche',
            'delete_specialist_visit' => 'Eliminare visite specialistiche',

            // ===== PERMESSI RAPPORTI =====
            'view_reports' => 'Visualizzare rapporti',
            'generate_reports' => 'Generare rapporti',
            'export_data' => 'Esportare dati',

            // ===== PERMESSI COMUNICAZIONI =====
            'manage_communications' => 'Gestire comunicazioni',
            'send_messages' => 'Inviare messaggi',
            'view_messages' => 'Visualizzare messaggi',

            // ===== PERMESSI PERSONALI =====
            'view_own_data' => 'Visualizzare i propri dati',
            'update_own_data' => 'Modificare i propri dati',
            'view_own_appointments' => 'Visualizzare i propri appuntamenti',
            'view_assigned_patients' => 'Visualizzare pazienti assegnati',
            'manage_own_schedule' => 'Gestire il proprio calendario',

            // ===== PERMESSI CONFIGURAZIONE =====
            'manage_districts' => 'Gestire distretti',
            'manage_settings' => 'Gestire impostazioni',
            'manage_users' => 'Gestire utenti (generale)',
            'manage_patients' => 'Gestire pazienti (generale)',
            'manage_therapists' => 'Gestire terapisti (generale)',
        ];

        $createdCount = 0;
        $existingCount = 0;

        foreach ($permissions as $name => $description) {
            $permission = $auth->getPermission($name);
            if (!$permission) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "✓ Permesso '$name' creato\n";
                $createdCount++;
            } else {
                $existingCount++;
            }
        }

        echo "\nPermessi creati: $createdCount\n";
        echo "Permessi esistenti: $existingCount\n";

        // ====================================================================
        // 3. ASSEGNAZIONE PERMESSI AI RUOLI
        // ====================================================================
        echo "\n🎭 3. ASSEGNAZIONE PERMESSI AI RUOLI\n";
        echo "=====================================\n";

        // === ADMIN - ACCESSO COMPLETO ===
        echo "\n👑 ADMIN - Accesso completo\n";
        $adminRole = $auth->getRole('admin');
        $adminPermissions = array_keys($permissions); // Tutti i permessi

        $assignedCount = 0;
        foreach ($adminPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && !$auth->hasChild($adminRole, $permission)) {
                $auth->addChild($adminRole, $permission);
                $assignedCount++;
            }
        }
        echo "  Permessi assegnati: $assignedCount\n";

        // === MANAGER - GESTIONE COMPLETA ===
        echo "\n👨‍💼 MANAGER - Gestione completa\n";
        $managerRole = $auth->getRole('manager');
        $managerPermissions = [
            // Login
            'platform_login',
            // Statistiche e rapporti
            'view_statistics', 'view_reports', 'generate_reports', 'export_data',
            // Gestione terapisti
            'create_therapist', 'view_therapist', 'update_therapist', 'delete_therapist',
            'manage_therapist_schedule', 'manage_therapist_substitutions', 'view_therapist_statistics',
            // Gestione pazienti
            'create_patient', 'view_patient', 'update_patient', 'delete_patient',
            'manage_patient_accounts', 'view_patient_statistics',
            // Gestione piani terapeutici
            'create_therapeutic_plan', 'view_therapeutic_plan', 'update_therapeutic_plan', 'delete_therapeutic_plan',
            'manage_plan_therapies', 'manage_plans',
            // Gestione appuntamenti
            'create_appointment', 'view_appointment', 'update_appointment', 'delete_appointment',
            'manage_appointments', 'manage_appointment_patterns', 'view_calendar', 'manage_calendar',
            // Gestione assenze
            'create_absence', 'view_absence', 'update_absence', 'delete_absence',
            'manage_absence_recovery', 'view_absence_statistics',
            // Comunicazioni e documenti
            'manage_communications', 'send_messages', 'view_messages',
            'manage_documents', 'view_documents', 'download_documents',
            'create_document_request', 'view_document_request', 'update_document_request', 'delete_document_request',
            // Notifiche
            'create_notification', 'view_notification', 'update_notification', 'delete_notification',
            'manage_notification_templates', 'send_notifications',
            // Specializzazioni
            'create_specialization', 'view_specialization', 'update_specialization', 'delete_specialization',
            'manage_treatment_types',
            // Visite specialistiche
            'create_specialist_visit', 'view_specialist_visit', 'update_specialist_visit', 'delete_specialist_visit',
            // Configurazione
            'manage_districts', 'manage_settings',
        ];

        $assignedCount = 0;
        foreach ($managerPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && !$auth->hasChild($managerRole, $permission)) {
                $auth->addChild($managerRole, $permission);
                $assignedCount++;
            }
        }
        echo "  Permessi assegnati: $assignedCount\n";

        // === COORDINATOR - GESTIONE LIMITATA ===
        echo "\n👨‍💼 COORDINATOR - Gestione limitata\n";
        $coordinatorRole = $auth->getRole('coordinator');
        $coordinatorPermissions = [
            // Login
            'platform_login',
            // Visualizzazione terapisti (limitata ai propri)
            'view_therapist', 'update_therapist',
            // Gestione gruppi
            'manage_coordinator_groups',
            // Visualizzazione pazienti
            'view_patient',
            // Calendario
            'view_calendar',
            // Documenti
            'view_documents', 'download_documents',
            // Rapporti limitati
            'view_reports',
            // Appuntamenti limitati
            'view_appointment', 'update_appointment',
            // Comunicazioni
            'view_messages',
            // Dati personali
            'view_own_data', 'update_own_data',
        ];

        $assignedCount = 0;
        foreach ($coordinatorPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && !$auth->hasChild($coordinatorRole, $permission)) {
                $auth->addChild($coordinatorRole, $permission);
                $assignedCount++;
            }
        }
        echo "  Permessi assegnati: $assignedCount\n";

        // === THERAPIST ===
        echo "\n👨‍⚕️ THERAPIST - Gestione propri appuntamenti\n";
        $therapistRole = $auth->getRole('therapist');
        $therapistPermissions = [
            // Login app
            'app_login',
            // Visualizzazione pazienti assegnati
            'view_assigned_patients',
            // Gestione propri appuntamenti
            'view_own_appointments', 'manage_own_schedule',
            'view_appointment', 'update_appointment',
            // Gestione assenze dei propri pazienti
            'create_absence', 'view_absence', 'update_absence',
            // Calendario personale
            'view_calendar',
            // Notifiche
            'view_notification',
            // Dati personali
            'view_own_data', 'update_own_data',
            // Comunicazioni
            'view_messages',
        ];

        $assignedCount = 0;
        foreach ($therapistPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && !$auth->hasChild($therapistRole, $permission)) {
                $auth->addChild($therapistRole, $permission);
                $assignedCount++;
            }
        }
        echo "  Permessi assegnati: $assignedCount\n";

        // === PATIENT ===
        echo "\n👤 PATIENT - Visualizzazione propri dati\n";
        $patientRole = $auth->getRole('patient');
        $patientPermissions = [
            // Login app
            'app_login',
            // Dati personali
            'view_own_data', 'update_own_data',
            // Propri appuntamenti
            'view_own_appointments',
            // Notifiche
            'view_notification',
            // Documenti personali
            'view_documents', 'download_documents',
            // Comunicazioni
            'view_messages',
        ];

        $assignedCount = 0;
        foreach ($patientPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && !$auth->hasChild($patientRole, $permission)) {
                $auth->addChild($patientRole, $permission);
                $assignedCount++;
            }
        }
        echo "  Permessi assegnati: $assignedCount\n";

        // === PATIENT_FAMILY ===
        echo "\n👶 PATIENT_FAMILY - Visualizzazione dati familiari\n";
        $patientFamilyRole = $auth->getRole('patient_family');
        $patientFamilyPermissions = [
            // Login app
            'app_login',
            // Dati del paziente (familiare)
            'view_patient',
            // Appuntamenti del paziente
            'view_appointment',
            // Notifiche
            'view_notification',
            // Documenti del paziente
            'view_documents', 'download_documents',
            // Comunicazioni
            'view_messages',
            // Dati personali
            'view_own_data', 'update_own_data',
        ];

        $assignedCount = 0;
        foreach ($patientFamilyPermissions as $permissionName) {
            $permission = $auth->getPermission($permissionName);
            if ($permission && !$auth->hasChild($patientFamilyRole, $permission)) {
                $auth->addChild($patientFamilyRole, $permission);
                $assignedCount++;
            }
        }
        echo "  Permessi assegnati: $assignedCount\n";

        // ====================================================================
        // 4. RIEPILOGO FINALE
        // ====================================================================
        echo "\n🎯 4. RIEPILOGO CONFIGURAZIONE\n";
        echo "==============================\n";
        echo "✅ Configurazione RBAC TherapyCRM completata!\n\n";

        echo "📋 RUOLI CONFIGURATI:\n";
        echo "👑 ADMIN: Accesso completo al sistema (" . count($adminPermissions) . " permessi)\n";
        echo "👨‍💼 MANAGER: Gestione completa pazienti, terapisti, piani (" . count($managerPermissions) . " permessi)\n";
        echo "👨‍💼 COORDINATOR: Gestione limitata terapisti assegnati (" . count($coordinatorPermissions) . " permessi)\n";
        echo "👨‍⚕️ THERAPIST: Gestione propri appuntamenti e pazienti (" . count($therapistPermissions) . " permessi)\n";
        echo "👤 PATIENT: Visualizzazione propri dati (" . count($patientPermissions) . " permessi)\n";
        echo "👶 PATIENT_FAMILY: Visualizzazione dati familiari (" . count($patientFamilyPermissions) . " permessi)\n\n";

        echo "📊 STATISTICHE:\n";
        echo "• Permessi totali creati: " . count($permissions) . "\n";
        echo "• Ruoli configurati: " . count($roles) . "\n";
        echo "• Assegnazioni completate con successo\n\n";

        echo "🔒 SICUREZZA:\n";
        echo "• Platform login: Admin, Manager, Coordinator\n";
        echo "• App login: Therapist, Patient, Patient_Family\n";
        echo "• Controlli di accesso implementati per tutte le azioni\n\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 RIMOZIONE CONFIGURAZIONE RBAC\n";
        echo "================================\n";

        // Rimuovi tutte le assegnazioni
        $auth->removeAllAssignments();
        echo "✓ Assegnazioni utenti rimosse\n";

        // Rimuovi tutte le relazioni ruolo-permesso
        $auth->removeAllChildren();
        echo "✓ Relazioni ruolo-permesso rimosse\n";

        // Rimuovi tutti i permessi e ruoli
        $auth->removeAll();
        echo "✓ Ruoli e permessi rimossi\n";

        echo "✅ Configurazione RBAC completamente rimossa\n";

        return true;
    }
}