<?php

use yii\db\Migration;

/**
 * Crea la tabella document_request_status_history per tracciare lo storico 
 * dei cambi di stato delle richieste documenti.
 * 
 * Questa tabella permette:
 * 1. Audit trail completo di tutti i cambi stato
 * 2. Analytics sui tempi di workflow
 * 3. Possibilità di rollback/ripristino stati precedenti
 * 4. Compliance e tracciabilità per normative sanitarie
 */
class m250630_214500_create_document_request_status_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Crea la tabella per lo storico cambi stato
        $this->createTable('{{%document_request_status_history}}', [
            'id' => $this->primaryKey()->comment('Chiave primaria'),
            'document_request_id' => $this->integer()->notNull()->comment('ID richiesta documento (FK a document_requests)'),
            'from_status_id' => $this->integer()->null()->comment('Stato precedente (FK a request_statuses, NULL per primo stato)'),
            'to_status_id' => $this->integer()->notNull()->comment('Nuovo stato (FK a request_statuses)'),
            'changed_by_user_id' => $this->integer()->notNull()->comment('ID utente che ha effettuato il cambio (FK a users)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Data e ora del cambio stato'),
        ]);

        // Crea foreign keys con comportamenti appropriati
        $this->addForeignKey(
            'fk-doc_req_status_history-document_request_id',
            '{{%document_request_status_history}}',
            'document_request_id',
            '{{%document_requests}}',
            'id',
            'CASCADE'  // Se la richiesta viene eliminata, elimina anche lo storico
        );

        $this->addForeignKey(
            'fk-doc_req_status_history-from_status_id',
            '{{%document_request_status_history}}',
            'from_status_id',
            '{{%request_statuses}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione stati usati nello storico
        );

        $this->addForeignKey(
            'fk-doc_req_status_history-to_status_id',
            '{{%document_request_status_history}}',
            'to_status_id',
            '{{%request_statuses}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione stati usati nello storico
        );

        $this->addForeignKey(
            'fk-doc_req_status_history-changed_by_user_id',
            '{{%document_request_status_history}}',
            'changed_by_user_id',
            '{{%users}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione utenti che hanno fatto cambi
        );

        // Crea indici per performance su query frequenti
        
        // Query storico per richiesta specifica (più frequente)
        $this->createIndex('idx_doc_req_status_history_request_created', 
            '{{%document_request_status_history}}', 
            ['document_request_id', 'created_at']);
            
        // Query per stato specifico (analytics)
        $this->createIndex('idx_doc_req_status_history_to_status', 
            '{{%document_request_status_history}}', 
            'to_status_id');
            
        // Query per utente (audit trail utente)
        $this->createIndex('idx_doc_req_status_history_user', 
            '{{%document_request_status_history}}', 
            'changed_by_user_id');
            
        // Query per range temporale (report periodici)
        $this->createIndex('idx_doc_req_status_history_created_at', 
            '{{%document_request_status_history}}', 
            'created_at');
            
        // Query workflow: transizioni stato specifiche
        $this->createIndex('idx_doc_req_status_history_transition', 
            '{{%document_request_status_history}}', 
            ['from_status_id', 'to_status_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-doc_req_status_history-changed_by_user_id', '{{%document_request_status_history}}');
        $this->dropForeignKey('fk-doc_req_status_history-to_status_id', '{{%document_request_status_history}}');
        $this->dropForeignKey('fk-doc_req_status_history-from_status_id', '{{%document_request_status_history}}');
        $this->dropForeignKey('fk-doc_req_status_history-document_request_id', '{{%document_request_status_history}}');
        
        // Rimuovi indici (automatico con drop table, ma per chiarezza)
        $this->dropIndex('idx_doc_req_status_history_transition', '{{%document_request_status_history}}');
        $this->dropIndex('idx_doc_req_status_history_created_at', '{{%document_request_status_history}}');
        $this->dropIndex('idx_doc_req_status_history_user', '{{%document_request_status_history}}');
        $this->dropIndex('idx_doc_req_status_history_to_status', '{{%document_request_status_history}}');
        $this->dropIndex('idx_doc_req_status_history_request_created', '{{%document_request_status_history}}');
        
        // Drop della tabella
        $this->dropTable('{{%document_request_status_history}}');
    }
} 