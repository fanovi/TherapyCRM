<?php

use yii\db\Migration;

class m250728_172132_remove_unique_constraint_private_cycles extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Verifica se l'indice esiste
        $indexExists = $this->db->createCommand("
            SELECT COUNT(*) 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'private_cycles' 
            AND index_name = 'idx-private_cycles-patient_month'
        ")->queryScalar();

        if ($indexExists > 0) {
            echo "Rimozione dell'indice su patient_id e month_year...\n";
            
            // Prima rimuovi la foreign key che utilizza questo indice
            $this->dropForeignKey('fk-private_cycles-patient_id', '{{%private_cycles}}');
            
            // Ora puoi rimuovere l'indice
            $this->dropIndex('idx-private_cycles-patient_month', '{{%private_cycles}}');
            
            // Ricrea la foreign key senza l'indice (MySQL creerà automaticamente un indice se necessario)
            $this->addForeignKey(
                'fk-private_cycles-patient_id',
                '{{%private_cycles}}',
                'patient_id',
                '{{%patients}}',
                'id',
                'RESTRICT',
                'CASCADE'
            );
            
            echo "Indice rimosso con successo.\n";
        } else {
            echo "Indice non trovato. Nessuna azione necessaria.\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "Ripristino dell'indice su patient_id e month_year...\n";
        
        // Verifica se l'indice esiste già
        $indexExists = $this->db->createCommand("
            SELECT COUNT(*) 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'private_cycles' 
            AND index_name = 'idx-private_cycles-patient_month'
        ")->queryScalar();

        if ($indexExists > 0) {
            echo "L'indice esiste già. Nessuna azione necessaria.\n";
            return true;
        }

        // Rimuovi la foreign key esistente
        $this->dropForeignKey('fk-private_cycles-patient_id', '{{%private_cycles}}');
        
        // Ricrea l'indice
        $this->createIndex('idx-private_cycles-patient_month', '{{%private_cycles}}', ['patient_id', 'month_year']);
        
        // Ricrea la foreign key
        $this->addForeignKey(
            'fk-private_cycles-patient_id',
            '{{%private_cycles}}',
            'patient_id',
            '{{%patients}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        
        echo "Indice ripristinato con successo.\n";
        return true;
    }
}
