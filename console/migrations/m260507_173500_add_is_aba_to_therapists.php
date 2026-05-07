<?php

use yii\db\Migration;

/**
 * Aggiunge colonna `is_aba` (boolean) alla tabella `therapists`.
 * Indica se il terapista e' abilitato a effettuare interventi ABA.
 */
class m260507_173500_add_is_aba_to_therapists extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%therapists}}',
            'is_aba',
            $this->boolean()->notNull()->defaultValue(false)->after('can_parental_training')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%therapists}}', 'is_aba');
    }
}
