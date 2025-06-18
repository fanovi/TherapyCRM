<?php

use yii\db\Migration;

/**
 * Ottimizzazioni specifiche per migliorare le performance del login API
 */
class m250618_211137_optimize_login_performance extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Indice composito per la query di login più frequente
        // Combina email + status per ottimizzare la ricerca principale
        $this->createIndex(
            'idx_users_email_status',
            '{{%users}}',
            ['email', 'status']
        );

        // 2. Indice composito per user_profiles con foreign key
        // Ottimizza il JOIN users -> user_profiles
        $this->createIndex(
            'idx_user_profiles_user_id_names',
            '{{%user_profiles}}',
            ['user_id', 'first_name', 'last_name']
        );

        // 3. Indice per therapists con specialization (per eager loading)
        $this->createIndex(
            'idx_therapists_user_specialization',
            '{{%therapists}}',
            ['user_id', 'specialization_id', 'is_active']
        );

        // 4. Indice per account_patients ottimizzato per il JOIN con patients
        $this->createIndex(
            'idx_account_patients_user_patient',
            '{{%account_patients}}',
            ['user_id', 'patient_id', 'relationship_type']
        );

        // 5. Ottimizzazione tabella auth_token per cleanup e ricerche
        $this->createIndex(
            'idx_auth_token_user_expires',
            '{{%auth_token}}',
            ['user_id', 'expires_at', 'is_revoked']
        );

        // 6. Indice per patients per ottimizzare il JOIN con account_patients
        $this->createIndex(
            'idx_patients_names',
            '{{%patients}}',
            ['first_name', 'last_name']
        );

        // 7. Indice per specializations (usato nel JOIN con therapists)
        $this->createIndex(
            'idx_specializations_name',
            '{{%specializations}}',
            ['name']
        );

        // 8. Configura parametri MySQL per performance
        $this->execute("
            -- Ottimizza la configurazione InnoDB per performance
            SET GLOBAL innodb_buffer_pool_size = 134217728; -- 128MB per development
            SET GLOBAL query_cache_type = ON;
            SET GLOBAL query_cache_size = 16777216; -- 16MB
            SET GLOBAL query_cache_limit = 1048576; -- 1MB per query
        ");

        // 9. Analizza le tabelle per aggiornare le statistiche
        $this->execute("ANALYZE TABLE {{%users}}, {{%user_profiles}}, {{%therapists}}, {{%account_patients}}, {{%patients}}, {{%specializations}}");

        echo "Ottimizzazioni performance login applicate con successo.\n";
        echo "Indici creati:\n";
        echo "- idx_users_email_status: Ottimizza ricerca utente per email+status\n";
        echo "- idx_user_profiles_user_id_names: Ottimizza JOIN con profili\n";
        echo "- idx_therapists_user_specialization: Ottimizza ricerca terapisti\n";
        echo "- idx_account_patients_user_patient: Ottimizza ricerca account pazienti\n";
        echo "- idx_auth_token_user_expires: Ottimizza gestione token\n";
        echo "- idx_patients_names: Ottimizza ricerca pazienti\n";
        echo "- idx_specializations_name: Ottimizza ricerca specializzazioni\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi gli indici creati
        $this->dropIndex('idx_specializations_name', '{{%specializations}}');
        $this->dropIndex('idx_patients_names', '{{%patients}}');
        $this->dropIndex('idx_auth_token_user_expires', '{{%auth_token}}');
        $this->dropIndex('idx_account_patients_user_patient', '{{%account_patients}}');
        $this->dropIndex('idx_therapists_user_specialization', '{{%therapists}}');
        $this->dropIndex('idx_user_profiles_user_id_names', '{{%user_profiles}}');
        $this->dropIndex('idx_users_email_status', '{{%users}}');

        echo "Ottimizzazioni performance login rimosse.\n";
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250618_211137_optimize_login_performance cannot be reverted.\n";

        return false;
    }
    */
}
