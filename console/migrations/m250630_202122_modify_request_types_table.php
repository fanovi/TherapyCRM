<?php

use yii\db\Migration;

/**
 * Modifica la tabella request_types con la nuova struttura
 */
class m250630_202122_modify_request_types_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Drop della tabella esistente se presente
        $this->dropTableIfExists('{{%request_types}}');
        
        // Crea la nuova tabella con la struttura richiesta
        $this->createTable('{{%request_types}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->comment('Nome del tipo di richiesta'),
            'therapeutic_plan_rule' => $this->integer()->notNull()->defaultValue(1)->comment('Regola piano terapeutico: 1=opzionale, 2=non associabile, 3=obbligatorio'),
            'allow_multiple_requests' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Permette richieste multiple: 0=no, 1=si'),
            'require_therapy_assignment' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Richiede assegnazione terapia: 0=no, 1=si'),
            'require_notes' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Richiede note: 0=no, 1=si'),
            'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1)->comment('Tipo attivo: 0=inattivo, 1=attivo'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Data di creazione'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Data ultima modifica'),
        ]);

        // Aggiungi indici
        $this->createIndex('idx_request_types_therapeutic_plan_rule', '{{%request_types}}', 'therapeutic_plan_rule');
        $this->createIndex('idx_request_types_allow_multiple', '{{%request_types}}', 'allow_multiple_requests');
        $this->createIndex('idx_request_types_is_active', '{{%request_types}}', 'is_active');

        // Inserisci i dati iniziali
        $this->batchInsert('{{%request_types}}', 
            ['name', 'therapeutic_plan_rule', 'allow_multiple_requests', 'require_therapy_assignment', 'require_notes', 'is_active'],
            [
                // Copia Piano Terapeutico - piano obbligatorio (3), non multiple (0), no terapia (0), no note (0), attivo (1)
                ['Copia Piano Terapeutico', 3, 0, 0, 0, 1],
                
                // Relazione terapista - piano obbligatorio (3), non multiple (0), terapia (1), no note (0), attivo (1)
                ['Relazione terapista', 3, 0, 1, 0, 1],
                
                // Relazione visita specialistica - piano non associabile (2), non multiple (0), no terapia (0), no note (0), attivo (1)
                ['Relazione visita specialistica', 2, 0, 0, 0, 1],
                
                // Attestato frequenza - piano opzionale (1), non multiple (0), no terapia (0), no note (0), attivo (1)
                ['Attestato frequenza', 1, 0, 0, 0, 1],
                
                // Altro - piano non associabile (2), multiple (1), no terapia (0), note obbligatorie (1), attivo (1)
                ['Altro', 2, 1, 0, 1, 1],
            ]
        );

        echo "✓ Tabella request_types modificata e popolata con successo\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%request_types}}');
        echo "✓ Tabella request_types eliminata\n";
    }

    /**
     * Helper method to drop table if exists
     */
    private function dropTableIfExists($tableName)
    {
        $tableSchema = $this->db->getTableSchema($tableName, true);
        if ($tableSchema !== null) {
            $this->dropTable($tableName);
            echo "✓ Tabella esistente $tableName eliminata\n";
        }
    }
} 