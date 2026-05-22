<?php

use yii\db\Migration;

/**
 * Backfill: per ogni therapist esistente copia therapists.specialization_id
 * nella tabella ponte therapist_specializations, marcando la riga come
 * is_primary=1 (è l'unica specializzazione corrente, quindi è la principale).
 *
 * Dipende da m260522_140000_create_therapist_specializations_table.
 */
class m260522_140100_backfill_therapist_specializations extends Migration
{
    public function safeUp()
    {
        $inserted = $this->db->createCommand("
            INSERT INTO {{%therapist_specializations}} (therapist_id, specialization_id, is_primary, created_at)
            SELECT id, specialization_id, 1, CURRENT_TIMESTAMP
            FROM {{%therapists}}
            WHERE specialization_id IS NOT NULL
        ")->execute();

        $therapistsTotal = $this->db->createCommand('SELECT COUNT(*) FROM {{%therapists}}')->queryScalar();
        $therapistsWithSpec = $this->db->createCommand(
            'SELECT COUNT(*) FROM {{%therapists}} WHERE specialization_id IS NOT NULL'
        )->queryScalar();

        echo "Backfill therapist_specializations: {$inserted} righe inserite "
            . "({$therapistsWithSpec}/{$therapistsTotal} terapisti con specializzazione).\n";

        if ((int)$inserted !== (int)$therapistsWithSpec) {
            echo "ATTENZIONE: numero di righe inserite diverso dal numero di terapisti attesi.\n";
        }
    }

    public function safeDown()
    {
        // Svuota solo le righe che corrispondono alla specializzazione corrente
        // del terapista, per non perdere eventuali specializzazioni aggiunte
        // dopo il backfill.
        $deleted = $this->db->createCommand("
            DELETE ts FROM {{%therapist_specializations}} ts
            INNER JOIN {{%therapists}} t
              ON t.id = ts.therapist_id
             AND t.specialization_id = ts.specialization_id
            WHERE ts.is_primary = 1
        ")->execute();

        echo "Rollback backfill: {$deleted} righe rimosse.\n";
    }
}
