<?php

use yii\db\Migration;

/**
 * Aggiunge flag is_admin_absence agli appuntamenti.
 * Distingue assenze paziente create da gestionale (non revocabili dall'app)
 * da quelle inserite dal paziente via mobile (revocabili).
 */
class m260508_152327_add_is_admin_absence_to_appointments extends Migration
{
    public function safeUp()
    {
        $this->addColumn('appointments', 'is_admin_absence', $this->boolean()->notNull()->defaultValue(false)
            ->comment('1 se l\'assenza paziente e\' stata creata da gestionale (non revocabile dal paziente)'));
    }

    public function safeDown()
    {
        $this->dropColumn('appointments', 'is_admin_absence');
    }
}
