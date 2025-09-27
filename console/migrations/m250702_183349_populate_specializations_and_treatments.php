<?php

use yii\db\Migration;

class m250702_183349_populate_specializations_and_treatments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Svuoto le tabelle nell'ordine corretto (prima le tabelle di relazione)
        $this->delete('specialization_treatments');
        $this->delete('treatment_types');
        $this->delete('specializations');

        // 2. Inserisco le specializzazioni
        $specializations = [
            ['id' => 1, 'code' => 'LOGOP', 'name' => 'Logopedista', 'description' => 'Specialista in disturbi della comunicazione e del linguaggio'],
            ['id' => 2, 'code' => 'NPM', 'name' => 'Psicomotricista', 'description' => 'Terapista della Neuro e Psicomotricità dell\'età evolutiva'],
            ['id' => 3, 'code' => 'FISIOT', 'name' => 'Fisioterapista', 'description' => 'Specialista in riabilitazione motoria e fisioterapia'],
            ['id' => 4, 'code' => 'TO', 'name' => 'Terapista Occupazionale', 'description' => 'Specialista in terapia occupazionale e riabilitazione funzionale'],
            ['id' => 5, 'code' => 'PSICOT', 'name' => 'Psicologo/Psicoterapeuta', 'description' => 'Specialista in psicologia clinica e psicoterapia'],
        ];

        foreach ($specializations as $specialization) {
            $this->insert('specializations', $specialization);
        }

        // 3. Inserisco i tipi di trattamento
        $treatmentTypes = [
            ['code' => 'LOG_IND', 'name' => 'Logopedia', 'description' => 'Trattamento logopedico individuale'],
            ['code' => 'LOG_PG', 'name' => 'Logopedista PG', 'description' => 'Trattamento logopedico di piccolo gruppo'],
            ['code' => 'NPM_IND', 'name' => 'Neuropsicomotricità', 'description' => 'Trattamento neuropsicomotorio individuale'],
            ['code' => 'NPM_PG', 'name' => 'Neuropsicomotricità PG', 'description' => 'Trattamento neuropsicomotorio di piccolo gruppo'],
            ['code' => 'FIS_NEURO', 'name' => 'Riabilitazione Neuromotoria', 'description' => 'Riabilitazione neuromotoria specialistica'],
            ['code' => 'FKT_RESP', 'name' => 'FKT respiratoria', 'description' => 'Fisioterapia respiratoria specializzata'],
            ['code' => 'FKT_IND', 'name' => 'Fisiokinesiterapia', 'description' => 'Fisiokinesiterapia individuale'],
            ['code' => 'TO_IND', 'name' => 'Terapia Occupazionale', 'description' => 'Terapia occupazionale individuale'],
            ['code' => 'TO_PG', 'name' => 'Terapia Occupazionale PG', 'description' => 'Terapia occupazionale di piccolo gruppo'],
            ['code' => 'PSICOT_IND', 'name' => 'Psicoterapia', 'description' => 'Psicoterapia individuale'],
            ['code' => 'PSICOT_PG', 'name' => 'Psicoterapia PG', 'description' => 'Psicoterapia di piccolo gruppo'],
        ];

        foreach ($treatmentTypes as $treatmentType) {
            $this->insert('treatment_types', $treatmentType);
        }

        // 4. Inserisco le relazioni nella tabella specialization_treatments
        // Ottengo gli ID reali delle specializzazioni e dei treatment types
        $specializationIds = [];
        $specializationCodes = ['LOGOP', 'NPM', 'FISIOT', 'TO', 'PSICOT'];
        foreach ($specializationCodes as $code) {
            $spec = $this->db->createCommand('SELECT id FROM {{%specializations}} WHERE code = :code')
                ->bindValue(':code', $code)
                ->queryScalar();
            $specializationIds[$code] = $spec;
        }

        $treatmentIds = [];
        $treatmentCodes = ['LOG_IND', 'LOG_PG', 'NPM_IND', 'NPM_PG', 'FIS_NEURO', 'FKT_RESP', 'FKT_IND', 'TO_IND', 'TO_PG', 'PSICOT_IND', 'PSICOT_PG'];
        foreach ($treatmentCodes as $code) {
            $treatment = $this->db->createCommand('SELECT id FROM {{%treatment_types}} WHERE code = :code')
                ->bindValue(':code', $code)
                ->queryScalar();
            $treatmentIds[$code] = $treatment;
        }

        // Definisco le relazioni usando i codici
        $relations = [
            ['LOGOP', 'LOG_IND'],     // Logopedista -> Logopedia
            ['LOGOP', 'LOG_PG'],      // Logopedista -> Logopedista PG
            ['NPM', 'NPM_IND'],       // Psicomotricista -> Neuropsicomotricità
            ['NPM', 'NPM_PG'],        // Psicomotricista -> Neuropsicomotricità PG
            ['FISIOT', 'FIS_NEURO'],  // Fisioterapista -> Riabilitazione Neuromotoria
            ['FISIOT', 'FKT_RESP'],   // Fisioterapista -> FKT respiratoria
            ['FISIOT', 'FKT_IND'],    // Fisioterapista -> Fisiokinesiterapia
            ['TO', 'TO_IND'],         // Terapista Occupazionale -> Terapia Occupazionale
            ['TO', 'TO_PG'],          // Terapista Occupazionale -> Terapia Occupazionale PG
            ['PSICOT', 'PSICOT_IND'], // Psicologo/Psicoterapeuta -> Psicoterapia
            ['PSICOT', 'PSICOT_PG'],  // Psicologo/Psicoterapeuta -> Psicoterapia PG
        ];

        foreach ($relations as $relation) {
            $this->insert('specialization_treatments', [
                'specialization_id' => $specializationIds[$relation[0]],
                'treatment_type_id' => $treatmentIds[$relation[1]]
            ]);
        }


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Svuoto le tabelle nell'ordine inverso per il rollback
        $this->delete('specialization_treatments');
        $this->delete('treatment_types');
        $this->delete('specializations');
        
        echo "Migration reverted: all specializations, treatment types and their relationships have been cleared.\n";
        
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250702_183349_populate_specializations_and_treatments cannot be reverted.\n";

        return false;
    }
    */
}
