<?php

use yii\db\Migration;

/**
 * Crea le viste ottimizzate per le statistiche di TherapyCRM
 */
class m250716_195301_create_statistics_views extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Vista ottimizzata per statistiche pazienti
        $this->execute("
            CREATE VIEW statistics_patients_mv AS
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.birth_date,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) as age,
                'N/A' as gender,
                p.created_at,
                -- Regime information
                CASE WHEN tpa.id IS NOT NULL THEN 'SI' ELSE 'NO' END as piano_terapeutico_attivo,
                'NO' as dismesso,
                -- Count trattamenti attivi (escluso ABA)
                (SELECT COUNT(DISTINCT pt.treatment_type_id) 
                 FROM plan_therapies pt 
                 JOIN therapeutic_plans tp2 ON pt.therapeutic_plan_id = tp2.id 
                 JOIN treatment_types tt ON pt.treatment_type_id = tt.id
                 WHERE tp2.patient_id = p.id 
                   AND tt.name NOT LIKE '%ABA%'
                ) as trattamenti_count_no_aba
            FROM patients p
            LEFT JOIN therapeutic_plans tpa ON p.id = tpa.patient_id
        ");

                // Vista ottimizzata per statistiche assenze
        $this->execute("
            CREATE VIEW statistics_absences_mv AS
            SELECT 
                a.id,
                a.appointment_id,
                a.reason,
                a.is_justified,
                a.is_communicated,
                a.notes,
                a.created_at,
                a.communicated_by,
                -- Dati appuntamento
                ap.appointment_datetime,
                ap.duration_minutes,
                ap.status as appointment_status,
                DATE(ap.appointment_datetime) as absence_date,
                HOUR(ap.appointment_datetime) as absence_hour,
                DAYNAME(ap.appointment_datetime) as absence_day_name,
                DAYOFWEEK(ap.appointment_datetime) as absence_day_number,
                -- Chi ha generato l'assenza
                CASE 
                    WHEN a.communicated_by = a.patient_id THEN 'patient'
                    WHEN a.communicated_by = ap.therapist_id THEN 'therapist'
                    ELSE 'system'
                END as generated_by,
                -- Dati paziente
                a.patient_id,
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
             FROM absences a
             JOIN appointments ap ON a.appointment_id = ap.id
             LEFT JOIN plan_therapies pt ON ap.plan_therapy_id = pt.id
             LEFT JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
             LEFT JOIN patients p ON a.patient_id = p.id
             LEFT JOIN therapists th ON ap.therapist_id = th.id
             LEFT JOIN users u_th ON th.user_id = u_th.id
             LEFT JOIN user_profiles up_th ON u_th.id = up_th.user_id
             LEFT JOIN treatment_types tt ON pt.treatment_type_id = tt.id
             LEFT JOIN absence_recoveries ar ON ar.original_appointment_id = ap.id
        ");

        // Vista per statistiche trattamenti
        $this->execute("
            CREATE VIEW statistics_treatments_mv AS
            SELECT 
                tt.id,
                tt.name,
                tt.code,
                tt.description,
                -- Count pazienti attivi per trattamento
                (SELECT COUNT(DISTINCT tp.patient_id) 
                 FROM plan_therapies pt
                 JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                 WHERE pt.treatment_type_id = tt.id
                ) as active_patients_count,
                -- Count pazienti totali per trattamento  
                (SELECT COUNT(DISTINCT tp.patient_id) 
                 FROM plan_therapies pt
                 JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                 WHERE pt.treatment_type_id = tt.id
                ) as total_patients_count
            FROM treatment_types tt
        ");

        // Indici per performance
        $this->execute("CREATE INDEX idx_statistics_patients_age ON patients(birth_date)");
        $this->execute("CREATE INDEX idx_absences_date ON appointments(appointment_datetime)");
        $this->execute("CREATE INDEX idx_absences_reason ON absences(reason)");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP VIEW IF EXISTS statistics_treatments_mv");
        $this->execute("DROP VIEW IF EXISTS statistics_absences_mv");
        $this->execute("DROP VIEW IF EXISTS statistics_patients_mv");
        
        $this->execute("DROP INDEX IF EXISTS idx_statistics_patients_age ON patients");
        $this->execute("DROP INDEX IF EXISTS idx_absences_date ON appointments");
        $this->execute("DROP INDEX IF EXISTS idx_absences_reason ON absences");
    }
}
