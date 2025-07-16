<?php

use yii\db\Migration;

class m250715_212258_add_private_appointments_support extends Migration
{
    public function safeUp()
    {
        // Modifica la tabella appointments
        $this->alterColumn('{{%appointments}}', 'plan_therapy_id', $this->integer()->null());
        
        $this->addColumn('{{%appointments}}', 'appointment_source', "ENUM('therapeutic_plan', 'private') NOT NULL DEFAULT 'therapeutic_plan' AFTER plan_therapy_id");
        $this->addColumn('{{%appointments}}', 'treatment_type_id', $this->integer()->null()->after('appointment_source'));
        $this->addColumn('{{%appointments}}', 'private_cycle_id', $this->integer()->null()->after('treatment_type_id'));
        $this->addColumn('{{%appointments}}', 'patient_id', $this->integer()->null()->after('private_cycle_id'));
        
        // Aggiungi indici
        $this->createIndex('idx-appointments-appointment_source', '{{%appointments}}', 'appointment_source');
        $this->createIndex('idx-appointments-treatment_type_id', '{{%appointments}}', 'treatment_type_id');
        $this->createIndex('idx-appointments-private_cycle_id', '{{%appointments}}', 'private_cycle_id');
        $this->createIndex('idx-appointments-patient_id', '{{%appointments}}', 'patient_id');
        
        // Aggiungi foreign keys
        $this->addForeignKey(
            'fk-appointments-treatment_type_id',
            '{{%appointments}}',
            'treatment_type_id',
            '{{%treatment_types}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-appointments-patient_id',
            '{{%appointments}}',
            'patient_id',
            '{{%patients}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        
        // Crea tabella private_cycles
        $this->createTable('{{%private_cycles}}', [
            'id' => $this->primaryKey(),
            'patient_id' => $this->integer()->notNull(),
            'month_year' => $this->date()->notNull()->comment('Primo giorno del mese di riferimento'),
            'total_sessions' => $this->integer()->notNull(),
            'notes' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_by' => $this->integer()->notNull(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        $this->createIndex('idx-private_cycles-patient_month', '{{%private_cycles}}', ['patient_id', 'month_year']);
        
        $this->addForeignKey(
            'fk-private_cycles-patient_id',
            '{{%private_cycles}}',
            'patient_id',
            '{{%patients}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-private_cycles-created_by',
            '{{%private_cycles}}',
            'created_by',
            '{{%users}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-appointments-private_cycle_id',
            '{{%appointments}}',
            'private_cycle_id',
            '{{%private_cycles}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        
        // Migra i dati esistenti per popolare patient_id
        $this->execute("
            UPDATE {{%appointments}} a
            INNER JOIN {{%plan_therapies}} pt ON pt.id = a.plan_therapy_id
            INNER JOIN {{%therapeutic_plans}} tp ON tp.id = pt.therapeutic_plan_id
            SET a.patient_id = tp.patient_id
            WHERE a.plan_therapy_id IS NOT NULL
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-appointments-private_cycle_id', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-treatment_type_id', '{{%appointments}}');
        $this->dropForeignKey('fk-appointments-patient_id', '{{%appointments}}');
        
        // Rimuovi indici
        $this->dropIndex('idx-appointments-appointment_source', '{{%appointments}}');
        $this->dropIndex('idx-appointments-treatment_type_id', '{{%appointments}}');
        $this->dropIndex('idx-appointments-private_cycle_id', '{{%appointments}}');
        $this->dropIndex('idx-appointments-patient_id', '{{%appointments}}');
        
        // Rimuovi colonne
        $this->dropColumn('{{%appointments}}', 'appointment_source');
        $this->dropColumn('{{%appointments}}', 'treatment_type_id');
        $this->dropColumn('{{%appointments}}', 'private_cycle_id');
        $this->dropColumn('{{%appointments}}', 'patient_id');
        
        // Ripristina plan_therapy_id come NOT NULL
        $this->alterColumn('{{%appointments}}', 'plan_therapy_id', $this->integer()->notNull());
        
        // Elimina tabella private_cycles
        $this->dropTable('{{%private_cycles}}');
    }

   
}
