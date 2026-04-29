<?php

use yii\db\Migration;

/**
 * Aggiunge i campi di interruzione (status='terminated') ai piani terapeutici.
 *
 * Campi aggiunti:
 * - termination_date  (DATE)  - data dell'interruzione del piano
 * - termination_reason (TEXT) - motivo dell'interruzione
 *
 * Questi campi sono indipendenti da `suspension_date` / `suspension_reason`
 * (riservati a status='suspended') in modo da preservare la storia se un
 * piano passa per stati multipli (suspended → terminated).
 */
class m260429_191252_add_termination_fields_to_therapeutic_plans extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%therapeutic_plans}}', 'termination_date', $this->date()->null()->after('suspension_reason'));
        $this->addColumn('{{%therapeutic_plans}}', 'termination_reason', $this->text()->null()->after('termination_date'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%therapeutic_plans}}', 'termination_reason');
        $this->dropColumn('{{%therapeutic_plans}}', 'termination_date');
        return true;
    }
}
