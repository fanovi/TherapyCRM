<?php

use yii\db\Migration;

class m250710_200358_remove_patient_id_and_location_type_from_appointments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Prima rimuovo la foreign key di patient_id
        $this->dropForeignKey('fk-appointments-patient_id', '{{%appointments}}');
        
        // Rimuovo l'indice che include patient_id
        $this->dropIndex('idx_patient_date', '{{%appointments}}');
        
        // Rimuovo le colonne
        $this->dropColumn('{{%appointments}}', 'patient_id');
        $this->dropColumn('{{%appointments}}', 'location_type');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ricreo le colonne
        $this->addColumn('{{%appointments}}', 'patient_id', $this->integer()->notNull()->after('therapist_id'));
        $this->addColumn('{{%appointments}}', 'location_type', "ENUM('office', 'home') DEFAULT 'office'" . ' AFTER duration_minutes');
        
        // Ricreo la foreign key per patient_id
        $this->addForeignKey(
            'fk-appointments-patient_id',
            '{{%appointments}}',
            'patient_id',
            '{{%patients}}',
            'id'
        );
        
        // Ricreo l'indice che include patient_id
        $this->createIndex('idx_patient_date', '{{%appointments}}', ['patient_id', 'appointment_datetime']);
    }
}
