<?php

use yii\db\Migration;

/**
 * Aggiunge gestione stato al piano terapeutico
 */
class m251007_161807_add_status_to_therapeutic_plans extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi colonna status con enum
        $this->addColumn('{{%therapeutic_plans}}', 'status', 
            "ENUM('draft', 'pending', 'active', 'suspended', 'completed', 'terminated', 'expired') " .
            "NOT NULL DEFAULT 'draft' " .
            "COMMENT 'Stato del piano terapeutico: " .
            "draft=bozza non ancora finalizzata, " .
            "pending=in attesa di approvazione, " .
            "active=piano attivo e in corso, " .
            "suspended=temporaneamente sospeso, " .
            "completed=concluso regolarmente, " .
            "terminated=interrotto anticipatamente, " .
            "expired=scaduto automaticamente' " .
            "AFTER `notes`"
        );

        // Aggiungi colonna per data sospensione
        $this->addColumn('{{%therapeutic_plans}}', 'suspension_date', 
            "DATE NULL DEFAULT NULL " .
            "COMMENT 'Data di inizio sospensione (popolato solo se status=suspended)' " .
            "AFTER `status`"
        );

        // Aggiungi colonna per motivo sospensione
        $this->addColumn('{{%therapeutic_plans}}', 'suspension_reason', 
            "TEXT NULL DEFAULT NULL " .
            "COMMENT 'Motivo della sospensione del piano (popolato solo se status=suspended)' " .
            "AFTER `suspension_date`"
        );

        // Crea indice per lo status
        $this->createIndex(
            'idx-therapeutic_plans-status',
            '{{%therapeutic_plans}}',
            'status'
        );

        // Crea indice composito per ricerche comuni
        $this->createIndex(
            'idx-therapeutic_plans-status_patient',
            '{{%therapeutic_plans}}',
            ['status', 'patient_id']
        );

        // Aggiorna i record esistenti basandosi sulle date
        // Imposta come 'active' i piani con date valide, 'draft' per quelli senza approvazione
        $this->execute("
            UPDATE {{%therapeutic_plans}} 
            SET status = CASE
                WHEN approval_date IS NULL THEN 'draft'
                WHEN start_date > CURDATE() THEN 'pending'
                WHEN start_date <= CURDATE() AND end_date >= CURDATE() THEN 'active'
                WHEN end_date < CURDATE() THEN 'expired'
                ELSE 'draft'
            END
        ");

        // Crea trigger per gestione automatica stati e pulizia campi sospensione
        $this->execute("
            CREATE TRIGGER update_therapeutic_plan_status_before_update
            BEFORE UPDATE ON {{%therapeutic_plans}}
            FOR EACH ROW
            BEGIN
                -- Pulisci campi sospensione quando si esce dallo stato suspended
                IF OLD.status = 'suspended' AND NEW.status != 'suspended' THEN
                    SET NEW.suspension_reason = NULL;
                    SET NEW.suspension_date = NULL;
                END IF;
                
                -- Auto-transizione a expired se la data fine è passata e il piano è ancora attivo
                IF NEW.end_date < CURDATE() AND NEW.status = 'active' THEN
                    SET NEW.status = 'expired';
                END IF;
            END
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi trigger
        $this->execute("DROP TRIGGER IF EXISTS update_therapeutic_plan_status_before_update");

        // Rimuovi indici
        $this->dropIndex('idx-therapeutic_plans-status_patient', '{{%therapeutic_plans}}');
        $this->dropIndex('idx-therapeutic_plans-status', '{{%therapeutic_plans}}');

        // Rimuovi colonne
        $this->dropColumn('{{%therapeutic_plans}}', 'suspension_reason');
        $this->dropColumn('{{%therapeutic_plans}}', 'suspension_date');
        $this->dropColumn('{{%therapeutic_plans}}', 'status');
    }
}