<?php

use yii\db\Migration;

class m250726_161939_add_group_appointment_support extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi colonna per identificare sessioni di gruppo
        $this->addColumn('appointments', 'group_session_id', $this->string(36)->defaultValue(null)
            ->comment('UUID per raggruppare appuntamenti della stessa sessione di gruppo')->after('patient_id'));

        // Aggiungi indice per performance nelle query di gruppo
        $this->createIndex(
            'idx-appointments-group_session',
            'appointments',
            'group_session_id',
            false
        );

        // Indice composto per query di gruppo con filtri
        $this->createIndex(
            'idx-appointments-group_search',
            'appointments',
            ['group_session_id', 'appointment_datetime', 'status'],
            false
        );

        // Indice per trovare velocemente tutti gli appuntamenti di un terapista in un determinato momento
        $this->createIndex(
            'idx-appointments-therapist_datetime',
            'appointments',
            ['therapist_id', 'appointment_datetime', 'group_session_id'],
            false
        );

        echo "Supporto appuntamenti di gruppo aggiunto con successo.\n";
        echo "Utilizzare un UUID come group_session_id per raggruppare appuntamenti.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi gli indici
        $this->dropIndex('idx-appointments-therapist_datetime', 'appointments');
        $this->dropIndex('idx-appointments-group_search', 'appointments');
        $this->dropIndex('idx-appointments-group_session', 'appointments');

        // Rimuovi la colonna
        $this->dropColumn('appointments', 'group_session_id');

        echo "Supporto appuntamenti di gruppo rimosso.\n";
    }
}
