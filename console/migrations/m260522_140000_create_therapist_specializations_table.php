<?php

use yii\db\Migration;

/**
 * Tabella ponte therapist_specializations per supportare N specializzazioni
 * per terapista (sostituisce la FK 1:1 therapists.specialization_id).
 *
 * is_primary: una sola specializzazione "principale" per terapista, usata
 * come fallback nei contesti che ancora si aspettano un valore scalare
 * (es. campo "specializzazione" nel login response per l'app mobile).
 * Vincolo realizzato tramite UNIQUE su (therapist_id, is_primary) con
 * is_primary NULLABLE: MySQL permette più NULL ma un solo 1.
 */
class m260522_140000_create_therapist_specializations_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%therapist_specializations}}', [
            'id' => $this->primaryKey(),
            'therapist_id' => $this->integer()->notNull(),
            'specialization_id' => $this->integer()->notNull(),
            'is_primary' => $this->tinyInteger(1)->null()->defaultValue(null),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey(
            'fk-therapist_specializations-therapist_id',
            '{{%therapist_specializations}}',
            'therapist_id',
            '{{%therapists}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-therapist_specializations-specialization_id',
            '{{%therapist_specializations}}',
            'specialization_id',
            '{{%specializations}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->createIndex(
            'uniq-therapist_specialization',
            '{{%therapist_specializations}}',
            ['therapist_id', 'specialization_id'],
            true
        );

        // Garantisce al più una sola riga is_primary=1 per terapista.
        // Sfrutta il comportamento MySQL: NULL != NULL per UNIQUE.
        $this->createIndex(
            'uniq-therapist_primary',
            '{{%therapist_specializations}}',
            ['therapist_id', 'is_primary'],
            true
        );

        $this->createIndex(
            'idx-therapist_specializations-specialization',
            '{{%therapist_specializations}}',
            'specialization_id'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-therapist_specializations-specialization_id', '{{%therapist_specializations}}');
        $this->dropForeignKey('fk-therapist_specializations-therapist_id', '{{%therapist_specializations}}');
        $this->dropTable('{{%therapist_specializations}}');
    }
}
