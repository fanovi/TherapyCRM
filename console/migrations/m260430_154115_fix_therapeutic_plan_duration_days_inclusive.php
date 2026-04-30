<?php

use yii\db\Migration;

/**
 * Allinea la semantica di `therapeutic_plans.duration_days` con il resto
 * dell'applicazione (durata INCLUSIVA: l'ultimo giorno del piano coincide
 * con `end_date`).
 *
 * Stato precedente:
 *   - colonna generata: end_date = start_date + duration_days
 *     (semantica esclusiva: end_date = giorno DOPO l'ultimo del piano)
 *   - JS form (`_form.php`) e modello PHP `getCalculatedEndDate()` usano
 *     start_date + (duration_days - 1) (semantica inclusiva)
 *   - `ImportController::getDays()` calcolava la durata come diff esclusiva
 *     (`DateTime::diff()->days`), quindi i piani importati hanno
 *     duration_days inferiore di 1 rispetto alla semantica desiderata.
 *
 * Stato dopo questa migration:
 *   - colonna generata: end_date = start_date + (duration_days - 1)
 *   - i piani importati via `actionSanLuca` ricevono +1 a duration_days,
 *     così la loro `end_date` resta invariata (era già corretta).
 *   - i piani creati da form non vengono toccati: la loro `end_date` si
 *     accorcia di 1 giorno, allineandosi a quanto già mostrato nel form.
 *
 * CAVEAT: i piani importati dai metodi batch RIA/ABA
 * (`ImportController::getPlanDataForBatch` / `getAbaPlanDataForBatch`)
 * NON impostano `notes`, quindi NON sono distinguibili dai piani creati a
 * mano. Se in produzione ne esistono, vanno aggiornati separatamente con
 * una query mirata prima di applicare questa migration (oppure si dovrà
 * adattare il filtro qui sotto).
 */
class m260430_154115_fix_therapeutic_plan_duration_days_inclusive extends Migration
{
    public function safeUp()
    {
        // 1) Backfill dei piani importati via San Luca: la diff esclusiva
        //    usata in import diventava duration_days inferiore di 1.
        //    Eseguito PRIMA del cambio formula così la end_date generata
        //    risulta invariata al termine dei due passaggi.
        $updated = $this->db->createCommand()
            ->update(
                '{{%therapeutic_plans}}',
                ['duration_days' => new \yii\db\Expression('duration_days + 1')],
                ['like', 'notes', 'IMPORT_SAN_LUCA']
            )
            ->execute();

        echo "    > backfill duration_days su {$updated} piani importati (San Luca)\n";

        // 2) Cambia la formula della colonna generata `end_date` da
        //    semantica esclusiva a inclusiva. La colonna è STORED quindi
        //    MySQL ricalcola tutte le righe.
        $this->execute(
            "ALTER TABLE {{%therapeutic_plans}} "
            . "MODIFY COLUMN end_date DATE GENERATED ALWAYS AS "
            . "(DATE_ADD(start_date, INTERVAL (duration_days - 1) DAY)) STORED"
        );
    }

    public function safeDown()
    {
        // Ripristina la formula esclusiva originale.
        $this->execute(
            "ALTER TABLE {{%therapeutic_plans}} "
            . "MODIFY COLUMN end_date DATE GENERATED ALWAYS AS "
            . "(DATE_ADD(start_date, INTERVAL duration_days DAY)) STORED"
        );

        // Riporta indietro il backfill applicato in safeUp.
        $this->db->createCommand()
            ->update(
                '{{%therapeutic_plans}}',
                ['duration_days' => new \yii\db\Expression('duration_days - 1')],
                ['like', 'notes', 'IMPORT_SAN_LUCA']
            )
            ->execute();
    }
}
