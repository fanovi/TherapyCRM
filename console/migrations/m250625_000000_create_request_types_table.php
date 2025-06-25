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

        // Inserisci i dati iniziali (migrati dai dati statici dell'API)
        $this->batchInsert('{{%request_types}}', 
            ['name', 'description', 'category', 'estimated_days', 'requires_reason', 'requires_date_range', 'is_active'],
            [
                ['Certificato Medico', 'Richiesta certificato medico per assenza lavorativa', 'medical', 3, 1, 1, 1],
                ['Relazione Terapeutica', 'Richiesta relazione dettagliata sui progressi terapeutici', 'therapy', 5, 1, 0, 1],
                ['Programma Riabilitativo', 'Richiesta piano riabilitativo personalizzato per il paziente', 'therapy', 7, 1, 0, 1],
                ['Certificato Idoneità Fisica', 'Certificato per attività fisica e sportiva', 'fitness', 2, 0, 0, 1],
                ['Richiesta Appuntamento Urgente', 'Richiesta di appuntamento con priorità alta', 'appointment', 1, 1, 1, 1],
                ['Copia Cartella Clinica', 'Richiesta copia della cartella clinica del paziente', 'medical', 4, 1, 0, 1],
                ['Prescrizione Esercizi Domiciliari', 'Richiesta programma di esercizi da svolgere a casa', 'therapy', 3, 0, 0, 1],
                ['Rivalutazione Funzionale', 'Richiesta rivalutazione completa delle capacità funzionali', 'medical', 6, 1, 0, 1],
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