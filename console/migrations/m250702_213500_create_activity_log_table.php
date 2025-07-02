<?php

use yii\db\Migration;

/**
 * Migration per la creazione della tabella activity_log
 * Gestisce il logging di tutte le attività CRUD nel sistema
 */
class m250702_213500_create_activity_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Creazione tabella activity_log
        $this->createTable('{{%activity_log}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull()->comment('ID utente che ha eseguito l\'azione'),
            'action' => "ENUM('create','update','delete') NOT NULL COMMENT 'Tipo di azione eseguita'",
            'entity_name' => $this->string(100)->notNull()->comment('Nome della tabella/entità'),
            'entity_id' => $this->integer()->notNull()->comment('ID del record modificato'),
            'old_values' => $this->text()->comment('Valori precedenti in formato JSON'),
            'new_values' => $this->text()->comment('Nuovi valori in formato JSON'),
            'ip_address' => $this->string(45)->comment('Indirizzo IP dell\'utente'),
            'user_agent' => $this->text()->comment('User Agent del browser'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Data e ora dell\'azione'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB COMMENT="Log delle attività sistema"');

        // Indici per ottimizzare le query
        $this->createIndex('idx_activity_log_user_id', '{{%activity_log}}', 'user_id');
        $this->createIndex('idx_activity_log_entity', '{{%activity_log}}', ['entity_name', 'entity_id']);
        $this->createIndex('idx_activity_log_created_at', '{{%activity_log}}', 'created_at');
        $this->createIndex('idx_activity_log_action', '{{%activity_log}}', 'action');

        // Foreign key verso la tabella users
        $this->addForeignKey(
            'fk_activity_log_user_id',
            '{{%activity_log}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimozione foreign key
        $this->dropForeignKey('fk_activity_log_user_id', '{{%activity_log}}');
        
        // Rimozione indici
        $this->dropIndex('idx_activity_log_user_id', '{{%activity_log}}');
        $this->dropIndex('idx_activity_log_entity', '{{%activity_log}}');
        $this->dropIndex('idx_activity_log_created_at', '{{%activity_log}}');
        $this->dropIndex('idx_activity_log_action', '{{%activity_log}}');
        
        // Rimozione tabella
        $this->dropTable('{{%activity_log}}');
    }
} 