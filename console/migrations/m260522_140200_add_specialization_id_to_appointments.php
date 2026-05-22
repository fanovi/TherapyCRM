<?php

use yii\db\Migration;

/**
 * Aggiunge appointments.specialization_id per storicizzare quale specializzazione
 * del terapista è stata usata per ogni singolo appuntamento.
 *
 * Necessario perché con N specializzazioni per terapista non è più possibile
 * derivare in modo univoco la specializzazione dall'associazione therapist↔appointment:
 * va memorizzata esplicitamente al momento della creazione dell'appuntamento.
 *
 * NULL ammesso per i record storici già esistenti (verranno riempiti dal backfill
 * nella migrazione successiva).
 */
class m260522_140200_add_specialization_id_to_appointments extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%appointments}}',
            'specialization_id',
            $this->integer()->null()->after('therapist_id')
        );

        $this->addForeignKey(
            'fk-appointments-specialization_id',
            '{{%appointments}}',
            'specialization_id',
            '{{%specializations}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->createIndex(
            'idx-appointments-specialization_id',
            '{{%appointments}}',
            'specialization_id'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-appointments-specialization_id', '{{%appointments}}');
        $this->dropIndex('idx-appointments-specialization_id', '{{%appointments}}');
        $this->dropColumn('{{%appointments}}', 'specialization_id');
    }
}
