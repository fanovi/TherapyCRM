<?php

use yii\db\Migration;

class m260114_091353_update_request_statuses_names extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Cambia "Stampato" in "Da ritirare" (ID 3)
        $this->update('request_statuses', 
            ['name' => 'Da ritirare'],
            ['id' => 3]
        );
        
        // Cambia "Consegnato" in "Evaso" (ID 4)
        $this->update('request_statuses', 
            ['name' => 'Evaso'],
            ['id' => 4]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Ripristina "Da ritirare" a "Stampato" (ID 3)
        $this->update('request_statuses', 
            ['name' => 'Stampato'],
            ['id' => 3]
        );
        
        // Ripristina "Evaso" a "Consegnato" (ID 4)
        $this->update('request_statuses', 
            ['name' => 'Consegnato'],
            ['id' => 4]
        );
    }
}
