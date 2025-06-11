<?php

use yii\db\Migration;

/**
 * Class m250112_000000_add_refresh_expires_at_to_auth_token
 * Aggiunge la colonna refresh_expires_at alla tabella auth_token
 */
class m250112_000000_add_refresh_expires_at_to_auth_token extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%auth_token}}', 'refresh_expires_at', $this->integer()->notNull()->after('expires_at'));
        
        // Crea un indice per migliorare le performance nelle query di cleanup dei refresh token
        $this->createIndex(
            '{{%idx-auth_token-refresh_expires_at}}',
            '{{%auth_token}}',
            'refresh_expires_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuove l'indice
        $this->dropIndex(
            '{{%idx-auth_token-refresh_expires_at}}',
            '{{%auth_token}}'
        );
        
        // Rimuove la colonna
        $this->dropColumn('{{%auth_token}}', 'refresh_expires_at');
    }
} 