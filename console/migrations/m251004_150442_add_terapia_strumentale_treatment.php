<?php

use yii\db\Migration;

/**
 * Class m251004_150442_add_terapia_strumentale_treatment
 * 
 * Aggiunge:
 * - Trattamento "Terapia Strumentale" nella tabella treatment_types
 * - Relazione con la specializzazione Fisioterapista nella tabella specialization_treatments
 */
class m251004_150442_add_terapia_strumentale_treatment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Inserimento del trattamento Terapia Strumentale
        $this->insert('{{%treatment_types}}', [
            'code' => 'TER_STRUM',
            'name' => 'Terapia Strumentale',
            'description' => 'Terapia fisica strumentale (ultrasuoni, TENS, tecarterapia, laserterapia, magnetoterapia)'
        ]);
        
        // Recupero l'ID del trattamento appena inserito
        $terapiaStrumentaleId = $this->db->getLastInsertID();
        
        // Recupero l'ID della specializzazione Fisioterapista
        $fisioterapistaId = $this->db->createCommand(
            "SELECT id FROM {{%specializations}} WHERE code = 'FISIOT'"
        )->queryScalar();
        
        if (!$fisioterapistaId) {
            throw new \yii\db\Exception('Specializzazione FISIOT non trovata nel database');
        }
        
        // Creazione della relazione tra Fisioterapista e Terapia Strumentale
        $this->insert('{{%specialization_treatments}}', [
            'specialization_id' => $fisioterapistaId,
            'treatment_type_id' => $terapiaStrumentaleId
        ]);
        
        echo "Trattamento 'Terapia Strumentale' aggiunto e associato alla specializzazione Fisioterapista.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Recupero l'ID del trattamento per la rimozione
        $terapiaStrumentaleId = $this->db->createCommand(
            "SELECT id FROM {{%treatment_types}} WHERE code = 'TER_STRUM'"
        )->queryScalar();
        
        // Rimozione delle relazioni dalla tabella specialization_treatments
        if ($terapiaStrumentaleId) {
            $this->delete('{{%specialization_treatments}}', [
                'treatment_type_id' => $terapiaStrumentaleId
            ]);
        }
        
        // Rimozione del trattamento Terapia Strumentale
        $this->delete('{{%treatment_types}}', ['code' => 'TER_STRUM']);
        
        echo "Trattamento 'Terapia Strumentale' rimosso con successo.\n";
    }
}