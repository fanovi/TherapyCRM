<?php

use yii\db\Migration;

/**
 * Handles the modification of setting with id 6 and adds new settings
 */
class m251005_113157_update_and_add_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Update existing setting with id 6
        $this->update('{{%setting}}', 
            ['nome' => 'Semiconvitto Medio'], 
            ['id' => 6]
        );
        
        // Add new settings
        $this->batchInsert('{{%setting}}', 
            ['nome'], 
            [
                ['ABA SP'],
                ['ABA PT'],
                ['ABA RBT'],
                ['Medicina di base Privata'],
                ['Medicina di base convenzionata'],
                ['Semiconvitto Grave']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert the update of setting with id 6
        $this->update('{{%setting}}', 
            ['nome' => 'Semiconvitto'], 
            ['id' => 6]
        );
        
        // Delete the newly added settings
        $this->delete('{{%setting}}', ['nome' => [
            'ABA SP',
            'ABA PT',
            'ABA RBT',
            'Medicina di base Privata',
            'Medicina di base convenzionata',
            'Semiconvitto Grave'
        ]]);
    }
}