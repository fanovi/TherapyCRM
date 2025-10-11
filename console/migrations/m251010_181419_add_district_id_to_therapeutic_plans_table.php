<?php

use yii\db\Migration;

/**
 * Handles adding district_id to table `{{%therapeutic_plans}}`.
 * Has foreign key to the table `{{%districts}}`.
 * Creates unique index on protocol_number and district_id.
 */
class m251010_181419_add_district_id_to_therapeutic_plans_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi la colonna district_id
        $this->addColumn(
            '{{%therapeutic_plans}}',
            'district_id',
            $this->integer()->null()->comment('ID del distretto di riferimento')
        );

        // Crea l'indice per la foreign key
        $this->createIndex(
            'idx-therapeutic_plans-district_id',
            '{{%therapeutic_plans}}',
            'district_id'
        );

        // Aggiungi la foreign key constraint
        $this->addForeignKey(
            'fk-therapeutic_plans-district_id',
            '{{%therapeutic_plans}}',
            'district_id',
            '{{%districts}}',
            'id',
            'SET NULL', // Se il distretto viene eliminato, imposta NULL
            'CASCADE'   // Se l'id del distretto viene aggiornato, aggiorna anche qui
        );

        // Crea l'indice unique sulla coppia protocol_number e district_id
        $this->createIndex(
            'idx-therapeutic_plans-protocol_district-unique',
            '{{%therapeutic_plans}}',
            ['protocol_number', 'district_id'],
            true // unique = true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi l'indice unique
        $this->dropIndex(
            'idx-therapeutic_plans-protocol_district-unique',
            '{{%therapeutic_plans}}'
        );

        // Rimuovi la foreign key
        $this->dropForeignKey(
            'fk-therapeutic_plans-district_id',
            '{{%therapeutic_plans}}'
        );

        // Rimuovi l'indice
        $this->dropIndex(
            'idx-therapeutic_plans-district_id',
            '{{%therapeutic_plans}}'
        );

        // Rimuovi la colonna
        $this->dropColumn(
            '{{%therapeutic_plans}}',
            'district_id'
        );
    }
}