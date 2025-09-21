<?php

use yii\db\Migration;

class m250921_142835_birth_location_columns_to_patients_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi colonna per il comune di nascita
        $this->addColumn('{{%patients}}', 'birth_city', $this->string(100)->null()->defaultValue(null)->after('birth_date'));

        // Aggiungi colonna per il nome completo della provincia di nascita
        $this->addColumn('{{%patients}}', 'birth_province_name', $this->string(100)->null()->defaultValue(null)->after('birth_city'));

        // Aggiungi colonna per la sigla della provincia di nascita (2 caratteri)
        $this->addColumn('{{%patients}}', 'birth_province_code', $this->string(2)->null()->defaultValue(null)->after('birth_province_name'));

        // Aggiungi indice per il comune di nascita
        $this->createIndex(
            'idx-patients-birth_city',
            '{{%patients}}',
            'birth_city'
        );

        // Aggiungi indice per il nome della provincia di nascita
        $this->createIndex(
            'idx-patients-birth_province_name',
            '{{%patients}}',
            'birth_province_name'
        );

        // Aggiungi indice per la sigla della provincia di nascita
        $this->createIndex(
            'idx-patients-birth_province_code',
            '{{%patients}}',
            'birth_province_code'
        );

        // Aggiungi indice combinato per ricerche su comune e sigla provincia insieme (più comune)
        $this->createIndex(
            'idx-patients-birth_location',
            '{{%patients}}',
            ['birth_city', 'birth_province_code']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi gli indici
        $this->dropIndex(
            'idx-patients-birth_location',
            '{{%patients}}'
        );

        $this->dropIndex(
            'idx-patients-birth_province_code',
            '{{%patients}}'
        );

        $this->dropIndex(
            'idx-patients-birth_province_name',
            '{{%patients}}'
        );

        $this->dropIndex(
            'idx-patients-birth_city',
            '{{%patients}}'
        );

        // Rimuovi le colonne
        $this->dropColumn('{{%patients}}', 'birth_province_code');
        $this->dropColumn('{{%patients}}', 'birth_province_name');
        $this->dropColumn('{{%patients}}', 'birth_city');
    }
}
