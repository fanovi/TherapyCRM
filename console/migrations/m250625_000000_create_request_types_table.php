<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%request_types}}`.
 * 
 * Questa tabella contiene le tipologie di richieste che i pazienti possono fare
 * tramite l'app mobile. Sostituisce i dati statici nell'endpoint API.
 */
class m250625_000000_create_request_types_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%request_types}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'category' => $this->string(50)->notNull()->defaultValue('medical'),
            'estimated_days' => $this->integer()->notNull()->defaultValue(3),
            'requires_reason' => $this->boolean()->notNull()->defaultValue(false),
            'requires_date_range' => $this->boolean()->notNull()->defaultValue(false),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea indici per performance
        $this->createIndex('idx_category', '{{%request_types}}', 'category');
        $this->createIndex('idx_active', '{{%request_types}}', 'is_active');
        $this->createIndex('idx_category_active', '{{%request_types}}', ['category', 'is_active']);

        // Inserisci i dati iniziali
        $this->batchInsert('{{%request_types}}', 
            ['name', 'description', 'category', 'estimated_days', 'requires_reason', 'requires_date_range', 'is_active'],
            [
                ['Certificato Medico', 'Richiesta certificato medico per assenza lavorativa', 'medical', 3, 1, 1, 1],
                ['Relazione Terapeutica', 'Richiesta relazione dettagliata sui progressi terapeutici', 'therapy', 5, 1, 0, 1],
                ['Copia Cartella Clinica', 'Richiesta copia della cartella clinica completa', 'medical', 7, 1, 0, 1],
                ['Certificato di Idoneità', 'Certificato di idoneità per attività sportiva/lavorativa', 'fitness', 2, 1, 0, 1],
                ['Referto Esami', 'Richiesta copia referto di esami specifici', 'medical', 1, 0, 1, 1],
                ['Cambio Appuntamento', 'Richiesta modifica o spostamento appuntamento esistente', 'appointment', 1, 1, 0, 1],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%request_types}}');
    }
} 