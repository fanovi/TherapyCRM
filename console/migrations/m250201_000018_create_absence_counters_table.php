<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%absence_counters}}`.
 */
class m250201_000018_create_absence_counters_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%absence_counters}}', [
            'id' => $this->primaryKey(),
            'patient_id' => $this->integer()->notNull(),
            'therapeutic_plan_id' => $this->integer()->notNull(),
            'total_appointments' => $this->integer()->defaultValue(0),
            'total_absences' => $this->integer()->defaultValue(0),
            'justified_absences' => $this->integer()->defaultValue(0),
            'unjustified_absences' => $this->integer()->defaultValue(0),
            'absence_percentage' => 'DECIMAL(5,2) GENERATED ALWAYS AS (CASE WHEN total_appointments > 0 THEN total_absences * 100.0 / total_appointments ELSE 0 END) STORED',
            'last_updated' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-absence_counters-patient_id',
            '{{%absence_counters}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );

        $this->addForeignKey(
            'fk-absence_counters-therapeutic_plan_id',
            '{{%absence_counters}}',
            'therapeutic_plan_id',
            '{{%therapeutic_plans}}',
            'id'
        );

        // Crea unique constraint
        $this->createIndex(
            'unique_patient_plan',
            '{{%absence_counters}}',
            ['patient_id', 'therapeutic_plan_id'],
            true
        );

        // Crea indice per ricerca per percentuale
        $this->createIndex('idx_percentage', '{{%absence_counters}}', 'absence_percentage');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-absence_counters-therapeutic_plan_id', '{{%absence_counters}}');
        $this->dropForeignKey('fk-absence_counters-patient_id', '{{%absence_counters}}');
        
        $this->dropTable('{{%absence_counters}}');
    }
} 