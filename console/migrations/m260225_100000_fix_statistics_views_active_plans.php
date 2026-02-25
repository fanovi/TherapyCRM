<?php

use yii\db\Migration;

/**
 * Fix delle viste statistiche: piano_terapeutico_attivo e trattamenti_count_no_aba
 * ora considerano solo piani con end_date >= CURDATE() (piani realmente attivi).
 *
 * Problema precedente: la vista considerava "attivo" qualsiasi paziente con
 * almeno un piano terapeutico, anche se scaduto.
 */
class m260225_100000_fix_statistics_views_active_plans extends Migration
{
    public function safeUp()
    {
        // Fix statistics_patients_mv: filtra per piani attivi (end_date >= CURDATE())
        $this->execute('DROP VIEW IF EXISTS statistics_patients_mv');

        $this->execute("
            CREATE VIEW statistics_patients_mv AS
            SELECT
                p.id,
                p.first_name,
                p.last_name,
                p.birth_date,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) as age,
                p.gender,
                p.created_at,
                CASE WHEN COUNT(tpa.id) > 0 THEN 'SI' ELSE 'NO' END as piano_terapeutico_attivo,
                'NO' as dismesso,
                (
                    SELECT COUNT(DISTINCT pt.treatment_type_id)
                    FROM plan_therapies pt
                    INNER JOIN therapeutic_plans tp2 ON pt.therapeutic_plan_id = tp2.id
                    INNER JOIN treatment_types tt ON pt.treatment_type_id = tt.id
                    WHERE tp2.patient_id = p.id
                    AND tp2.end_date >= CURDATE()
                    AND tt.name NOT LIKE '%ABA%'
                ) as trattamenti_count_no_aba
            FROM patients p
            LEFT JOIN therapeutic_plans tpa ON p.id = tpa.patient_id AND tpa.end_date >= CURDATE()
            GROUP BY p.id, p.first_name, p.last_name, p.birth_date, p.gender, p.created_at
        ");

        // Fix statistics_treatments_mv: filtra per piani attivi
        $this->execute('DROP VIEW IF EXISTS statistics_treatments_mv');

        $this->execute("
            CREATE VIEW statistics_treatments_mv AS
            SELECT
                tt.id,
                tt.name,
                tt.code,
                tt.description,
                (SELECT COUNT(DISTINCT tp.patient_id)
                 FROM plan_therapies pt
                 JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                 WHERE pt.treatment_type_id = tt.id
                 AND tp.end_date >= CURDATE()
                ) as active_patients_count,
                (SELECT COUNT(DISTINCT tp.patient_id)
                 FROM plan_therapies pt
                 JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                 WHERE pt.treatment_type_id = tt.id
                ) as total_patients_count
            FROM treatment_types tt
        ");

        echo "Fix viste statistiche: piano_terapeutico_attivo e trattamenti ora filtrano per piani attivi\n";
        return true;
    }

    public function safeDown()
    {
        // Ripristina statistics_patients_mv senza filtro end_date
        $this->execute('DROP VIEW IF EXISTS statistics_patients_mv');

        $this->execute("
            CREATE VIEW statistics_patients_mv AS
            SELECT
                p.id,
                p.first_name,
                p.last_name,
                p.birth_date,
                TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) as age,
                p.gender,
                p.created_at,
                CASE WHEN COUNT(tpa.id) > 0 THEN 'SI' ELSE 'NO' END as piano_terapeutico_attivo,
                'NO' as dismesso,
                (
                    SELECT COUNT(DISTINCT pt.treatment_type_id)
                    FROM plan_therapies pt
                    INNER JOIN therapeutic_plans tp2 ON pt.therapeutic_plan_id = tp2.id
                    INNER JOIN treatment_types tt ON pt.treatment_type_id = tt.id
                    WHERE tp2.patient_id = p.id
                    AND tt.name NOT LIKE '%ABA%'
                ) as trattamenti_count_no_aba
            FROM patients p
            LEFT JOIN therapeutic_plans tpa ON p.id = tpa.patient_id
            GROUP BY p.id, p.first_name, p.last_name, p.birth_date, p.gender, p.created_at
        ");

        // Ripristina statistics_treatments_mv senza filtro end_date
        $this->execute('DROP VIEW IF EXISTS statistics_treatments_mv');

        $this->execute("
            CREATE VIEW statistics_treatments_mv AS
            SELECT
                tt.id,
                tt.name,
                tt.code,
                tt.description,
                (SELECT COUNT(DISTINCT tp.patient_id)
                 FROM plan_therapies pt
                 JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                 WHERE pt.treatment_type_id = tt.id
                ) as active_patients_count,
                (SELECT COUNT(DISTINCT tp.patient_id)
                 FROM plan_therapies pt
                 JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
                 WHERE pt.treatment_type_id = tt.id
                ) as total_patients_count
            FROM treatment_types tt
        ");

        echo "Ripristinate viste statistiche originali (senza filtro end_date)\n";
        return true;
    }
}
