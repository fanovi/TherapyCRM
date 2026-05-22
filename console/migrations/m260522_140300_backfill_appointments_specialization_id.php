<?php

use yii\db\Migration;

/**
 * Backfill di appointments.specialization_id.
 *
 * Strategia:
 *  1. Tenta di derivare la specializzazione dall'intersezione
 *     (treatment del piano) ∩ (specializzazione corrente del terapista),
 *     via specialization_treatments. Funziona quando la coppia è coerente.
 *  2. Per le righe ancora NULL, fallback su therapist.specialization_id.
 *     Sicuro perché al momento di questa migrazione i terapisti hanno
 *     ancora una sola specializzazione (relazione 1:1 pre-esistente).
 *  3. Loggia le righe non risolvibili (terapista senza specializzazione).
 *
 * Dipende da m260522_140200_add_specialization_id_to_appointments.
 */
class m260522_140300_backfill_appointments_specialization_id extends Migration
{
    public function safeUp()
    {
        // Step 1: match (treatment_type_id del piano) ∩ (specializzazione del terapista)
        $matched = $this->db->createCommand("
            UPDATE {{%appointments}} a
            INNER JOIN {{%therapists}} t ON t.id = a.therapist_id
            INNER JOIN {{%plan_therapies}} pt ON pt.id = a.plan_therapy_id
            INNER JOIN {{%specialization_treatments}} st
                ON st.treatment_type_id = pt.treatment_type_id
               AND st.specialization_id = t.specialization_id
            SET a.specialization_id = t.specialization_id
            WHERE a.specialization_id IS NULL
              AND t.specialization_id IS NOT NULL
        ")->execute();

        // Step 2: fallback su therapist.specialization_id per il residuo
        // (es. treatment storico non più mappato in specialization_treatments)
        $fallback = $this->db->createCommand("
            UPDATE {{%appointments}} a
            INNER JOIN {{%therapists}} t ON t.id = a.therapist_id
            SET a.specialization_id = t.specialization_id
            WHERE a.specialization_id IS NULL
              AND t.specialization_id IS NOT NULL
        ")->execute();

        // Step 3: log delle righe ancora non risolte
        $unresolved = $this->db->createCommand(
            'SELECT COUNT(*) FROM {{%appointments}} WHERE specialization_id IS NULL'
        )->queryScalar();

        $total = $this->db->createCommand('SELECT COUNT(*) FROM {{%appointments}}')->queryScalar();

        echo "Backfill appointments.specialization_id:\n";
        echo "  - match treatment↔spec del terapista: {$matched} righe\n";
        echo "  - fallback su therapist.specialization_id: {$fallback} righe\n";
        echo "  - non risolte (specialization_id ancora NULL): {$unresolved} / {$total}\n";

        if ((int)$unresolved > 0) {
            echo "ATTENZIONE: {$unresolved} appuntamenti restano senza specialization_id "
                . "(terapista senza specializzazione). Vanno gestiti manualmente o lasciati "
                . "come storico non classificato.\n";
        }
    }

    public function safeDown()
    {
        $this->db->createCommand(
            'UPDATE {{%appointments}} SET specialization_id = NULL'
        )->execute();
    }
}
