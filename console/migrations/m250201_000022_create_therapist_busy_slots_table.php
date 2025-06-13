<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%therapist_busy_slots}}`.
 * Tabella helper per slot occupati (aggiornata via trigger)
 */
class m250201_000022_create_therapist_busy_slots_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%therapist_busy_slots}}', [
            'id' => $this->primaryKey(),
            'therapist_id' => $this->integer()->notNull(),
            'slot_date' => $this->date()->notNull(),
            'slot_start' => $this->time()->notNull(),
            'slot_end' => $this->time()->notNull(),
        ]);

        // Crea foreign key
        $this->addForeignKey(
            'fk-therapist_busy_slots-therapist_id',
            '{{%therapist_busy_slots}}',
            'therapist_id',
            '{{%therapists}}',
            'id'
        );

        // Crea indici per performance
        $this->createIndex(
            'idx_therapist_date_time',
            '{{%therapist_busy_slots}}',
            ['therapist_id', 'slot_date', 'slot_start']
        );

        $this->createIndex(
            'unique_slot',
            '{{%therapist_busy_slots}}',
            ['therapist_id', 'slot_date', 'slot_start'],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign key
        $this->dropForeignKey('fk-therapist_busy_slots-therapist_id', '{{%therapist_busy_slots}}');
        
        $this->dropTable('{{%therapist_busy_slots}}');
    }
} 