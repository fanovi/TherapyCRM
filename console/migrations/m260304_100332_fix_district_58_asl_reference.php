<?php

use yii\db\Migration;

class m260304_100332_fix_district_58_asl_reference extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update('{{%districts}}', ['asl_reference' => 'Napoli'], ['code' => '58']);
    }

    public function safeDown()
    {
        $this->update('{{%districts}}', ['asl_reference' => 'Salerno'], ['code' => '58']);
    }
}
