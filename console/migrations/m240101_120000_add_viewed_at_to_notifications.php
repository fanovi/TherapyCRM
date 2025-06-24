<?php

use yii\db\Migration;

/**
 * Class m240101_120000_add_viewed_at_to_notifications
 * 
 * Aggiunge il campo viewed_at per tracciare quando una notifica è stata visualizzata
 * (ma non necessariamente confermata come letta)
 */
class m240101_120000_add_viewed_at_to_notifications extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi il campo viewed_at
        $this->addColumn('{{%notifications}}', 'viewed_at', $this->timestamp()->null()->after('read_at'));
        
        // Aggiungi un indice per performance su query di ricerca notifiche visualizzate
        $this->createIndex(
            'idx-notifications-viewed_at',
            '{{%notifications}}',
            'viewed_at'
        );
        
        // Aggiungi un indice composto per query specifiche su notifiche bloccanti
        $this->createIndex(
            'idx-notifications-blocking-status',
            '{{%notifications}}',
            ['recipient_user_id', 'requires_read_confirmation', 'read_at', 'viewed_at']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi gli indici
        $this->dropIndex('idx-notifications-blocking-status', '{{%notifications}}');
        $this->dropIndex('idx-notifications-viewed_at', '{{%notifications}}');
        
        // Rimuovi il campo
        $this->dropColumn('{{%notifications}}', 'viewed_at');
    }
} 