<?php

use yii\db\Migration;

/**
 * Class m250927_160000_add_approval_date_and_setting_id_fields
 * 
 * Aggiunge:
 * - Campo approval_date alla tabella therapeutic_plans
 * - Campo id_setting alla tabella appointments con foreign key
 */
class m250927_143656_add_approval_date_and_setting_id_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi campo approval_date alla tabella therapeutic_plans
        $this->addColumn(
            'therapeutic_plans', 
            'approval_date', 
            $this->date()->null()->after('regime_id')->comment('Data di approvazione del piano terapeutico')
        );
        
        // Crea indice per approval_date per ottimizzare le query
        $this->createIndex(
            'idx-therapeutic_plans-approval_date',
            'therapeutic_plans',
            'approval_date'
        );
        
        // Aggiungi campo id_setting alla tabella appointments
        $this->addColumn(
            'appointments',
            'id_setting',
            $this->integer()->null()->after('treatment_type_id')->comment('ID del setting (FK a setting)')
        );
        
        // Crea indice per id_setting
        $this->createIndex(
            'idx-appointments-id_setting',
            'appointments',
            'id_setting'
        );
        
        // Aggiungi foreign key per id_setting
        $this->addForeignKey(
            'fk-appointments-id_setting',
            'appointments',
            'id_setting',
            'setting',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        echo "Campi aggiunti con successo:\n";
        echo "- therapeutic_plans.approval_date (date, nullable)\n";
        echo "- appointments.id_setting (integer, nullable, FK to setting.id)\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key per id_setting
        $this->dropForeignKey(
            'fk-appointments-id_setting',
            'appointments'
        );
        
        // Rimuovi indice per id_setting
        $this->dropIndex(
            'idx-appointments-id_setting',
            'appointments'
        );
        
        // Rimuovi campo id_setting dalla tabella appointments
        $this->dropColumn('appointments', 'id_setting');
        
        // Rimuovi indice per approval_date
        $this->dropIndex(
            'idx-therapeutic_plans-approval_date',
            'therapeutic_plans'
        );
        
        // Rimuovi campo approval_date dalla tabella therapeutic_plans
        $this->dropColumn('therapeutic_plans', 'approval_date');
        
        echo "Campi rimossi con successo.\n";
    }
}