<?php

use yii\db\Migration;

/**
 * Modifica tabella users per supportare il sistema CRM
 */
class m250201_000001_update_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Controlla se la colonna auth_key esiste già
        $schema = $this->db->getTableSchema('{{%user}}');
        
        // La colonna auth_key esiste già nella struttura Yii2 standard
        // Non è necessario aggiungerla
        
        // Controlla se la colonna status ha il tipo corretto
        if ($schema->getColumn('status')) {
            // La colonna status esiste ma potrebbe avere un tipo diverso
            // Modifica il tipo se necessario (da smallint a ENUM)
            $this->alterColumn('{{%user}}', 'status', "ENUM('active', 'inactive') DEFAULT 'active'");
        }
        
        // Crea indici se non esistono già
        try {
            $this->createIndex('idx_user_email', '{{%user}}', 'email');
        } catch (\Exception $e) {
            // Indice potrebbe già esistere
        }
        
        try {
            $this->createIndex('idx_user_status', '{{%user}}', 'status');
        } catch (\Exception $e) {
            // Indice potrebbe già esistere
        }
        
        // Rinomina la tabella per coerenza con il resto del sistema
        $this->renameTable('{{%user}}', '{{%users}}');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rinomina indietro la tabella
        $this->renameTable('{{%users}}', '{{%user}}');
        
        // Ripristina il tipo originale della colonna status
        $this->alterColumn('{{%user}}', 'status', $this->smallInteger()->notNull()->defaultValue(10));
        
        // Rimuovi indici se esistono
        try {
            $this->dropIndex('idx_user_status', '{{%user}}');
        } catch (\Exception $e) {
            // Ignora se l'indice non esiste
        }
        
        try {
            $this->dropIndex('idx_user_email', '{{%user}}');
        } catch (\Exception $e) {
            // Ignora se l'indice non esiste
        }
    }
} 