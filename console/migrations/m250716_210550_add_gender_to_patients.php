<?php

use yii\db\Migration;

/**
 * Class m250716_210550_add_gender_to_patients
 */
class m250716_210550_add_gender_to_patients extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi colonna gender alla tabella patients
        $this->addColumn('patients', 'gender', $this->string(1)->defaultValue('N')->comment('M=Maschio, F=Femmina, N=Non specificato'));
        
        // Aggiorna la view statistics_patients_mv per usare la nuova colonna gender
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
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ripristina la view originale
        $this->execute('DROP VIEW IF EXISTS statistics_patients_mv');
        
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
            GROUP BY p.id, p.first_name, p.last_name, p.birth_date, p.created_at
        ");
        
        // Rimuovi la colonna gender
        $this->dropColumn('patients', 'gender');
    }
}
