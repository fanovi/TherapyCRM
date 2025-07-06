<?php

use yii\db\Migration;

class m250706_174000_add_notes_to_therapeutic_plans extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%therapeutic_plans}}', 'notes', $this->text()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%therapeutic_plans}}', 'notes');
    }
} 