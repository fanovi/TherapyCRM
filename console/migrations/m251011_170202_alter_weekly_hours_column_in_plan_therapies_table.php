<?php

use yii\db\Migration;

/**
 * Handles the modification of weekly_hours column in table `{{%plan_therapies}}`.
 * Changes from DECIMAL(4,2) to DECIMAL(5,2) to allow values up to 999.99
 */
class m251011_170202_alter_weekly_hours_column_in_plan_therapies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Modifica la colonna weekly_hours da DECIMAL(4,2) a DECIMAL(5,2)
        // Questo permetterà valori fino a 999.99
        $this->alterColumn(
            '{{%plan_therapies}}',
            'weekly_hours',
            $this->decimal(5, 2)->notNull()->comment('Ore settimanali di terapia (max 999.99)')
        );
        
        echo "    > Column 'weekly_hours' successfully modified to DECIMAL(5,2)\n";
        echo "    > Now accepts values up to 999.99\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Prima di fare il rollback, verifica che non ci siano valori > 99.99
        $count = $this->db->createCommand(
            "SELECT COUNT(*) FROM {{%plan_therapies}} WHERE weekly_hours > 99.99"
        )->queryScalar();
        
        if ($count > 0) {
            echo "    > WARNING: There are {$count} records with weekly_hours > 99.99\n";
            echo "    > These values would be truncated if you proceed with the rollback.\n";
            
            // Opzionale: puoi decidere di bloccare il rollback
            // throw new \yii\db\Exception("Cannot rollback: values would be lost");
            
            // O di procedere comunque (decommentare la riga seguente)
            // echo "    > Proceeding anyway...\n";
        }
        
        // Ripristina la colonna a DECIMAL(4,2)
        $this->alterColumn(
            '{{%plan_therapies}}',
            'weekly_hours',
            $this->decimal(4, 2)->notNull()
        );
        
        echo "    > Column 'weekly_hours' reverted to DECIMAL(4,2)\n";
    }
}