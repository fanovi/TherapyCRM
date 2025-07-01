<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%request_statuses}}`.
 * 
 * Questa tabella contiene gli stati possibili per le richieste di documenti,
 * sostituendo l'ENUM status nella tabella document_requests.
 */
class m250630_213136_create_request_statuses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%request_statuses}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->comment('Nome dello stato'),
            'display_order' => $this->integer()->notNull()->comment('Ordine di visualizzazione'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Data di creazione'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Data ultima modifica'),
        ]);

        // Aggiungi indici per performance
        $this->createIndex('idx_request_statuses_display_order', '{{%request_statuses}}', 'display_order');
        $this->createIndex('idx_request_statuses_name', '{{%request_statuses}}', 'name', true); // unique

        // Inserisci i dati iniziali dei 4 stati richiesti
        $this->batchInsert('{{%request_statuses}}', 
            ['id', 'name', 'display_order'],
            [
                [1, 'Inviata', 1],
                [2, 'Presa in carico', 2], 
                [3, 'Stampato', 3],
                [4, 'Consegnato', 4],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%request_statuses}}');
    }
} 