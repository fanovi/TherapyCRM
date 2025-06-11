<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Handles the creation of table `{{%auth_token}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 */
class m240101_000000_create_auth_token_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%auth_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->text()->notNull(),
            'refresh_token' => $this->text()->null(),
            'is_revoked' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
            'last_used_at' => $this->integer()->null(),
        ]);

        // creates index for column `user_id`
        $this->createIndex(
            '{{%idx-auth_token-user_id}}',
            '{{%auth_token}}',
            'user_id'
        );

        // creates index for column `token` (for faster token lookups)
        // Using first 255 characters for TEXT column index
        $this->execute('CREATE INDEX {{%idx-auth_token-token}} ON {{%auth_token}} (token(255))');

        // creates index for column `expires_at` (for cleanup queries)
        $this->createIndex(
            '{{%idx-auth_token-expires_at}}',
            '{{%auth_token}}',
            'expires_at'
        );

        // add foreign key for table `{{%user}}`
        $this->addForeignKey(
            '{{%fk-auth_token-user_id}}',
            '{{%auth_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%user}}`
        $this->dropForeignKey(
            '{{%fk-auth_token-user_id}}',
            '{{%auth_token}}'
        );

        // drops index for column `user_id`
        $this->dropIndex(
            '{{%idx-auth_token-user_id}}',
            '{{%auth_token}}'
        );

        // drops index for column `token`
        $this->execute('DROP INDEX {{%idx-auth_token-token}} ON {{%auth_token}}');

        // drops index for column `expires_at`
        $this->dropIndex(
            '{{%idx-auth_token-expires_at}}',
            '{{%auth_token}}'
        );

        $this->dropTable('{{%auth_token}}');
    }
} 