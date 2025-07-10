<?php

use yii\db\Migration;

class m250710_204040_add_appointment_type_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Aggiungi la colonna appointment_type alla tabella appointments
        $this->addColumn('{{%appointments}}', 'appointment_type', $this->string(20)->notNull()->defaultValue('terapia'));
        
        // Aggiungi la colonna appointment_type alla tabella appointment_patterns
        $this->addColumn('{{%appointment_patterns}}', 'appointment_type', $this->string(20)->notNull()->defaultValue('terapia'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi la colonna appointment_type dalla tabella appointments
        $this->dropColumn('{{%appointments}}', 'appointment_type');
        
        // Rimuovi la colonna appointment_type dalla tabella appointment_patterns
        $this->dropColumn('{{%appointment_patterns}}', 'appointment_type');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250710_204040_add_appointment_type_column cannot be reverted.\n";

        return false;
    }
    */
}
