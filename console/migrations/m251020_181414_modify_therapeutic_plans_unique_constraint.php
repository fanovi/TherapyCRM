<?php
// File: migrations/m250000_000000_modify_therapeutic_plans_unique_constraint.php

use yii\db\Migration;

/**
 * Class m250000_000000_modify_therapeutic_plans_unique_constraint
 */
class m251020_181414_modify_therapeutic_plans_unique_constraint extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuove il vecchio vincolo di unicità
        $this->dropIndex('idx-therapeutic_plans-protocol_district-unique', 'therapeutic_plans');
        
        // Crea il nuovo vincolo di unicità che include regime_id
        $this->createIndex(
            'idx-therapeutic_plans-protocol_district_regime-unique',
            'therapeutic_plans',
            ['protocol_number', 'district_id', 'regime_id'],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuove il nuovo vincolo di unicità
        $this->dropIndex('idx-therapeutic_plans-protocol_district_regime-unique', 'therapeutic_plans');
        
        // Ripristina il vecchio vincolo di unicità
        $this->createIndex(
            'idx-therapeutic_plans-protocol_district-unique',
            'therapeutic_plans',
            ['protocol_number', 'district_id'],
            true
        );
    }
}