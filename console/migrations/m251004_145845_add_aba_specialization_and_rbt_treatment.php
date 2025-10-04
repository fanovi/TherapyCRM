<?php

use yii\db\Migration;

/**
 * Class m251004_145845_add_aba_specialization_and_rbt_treatment
 * 
 * Aggiunge:
 * - Specializzazione ABA nella tabella specializations
 * - Trattamento RBT nella tabella treatment_types
 * - Relazione tra ABA e RBT nella tabella specialization_treatments
 */
class m251004_145845_add_aba_specialization_and_rbt_treatment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Inserimento della specializzazione ABA
        $this->insert('{{%specializations}}', [
            'code' => 'ABA',
            'name' => 'Analista del Comportamento ABA',
            'description' => 'Specialista in Applied Behavior Analysis per interventi comportamentali'
        ]);
        
        // Recupero l'ID della specializzazione ABA appena inserita
        $abaSpecializationId = $this->db->getLastInsertID();
        
        // Inserimento del trattamento RBT
        $this->insert('{{%treatment_types}}', [
            'code' => 'RBT',
            'name' => 'RBT - Registered Behavior Technician',
            'description' => 'Intervento comportamentale con tecnico RBT certificato'
        ]);
        
        // Recupero l'ID del trattamento RBT appena inserito
        $rbtTreatmentId = $this->db->getLastInsertID();
        
        // Creazione della relazione tra specializzazione ABA e trattamento RBT
        $this->insert('{{%specialization_treatments}}', [
            'specialization_id' => $abaSpecializationId,
            'treatment_type_id' => $rbtTreatmentId
        ]);
        
        // Opzionale: Aggiungi anche la possibilità per ABA di fare Parental Training e Supervisor
        // (come le altre specializzazioni)
        
        // Recupero gli ID dei trattamenti PT e SUP
        $ptId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'PT'")->queryScalar();
        $supId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'SUP'")->queryScalar();
        
        if ($ptId) {
            $this->insert('{{%specialization_treatments}}', [
                'specialization_id' => $abaSpecializationId,
                'treatment_type_id' => $ptId
            ]);
        }
        
        if ($supId) {
            $this->insert('{{%specialization_treatments}}', [
                'specialization_id' => $abaSpecializationId,
                'treatment_type_id' => $supId
            ]);
        }
        
        echo "Specializzazione ABA e trattamento RBT aggiunti con successo.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Recupero gli ID per la rimozione
        $abaSpecializationId = $this->db->createCommand("SELECT id FROM {{%specializations}} WHERE code = 'ABA'")->queryScalar();
        $rbtTreatmentId = $this->db->createCommand("SELECT id FROM {{%treatment_types}} WHERE code = 'RBT'")->queryScalar();
        
        // Rimozione delle relazioni dalla tabella specialization_treatments
        if ($abaSpecializationId) {
            $this->delete('{{%specialization_treatments}}', ['specialization_id' => $abaSpecializationId]);
        }
        
        if ($rbtTreatmentId) {
            $this->delete('{{%specialization_treatments}}', ['treatment_type_id' => $rbtTreatmentId]);
        }
        
        // Rimozione del trattamento RBT
        $this->delete('{{%treatment_types}}', ['code' => 'RBT']);
        
        // Rimozione della specializzazione ABA
        $this->delete('{{%specializations}}', ['code' => 'ABA']);
        
        echo "Specializzazione ABA e trattamento RBT rimossi con successo.\n";
    }
}