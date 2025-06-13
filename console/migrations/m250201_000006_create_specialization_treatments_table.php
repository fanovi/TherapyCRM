<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%specialization_treatments}}`.
 */
class m250201_000006_create_specialization_treatments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%specialization_treatments}}', [
            'id' => $this->primaryKey(),
            'specialization_id' => $this->integer()->notNull(),
            'treatment_type_id' => $this->integer()->notNull(),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-specialization_treatments-specialization_id',
            '{{%specialization_treatments}}',
            'specialization_id',
            '{{%specializations}}',
            'id'
        );

        $this->addForeignKey(
            'fk-specialization_treatments-treatment_type_id',
            '{{%specialization_treatments}}',
            'treatment_type_id',
            '{{%treatment_types}}',
            'id'
        );

        // Crea unique constraint
        $this->createIndex(
            'unique_spec_treatment',
            '{{%specialization_treatments}}',
            ['specialization_id', 'treatment_type_id'],
            true
        );

        // Inserisci relazioni di esempio
        $this->batchInsert('{{%specialization_treatments}}', ['specialization_id', 'treatment_type_id'], [
            [1, 1], // Logopedista -> Logopedia Individuale
            [1, 2], // Logopedista -> Logopedia di Gruppo
            [2, 3], // Psicologo -> Psicologia Individuale
            [2, 4], // Psicologo -> Psicologia di Gruppo
            [3, 5], // Psicoterapista -> Psicoterapia Individuale
            [3, 6], // Psicoterapista -> Psicoterapia Familiare
            [4, 7], // Fisioterapista -> Fisioterapia Individuale
            [4, 8], // Fisioterapista -> Fisioterapia di Gruppo
            [5, 9], // TNP -> Neuropsicomotricità Individuale
            [5, 10], // TNP -> Neuropsicomotricità di Gruppo
            [6, 11], // TO -> Terapia Occupazionale Individuale
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-specialization_treatments-treatment_type_id', '{{%specialization_treatments}}');
        $this->dropForeignKey('fk-specialization_treatments-specialization_id', '{{%specialization_treatments}}');
        
        $this->dropTable('{{%specialization_treatments}}');
    }
} 