<?php

use yii\db\Migration;

/**
 * Class m251003_145905_drop_protocol_number_unique_index
 */
class m251003_145905_drop_protocol_number_unique_index extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuove l'indice UNIQUE su protocol_number
        $this->dropIndex('idx-therapeutic_plans-protocol_number-unique', 'therapeutic_plans');
        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ripristina l'indice UNIQUE (rollback)
        $this->createIndex('idx-therapeutic_plans-protocol_number-unique', 'therapeutic_plans', 'protocol_number', true);
    }
}