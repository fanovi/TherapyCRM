<?php

use yii\db\Migration;

class m250723_125927_add_parental_training_and_supervisione_treatments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Inserisci i nuovi treatment_types
        $this->batchInsert(
            '{{%treatment_types}}',
            ['code', 'name', 'description'],
            [
                ['PT', 'Parental Training', 'Formazione e supporto ai genitori per gestire le difficoltà del bambino'],
                ['SUP', 'Supervisione', 'Supervisione clinica e consulenza specialistica']
            ]
        );

        // Ottieni gli ID dei treatment_types appena inseriti
        $parentalTrainingId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'PT'")->queryScalar();
        $supervisioneId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'SUP'")->queryScalar();

        // Ottieni tutti gli ID delle specializzazioni
        $specializationIds = $this->db->createCommand("SELECT id FROM {{%specializations}}")->queryColumn();

        // Prepara i dati per l'inserimento batch nella tabella specialization_treatments
        $data = [];
        foreach ($specializationIds as $specializationId) {
            // Aggiungi associazione per Parental Training
            $data[] = [$specializationId, $parentalTrainingId];
            // Aggiungi associazione per Supervisione
            $data[] = [$specializationId, $supervisioneId];
        }

        // Inserisci tutte le associazioni
        if (!empty($data)) {
            $this->batchInsert(
                '{{%specialization_treatments}}',
                ['specialization_id', 'treatment_type_id'],
                $data
            );
        }

        echo "Trattamenti 'Parental Training' e 'Supervisione' aggiunti con successo.\n";
        echo "Associazioni create per tutte le specializzazioni.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ottieni gli ID dei treatment_types da rimuovere
        $parentalTrainingId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'PT'")->queryScalar();
        $supervisioneId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'SUP'")->queryScalar();

        // Rimuovi le associazioni dalla tabella specialization_treatments
        if ($parentalTrainingId) {
            $this->delete('{{%specialization_treatments}}', ['treatment_type_id' => $parentalTrainingId]);
        }
        if ($supervisioneId) {
            $this->delete('{{%specialization_treatments}}', ['treatment_type_id' => $supervisioneId]);
        }

        // Rimuovi i treatment_types
        $this->delete('{{%treatment_types}}', ['code' => 'PT']);
        $this->delete('{{%treatment_types}}', ['code' => 'SUP']);

        echo "Trattamenti 'Parental Training' e 'Supervisione' rimossi con successo.\n";
        echo "Associazioni rimosse.\n";
    }
}
