<?php

use yii\db\Migration;

/**
 * Corregge la vista statistics_absences_mv per lavorare con la struttura attuale
 * del database dove le assenze dei pazienti sono gestite tramite appointment.status
 */
class m250726_170000_fix_statistics_absences_view extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Drop existing broken view/table
        $this->execute("DROP VIEW IF EXISTS statistics_absences_mv");
        $this->execute("DROP TABLE IF EXISTS statistics_absences_mv");

        // Create corrected view that works with appointments table
        $this->execute("
            CREATE VIEW statistics_absences_mv AS
            SELECT 
                ap.id,
                ap.id as appointment_id,
                ap.notes as reason,
                CASE WHEN ap.status = 'absent_justified' THEN 1 ELSE 0 END as is_justified,
                1 as is_communicated,
                ap.notes,
                ap.created_at,
                NULL as communicated_by,
                -- Dati appuntamento
                ap.appointment_datetime,
                ap.duration_minutes,
                ap.status as appointment_status,
                DATE(ap.appointment_datetime) as absence_date,
                HOUR(ap.appointment_datetime) as absence_hour,
                DAYNAME(ap.appointment_datetime) as absence_day_name,
                DAYOFWEEK(ap.appointment_datetime) as absence_day_number,
                -- Chi ha generato l'assenza (sempre system per ora)
                'system' as generated_by,
                -- Dati paziente
                ap.patient_id,
                p.first_name as patient_name,
                p.last_name as patient_surname,
                -- Dati terapista
                ap.therapist_id,
                up_th.first_name as therapist_name,
                up_th.last_name as therapist_surname,
                -- Dati trattamento
                pt.treatment_type_id,
                tt.name as treatment_name,
                tt.code as treatment_code,
                -- Recupero info
                CASE WHEN ar.id IS NOT NULL THEN 'SI' ELSE 'NO' END as has_recovery
             FROM appointments ap
             LEFT JOIN plan_therapies pt ON ap.plan_therapy_id = pt.id
             LEFT JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
             LEFT JOIN patients p ON ap.patient_id = p.id
             LEFT JOIN therapists th ON ap.therapist_id = th.id
             LEFT JOIN users u_th ON th.user_id = u_th.id
             LEFT JOIN user_profiles up_th ON u_th.id = up_th.user_id
             LEFT JOIN treatment_types tt ON pt.treatment_type_id = tt.id
             LEFT JOIN absence_recoveries ar ON ar.original_appointment_id = ap.id
             WHERE ap.status IN ('absent_justified', 'absent_not_justified')
        ");

        echo "Fixed statistics_absences_mv view to work with current database structure\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP VIEW IF EXISTS statistics_absences_mv");
        
        // Restore original broken view (commented out as it won't work with current structure)
        echo "View dropped. Original view structure is incompatible with current database.\n";
    }
} 