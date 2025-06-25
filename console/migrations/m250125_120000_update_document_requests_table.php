<?php

use yii\db\Migration;

/**
 * Modifica la tabella document_requests per renderla compatibile con l'endpoint API actionCreate
 * 
 * Cambiamenti principali:
 * 1. Sostituisce document_type con request_type_id (FK a request_types)
 * 2. Cambia requested_by per puntare a account_patients invece di users
 * 3. Aggiorna status per includere nuovo workflow
 * 4. Aggiunge campi per gestione date e workflow completo
 * 5. Aggiunge estimated_completion calcolato automaticamente
 * 6. Aggiunge timestamp per workflow tracking
 */
class m250125_120000_update_document_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Rimuovi foreign key esistenti che verranno modificati
        $this->dropForeignKey('fk-document_requests-requested_by', '{{%document_requests}}');
        $this->dropForeignKey('fk-document_requests-completed_by', '{{%document_requests}}');
        
        // 2. Rimuovi indice esistente
        $this->dropIndex('idx_status', '{{%document_requests}}');
        
        // 3. Rimuovi colonne che non servono più
        $this->dropColumn('{{%document_requests}}', 'document_type');
        $this->dropColumn('{{%document_requests}}', 'completed_by');
        $this->dropColumn('{{%document_requests}}', 'completed_at');
        
        // 4. Rinomina requested_by in requested_by_account_patient_id per chiarezza
        $this->renameColumn('{{%document_requests}}', 'requested_by', 'requested_by_account_patient_id');
        
        // 5. Aggiungi nuove colonne
        $this->addColumn('{{%document_requests}}', 'request_type_id', $this->integer()->notNull()->after('patient_id'));
        $this->addColumn('{{%document_requests}}', 'reason', $this->text()->after('request_type_id'));
        $this->addColumn('{{%document_requests}}', 'date_from', $this->date()->null()->after('notes'));
        $this->addColumn('{{%document_requests}}', 'date_to', $this->date()->null()->after('date_from'));
        $this->addColumn('{{%document_requests}}', 'estimated_completion', $this->dateTime()->notNull()->after('date_to'));
        
        // 6. Aggiungi timestamp per workflow tracking
        $this->addColumn('{{%document_requests}}', 'completed_at', $this->dateTime()->null()->after('estimated_completion'));
        $this->addColumn('{{%document_requests}}', 'delivered_at', $this->dateTime()->null()->after('completed_at'));
        $this->addColumn('{{%document_requests}}', 'rejected_at', $this->dateTime()->null()->after('delivered_at'));
        $this->addColumn('{{%document_requests}}', 'rejection_reason', $this->text()->after('rejected_at'));
        $this->addColumn('{{%document_requests}}', 'cancelled_at', $this->dateTime()->null()->after('rejection_reason'));
        $this->addColumn('{{%document_requests}}', 'cancellation_reason', $this->text()->after('cancelled_at'));
        
        // 7. Aggiungi updated_at mancante
        $this->addColumn('{{%document_requests}}', 'updated_at', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->after('created_at'));
        
        // 8. Modifica status ENUM per includere nuovo workflow
        $this->alterColumn('{{%document_requests}}', 'status', "ENUM('pending', 'rejected', 'accepted', 'processing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending'");
        
        // 9. Crea nuove foreign keys
        $this->addForeignKey(
            'fk-document_requests-request_type_id',
            '{{%document_requests}}',
            'request_type_id',
            '{{%request_types}}',
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
        
        // 10. Crea indici ottimizzati
        $this->createIndex('idx_patient_status', '{{%document_requests}}', ['patient_id', 'status']);
        $this->createIndex('idx_status_created', '{{%document_requests}}', ['status', 'created_at']);
        $this->createIndex('idx_requested_by', '{{%document_requests}}', 'requested_by_account_patient_id');
        $this->createIndex('idx_estimated_completion', '{{%document_requests}}', 'estimated_completion');
        $this->createIndex('idx_request_type', '{{%document_requests}}', 'request_type_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi nuove foreign keys
        $this->dropForeignKey('fk-document_requests-request_type_id', '{{%document_requests}}');
        $this->dropForeignKey('fk-document_requests-requested_by_account_patient_id', '{{%document_requests}}');
        
        // Rimuovi indici
        $this->dropIndex('idx_patient_status', '{{%document_requests}}');
        $this->dropIndex('idx_status_created', '{{%document_requests}}');
        $this->dropIndex('idx_requested_by', '{{%document_requests}}');
        $this->dropIndex('idx_estimated_completion', '{{%document_requests}}');
        $this->dropIndex('idx_request_type', '{{%document_requests}}');
        
        // Rimuovi colonne aggiunte
        $this->dropColumn('{{%document_requests}}', 'request_type_id');
        $this->dropColumn('{{%document_requests}}', 'reason');
        $this->dropColumn('{{%document_requests}}', 'date_from');
        $this->dropColumn('{{%document_requests}}', 'date_to');
        $this->dropColumn('{{%document_requests}}', 'estimated_completion');
        $this->dropColumn('{{%document_requests}}', 'delivered_at');
        $this->dropColumn('{{%document_requests}}', 'rejected_at');
        $this->dropColumn('{{%document_requests}}', 'rejection_reason');
        $this->dropColumn('{{%document_requests}}', 'cancelled_at');
        $this->dropColumn('{{%document_requests}}', 'cancellation_reason');
        $this->dropColumn('{{%document_requests}}', 'updated_at');
        
        // Ripristina status originale
        $this->alterColumn('{{%document_requests}}', 'status', "ENUM('pending', 'processing', 'completed') DEFAULT 'pending'");
        
        // Rinomina colonna
        $this->renameColumn('{{%document_requests}}', 'requested_by_account_patient_id', 'requested_by');
        
        // Ripristina colonne originali
        $this->addColumn('{{%document_requests}}', 'document_type', "ENUM('attendance_generic', 'attendance_school', 'attendance_inps')");
        $this->addColumn('{{%document_requests}}', 'completed_by', $this->integer());
        $this->addColumn('{{%document_requests}}', 'completed_at', $this->dateTime());
        
        // Ripristina foreign keys originali
        $this->addForeignKey(
            'fk-document_requests-requested_by',
            '{{%document_requests}}',
            'requested_by',
            '{{%users}}',
            'id'
        );
        
        $this->addForeignKey(
            'fk-document_requests-completed_by',
            '{{%document_requests}}',
            'completed_by',
            '{{%users}}',
            'id'
        );
        
        // Ripristina indice originale
        $this->createIndex('idx_status', '{{%document_requests}}', 'status');
    }
} 