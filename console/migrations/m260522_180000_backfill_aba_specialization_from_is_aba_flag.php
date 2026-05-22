<?php

use yii\db\Migration;

/**
 * Allinea il flag legacy therapists.is_aba con la tabella ponte
 * therapist_specializations: per ogni terapista marcato is_aba=1 che NON ha
 * ancora la specializzazione "ABA" come una delle sue specializzazioni,
 * inserisce la riga corrispondente (come secondaria, is_primary = NULL,
 * per non sovrascrivere l'eventuale principal corrente).
 *
 * Dopo questa migrazione il flag è ridondante: la sorgente canonica diventa
 * la tabella ponte. Il flag NON viene droppato qui per evitare regressioni
 * sul codice/clients che ancora lo leggono.
 */
class m260522_180000_backfill_aba_specialization_from_is_aba_flag extends Migration
{
    public function safeUp()
    {
        $abaSpecId = $this->db
            ->createCommand("SELECT id FROM {{%specializations}} WHERE code = 'ABA'")
            ->queryScalar();

        if (!$abaSpecId) {
            echo "Specializzazione 'ABA' non trovata: skip backfill.\n";
            return;
        }

        $inserted = $this->db->createCommand("
            INSERT INTO {{%therapist_specializations}} (therapist_id, specialization_id, is_primary, created_at)
            SELECT t.id, :specId, NULL, CURRENT_TIMESTAMP
            FROM {{%therapists}} t
            WHERE t.is_aba = 1
              AND NOT EXISTS (
                  SELECT 1 FROM {{%therapist_specializations}} ts
                  WHERE ts.therapist_id = t.id AND ts.specialization_id = :specId
              )
        ", [':specId' => $abaSpecId])->execute();

        $totalAbaFlag = $this->db
            ->createCommand('SELECT COUNT(*) FROM {{%therapists}} WHERE is_aba = 1')
            ->queryScalar();
        $totalAbaSpec = $this->db
            ->createCommand("
                SELECT COUNT(DISTINCT ts.therapist_id)
                FROM {{%therapist_specializations}} ts
                WHERE ts.specialization_id = :specId
            ", [':specId' => $abaSpecId])
            ->queryScalar();

        echo "Backfill ABA: {$inserted} righe inserite in therapist_specializations.\n";
        echo "Terapisti con flag is_aba=1: {$totalAbaFlag}\n";
        echo "Terapisti con specializzazione ABA: {$totalAbaSpec}\n";
    }

    public function safeDown()
    {
        $abaSpecId = $this->db
            ->createCommand("SELECT id FROM {{%specializations}} WHERE code = 'ABA'")
            ->queryScalar();

        if (!$abaSpecId) {
            return;
        }

        // Rollback: rimuove la spec ABA SOLO dai terapisti che hanno ancora
        // il flag is_aba=1 (cioè quelli toccati dal backfill). Non tocca le
        // assegnazioni "manuali" fatte dopo via form.
        $deleted = $this->db->createCommand("
            DELETE ts FROM {{%therapist_specializations}} ts
            INNER JOIN {{%therapists}} t ON t.id = ts.therapist_id
            WHERE ts.specialization_id = :specId
              AND ts.is_primary IS NULL
              AND t.is_aba = 1
        ", [':specId' => $abaSpecId])->execute();

        echo "Rollback backfill ABA: {$deleted} righe rimosse.\n";
    }
}
