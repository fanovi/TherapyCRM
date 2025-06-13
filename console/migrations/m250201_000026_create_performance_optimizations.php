<?php

use yii\db\Migration;

/**
 * Creates performance optimizations including triggers and materialized views
 */
class m250201_000026_create_performance_optimizations extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Trigger per aggiornare la tabella therapist_busy_slots
        $this->execute("
            CREATE TRIGGER update_busy_slots_insert 
            AFTER INSERT ON appointments 
            FOR EACH ROW 
            BEGIN
                INSERT INTO therapist_busy_slots (therapist_id, slot_date, slot_start, slot_end)
                VALUES (
                    NEW.therapist_id, 
                    DATE(NEW.appointment_datetime), 
                    TIME(NEW.appointment_datetime),
                    ADDTIME(TIME(NEW.appointment_datetime), SEC_TO_TIME(NEW.duration_minutes * 60))
                )
                ON DUPLICATE KEY UPDATE 
                    slot_end = ADDTIME(TIME(NEW.appointment_datetime), SEC_TO_TIME(NEW.duration_minutes * 60));
            END
        ");

        $this->execute("
            CREATE TRIGGER update_busy_slots_update 
            AFTER UPDATE ON appointments 
            FOR EACH ROW 
            BEGIN
                -- Rimuovi slot vecchio
                DELETE FROM therapist_busy_slots 
                WHERE therapist_id = OLD.therapist_id 
                  AND slot_date = DATE(OLD.appointment_datetime) 
                  AND slot_start = TIME(OLD.appointment_datetime);
                
                -- Aggiungi nuovo slot se l'appuntamento è ancora valido
                IF NEW.status = 'scheduled' THEN
                    INSERT INTO therapist_busy_slots (therapist_id, slot_date, slot_start, slot_end)
                    VALUES (
                        NEW.therapist_id, 
                        DATE(NEW.appointment_datetime), 
                        TIME(NEW.appointment_datetime),
                        ADDTIME(TIME(NEW.appointment_datetime), SEC_TO_TIME(NEW.duration_minutes * 60))
                    )
                    ON DUPLICATE KEY UPDATE 
                        slot_end = ADDTIME(TIME(NEW.appointment_datetime), SEC_TO_TIME(NEW.duration_minutes * 60));
                END IF;
            END
        ");

        $this->execute("
            CREATE TRIGGER update_busy_slots_delete 
            AFTER DELETE ON appointments 
            FOR EACH ROW 
            BEGIN
                DELETE FROM therapist_busy_slots 
                WHERE therapist_id = OLD.therapist_id 
                  AND slot_date = DATE(OLD.appointment_datetime) 
                  AND slot_start = TIME(OLD.appointment_datetime);
            END
        ");

        // Vista materializzata per calendario terapista
        // Nota: MySQL non supporta viste materializzate native, creiamo una vista normale
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

        // Trigger per aggiornare contatori assenze
        $this->execute("
            CREATE TRIGGER update_absence_counters_insert 
            AFTER INSERT ON absences 
            FOR EACH ROW 
            BEGIN
                DECLARE plan_id INT;
                
                -- Ottieni il plan_id dall'appuntamento
                SELECT pt.therapeutic_plan_id INTO plan_id
                FROM appointments a
                JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
                WHERE a.id = NEW.appointment_id;
                
                -- Aggiorna o inserisci il contatore
                INSERT INTO absence_counters (patient_id, therapeutic_plan_id, total_absences, justified_absences, unjustified_absences)
                VALUES (
                    NEW.patient_id, 
                    plan_id,
                    1,
                    IF(NEW.is_justified, 1, 0),
                    IF(NEW.is_justified, 0, 1)
                )
                ON DUPLICATE KEY UPDATE
                    total_absences = total_absences + 1,
                    justified_absences = justified_absences + IF(NEW.is_justified, 1, 0),
                    unjustified_absences = unjustified_absences + IF(NEW.is_justified, 0, 1);
            END
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi trigger
        $this->execute("DROP TRIGGER IF EXISTS update_busy_slots_insert");
        $this->execute("DROP TRIGGER IF EXISTS update_busy_slots_update");
        $this->execute("DROP TRIGGER IF EXISTS update_busy_slots_delete");
        $this->execute("DROP TRIGGER IF EXISTS update_absence_counters_insert");
        
        // Rimuovi vista
        $this->execute("DROP VIEW IF EXISTS therapist_calendar_mv");
    }
} 