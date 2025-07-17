<?php

use yii\db\Migration;

/**
 * Class m250101_120000_add_therapist_absent_status
 */
class m250717_200458_add_therapist_absent_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Modifica l'ENUM del campo status per includere 'therapist_absent'
        $this->execute("
            ALTER TABLE {{%appointments}} 
            MODIFY COLUMN status ENUM(
                'scheduled',
                'completed', 
                'absent_justified',
                'absent_not_justified',
                'cancelled',
                'therapist_absent'
            ) DEFAULT 'scheduled'
        ");
        
        echo "Aggiunto status 'therapist_absent' alla tabella appointments\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Prima di rimuovere il nuovo status, resettiamo eventuali record che lo usano
        $this->update('{{%appointments}}', 
            ['status' => 'cancelled'], 
            ['status' => 'therapist_absent']
        );
        
        // Rimuove 'therapist_absent' dall'ENUM
        $this->execute("
            ALTER TABLE {{%appointments}} 
            MODIFY COLUMN status ENUM(
                'scheduled',
                'completed',
                'absent_justified', 
                'absent_not_justified',
                'cancelled'
            ) DEFAULT 'scheduled'
        ");
        
        echo "Rimosso status 'therapist_absent' dalla tabella appointments\n";
    }
} 