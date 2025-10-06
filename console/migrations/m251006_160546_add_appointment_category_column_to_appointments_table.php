<?php

use yii\db\Migration;

/**
 * Handles adding column appointment_category to table {{%appointments}}.
 * This column categorizes appointments to handle different validation rules
 */
class m251006_160546_add_appointment_category_column_to_appointments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi la colonna appointment_category come ENUM
        $this->addColumn('{{%appointments}}', 'appointment_category', 
            "ENUM('regular', 'recovery', 'advance', 'extra', 'compensation') NOT NULL DEFAULT 'regular' COMMENT 'Categoria appuntamento: regular=normale con controllo ore, recovery=recupero, advance=anticipo, extra=straordinario, compensation=compensazione' AFTER `appointment_type`"
        );
        
        // Aggiungi un indice per migliorare le performance nelle query
        $this->createIndex(
            'idx-appointments-appointment_category',
            '{{%appointments}}',
            'appointment_category'
        );
        
        // Aggiungi una colonna per collegare recuperi/anticipi all'appuntamento originale (opzionale ma molto utile)
        $this->addColumn('{{%appointments}}', 'related_appointment_id', 
            $this->integer()->null()->comment('ID dell\'appuntamento correlato (per recuperi/anticipi)')->after('appointment_category')
        );
        
        // Aggiungi foreign key per l'appuntamento correlato
        $this->addForeignKey(
            'fk-appointments-related_appointment_id',
            '{{%appointments}}',
            'related_appointment_id',
            '{{%appointments}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        // Aggiungi indice per le query sugli appuntamenti correlati
        $this->createIndex(
            'idx-appointments-related_appointment',
            '{{%appointments}}',
            'related_appointment_id'
        );
        
        // Aggiungi una colonna note per spiegare il motivo del recupero/anticipo
        $this->addColumn('{{%appointments}}', 'category_notes', 
            $this->text()->null()->comment('Note specifiche per recuperi/anticipi/extra')->after('related_appointment_id')
        );
        
        echo "Columns 'appointment_category', 'related_appointment_id' and 'category_notes' added successfully to appointments table.\n";
        
        // Opzionale: Aggiorna tutti gli appuntamenti esistenti per essere 'regular'
        // Questo è già gestito dal DEFAULT, ma lo facciamo esplicitamente per sicurezza
        $this->update('{{%appointments}}', ['appointment_category' => 'regular']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi la foreign key
        $this->dropForeignKey('fk-appointments-related_appointment_id', '{{%appointments}}');
        
        // Rimuovi gli indici
        $this->dropIndex('idx-appointments-related_appointment', '{{%appointments}}');
        $this->dropIndex('idx-appointments-appointment_category', '{{%appointments}}');
        
        // Rimuovi le colonne
        $this->dropColumn('{{%appointments}}', 'category_notes');
        $this->dropColumn('{{%appointments}}', 'related_appointment_id');
        $this->dropColumn('{{%appointments}}', 'appointment_category');
        
        echo "Columns 'appointment_category', 'related_appointment_id' and 'category_notes' removed successfully from appointments table.\n";
    }
}