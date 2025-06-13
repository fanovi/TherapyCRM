<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%specializations}}`.
 */
class m250201_000004_create_specializations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%specializations}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'name' => $this->string(100)->notNull(),
            'description' => $this->text(),
        ]);

        // Inserisci dati di esempio
        $this->batchInsert('{{%specializations}}', ['code', 'name', 'description'], [
            ['LOGOP', 'Logopedista', 'Specialista in disturbi della comunicazione e del linguaggio'],
            ['PSICOL', 'Psicologo', 'Specialista in psicologia clinica e dello sviluppo'],
            ['PSICOT', 'Psicoterapista', 'Specialista in psicoterapia'],
            ['FISIOT', 'Fisioterapista', 'Specialista in riabilitazione motoria'],
            ['TNP', 'Terapista della Neuro e Psicomotricità', 'Specialista in neuropsicomotricità infantile'],
            ['TO', 'Terapista Occupazionale', 'Specialista in terapia occupazionale'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%specializations}}');
    }
} 