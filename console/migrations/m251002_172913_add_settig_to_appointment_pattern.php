<?php

use yii\db\Migration;

class m251002_172913_add_settig_to_appointment_pattern extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {  
        // Aggiungi campo id_setting alla tabella appointments
        $this->addColumn(
            'appointment_patterns',
            'id_setting',
            $this->integer()->null()->after('therapist_id')->defaultValue(1)->comment('ID del setting (FK a setting)')
        );
        
        // Crea indice per id_setting
        $this->createIndex(
            'idx-appointment_patterns-id_setting',
            'appointment_patterns',
            'id_setting'
        );
        
        // Aggiungi foreign key per id_setting
        $this->addForeignKey(
            'fk-appointment_patterns-id_setting',
            'appointment_patterns',
            'id_setting',
            'setting',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        echo "Campi aggiunti con successo:\n";
        echo "- appointment_patterns.id_setting (integer, nullable, FK to setting.id)\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key per id_setting
        $this->dropForeignKey(
            'fk-appointment_patterns-id_setting',
            'appointment_patterns'
        );
        
        // Rimuovi indice per id_setting
        $this->dropIndex(
            'idx-appointment_patterns-id_setting',
            'appointment_patterns'
        );
        
        // Rimuovi campo id_setting dalla tabella appointments
        $this->dropColumn('appointment_patterns', 'id_setting');
        
        echo "Campi rimossi con successo.\n";
    }
}
