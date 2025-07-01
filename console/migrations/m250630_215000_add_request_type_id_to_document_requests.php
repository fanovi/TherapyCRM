<?php

use yii\db\Migration;

/**
 * Aggiunge il campo request_type_id alla tabella document_requests
 * per mantenere la relazione con la tabella request_types.
 * 
 * Questo campo era stato erroneamente omesso nella migration precedente
 * ed è necessario per identificare il tipo di richiesta.
 */
class m250630_215000_add_request_type_id_to_document_requests extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi il campo request_type_id dopo patient_id
        $this->addColumn('{{%document_requests}}', 'request_type_id', 
            $this->integer()->notNull()->after('patient_id')->comment('ID tipo richiesta (FK a request_types)'));

        // Crea foreign key verso request_types
        $this->addForeignKey(
            'fk-document_requests-request_type_id',
            '{{%document_requests}}',
            'request_type_id',
            '{{%request_types}}',
            'id',
            'RESTRICT'  // Non permettere eliminazione tipi usati
        );

        // Aggiungi indice per performance
        $this->createIndex('idx_document_requests_request_type', '{{%document_requests}}', 'request_type_id');
        
        // Aggiorna indice composto per controllo duplicati
        $this->createIndex('idx_document_requests_patient_type_status', 
            '{{%document_requests}}', 
            ['patient_id', 'request_type_id', 'status']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi indici
        $this->dropIndex('idx_document_requests_patient_type_status', '{{%document_requests}}');
        $this->dropIndex('idx_document_requests_request_type', '{{%document_requests}}');
        
        // Rimuovi foreign key
        $this->dropForeignKey('fk-document_requests-request_type_id', '{{%document_requests}}');
        
        // Rimuovi colonna
        $this->dropColumn('{{%document_requests}}', 'request_type_id');
    }
} 