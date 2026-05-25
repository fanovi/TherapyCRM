<?php

use yii\db\Migration;

/**
 * Aggiunge absences.start_time e absences.end_time (TIME NULL) per supportare
 * assenze orarie su singolo giorno (es. permesso 10:00-12:00) oltre alle
 * assenze full-day esistenti.
 *
 * Semantica:
 *  - start_time IS NULL AND end_time IS NULL  → assenza tutto il giorno
 *    (comportamento esistente, backward compatible: i record storici restano
 *    full-day senza alcun backfill).
 *  - start_time IS NOT NULL AND end_time IS NOT NULL → assenza oraria,
 *    obbligatoriamente single-day (start_date == end_date), validato a livello
 *    di model.
 */
class m260525_120000_add_time_fields_to_absences extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%absences}}',
            'start_time',
            $this->time()->null()->after('end_date')
        );
        $this->addColumn(
            '{{%absences}}',
            'end_time',
            $this->time()->null()->after('start_time')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%absences}}', 'end_time');
        $this->dropColumn('{{%absences}}', 'start_time');
    }
}
