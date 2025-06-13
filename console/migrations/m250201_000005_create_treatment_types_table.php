<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%treatment_types}}`.
 */
class m250201_000005_create_treatment_types_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%treatment_types}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'name' => $this->string(100)->notNull(),
            'description' => $this->text(),
        ]);

        // Inserisci dati di esempio
        $this->batchInsert('{{%treatment_types}}', ['code', 'name', 'description'], [
            ['LOG_IND', 'Logopedia Individuale', 'Trattamento logopedico individuale'],
            ['LOG_GRP', 'Logopedia di Gruppo', 'Trattamento logopedico di gruppo'],
            ['PSI_IND', 'Psicologia Individuale', 'Colloquio psicologico individuale'],
            ['PSI_GRP', 'Psicologia di Gruppo', 'Terapia psicologica di gruppo'],
            ['PSICOT_IND', 'Psicoterapia Individuale', 'Seduta di psicoterapia individuale'],
            ['PSICOT_FAM', 'Psicoterapia Familiare', 'Seduta di psicoterapia familiare'],
            ['FKT_IND', 'Fisioterapia Individuale', 'Trattamento fisioterapico individuale'],
            ['FKT_GRP', 'Fisioterapia di Gruppo', 'Trattamento fisioterapico di gruppo'],
            ['TNP_IND', 'Neuropsicomotricità Individuale', 'Trattamento neuropsicomotorio individuale'],
            ['TNP_GRP', 'Neuropsicomotricità di Gruppo', 'Trattamento neuropsicomotorio di gruppo'],
            ['TO_IND', 'Terapia Occupazionale Individuale', 'Trattamento di terapia occupazionale individuale'],
            ['ABA_IND', 'Terapia ABA Individuale', 'Terapia comportamentale ABA individuale'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%treatment_types}}');
    }
} 