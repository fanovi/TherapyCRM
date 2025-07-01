<?php

use yii\db\Migration;

/**
 * Aggiorna la tabella document_requests per supportare il nuovo workflow
 */
class m250625_000001_update_document_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rimuovi indice status esistente se presente
        try {
            $this->dropIndex('idx_status', '{{%document_requests}}');
        } catch (\Exception $e) {
            echo "Indice idx_status non trovato. Procedo...\n";
        }

        // Array di colonne da aggiungere
        $columns = [
            'date_from' => $this->date()->null(),
            'date_to' => $this->date()->null(),
            'estimated_completion' => $this->dateTime()->notNull(),
            'completed_at' => $this->dateTime()->null(),
            'delivered_at' => $this->dateTime()->null(),
            'rejected_at' => $this->dateTime()->null(),
            'rejection_reason' => $this->text(),
            'cancelled_at' => $this->dateTime()->null(),
            'cancellation_reason' => $this->text(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
        ];

        // Aggiungi colonne se non esistono
        foreach ($columns as $column => $type) {
            try {
                if (!$this->getDb()->getSchema()->getTableSchema('{{%document_requests}}')->getColumn($column)) {
                    $this->addColumn('{{%document_requests}}', $column, $type);
                    echo "Colonna $column aggiunta con successo.\n";
                } else {
                    echo "Colonna $column già esistente. Skip...\n";
                }
            } catch (\Exception $e) {
                echo "Errore durante l'aggiunta della colonna $column: " . $e->getMessage() . "\n";
            }
        }

        // Aggiorna il tipo di campo status per supportare i nuovi stati
        try {
            $this->alterColumn('{{%document_requests}}', 'status', "ENUM('pending', 'rejected', 'accepted', 'processing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending'");
            echo "Campo status aggiornato con successo.\n";
        } catch (\Exception $e) {
            echo "Errore durante l'aggiornamento del campo status: " . $e->getMessage() . "\n";
        }

        // Crea nuovi indici per ottimizzare le query comuni
        $indices = [
            'idx_document_requests_status' => ['status'],
            'idx_document_requests_dates' => ['date_from', 'date_to'],
            'idx_document_requests_workflow' => ['completed_at', 'delivered_at', 'rejected_at', 'cancelled_at']
        ];

        foreach ($indices as $name => $columns) {
            try {
                $this->createIndex($name, '{{%document_requests}}', $columns);
                echo "Indice $name creato con successo.\n";
            } catch (\Exception $e) {
                echo "Errore durante la creazione dell'indice $name: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi indici se esistono
        $indices = [
            'idx_document_requests_workflow',
            'idx_document_requests_dates',
            'idx_document_requests_status'
        ];

        foreach ($indices as $index) {
            try {
                $this->dropIndex($index, '{{%document_requests}}');
                echo "Indice $index rimosso con successo.\n";
            } catch (\Exception $e) {
                echo "Errore durante la rimozione dell'indice $index: " . $e->getMessage() . "\n";
            }
        }

        // Array di colonne da rimuovere
        $columns = [
            'updated_at',
            'cancellation_reason',
            'cancelled_at',
            'rejection_reason',
            'rejected_at',
            'delivered_at',
            'completed_at',
            'estimated_completion',
            'date_to',
            'date_from'
        ];

        // Rimuovi colonne se esistono
        foreach ($columns as $column) {
            try {
                if ($this->getDb()->getSchema()->getTableSchema('{{%document_requests}}')->getColumn($column)) {
                    $this->dropColumn('{{%document_requests}}', $column);
                    echo "Colonna $column rimossa con successo.\n";
                }
            } catch (\Exception $e) {
                echo "Errore durante la rimozione della colonna $column: " . $e->getMessage() . "\n";
            }
        }

        // Ripristina il tipo di campo status originale
        try {
            $this->alterColumn('{{%document_requests}}', 'status', $this->integer()->notNull());
            echo "Campo status ripristinato con successo.\n";
        } catch (\Exception $e) {
            echo "Errore durante il ripristino del campo status: " . $e->getMessage() . "\n";
        }
        
        // Ricrea l'indice status originale
        try {
            $this->createIndex('idx_status', '{{%document_requests}}', 'status');
            echo "Indice idx_status ricreato con successo.\n";
        } catch (\Exception $e) {
            echo "Errore durante la ricreazione dell'indice idx_status: " . $e->getMessage() . "\n";
        }
    }
} 