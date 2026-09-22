<?php

use yii\db\Migration;

/**
 * Allinea le viste statistiche alla definizione di piano attivo del dominio:
 * status=active e data corrente compresa tra start_date ed end_date.
 */
class m260922_094500_align_statistics_active_plans extends Migration
{
    public function safeUp()
    {
        $this->execute('DROP VIEW IF EXISTS statistics_patients_mv');
        $this->execute("
            CREATE VIEW statistics_patients_mv AS
            SELECT
                p.id,
                p.first_name,
                p.last_name,
                p.birth_date,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
                p.gender,
                p.created_at,
                CASE WHEN COUNT(tpa.id) > 0 THEN 'SI' ELSE 'NO' END AS piano_terapeutico_attivo,
                'NO' AS dismesso,
                (
                    SELECT COUNT(DISTINCT pt.treatment_type_id)
                    FROM plan_therapies pt
                    INNER JOIN therapeutic_plans tp2 ON pt.therapeutic_plan_id = tp2.id
                    INNER JOIN treatment_types tt ON pt.treatment_type_id = tt.id
                    WHERE tp2.patient_id = p.id
                      AND tp2.status = 'active'
                      AND tp2.start_date <= CURDATE()
                      AND tp2.end_date >= CURDATE()
                      AND tt.name NOT LIKE '%ABA%'
                ) AS trattamenti_count_no_aba
            FROM patients p
            LEFT JOIN therapeutic_plans tpa
              ON p.id = tpa.patient_id
             AND tpa.status = 'active'
             AND tpa.start_date <= CURDATE()
             AND tpa.end_date >= CURDATE()
            GROUP BY p.id, p.first_name, p.last_name, p.birth_date, p.gender, p.created_at
        ");

        $this->execute('DROP VIEW IF EXISTS statistics_treatments_mv');
        $this->execute("
            CREATE VIEW statistics_treatments_mv AS
            SELECT
                tt.id,
                tt.name,
                tt.code,
                tt.description,
                (
                    SELECT COUNT(DISTINCT tp.patient_id)
                    FROM plan_therapies pt
                    INNER JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                    WHERE pt.treatment_type_id = tt.id
                      AND tp.status = 'active'
                      AND tp.start_date <= CURDATE()
                      AND tp.end_date >= CURDATE()
                ) AS active_patients_count,
                (
                    SELECT COUNT(DISTINCT tp.patient_id)
                    FROM plan_therapies pt
                    INNER JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                    WHERE pt.treatment_type_id = tt.id
                ) AS total_patients_count
            FROM treatment_types tt
        ");
    }

    public function safeDown()
    {
        echo "m260922_094500_align_statistics_active_plans non è reversibile automaticamente.\n";
        return false;
    }
}
