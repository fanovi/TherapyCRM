<?php

use yii\db\Migration;

/**
 * Class m250927_161500_add_protocol_number_to_therapeutic_plans
 * 
 * Aggiunge il campo protocol_number alla tabella therapeutic_plans
 */
class m250927_154109_add_protocol_number_to_therapeutic_plans extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi campo protocol_number alla tabella therapeutic_plans
        $this->addColumn(
            'therapeutic_plans', 
            'protocol_number', 
            $this->string(50)->null()->after('approval_date')->comment('Numero di protocollo del piano terapeutico')
        );
        
        // Crea indice per protocol_number per ottimizzare le ricerche
        $this->createIndex(
            'idx-therapeutic_plans-protocol_number',
            'therapeutic_plans',
            'protocol_number'
        );
        
        // Crea indice UNIQUE
        $this->createIndex(
            'idx-therapeutic_plans-protocol_number-unique',
            'therapeutic_plans',
            'protocol_number',
            true
        );
        
        echo "Campo protocol_number aggiunto con successo alla tabella therapeutic_plans\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi indice per protocol_number
        $this->dropIndex(
            'idx-therapeutic_plans-protocol_number',
            'therapeutic_plans'
        );
        
        // Rimuovi indice unique per protocol_number
        $this->dropIndex(
            'idx-therapeutic_plans-protocol_number-unique',
            'therapeutic_plans'
        );
        
        // Rimuovi campo protocol_number dalla tabella therapeutic_plans
        $this->dropColumn('therapeutic_plans', 'protocol_number');
        
        echo "Campo protocol_number rimosso con successo.\n";
    }
}