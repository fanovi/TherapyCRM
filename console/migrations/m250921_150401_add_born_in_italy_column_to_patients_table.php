<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%patients}}`.
 */
class m250921_150401_add_born_in_italy_column_to_patients_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi colonna per indicare se nato in Italia (1 = sì, 0 = no)
        $this->addColumn(
            '{{%patients}}', 
            'born_in_italy', 
            $this->tinyInteger(1)->notNull()->defaultValue(1)->after('birth_province_code')->comment('1 = Nato in Italia, 0 = Nato all\'estero')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {   
        // Rimuovi la colonna
        $this->dropColumn('{{%patients}}', 'born_in_italy');
    }
}
