<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%patients}}`.
 */
class m250923_182432_add_residence_and_phone_columns_to_patients_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi campo indirizzo di residenza
        $this->addColumn('{{%patients}}', 'residence_address', $this->string(255)->null()->defaultValue(null)->after('birth_province_code')->comment('Indirizzo di residenza'));
        
        // Aggiungi campo comune di residenza
        $this->addColumn('{{%patients}}', 'residence_city', $this->string(100)->null()->defaultValue(null)->after('residence_address')->comment('Comune di residenza'));
        
        // Aggiungi campo provincia di residenza (nome)
        $this->addColumn('{{%patients}}', 'residence_province_name', $this->string(100)->null()->defaultValue(null)->after('residence_city')->comment('Provincia di residenza - nome completo'));
        
        // Aggiungi campo provincia di residenza (sigla)
        $this->addColumn('{{%patients}}', 'residence_province_code', $this->string(2)->null()->defaultValue(null)->after('residence_province_name')->comment('Provincia di residenza - sigla'));
        
        // Aggiungi campo CAP di residenza
        $this->addColumn('{{%patients}}', 'residence_postal_code', $this->string(5)->null()->defaultValue(null)->after('residence_province_code')->comment('CAP di residenza'));
        
        // Aggiungi campo numero di telefono
        $this->addColumn('{{%patients}}', 'phone_number', $this->string(20)->null()->defaultValue(null)->after('residence_postal_code')->comment('Numero di telefono'));
        
        // Aggiungi indici per ottimizzare le ricerche
        $this->createIndex(
            'idx-patients-residence_city',
            '{{%patients}}',
            'residence_city'
        );
        
        $this->createIndex(
            'idx-patients-residence_province_code',
            '{{%patients}}',
            'residence_province_code'
        );
        
        $this->createIndex(
            'idx-patients-residence_postal_code',
            '{{%patients}}',
            'residence_postal_code'
        );
        
        $this->createIndex(
            'idx-patients-phone_number',
            '{{%patients}}',
            'phone_number'
        );
        
        // Indice combinato per ricerche per località di residenza
        $this->createIndex(
            'idx-patients-residence_location',
            '{{%patients}}',
            ['residence_city', 'residence_province_code']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi gli indici
        $this->dropIndex('idx-patients-residence_location', '{{%patients}}');
        $this->dropIndex('idx-patients-phone_number', '{{%patients}}');
        $this->dropIndex('idx-patients-residence_postal_code', '{{%patients}}');
        $this->dropIndex('idx-patients-residence_province_code', '{{%patients}}');
        $this->dropIndex('idx-patients-residence_city', '{{%patients}}');
        
        // Rimuovi le colonne
        $this->dropColumn('{{%patients}}', 'phone_number');
        $this->dropColumn('{{%patients}}', 'residence_postal_code');
        $this->dropColumn('{{%patients}}', 'residence_province_code');
        $this->dropColumn('{{%patients}}', 'residence_province_name');
        $this->dropColumn('{{%patients}}', 'residence_city');
        $this->dropColumn('{{%patients}}', 'residence_address');
    }
}