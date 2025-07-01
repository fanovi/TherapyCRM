<?php

use yii\db\Migration;

class m250701_182305_add_display_order_and_attestato_types_to_request_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Aggiungi campo display_order
        $this->addColumn('{{%request_types}}', 'display_order', $this->integer()->notNull()->defaultValue(99)->after('name'));
        
        // 2. Aggiorna gli ordini per i tipi esistenti
        $this->update('{{%request_types}}', ['display_order' => 1], ['id' => 1]); // Copia Piano Terapeutico
        $this->update('{{%request_types}}', ['display_order' => 2], ['id' => 2]); // Relazione terapista
        $this->update('{{%request_types}}', ['display_order' => 3], ['id' => 3]); // Relazione visita specialistica
        $this->update('{{%request_types}}', ['display_order' => 4], ['id' => 4]); // Attestato frequenza (da aggiornare)
        $this->update('{{%request_types}}', ['display_order' => 9], ['id' => 5]); // Altro (sarà spostato dopo i nuovi)
        
        // 3. Aggiorna il tipo esistente "Attestato frequenza" (id=4) per essere "Attestato frequenza semplice"
        $this->update('{{%request_types}}', 
            ['name' => 'Attestato frequenza semplice'], 
            ['id' => 4]
        );
        
        // 4. Inserisci i nuovi tipi di attestato frequenza
        $this->batchInsert('{{%request_types}}', 
            ['name', 'therapeutic_plan_rule', 'allow_multiple_requests', 'require_therapy_assignment', 'require_notes', 'is_active', 'display_order'],
            [
                ['Attestato frequenza con orario', 1, 0, 0, 0, 1, 5],
                ['Attestato frequenza con date', 1, 0, 0, 0, 1, 6],
                ['Attestato frequenza certificato lavoro', 1, 0, 0, 0, 1, 7],
            ]
        );
        
        // 5. Aggiorna l'ordine del tipo "Altro" per essere l'ultimo
        $this->update('{{%request_types}}', ['display_order' => 8], ['id' => 5]);
        
        // 6. Aggiungi indice per migliorare le performance delle query ordinate
        $this->createIndex('idx_request_types_display_order', '{{%request_types}}', 'display_order');
        
        echo "✅ Campo display_order aggiunto\n";
        echo "✅ Tipi esistenti riordinati\n";
        echo "✅ Tipo 'Attestato frequenza' aggiornato a 'Attestato frequenza semplice'\n";
        echo "✅ 3 nuovi tipi di attestato frequenza aggiunti\n";
        echo "✅ Tipo 'Altro' spostato alla fine\n";
        echo "✅ Indice per display_order creato\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi indice
        $this->dropIndex('idx_request_types_display_order', '{{%request_types}}');
        
        // Rimuovi i nuovi tipi aggiunti (assumendo che siano gli ultimi 3 inseriti)
        $this->delete('{{%request_types}}', ['name' => [
            'Attestato frequenza con orario',
            'Attestato frequenza con date', 
            'Attestato frequenza certificato lavoro'
        ]]);
        
        // Ripristina il nome originale del tipo id=4
        $this->update('{{%request_types}}', 
            ['name' => 'Attestato frequenza'], 
            ['id' => 4]
        );
        
        // Rimuovi campo display_order
        $this->dropColumn('{{%request_types}}', 'display_order');
        
        echo "✅ Migration reverted: campo display_order rimosso e tipi ripristinati\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250701_182305_add_display_order_and_attestato_types_to_request_types cannot be reverted.\n";

        return false;
    }
    */
}
