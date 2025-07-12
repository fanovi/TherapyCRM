<?php

use yii\db\Migration;

/**
 * Aggiorna la vista therapist_calendar_mv per renderla coerente con la struttura attuale delle tabelle
 * 
 * Modifiche principali:
 * - Rimossa colonna patient_id (non più presente in appointments)
 * - Rimossa colonna location_type (non più presente in appointments)
 * - Aggiunta colonna appointment_type
 * - Aggiornata struttura therapeutic_plans (health_regime -> regime_id)
 * - Aggiornata struttura plan_therapies (health_regime -> setting_id)
 */
class m250712_181329_update_therapist_calendar_view extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuovi la vista esistente se presente
        $this->execute("DROP VIEW IF EXISTS therapist_calendar_mv");
        
        // Ricrea la vista con la struttura aggiornata
        $this->execute("
            CREATE VIEW therapist_calendar_mv AS
            SELECT 
                t.id as therapist_id,
                t.calendar_color,
                t.is_active as therapist_is_active,
                a.id as appointment_id,
                a.appointment_datetime,
                DATE(a.appointment_datetime) as appointment_date,
                TIME(a.appointment_datetime) as start_time,
                a.duration_minutes,
                a.appointment_type,
                a.status,
                a.notes as appointment_notes,
                pt.id as plan_therapy_id,
                pt.weekly_hours,
                pt.is_group,
                pt.notes as therapy_notes,
                pt.setting_id,
                s.nome as setting_name,
                tt.id as treatment_type_id,
                tt.name as treatment_name,
                tt.code as treatment_code,
                tp.id as therapeutic_plan_id,
                tp.start_date as plan_start_date,
                tp.end_date as plan_end_date,
                tp.duration_days,
                r.nome as regime_name,
                r.descrizione as regime_description,
                p.id as patient_id,
                p.first_name as patient_first_name,
                p.last_name as patient_last_name,
                p.birth_date as patient_birth_date,
                p.fiscal_code as patient_fiscal_code,
                up.first_name as therapist_first_name,
                up.last_name as therapist_last_name,
                sp.name as specialization_name,
                sp.code as specialization_code
            FROM appointments a
            JOIN therapists t ON a.therapist_id = t.id
            JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
            JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
            JOIN patients p ON tp.patient_id = p.id
            JOIN treatment_types tt ON pt.treatment_type_id = tt.id
            JOIN setting s ON pt.setting_id = s.id
            JOIN regime r ON tp.regime_id = r.id
            JOIN users u ON t.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            JOIN specializations sp ON t.specialization_id = sp.id
            WHERE a.status = 'scheduled'
                AND a.appointment_datetime >= CURRENT_DATE
                AND t.is_active = 1
        ");
        
        echo "✅ Vista therapist_calendar_mv aggiornata con successo\n";
        echo "📋 Modifiche applicate:\n";
        echo "   - Rimossa colonna patient_id da appointments\n";
        echo "   - Rimossa colonna location_type da appointments\n";
        echo "   - Aggiunta colonna appointment_type\n";
        echo "   - Aggiornata struttura therapeutic_plans (health_regime -> regime_id)\n";
        echo "   - Aggiornata struttura plan_therapies (health_regime -> setting_id)\n";
        echo "   - Aggiunti campi per setting, regime, specializzazione\n";
        echo "   - Aggiunto filtro per terapisti attivi\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi la vista aggiornata
        $this->execute("DROP VIEW IF EXISTS therapist_calendar_mv");
        
        // Ricrea la vista originale (se necessario per rollback)
        $this->execute("
            CREATE VIEW therapist_calendar_mv AS
            SELECT 
                t.id as therapist_id,
                t.calendar_color,
                a.id as appointment_id,
                a.appointment_datetime,
                DATE(a.appointment_datetime) as appointment_date,
                TIME(a.appointment_datetime) as start_time,
                a.duration_minutes,
                a.patient_id,
                p.first_name as patient_first_name,
                p.last_name as patient_last_name,
                pt.treatment_type_id,
                tt.name as treatment_name,
                pt.is_group,
                a.status,
                a.location_type,
                tp.health_regime
            FROM appointments a
            JOIN therapists t ON a.therapist_id = t.id
            JOIN patients p ON a.patient_id = p.id
            JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
            JOIN treatment_types tt ON pt.treatment_type_id = tt.id
            JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
            WHERE a.status = 'scheduled'
                AND a.appointment_datetime >= CURRENT_DATE
        ");
        
        echo "✅ Vista therapist_calendar_mv ripristinata alla versione originale\n";
    }
}
