<?php

use yii\db\Migration;

/**
 * Migration per eliminare completamente tutte le tabelle del database
 * ATTENZIONE: Questa operazione cancellerà completamente le tabelle!
 */
class m241201_000002_drop_all_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Disabilito i foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        echo "Inizio eliminazione di tutte le tabelle...\n";

        // Lista di tutte le possibili tabelle del sistema
        $tables = [
            // Tabelle RBAC
            'auth_assignment',
            'auth_item_child', 
            'auth_item',
            'auth_rule',
            
            // Tabelle relazioni
            'account_patients',
            'appointment_therapists',
            'patient_therapists',
            'therapist_specializations',
            'absence_counter',
            'absence_counters',
            'absence_recoveries',
            'absences',
            'appointments',
            'appointment_notes',
            'appointment_patterns',
            'therapy_plans',
            'therapeutic_plans',
            'plan_therapies',
            'therapy_plan_sessions',
            'notifications',
            'notification_templates',
            'documents',
            'document_requests',
            'reports',
            'calendars',
            'patient_documents',
            'absence_types',
            'therapist_busy_slots',
            'therapist_substitutions',
            'specialist_visits',
            'coordinator_groups',
            'group_therapists',
            'specialization_treatments',
            
            // Tabelle principali
            'therapists',
            'patients', 
            'user_profiles',
            'users',
            'auth_token',
            'auth_tokens',
            
            // Tabelle di configurazione
            'specializations',
            'districts',
            'therapy_types',
            'treatment_types',
            
            // Tabelle di sistema
            'migration',
        ];

        foreach ($tables as $table) {
            try {
                // Controlla se la tabella esiste
                $tableExists = $this->db->createCommand(
                    "SELECT COUNT(*) FROM information_schema.tables 
                     WHERE table_schema = DATABASE() AND table_name = '$table'"
                )->queryScalar();

                if ($tableExists > 0) {
                    $this->execute("DROP TABLE `$table`");
                    echo "✓ Tabella '$table' eliminata con successo\n";
                } else {
                    echo "- Tabella '$table' non esiste, saltata\n";
                }
            } catch (Exception $e) {
                echo "✗ Errore eliminando tabella '$table': " . $e->getMessage() . "\n";
                // Continua con le altre tabelle
            }
        }

        // Riabilito i foreign key checks
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');

        echo "Eliminazione completata!\n";
        echo "ATTENZIONE: Tutte le tabelle sono state eliminate dal database.\n";
        echo "È necessario riapplicare tutte le migration da zero.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "ATTENZIONE: Non è possibile annullare l'eliminazione delle tabelle.\n";
        echo "Le tabelle eliminate non possono essere recuperate automaticamente.\n";
        echo "Assicurati di avere un backup del database se necessario.\n";
        
        return false; // Impedisce il rollback
    }
} 