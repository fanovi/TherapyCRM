<?php

use yii\db\Migration;

/**
 * Ricrea completamente la tabella document_requests con SOLO gli attributi specificati.
 * 
 * Struttura finale:
 * 1. id (PRIMARY KEY)
 * 2. account_patient_id (FK a account_patients - utente che effettua la richiesta)  
 * 3. patient_id (FK a patients - paziente della richiesta)
 * 4. therapeutic_plan_id (FK nullable a therapeutic_plans)
 * 5. therapy_id (FK nullable a plan_therapies)
 * 6. notes (VARCHAR nullable - note)
 * 7. status (FK a request_statuses - stato)
 * 8. created_at (timestamp - data creazione)
 */
class m250630_213220_modify_document_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Drop della tabella esistente se presente (salva prima i dati se necessario)
        try {
            $this->dropTable('{{%document_requests}}');
        } catch (\Exception $e) {
            // Tabella non esiste, continua
        }
        
        // 2. Crea la nuova tabella con SOLO gli attributi richiesti
        $this->createTable('{{%document_requests}}', [
            'id' => $this->primaryKey()->comment('Chiave primaria'),
            'account_patient_id' => $this->integer()->notNull()->comment('ID account paziente che effettua la richiesta (FK a account_patients)'),
            'patient_id' => $this->integer()->notNull()->comment('ID paziente della richiesta (FK a patients)'),
            'therapeutic_plan_id' => $this->integer()->null()->comment('ID piano terapeutico (FK a therapeutic_plans, nullable)'),
            'therapy_id' => $this->integer()->null()->comment('ID terapia associata (FK a plan_therapies, nullable)'),
            'notes' => $this->string(2000)->null()->comment('Note aggiuntive (max 2000 caratteri, nullable)'),
            'status' => $this->integer()->notNull()->comment('Stato della richiesta (FK a request_statuses)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Data di creazione'),
        ]);

        // 3. Crea foreign keys
        $this->addForeignKey(
            'fk-document_requests-account_patient_id',
            '{{%document_requests}}',
            'account_patient_id',
            '{{%account_patients}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione account con richieste
        );

        $this->addForeignKey(
            'fk-document_requests-patient_id',
            '{{%document_requests}}',
            'patient_id',
            '{{%patients}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione pazienti con richieste
        );

        $this->addForeignKey(
            'fk-document_requests-therapeutic_plan_id',
            '{{%document_requests}}',
            'therapeutic_plan_id',
            '{{%therapeutic_plans}}',
            'id',
            'SET NULL'  // Se il piano viene eliminato, setta NULL
        );

        $this->addForeignKey(
            'fk-document_requests-therapy_id',
            '{{%document_requests}}',
            'therapy_id',
            '{{%plan_therapies}}',
            'id',
            'SET NULL'  // Se la terapia viene eliminata, setta NULL
        );

        $this->addForeignKey(
            'fk-document_requests-status',
            '{{%document_requests}}',
            'status',
            '{{%request_statuses}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione stati usati
        );

        // 4. Crea indici per performance sulle query più frequenti
        $this->createIndex('idx_document_requests_account_patient', '{{%document_requests}}', 'account_patient_id');
        $this->createIndex('idx_document_requests_patient', '{{%document_requests}}', 'patient_id');
        $this->createIndex('idx_document_requests_status', '{{%document_requests}}', 'status');
        $this->createIndex('idx_document_requests_patient_status', '{{%document_requests}}', ['patient_id', 'status']);
        $this->createIndex('idx_document_requests_therapeutic_plan', '{{%document_requests}}', 'therapeutic_plan_id');
        $this->createIndex('idx_document_requests_therapy', '{{%document_requests}}', 'therapy_id');
        $this->createIndex('idx_document_requests_created_at', '{{%document_requests}}', 'created_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop della nuova tabella
        try {
            $this->dropTable('{{%document_requests}}');
        } catch (\Exception $e) {
            // Tabella non esiste, continua
        }
        
        // Ricrea la struttura originale della tabella document_requests
        // (Nota: questo ripristinerà la struttura ma non i dati esistenti)
        $this->createTable('{{%document_requests}}', [
            'id' => $this->primaryKey(),
            'patient_id' => $this->integer()->notNull(),
            'requested_by_account_patient_id' => $this->integer()->notNull(),
            'request_type_id' => $this->integer()->notNull(),
            'status' => "ENUM('pending', 'rejected', 'accepted', 'processing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending'",
            'reason' => $this->text(),
            'notes' => $this->text(),
            'date_from' => $this->date()->null(),
            'date_to' => $this->date()->null(),
            'estimated_completion' => $this->dateTime()->notNull(),
            'completed_at' => $this->dateTime()->null(),
            'delivered_at' => $this->dateTime()->null(),
            'rejected_at' => $this->dateTime()->null(),
            'rejection_reason' => $this->text(),
            'cancelled_at' => $this->dateTime()->null(),
            'cancellation_reason' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Ricrea le foreign keys originali
        $this->addForeignKey(
            'fk-document_requests-patient_id',
            '{{%document_requests}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );

        $this->addForeignKey(
            'fk-document_requests-requested_by_account_patient_id',
            '{{%document_requests}}',
            'requested_by_account_patient_id',
            '{{%account_patients}}',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-document_requests-request_type_id',
            '{{%document_requests}}',
            'request_type_id',
            '{{%request_types}}',
            'id'
        );

        // Ricrea gli indici originali
        $this->createIndex('idx_document_requests_patient_status', '{{%document_requests}}', ['patient_id', 'status']);
        $this->createIndex('idx_document_requests_status_created', '{{%document_requests}}', ['status', 'created_at']);
        $this->createIndex('idx_document_requests_requested_by', '{{%document_requests}}', 'requested_by_account_patient_id');
    }
} 